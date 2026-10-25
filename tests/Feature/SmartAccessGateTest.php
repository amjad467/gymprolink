<?php

namespace Tests\Feature;

use App\Models\AccessDevice;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberAccessCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartAccessGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * ئەندامی چالاک بە بەشداری دروست دەبێت دەرگای بۆ بکرێتەوە (GRANTED)
     */
    public function test_active_member_is_granted_access(): void
    {
        $gym1 = Gym::where('code', 'CENTRAL')->first();
        $device = AccessDevice::where('gym_id', $gym1->id)->first();
        $activeMember = Member::withoutGlobalScopes()
            ->where('gym_id', $gym1->id)
            ->where('full_name', 'سەروەر هۆشیار')
            ->first();

        $credential = MemberAccessCredential::where('member_id', $activeMember->id)->first();

        $response = $this->postJson(route('api.access.scan'), [
            'device_identifier' => $device->device_identifier,
            'api_key' => $device->api_key,
            'scanned_code' => $credential->raw_token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'access' => true,
                'status' => 'GRANTED',
            ])
            ->assertJsonStructure([
                'access',
                'status',
                'message',
                'member' => ['id', 'name', 'code'],
                'device' => ['identifier', 'relay_pulse_ms'],
            ]);
    }

    /**
     * ئەندامی بەسەرچوو دەبێت ڕەتبکرێتەوە (membership_expired)
     */
    public function test_expired_membership_is_denied(): void
    {
        $gym1 = Gym::where('code', 'CENTRAL')->first();
        $device = AccessDevice::where('gym_id', $gym1->id)->first();
        $expiredMember = Member::withoutGlobalScopes()
            ->where('gym_id', $gym1->id)
            ->where('full_name', 'بەختیار عوسمان')
            ->first();

        $credential = MemberAccessCredential::where('member_id', $expiredMember->id)->first();

        $response = $this->postJson(route('api.access.scan'), [
            'device_identifier' => $device->device_identifier,
            'api_key' => $device->api_key,
            'scanned_code' => $credential->raw_token,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'access' => false,
                'status' => 'DENIED',
                'reason_code' => 'membership_expired',
            ]);
    }

    /**
     * ئەندامی ڕاگیراو (Frozen) دەبێت ڕەتبکرێتەوە (membership_frozen)
     */
    public function test_frozen_membership_is_denied(): void
    {
        $gym1 = Gym::where('code', 'CENTRAL')->first();
        $device = AccessDevice::where('gym_id', $gym1->id)->first();
        $frozenMember = Member::withoutGlobalScopes()
            ->where('gym_id', $gym1->id)
            ->where('full_name', 'ئاراس ئەحمەد')
            ->first();

        $credential = MemberAccessCredential::where('member_id', $frozenMember->id)->first();

        $response = $this->postJson(route('api.access.scan'), [
            'device_identifier' => $device->device_identifier,
            'api_key' => $device->api_key,
            'scanned_code' => $credential->raw_token,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'access' => false,
                'status' => 'DENIED',
                'reason_code' => 'membership_frozen',
            ]);
    }

    /**
     * ئەندامی هۆڵێکی تر کاتێک لە هۆڵی ١ سکەن دەکات دەبێت ڕەتبکرێتەوە (wrong_gym)
     */
    public function test_cross_gym_member_scan_is_denied(): void
    {
        $gym1 = Gym::where('code', 'CENTRAL')->first();
        $gym2 = Gym::where('code', 'SORAN')->first();

        $deviceGym1 = AccessDevice::where('gym_id', $gym1->id)->first();
        $memberGym2 = Member::withoutGlobalScopes()
            ->where('gym_id', $gym2->id)
            ->where('full_name', 'کاوە سۆران')
            ->first();

        $credential = MemberAccessCredential::where('member_id', $memberGym2->id)->first();

        $response = $this->postJson(route('api.access.scan'), [
            'device_identifier' => $deviceGym1->device_identifier,
            'api_key' => $deviceGym1->api_key,
            'scanned_code' => $credential->raw_token,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'access' => false,
                'status' => 'DENIED',
                'reason_code' => 'wrong_gym',
            ]);
    }

    /**
     * کۆدی ساختە بە واژۆی هەڵە دەبێت بلۆک بکرێت (invalid_signature)
     */
    public function test_tampered_qr_signature_is_denied(): void
    {
        $gym1 = Gym::where('code', 'CENTRAL')->first();
        $device = AccessDevice::where('gym_id', $gym1->id)->first();

        $tamperedToken = base64_encode(json_encode(['mid' => 1, 'gid' => $gym1->id])) . '.fake_signature_12345';

        $response = $this->postJson(route('api.access.scan'), [
            'device_identifier' => $device->device_identifier,
            'api_key' => $device->api_key,
            'scanned_code' => $tamperedToken,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'access' => false,
                'status' => 'DENIED',
                'reason_code' => 'invalid_signature',
            ]);
    }

    /**
     * تاقیکردنەوەی Anti-Passback: سکەنکردنی دووبارەی دەستبەجێ لە کەمتر لە ٥ خولەک
     */
    public function test_anti_passback_denies_immediate_duplicate_scan(): void
    {
        $gym1 = Gym::where('code', 'CENTRAL')->first();
        $device = AccessDevice::where('gym_id', $gym1->id)->first();
        $activeMember = Member::withoutGlobalScopes()
            ->where('gym_id', $gym1->id)
            ->where('full_name', 'سەروەر هۆشیار')
            ->first();

        $credential = MemberAccessCredential::where('member_id', $activeMember->id)->first();

        // سکەنی یەکەم: سەرکەوتوو
        $firstScan = $this->postJson(route('api.access.scan'), [
            'device_identifier' => $device->device_identifier,
            'api_key' => $device->api_key,
            'scanned_code' => $credential->raw_token,
        ]);
        $firstScan->assertStatus(200);

        // دەستبەجێ سکەنی دووەم لە هەمان دەرگا: ڕەتدەکرێتەوە
        $secondScan = $this->postJson(route('api.access.scan'), [
            'device_identifier' => $device->device_identifier,
            'api_key' => $device->api_key,
            'scanned_code' => $credential->raw_token,
        ]);

        $secondScan->assertStatus(403)
            ->assertJson([
                'access' => false,
                'status' => 'DENIED',
                'reason_code' => 'anti_passback',
            ]);
    }
}
