<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Scopes\GymScope;
use App\Services\MembershipService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberViewController extends Controller
{
    public function index(Request $request)
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $activeGym = Gym::find($activeGymId);

        // کۆکردنەوە و دەستنیشانکردنی یاریزانە بەسەرچووەکان (15+ / 30+ / 60+ ڕۆژ)
        $allGymMembers = Member::with([
            'memberships' => fn($q) => $q->latest('end_date'),
            'memberships.plan',
            'primaryCredential'
        ])->where('gym_id', $activeGymId)->get();

        $expired15To30 = collect();
        $expired30To60 = collect();
        $expired60Plus = collect();
        $allDormantExpired = collect();
        $dormantMemberIds = [];

        foreach ($allGymMembers as $m) {
            $hasActive = $m->memberships->first(function ($membership) {
                return $membership->status === 'active' && $membership->end_date && $membership->end_date->isFuture();
            });

            $hasFrozen = $m->memberships->first(fn($membership) => $membership->status === 'frozen');

            if (!$hasActive && !$hasFrozen) {
                $lastMembership = $m->memberships->first();
                if ($lastMembership && $lastMembership->end_date && $lastMembership->end_date->isPast()) {
                    $days = (int) Carbon::parse($lastMembership->end_date)->diffInDays(now());
                    
                    if ($days >= 15) {
                        $dormantMemberIds[] = $m->id;
                        $m->days_expired = $days;
                        $m->last_plan_name = $lastMembership->plan?->name ?? 'دیارینەکراو';
                        $m->last_end_date = $lastMembership->end_date->format('Y-m-d');
                        
                        $allDormantExpired->push($m);

                        if ($days >= 15 && $days < 30) {
                            $expired15To30->push($m);
                        } elseif ($days >= 30 && $days < 60) {
                            $expired30To60->push($m);
                        } else {
                            $expired60Plus->push($m);
                        }
                    }
                }
            }
        }

        // تۆمارکردنی لۆگی پشکنینی ئەرشیف بۆ سکیوریتی و چاودێری
        if ($request->has('archive_viewed')) {
            AuditLog::create([
                'gym_id' => $activeGymId,
                'user_id' => auth()->id() ?? 1,
                'action' => 'EXPIRED_MEMBERS_ARCHIVE_VIEWED',
                'auditable_type' => Member::class,
                'auditable_id' => 0,
                'new_values' => [
                    'dormant_count' => $allDormantExpired->count(),
                    'viewed_at' => now()->toIso8601String(),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // ئەژمارکردنی ئەو ئەندامانەی بەشدارییەکەیان لە بەسەرچووندایە (٧ ڕۆژ، ٣ ڕۆژ، ١ ڕۆژ)
        $now = now();
        $in7Days = now()->copy()->addDays(7);
        $in3Days = now()->copy()->addDays(3);
        $in1Day = now()->copy()->addDay();

        $expiring7dCount = Member::where('gym_id', $activeGymId)
            ->whereHas('activeMembership', function ($q) use ($now, $in7Days) {
                $q->whereBetween('end_date', [$now->toDateString(), $in7Days->toDateString()]);
            })->count();

        $expiring3dCount = Member::where('gym_id', $activeGymId)
            ->whereHas('activeMembership', function ($q) use ($now, $in3Days) {
                $q->whereBetween('end_date', [$now->toDateString(), $in3Days->toDateString()]);
            })->count();

        $expiring1dCount = Member::where('gym_id', $activeGymId)
            ->whereHas('activeMembership', function ($q) use ($now, $in1Day) {
                $q->whereBetween('end_date', [$now->toDateString(), $in1Day->toDateString()]);
            })->count();

        // فلتەری خشتەی سەرەکی یاریزانان
        $query = Member::with([
            'activeMembership.plan',
            'memberships.plan',
            'memberships.freezes',
            'primaryCredential'
        ])->where('gym_id', $activeGymId);

        // فلتەری ئاگادارکردنەوەی بەسەرچوون (٧ ڕۆژ، ٣ ڕۆژ، ١ ڕۆژ)
        if ($filter = $request->get('filter')) {
            if ($filter === 'expiring_7d') {
                $query->whereHas('activeMembership', fn($q) => $q->whereBetween('end_date', [$now->toDateString(), $in7Days->toDateString()]));
            } elseif ($filter === 'expiring_3d') {
                $query->whereHas('activeMembership', fn($q) => $q->whereBetween('end_date', [$now->toDateString(), $in3Days->toDateString()]));
            } elseif ($filter === 'expiring_1d') {
                $query->whereHas('activeMembership', fn($q) => $q->whereBetween('end_date', [$now->toDateString(), $in1Day->toDateString()]));
            }
        }

        // گەڕان بەپێی دەق
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('member_code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // بەپێی داواکاری: یاریزانانی بەسەرچووی 15+ ڕۆژ لە هیچ شوێنێکی خشتەی سەرەکی دەرناکەون مەگەر بە کلیک کردنی ئەرشیف
        if (!$request->filled('search') && !$request->boolean('show_archive') && $request->get('status') !== 'expired' && !$request->filled('filter')) {
            if (!empty($dormantMemberIds)) {
                $query->whereNotIn('id', $dormantMemberIds);
            }
        }

        $members = $query->latest()->paginate(12)->withQueryString();
        $plans = MembershipPlan::where('gym_id', $activeGymId)->where('is_active', true)->get();

        return view('members.index', [
            'activeGym' => $activeGym,
            'members' => $members,
            'plans' => $plans,
            'allDormantExpired' => $allDormantExpired,
            'expired15To30' => $expired15To30,
            'expired30To60' => $expired30To60,
            'expired60Plus' => $expired60Plus,
            'totalDormantCount' => $allDormantExpired->count(),
            'expiring7dCount' => $expiring7dCount,
            'expiring3dCount' => $expiring3dCount,
            'expiring1dCount' => $expiring1dCount,
            'activeFilter' => $request->get('filter'),
        ]);
    }

    /**
     * هەناردەکردنی لیستی بەسەرچووەکان بۆ باک ئەپ و پەیوەندی (CSV Backup Export)
     */
    public function exportExpired(Request $request): StreamedResponse
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $activeGym = Gym::find($activeGymId);

        $allGymMembers = Member::with([
            'memberships' => fn($q) => $q->latest('end_date'),
            'memberships.plan'
        ])->where('gym_id', $activeGymId)->get();

        $rows = [];
        foreach ($allGymMembers as $m) {
            $hasActive = $m->memberships->first(fn($ms) => $ms->status === 'active' && $ms->end_date && $ms->end_date->isFuture());
            $hasFrozen = $m->memberships->first(fn($ms) => $ms->status === 'frozen');

            if (!$hasActive && !$hasFrozen) {
                $last = $m->memberships->first();
                if ($last && $last->end_date && $last->end_date->isPast()) {
                    $days = (int) Carbon::parse($last->end_date)->diffInDays(now());
                    if ($days >= 15) {
                        $rows[] = [
                            $m->member_code,
                            $m->full_name,
                            $m->phone,
                            $last->plan?->name ?? 'نادیار',
                            $last->end_date->format('Y-m-d'),
                            $days . ' ڕۆژ',
                            $m->current_debt . ' IQD'
                        ];
                    }
                }
            }
        }

        // لۆگ کردنی داگرتنی باک ئەپ لە سێرڤەر بۆ پاراستنی داتا و سکیوریتی
        AuditLog::create([
            'gym_id' => $activeGymId,
            'user_id' => auth()->id() ?? 1,
            'action' => 'EXPIRED_MEMBERS_EXPORTED',
            'auditable_type' => Member::class,
            'auditable_id' => 0,
            'new_values' => [
                'exported_rows' => count($rows),
                'downloaded_at' => now()->toIso8601String(),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $fileName = 'GymPro_Expired_Members_Backup_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'w');
            // نووسینی UTF-8 BOM بۆ پشتگیریکردنی پیتی کوردی لە مایکرۆسۆفت ئێکسڵ
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'کۆدی یاریزان',
                'ناوی چوارینە',
                'ژمارەی مۆبایل',
                'پلانی کۆتایی',
                'بەرواری بەسەرچوون',
                'ماوەی بەسەرچوون (ڕۆژ)',
                'قەرزی ماوە'
            ]);

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * تۆمارکردنی یاریزانی نوێ لەگەڵ بەشداریکردنی دەستبەجێ
     */
    public function store(Request $request, MembershipService $service)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'phone' => 'required|string|max:50',
            'gender' => 'required|in:male,female',
            'birth_date' => 'nullable|date',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'health_notes' => 'nullable|string',
            'allow_credit' => 'boolean',
            'credit_limit' => 'nullable|numeric|min:0',
            'plan_id' => 'required|exists:membership_plans,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,bank_transfer',
        ]);

        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $plan = MembershipPlan::findOrFail($request->plan_id);

        $member = $service->registerMember([
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'gender' => $request->gender,
            'birth_date' => $request->birth_date,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'health_notes' => $request->health_notes,
            'allow_credit' => $request->boolean('allow_credit'),
            'credit_limit' => $request->credit_limit ?? 0.00,
        ], $activeGymId);

        $adminUserId = auth()->id() ?? 1;
        $membership = $service->subscribeToPlan(
            $member,
            $plan,
            $adminUserId,
            discount: (float) ($request->discount ?? 0.0),
            discountReason: $request->discount > 0 ? 'داشکاندنی تۆماری نوێ' : null,
            initialPayment: (float) $request->paid_amount,
            paymentMethod: $request->payment_method,
            customStartDate: $request->start_date,
            customEndDate: $request->end_date
        );

        $endFormatted = $membership->end_date ? $membership->end_date->format('Y-m-d') : 'دیارینەکراو';
        return back()->with('success', "یاریزان {$member->full_name} بە سەرکەوتوویی تۆمارکرا (#{$member->member_code}) بە پلانی {$plan->name}؛ چالاککراوە تاکو [{$endFormatted}].");
    }

    /**
     * نوێکردنەوەی بەشداری (Renew Membership) - مانگانە، ساڵانە، هتد
     */
    public function renew(Request $request, Member $member, MembershipService $service)
    {
        $request->validate([
            'plan_id' => 'required|exists:membership_plans,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,bank_transfer',
        ]);

        $plan = MembershipPlan::findOrFail($request->plan_id);
        $adminUserId = auth()->id() ?? 1;

        $membership = $service->subscribeToPlan(
            $member,
            $plan,
            $adminUserId,
            discount: (float) ($request->discount ?? 0.0),
            discountReason: $request->discount_reason ?? 'داشکاندنی نوێکردنەوە',
            initialPayment: (float) $request->paid_amount,
            paymentMethod: $request->payment_method,
            customStartDate: $request->start_date,
            customEndDate: $request->end_date
        );

        $endFormatted = $membership->end_date ? $membership->end_date->format('Y-m-d') : 'دیارینەکراو';
        return back()->with('success', "بەشداری یاریزان {$member->full_name} نوێکرایەوە بە پلانی {$plan->name}؛ چالاککراوە تاکو [{$endFormatted}].");
    }

    public function freeze(Request $request, Member $member, MembershipService $service)
    {
        $request->validate([
            'days_count' => 'required|integer|min:1|max:90',
            'reason' => 'required|string',
        ]);

        $membership = $member->activeMembership;
        if (!$membership) {
            return back()->with('error', 'ئەم ئەندامە هیچ بەشدارییەکی چالاکی نییە.');
        }

        $adminUser = \App\Models\User::first();
        $freeze = $service->freezeMembership($membership->id, (int) $request->days_count, $request->reason, $adminUser->id);

        return back()->with('success', "بەشداری یاریزان {$member->full_name} بۆ ماوەی {$request->days_count} ڕۆژ ڕاگیرا (لە {$freeze->freeze_start_date} تاکو {$freeze->freeze_end_date}). هۆکار: {$request->reason}");
    }

    public function unfreeze(Member $member, MembershipService $service)
    {
        $frozenMembership = $member->memberships()->where('status', 'frozen')->latest()->first();
        if (!$frozenMembership) {
            return back()->with('error', 'هیچ بەشدارییەکی ڕاگیراو بۆ ئەم ئەندامە نەدۆزرایەوە.');
        }

        $freeze = $frozenMembership->freezes()->where('status', 'active')->latest()->first();
        if (!$freeze) {
            return back()->with('error', 'تۆماری ڕاگرتن نەدۆزرایەوە.');
        }

        $adminUser = \App\Models\User::first();
        $updatedMembership = $service->unfreezeMembership($freeze->id, $adminUser->id);
        $newEndFormatted = $updatedMembership->end_date ? $updatedMembership->end_date->format('Y-m-d') : '';

        return back()->with('success', "بەشداری یاریزان {$member->full_name} چالاککرایەوە! بەرواری نوێی کۆتایی درێژکرایەوە و تاکو [{$newEndFormatted}] چالاککرا.");
    }

    /**
     * ناردنی وەسڵی بەشداری لە ڕێگەی WhatsApp
     */
    public function whatsappReceipt(Member $member, \App\Services\WhatsAppService $whatsAppService)
    {
        $member->load(['gym', 'activeMembership.plan', 'memberships.plan']);
        $membership = $member->activeMembership ?? $member->memberships()->latest('end_date')->first();

        if (!$membership) {
            return back()->with('error', 'هیچ بەشدارییەک بۆ ئەم ئەندامە نەدۆزرایەوە.');
        }

        $message = $whatsAppService->buildMembershipReceiptMessage($member, $membership);
        $url = $whatsAppService->generateWhatsAppUrl($member->phone, $message);

        return redirect()->away($url);
    }

    /**
     * ناردنی پەیامی ئاگادارکردنەوەی بەسەرچوونی بەشداری لە ڕێگەی WhatsApp
     */
    public function whatsappReminder(Request $request, Member $member, \App\Services\WhatsAppService $whatsAppService)
    {
        $member->load(['gym', 'memberships.plan']);
        $membership = $member->activeMembership ?? $member->memberships()->latest('end_date')->first();

        $days = $request->has('days') ? (int) $request->days : null;
        $message = $whatsAppService->buildExpiryNoticeMessage($member, $membership, $days);
        $url = $whatsAppService->generateWhatsAppUrl($member->phone, $message);

        return redirect()->away($url);
    }

    /**
     * پیشاندانی پرۆفایلی تەواوەتی ئەندام و مێژووی بەشداری و سەردانەکان (Member Profile & Timeline)
     */
    public function show(Member $member)
    {
        $member->load([
            'gym',
            'memberships' => fn($q) => $q->latest('created_at'),
            'memberships.plan',
            'memberships.freezes',
            'memberships.createdBy',
            'attendances' => fn($q) => $q->latest('entry_time')->take(30),
            'payments' => fn($q) => $q->latest('created_at')->take(30),
            'primaryCredential',
        ]);

        $plans = MembershipPlan::where('gym_id', $member->gym_id)->where('is_active', true)->get();

        return view('members.show', compact('member', 'plans'));
    }

    /**
     * چاپکردن و بینینی کارتی ئەندام بە QR و Barcode (Digital & Printable Member ID Card)
     */
    public function card(Member $member, \App\Services\AccessControlService $accessControl)
    {
        $member->load(['gym', 'activeMembership.plan', 'primaryCredential']);

        // دڵنیابوونەوە لە هەبوونی کارتی چوونەژوورەوە یان تۆکنی پارێزراوی QR
        if (!$member->primaryCredential) {
            $accessControl->generateSignedQrCredential($member);
            $member->load('primaryCredential');
        }

        return view('members.card', compact('member'));
    }
}
