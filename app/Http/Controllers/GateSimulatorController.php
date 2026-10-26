<?php

namespace App\Http\Controllers;

use App\Models\AccessDevice;
use App\Models\AccessEvent;
use App\Models\Gym;
use App\Models\Member;
use App\Scopes\GymScope;
use App\Services\AccessControlService;
use Illuminate\Http\Request;

class GateSimulatorController extends Controller
{
    public function index()
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $activeGym = Gym::find($activeGymId);
        $devices = AccessDevice::where('gym_id', $activeGymId)->where('is_active', true)->get();

        $members = Member::with(['activeMembership.plan', 'primaryCredential'])
            ->where('gym_id', $activeGymId)
            ->get();

        // هەروەها ئەندامێکی هۆڵی تر دەهێنین بۆ تاقیکردنەوەی خێرای چوونەژوورەوەی هۆڵی هەڵە
        $otherGymMember = Member::withoutGlobalScopes()
            ->with(['gym', 'primaryCredential'])
            ->where('gym_id', '!=', $activeGymId)
            ->first();

        $events = AccessEvent::with(['member', 'device'])
            ->where('gym_id', $activeGymId)
            ->latest('scanned_at')
            ->take(15)
            ->get();

        return view('gate.simulator', compact('activeGym', 'devices', 'members', 'otherGymMember', 'events'));
    }

    public function processScan(Request $request, AccessControlService $service)
    {
        $request->validate([
            'device_id' => 'required|exists:access_devices,id',
            'scanned_code' => 'required|string',
        ]);

        $device = AccessDevice::findOrFail($request->device_id);

        $result = $service->verifyAndProcessAccess(
            $device->gym_id,
            $device->device_identifier,
            $request->scanned_code,
            $request->ip()
        );

        if (!empty($result['access'])) {
            $hasWebSerial = $request->boolean('has_web_serial', false);
            if (!$hasWebSerial) {
                try {
                    $arduino = app(\App\Services\ArduinoRelayService::class);
                    $result['arduino'] = $arduino->triggerRelay();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Arduino trigger failed: " . $e->getMessage());
                }
            } else {
                $result['arduino'] = [
                    'success' => true,
                    'method' => 'web_serial',
                    'message' => 'سیگناڵ لە وێبگەڕەوە نێردرا بە Web Serial.',
                ];
            }
        }

        return response()->json($result);
    }
}
