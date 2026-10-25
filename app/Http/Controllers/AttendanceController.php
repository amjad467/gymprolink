<?php

namespace App\Http\Controllers;

use App\Models\AccessDevice;
use App\Models\AccessEvent;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberAttendance;
use App\Scopes\GymScope;
use App\Services\AccessControlService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * پیشاندانی داشبۆردی سەرەکی هاتن و ڕۆشتنی ڕۆژانەی یاریزانان (Daily Attendance & Gate Dashboard)
     */
    public function index(Request $request)
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $activeGym = Gym::find($activeGymId);
        $today = Carbon::today();

        // ١. ئامارە خێراکانی ئەمڕۆ
        $totalEntriesToday = MemberAttendance::where('gym_id', $activeGymId)
            ->whereDate('check_in_at', $today)
            ->count();

        $totalExitsToday = MemberAttendance::where('gym_id', $activeGymId)
            ->whereDate('check_out_at', $today)
            ->count();

        $currentlyInsideCount = MemberAttendance::where('gym_id', $activeGymId)
            ->where('status', 'INSIDE')
            ->count();

        // تێکڕای کاتی مانەوەی یاریزانان لەناو هۆڵ بۆ ئەوانەی دەرچوون
        $avgMinutes = (int) round(MemberAttendance::where('gym_id', $activeGymId)
            ->whereDate('check_in_at', $today)
            ->whereNotNull('duration_minutes')
            ->avg('duration_minutes') ?? 0);

        $avgDurationFormatted = $avgMinutes > 0
            ? ($avgMinutes >= 60 ? floor($avgMinutes / 60) . ' کاتژمێر و ' . ($avgMinutes % 60) . ' خولەک' : $avgMinutes . ' خولەک')
            : 'ـ';

        // ٢. فلتەرکردنی خشتە بەپێی تابی هەڵبژێردراو
        $tab = $request->query('tab', 'all'); // all, inside, entries, exits

        $query = MemberAttendance::with([
            'member.activeMembership.plan',
            'member.memberships.plan',
            'membership.plan',
            'device'
        ])->where('gym_id', $activeGymId);

        if ($tab === 'inside') {
            $query->where('status', 'INSIDE')->orderBy('check_in_at', 'desc');
        } elseif ($tab === 'exits') {
            $query->where('status', 'CHECKED_OUT')
                ->whereDate('check_out_at', $today)
                ->orderBy('check_out_at', 'desc');
        } elseif ($tab === 'entries') {
            $query->whereDate('check_in_at', $today)
                ->orderBy('check_in_at', 'desc');
        } else {
            $query->whereDate('check_in_at', $today)
                ->orderBy('check_in_at', 'desc');
        }

        $attendances = $query->paginate(25)->withQueryString();

        // ئامێرەکانی دەرگا و ئەندامان بۆ سکەن و تاقیکردنەوەی خێرا
        $devices = AccessDevice::where('gym_id', $activeGymId)->where('is_active', true)->get();
        $members = Member::with(['activeMembership.plan', 'memberships.plan', 'primaryCredential', 'currentAttendance'])
            ->where('gym_id', $activeGymId)
            ->get();

        return view('attendance.index', compact(
            'activeGym',
            'totalEntriesToday',
            'totalExitsToday',
            'currentlyInsideCount',
            'avgDurationFormatted',
            'tab',
            'attendances',
            'devices',
            'members'
        ));
    }

    /**
     * ئەنجامدانی پشکنینی کارت یان سکەن و تۆمارکردنی هاتن یان چوون (Check-in / Check-out Punch)
     */
    public function punch(Request $request, AccessControlService $accessService): JsonResponse
    {
        $request->validate([
            'scanned_code' => 'required|string',
            'mode' => 'nullable|in:auto,entry,exit',
            'device_id' => 'nullable|exists:access_devices,id',
        ]);

        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $device = $request->device_id
            ? AccessDevice::find($request->device_id)
            : AccessDevice::where('gym_id', $activeGymId)->where('is_active', true)->first();

        $deviceIdentifier = $device?->device_identifier ?? ('GATE_' . ($device?->gym?->code ?? 'CENTRAL') . '_01');

        // پشکنینی مۆڵەت لە ڕێگەی بزوێنەری ٨ مەرجی ئەکسێس کۆنترۆڵ
        $result = $accessService->verifyAndProcessAccess(
            $activeGymId,
            $deviceIdentifier,
            $request->scanned_code,
            $request->ip()
        );

        // ئەگەر دەستگەیشتن ڕەتکرایەوە
        if (!$result['access']) {
            return response()->json([
                'success' => false,
                'status' => 'DENIED',
                'reason' => $result['status'],
                'message' => $result['message'],
                'verification_time_ms' => $result['verification_time_ms'] ?? 15,
            ], 422);
        }

        $memberData = $result['member'];
        $memberId = $memberData['id'];
        $member = Member::with(['activeMembership.plan'])->findOrFail($memberId);
        $membership = $member->activeMembership;

        $mode = $request->input('mode', 'auto');
        $now = now();

        // پشکنینی ئایا یاریزانەکە لەم ساتەدا لە ژوورەوەیە
        $openAttendance = MemberAttendance::where('gym_id', $activeGymId)
            ->where('member_id', $member->id)
            ->where('status', 'INSIDE')
            ->latest('check_in_at')
            ->first();

        $action = 'ENTRY';
        $durationFormatted = null;
        $durationMinutes = null;

        if ($mode === 'exit' || ($mode === 'auto' && $openAttendance)) {
            // ئەنجامدانی چوونەدەرەوە (Check-out)
            $action = 'EXIT';
            if ($openAttendance) {
                $durationMinutes = (int) $openAttendance->check_in_at->diffInMinutes($now);
                $openAttendance->update([
                    'check_out_at' => $now,
                    'duration_minutes' => $durationMinutes,
                    'status' => 'CHECKED_OUT',
                ]);
                $durationFormatted = $openAttendance->duration_formatted;
            } else {
                // ئەگەر تۆماری هاتنی نەبوو بەڵام کلیکی دەرچوونی کرد
                $durationFormatted = 'ـ';
            }

            $message = "چوونەدەرەوە تۆمارکرا. ماوەی ڕاهێنان: {$durationFormatted}";
        } else {
            // ئەنجامدانی هاتنەژوورەوە (Check-in)
            $action = 'ENTRY';
            $attendance = MemberAttendance::create([
                'gym_id' => $activeGymId,
                'member_id' => $member->id,
                'membership_id' => $membership?->id,
                'access_device_id' => $device?->id,
                'check_in_at' => $now,
                'status' => 'INSIDE',
                'check_in_method' => 'card_scan',
            ]);

            $message = "بەخێربێیت {$member->full_name}؛ هاتنەژوورەوەت بە سەرکەوتوویی تۆمارکرا.";
        }

        // بەدەستهێنانی ئامارە نوێکراوەکان بۆ ناردنەوە دەستبەجێ
        $today = Carbon::today();
        $totalEntries = MemberAttendance::where('gym_id', $activeGymId)->whereDate('check_in_at', $today)->count();
        $totalExits = MemberAttendance::where('gym_id', $activeGymId)->whereDate('check_out_at', $today)->count();
        $currentlyInside = MemberAttendance::where('gym_id', $activeGymId)->where('status', 'INSIDE')->count();

        // لە حاڵەتی ڕێگەپێدان: ناردنی سیگناڵ بۆ ئاردینۆ و چرکاندنی ڕیلەی دەرگا (Arduino Relay Trigger)
        $arduinoResult = null;
        if ($action === 'ENTRY' || config('arduino.trigger_on_exit', false)) {
            try {
                $arduinoService = app(\App\Services\ArduinoRelayService::class);
                $arduinoResult = $arduinoService->triggerRelay();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Arduino trigger failed: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'status' => 'GRANTED',
            'action' => $action,
            'message' => $message,
            'member' => [
                'id' => $member->id,
                'name' => $member->full_name,
                'code' => $member->member_code,
                'phone' => $member->phone,
                'photo_url' => $member->photo_url,
                'plan_name' => $membership?->plan?->name ?? 'پلانی بێ ناو',
                'expires_at' => $membership?->end_date?->format('Y-m-d') ?? 'دیارینەکراو',
                'days_left' => $membership?->end_date ? (int) now()->diffInDays($membership->end_date, false) : 0,
                'status' => $member->status,
                'is_inside' => $action === 'ENTRY',
                'action_time' => $now->format('h:i A'),
                'duration_formatted' => $durationFormatted,
                'current_debt' => (float) $member->current_debt,
                'has_debt' => $member->current_debt > 0,
            ],
            'stats' => [
                'total_entries' => $totalEntries,
                'total_exits' => $totalExits,
                'currently_inside' => $currentlyInside,
            ],
            'arduino' => $arduinoResult,
            'verification_time_ms' => $result['verification_time_ms'] ?? 12,
        ]);
    }

    /**
     * تاقیکردنەوەی خێرای پەیوەندی بە ئاردینۆ لە کۆنترۆڵەکانەوە (Test Arduino Relay)
     */
    public function testArduino(Request $request, \App\Services\ArduinoRelayService $arduinoService): JsonResponse
    {
        $port = $request->input('port');
        $cmd = $request->input('command', '1');
        $result = $arduinoService->triggerRelay($port, $cmd);

        return response()->json($result);
    }

    /**
     * دەرکردن یان داخستنی دەستی هاتنی یاریزان (Manual Check-out)
     */
    public function manualCheckOut(Request $request, MemberAttendance $attendance)
    {
        if ($attendance->status === 'INSIDE') {
            $now = now();
            $duration = (int) $attendance->check_in_at->diffInMinutes($now);
            $attendance->update([
                'check_out_at' => $now,
                'duration_minutes' => $duration,
                'status' => 'CHECKED_OUT',
            ]);

            return back()->with('success', "دەرچوونی {$attendance->member->full_name} بە سەرکەوتوویی تۆمارکرا. ماوەی مانەوە: {$attendance->duration_formatted}");
        }

        return back()->with('info', 'ئەم یاریزانە پێشتر دەرچوونی بۆ کراوە.');
    }

    /**
     * کردنەوەی دەستیی دەرگا بۆ میوان یان ناونووسین بەبێ پێویستی بە کۆد (Manual Gate Release for Guests)
     */
    public function manualOpenGate(Request $request, \App\Services\ArduinoRelayService $arduinoService): JsonResponse
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $guestName = $request->input('guest_name');
        $reason = $request->input('reason', 'میوان / ناونووسینی نوێ (Guest Entry)');

        $device = AccessDevice::where('gym_id', $activeGymId)->where('is_active', true)->first();

        // ١. ناردنی سیگناڵ بۆ ئاردینۆ و چرکاندنی ڕیلەی دەرگای هۆڵ
        $arduinoResult = null;
        try {
            $arduinoResult = $arduinoService->triggerRelay();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("[ManualGateOpen] Arduino trigger: " . $e->getMessage());
        }

        // ٢. تۆمارکردنی ڕووداو لە لۆگی ئەکسێس (Access Event Audit Log)
        $event = AccessEvent::create([
            'gym_id' => $activeGymId,
            'access_device_id' => $device?->id,
            'member_id' => null,
            'membership_id' => null,
            'scanned_code' => 'MANUAL_GUEST_ENTRY' . ($guestName ? ": {$guestName}" : ''),
            'status' => 'GRANTED',
            'denial_reason' => $reason . ($guestName ? " (ناو: {$guestName})" : ''),
            'client_ip' => $request->ip(),
            'verification_time_ms' => 5,
            'scanned_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'status' => 'GRANTED',
            'action' => 'MANUAL_RELEASE',
            'message' => 'دەرگای سەرەکی هۆڵ بە سەرکەوتوویی کرایەوە بۆ میوان / ناونووسین 🚪✨',
            'guest_name' => $guestName ?? 'میوانی ئاسایی',
            'arduino' => $arduinoResult,
            'unlock_seconds' => 5,
            'event_id' => $event->id,
            'opened_at' => now()->format('h:i:s A'),
        ]);
    }
}
