<?php

namespace Tests\Feature;

use App\Models\AccessDevice;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberAttendance;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Scopes\GymScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyAttendanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_dashboard_loads_with_correct_metrics(): void
    {
        $this->seed();

        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('داشبۆردی هاتن و ڕۆشتنی ڕۆژانەی یاریزانان');
        $response->assertSee('سەرجەم هاتووەکانی ئەمڕۆ');
        $response->assertSee('ئێستا لە هۆڵدان');
        $response->assertSee('دەرچووەکانی ئەمڕۆ');
    }

    public function test_member_punch_entry_and_exit_calculates_duration(): void
    {
        $this->seed();

        $gym = Gym::first();
        GymScope::setActiveGymId($gym->id);

        $member = Member::where('gym_id', $gym->id)->where('status', 'active')->first();
        $cred = $member->primaryCredential;
        $token = $cred ? $cred->raw_token : $member->member_code;

        // Ensure clean state without open attendance
        MemberAttendance::where('member_id', $member->id)->delete();

        // 1. First Punch: Entry
        $entryResponse = $this->postJson('/attendance/punch', [
            'scanned_code' => $token,
            'mode' => 'entry',
        ]);

        $entryResponse->assertStatus(200);
        $entryResponse->assertJson([
            'success' => true,
            'status' => 'GRANTED',
            'action' => 'ENTRY',
        ]);

        $this->assertDatabaseHas('member_attendances', [
            'member_id' => $member->id,
            'status' => 'INSIDE',
        ]);

        // Mock passage of time (80 minutes later)
        $attendance = MemberAttendance::where('member_id', $member->id)->where('status', 'INSIDE')->first();
        $attendance->update(['check_in_at' => now()->subMinutes(80)]);
        \App\Models\AccessEvent::where('member_id', $member->id)->update(['scanned_at' => now()->subMinutes(80)]);

        // 2. Second Punch: Exit
        $exitResponse = $this->postJson('/attendance/punch', [
            'scanned_code' => $token,
            'mode' => 'exit',
        ]);

        $exitResponse->assertStatus(200);
        $exitResponse->assertJson([
            'success' => true,
            'status' => 'GRANTED',
            'action' => 'EXIT',
        ]);

        $attendance->refresh();
        $this->assertEquals('CHECKED_OUT', $attendance->status);
        $this->assertNotNull($attendance->check_out_at);
        $this->assertGreaterThanOrEqual(79, $attendance->duration_minutes);
    }

    public function test_manual_open_gate_without_code_for_guest(): void
    {
        $this->seed();

        $gym = Gym::first();
        GymScope::setActiveGymId($gym->id);

        // 1. One-click instant manual gate release (no code)
        $response = $this->postJson('/attendance/gate/manual-open');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'GRANTED',
                'action' => 'MANUAL_RELEASE',
            ]);

        $this->assertDatabaseHas('access_events', [
            'gym_id' => $gym->id,
            'scanned_code' => 'MANUAL_GUEST_ENTRY',
            'status' => 'GRANTED',
        ]);

        // 2. Manual gate release with guest name and reason
        $guestResponse = $this->postJson('/attendance/gate/manual-open', [
            'guest_name' => 'کاک دیار',
            'reason' => 'بۆ ناونووسینی نوێ',
        ]);

        $guestResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'GRANTED',
                'guest_name' => 'کاک دیار',
            ]);

        $this->assertDatabaseHas('access_events', [
            'gym_id' => $gym->id,
            'scanned_code' => 'MANUAL_GUEST_ENTRY: کاک دیار',
            'status' => 'GRANTED',
        ]);
    }
}
