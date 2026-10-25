<?php

namespace App\Services;

use App\Models\AccessDevice;
use App\Models\AccessEvent;
use App\Models\Member;
use App\Models\MemberAccessCredential;
use App\Models\Membership;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccessControlService
{
    /**
     * دروستکردنی تۆکنی پارێزراوی QR بە واژۆی HMAC-SHA256
     */
    public function generateMemberQrToken(Member $member): MemberAccessCredential
    {
        $payload = [
            'mid' => $member->id,
            'gid' => $member->gym_id,
            'code' => $member->member_code,
            'time' => now()->timestamp,
            'nonce' => bin2hex(random_bytes(8)),
        ];

        $jsonPayload = json_encode($payload);
        $base64Payload = base64_encode($jsonPayload);
        $secret = config('app.key');
        $signature = hash_hmac('sha256', $base64Payload, $secret);

        $rawToken = $base64Payload . '.' . $signature;
        $tokenHash = hash('sha256', $rawToken);

        // ناچالاککردنی تۆکنەکانی پێشوو
        MemberAccessCredential::where('member_id', $member->id)->update([
            'is_revoked' => true,
            'revocation_reason' => 'کۆدی نوێ دروستکراوە',
        ]);

        return MemberAccessCredential::create([
            'gym_id' => $member->gym_id,
            'member_id' => $member->id,
            'credential_type' => 'qr_code',
            'token_hash' => $tokenHash,
            'raw_token' => $rawToken,
            'is_dynamic' => false,
            'is_revoked' => false,
        ]);
    }

    public function generateSignedQrCredential(Member $member): MemberAccessCredential
    {
        return $this->generateMemberQrToken($member);
    }

    /**
     * بزوێنەری پشکنینی دەرگا و بەڕێوەبردنی ٨ مەرجی سەرەکی
     */
    public function verifyAndProcessAccess(
        int $gymId,
        string $deviceIdentifier,
        string $scannedCode,
        ?string $clientIp = null
    ): array {
        $startTime = microtime(true);

        // ١. پشکنینی ئامێری دەرگا
        $device = AccessDevice::where('gym_id', $gymId)
            ->where('device_identifier', $deviceIdentifier)
            ->first();

        if (!$device || !$device->is_active) {
            return $this->logAndDeny(
                $gymId,
                $device?->id,
                null,
                null,
                $scannedCode,
                'device_disabled_or_not_found',
                'ئامێری دەرگا لە سیستەم چالاک نییە یان نەناسراوە.',
                $startTime,
                $clientIp
            );
        }

        // نوێکردنەوەی Heartbeat ـی ئامێرەکە
        $device->update(['last_heartbeat_at' => now()]);

        // ٢. لێکدانەوەی کۆدی سکەنکراو (QR Token یان Barcode یان Member Code)
        $member = null;
        $credential = null;

        if (str_contains($scannedCode, '.')) {
            // شێوازی QR واژۆکراو
            [$base64Payload, $signature] = explode('.', $scannedCode, 2);
            $secret = config('app.key');
            $expectedSignature = hash_hmac('sha256', $base64Payload, $secret);

            if (!hash_equals($expectedSignature, $signature)) {
                return $this->logAndDeny(
                    $gymId,
                    $device->id,
                    null,
                    null,
                    $scannedCode,
                    'invalid_signature',
                    'کۆدی QR دەستکاری کراوە یان ساختەیە.',
                    $startTime,
                    $clientIp
                );
            }

            $payload = json_decode(base64_decode($base64Payload), true);
            if (!$payload || empty($payload['mid'])) {
                return $this->logAndDeny(
                    $gymId,
                    $device->id,
                    null,
                    null,
                    $scannedCode,
                    'invalid_payload',
                    'پێکهاتەی کۆدی QR نادروستە.',
                    $startTime,
                    $clientIp
                );
            }

            $member = Member::withoutGlobalScopes()->find($payload['mid']);
        } else {
            // گەڕان بە بارکۆد، ژمارەی کارت، یان member_code
            $member = Member::withoutGlobalScopes()
                ->where('member_code', $scannedCode)
                ->first();

            if (!$member) {
                $credential = MemberAccessCredential::where('card_number', $scannedCode)
                    ->where('is_revoked', false)
                    ->first();
                $member = $credential ? $credential->member : null;
            }
        }

        if (!$member) {
            return $this->logAndDeny(
                $gymId,
                $device->id,
                null,
                null,
                $scannedCode,
                'member_not_found',
                'ئەندام نەدۆزرایەوە یان کۆدەکە هەڵەیە.',
                $startTime,
                $clientIp
            );
        }

        // پشکنینی تۆکنی هەڵوەشێنراو (Revoked)
        $revokedCred = MemberAccessCredential::where('member_id', $member->id)
            ->where('token_hash', hash('sha256', $scannedCode))
            ->where('is_revoked', true)
            ->first();

        if ($revokedCred) {
            return $this->logAndDeny(
                $gymId,
                $device->id,
                $member->id,
                null,
                $scannedCode,
                'credential_revoked',
                'ئەم کۆدە هەڵوەشێنراوەتەوە؛ تکایە داوای کۆدی نوێ بکە.',
                $startTime,
                $clientIp
            );
        }

        // ٣. پشکنینی هۆڵ (Multi-Gym Isolation)
        if ($member->gym_id !== $gymId) {
            return $this->logAndDeny(
                $gymId,
                $device->id,
                $member->id,
                null,
                $scannedCode,
                'wrong_gym',
                'ئەم ئەندامە سەر بەم هۆڵە نییە و مۆڵەتی چوونەژوورەوەی ئەم لقەی نییە.',
                $startTime,
                $clientIp
            );
        }

        // ٤. پشکنینی دۆخی ئەندام (Member Status)
        if ($member->status === 'frozen') {
            return $this->logAndDeny(
                $gymId,
                $device->id,
                $member->id,
                null,
                $scannedCode,
                'membership_frozen',
                'بەشداری ڕاگیراوە.',
                $startTime,
                $clientIp
            );
        }

        if ($member->status === 'expired') {
            return $this->logAndDeny(
                $gymId,
                $device->id,
                $member->id,
                null,
                $scannedCode,
                'membership_expired',
                'بەشداری بەسەرچووە.',
                $startTime,
                $clientIp
            );
        }

        if ($member->status !== 'active') {
            return $this->logAndDeny(
                $gymId,
                $device->id,
                $member->id,
                null,
                $scannedCode,
                'member_inactive',
                'ئەندام ناچالاکە.',
                $startTime,
                $clientIp
            );
        }

        // ٥. پشکنینی بەشداری (Active Membership Check)
        $membership = Membership::where('member_id', $member->id)
            ->where('gym_id', $gymId)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$membership) {
            // پشکنینی ئایا بەشدارییەکەی ڕاگیراوە
            $frozen = Membership::where('member_id', $member->id)
                ->where('gym_id', $gymId)
                ->where('status', 'frozen')
                ->first();

            if ($frozen) {
                return $this->logAndDeny(
                    $gymId,
                    $device->id,
                    $member->id,
                    $frozen->id,
                    $scannedCode,
                    'membership_frozen',
                    'بەشداری ڕاگیراوە.',
                    $startTime,
                    $clientIp
                );
            }

            return $this->logAndDeny(
                $gymId,
                $device->id,
                $member->id,
                null,
                $scannedCode,
                'membership_expired',
                'بەشداری بەسەرچووە.',
                $startTime,
                $clientIp
            );
        }

        // ٦. پشکنینی بەرواری بەسەرچوون (Expiration Date)
        if ($membership->end_date->isPast()) {
            $membership->update(['status' => 'expired']);
            $member->update(['status' => 'expired']);

            return $this->logAndDeny(
                $gymId,
                $device->id,
                $member->id,
                $membership->id,
                $scannedCode,
                'membership_expired',
                'بەشداری بەسەرچووە.',
                $startTime,
                $clientIp
            );
        }

        // ٧. پشکنینی سنووری سەردان (Visits Limit)
        if ($membership->visits_limit !== null && $membership->visits_used >= $membership->visits_limit) {
            return $this->logAndDeny(
                $gymId,
                $device->id,
                $member->id,
                $membership->id,
                $scannedCode,
                'visits_exhausted',
                'سنووری سەردان تەواو بووە.',
                $startTime,
                $clientIp
            );
        }

        // ٨. پشکنینی کاتی ڕێگەپێدراوی پلان (Time Restriction & Days)
        $plan = $membership->plan;
        if ($plan) {
            $now = Carbon::now();
            $currentTime = $now->format('H:i:s');
            $currentDayOfWeek = $now->dayOfWeek; // 0 (Sunday) to 6 (Saturday)

            if ($plan->allowed_entry_start && $plan->allowed_entry_end) {
                if ($currentTime < $plan->allowed_entry_start || $currentTime > $plan->allowed_entry_end) {
                    return $this->logAndDeny(
                        $gymId,
                        $device->id,
                        $member->id,
                        $membership->id,
                        $scannedCode,
                        'time_restricted',
                        'ئەم پلانە ڕێگە بە چوونەژوورەوەی ئەم کاتە نادات.',
                        $startTime,
                        $clientIp
                    );
                }
            }

            if (!empty($plan->allowed_days_of_week) && !in_array($currentDayOfWeek, $plan->allowed_days_of_week)) {
                return $this->logAndDeny(
                    $gymId,
                    $device->id,
                    $member->id,
                    $membership->id,
                    $scannedCode,
                    'day_restricted',
                    'ئەم پلانە ڕێگە بە چوونەژوورەوە لەم ڕۆژەدا نادات.',
                    $startTime,
                    $clientIp
                );
            }
        }

        // ٩. پشکنینی Anti-Passback (ڕێگری لە دووبارە سکەنکردنی دەستبەجێ لە کەمتر لە ٥ خولەکدا)
        $recentScan = AccessEvent::where('gym_id', $gymId)
            ->where('member_id', $member->id)
            ->where('status', 'GRANTED')
            ->where('scanned_at', '>=', now()->subMinutes(5))
            ->first();

        if ($recentScan) {
            return $this->logAndDeny(
                $gymId,
                $device->id,
                $member->id,
                $membership->id,
                $scannedCode,
                'anti_passback',
                'ئەم کۆدە کەمێک پێش ئێستا بەکارهاتووە. تکایە پاش ٥ خولەک هەوڵ بدەرەوە.',
                $startTime,
                $clientIp
            );
        }

        // هەموو مەرجەکان سەرکەوتوون: تۆمارکردنی سەردان و زیادکردنی ژمارەی هاتنەژوورەوە
        DB::transaction(function () use ($membership) {
            $membership->increment('visits_used');
        });

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        AccessEvent::create([
            'gym_id' => $gymId,
            'access_device_id' => $device->id,
            'member_id' => $member->id,
            'membership_id' => $membership->id,
            'scanned_code' => substr($scannedCode, 0, 50) . '...',
            'status' => 'GRANTED',
            'denial_reason' => null,
            'client_ip' => $clientIp,
            'verification_time_ms' => $durationMs,
            'scanned_at' => now(),
        ]);

        return [
            'access' => true,
            'status' => 'GRANTED',
            'message' => 'بەخێربێیت؛ ڕێگەپێدراوە.',
            'member' => [
                'id' => $member->id,
                'code' => $member->member_code,
                'name' => $member->full_name,
                'photo_url' => $member->photo_url,
                'plan_name' => $plan?->name,
                'expires_at' => $membership->end_date->format('Y-m-d'),
                'visits_used' => $membership->visits_used,
                'current_debt' => (float) $member->current_debt,
                'has_debt' => $member->current_debt > 0,
            ],
            'device' => [
                'identifier' => $device->device_identifier,
                'relay_pulse_ms' => $device->relay_pulse_ms,
            ],
            'verification_time_ms' => $durationMs,
        ];
    }

    /**
     * تۆمارکردنی ڕەتکردنەوە لە لاگ و گەڕاندنەوەی پەیامی ڕوون
     */
    protected function logAndDeny(
        int $gymId,
        ?int $deviceId,
        ?int $memberId,
        ?int $membershipId,
        string $scannedCode,
        string $reasonCode,
        string $messageKu,
        float $startTime,
        ?string $clientIp
    ): array {
        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        AccessEvent::create([
            'gym_id' => $gymId,
            'access_device_id' => $deviceId,
            'member_id' => $memberId,
            'membership_id' => $membershipId,
            'scanned_code' => substr($scannedCode, 0, 50) . '...',
            'status' => 'DENIED',
            'denial_reason' => $reasonCode,
            'client_ip' => $clientIp,
            'verification_time_ms' => $durationMs,
            'scanned_at' => now(),
        ]);

        $memberData = null;
        if ($memberId) {
            $m = Member::find($memberId);
            if ($m) {
                $memberData = [
                    'id' => $m->id,
                    'code' => $m->member_code,
                    'name' => $m->full_name,
                    'photo_url' => $m->photo_url,
                    'has_debt' => $m->current_debt > 0,
                    'current_debt' => (float) $m->current_debt,
                ];
            }
        }

        return [
            'access' => false,
            'status' => 'DENIED',
            'reason_code' => $reasonCode,
            'message' => $messageKu,
            'member' => $memberData,
            'verification_time_ms' => $durationMs,
        ];
    }
}
