<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccessDevice;
use App\Services\AccessControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccessGateController extends Controller
{
    public function __construct(
        protected AccessControlService $accessControlService
    ) {}

    /**
     * ئەنجامدانی پشکنینی سکەن لە ئامێری دەرگاوە (QR / Barcode Scan API)
     */
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_identifier' => 'required|string',
            'api_key' => 'required|string',
            'scanned_code' => 'required|string',
        ]);

        // پشکنینی ئامێر و کلیلی نهێنی
        $device = AccessDevice::where('device_identifier', $validated['device_identifier'])->first();
        if (!$device || $device->api_key !== $validated['api_key']) {
            return response()->json([
                'access' => false,
                'status' => 'DENIED',
                'reason_code' => 'unauthorized_device',
                'message' => 'ئامێری دەرگا ڕێگەپێدراو نییە یان کلیلی دەستگەیشتن هەڵەیە.',
            ], 401);
        }

        $result = $this->accessControlService->verifyAndProcessAccess(
            $device->gym_id,
            $device->device_identifier,
            $validated['scanned_code'],
            $request->ip()
        );

        $httpStatus = $result['access'] ? 200 : 403;
        return response()->json($result, $httpStatus);
    }

    /**
     * پشکنینی دۆخی ئامێر و پەیوەندی بە سێرڤەر (Heartbeat)
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_identifier' => 'required|string',
            'api_key' => 'required|string',
        ]);

        $device = AccessDevice::where('device_identifier', $validated['device_identifier'])->first();
        if (!$device || $device->api_key !== $validated['api_key']) {
            return response()->json(['status' => 'ERROR', 'message' => 'Unauthorized Device'], 401);
        }

        $device->update(['last_heartbeat_at' => now()]);

        return response()->json([
            'status' => 'OK',
            'device' => $device->name,
            'gym_id' => $device->gym_id,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
