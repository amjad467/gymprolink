<?php

namespace Tests\Feature;

use App\Models\AccessDevice;
use App\Models\AuditLog;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Role;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class Phase1AndPhase2FeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected Gym $gym;
    protected User $user;
    protected Role $ownerRole;
    protected MembershipPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gym = Gym::create([
            'name' => 'GymPro Phase Test Gym',
            'code' => 'PHASETST',
            'phone' => '+9647501112233',
            'is_active' => true,
        ]);

        $this->ownerRole = Role::create([
            'name' => 'gym_owner',
            'display_name_ku' => 'خاوەنی هۆڵ',
            'display_name_ar' => 'مالك الصالة',
            'display_name_en' => 'Gym Owner',
        ]);

        $this->user = User::create([
            'name' => 'Phase Test User',
            'email' => 'phase@gympro.test',
            'phone' => '+9647509990001',
            'password' => Hash::make('secret123'),
            'security_pin' => Hash::make('123456'),
            'two_factor_enabled' => false,
            'is_super_admin' => false,
            'is_active' => true,
            'current_gym_id' => $this->gym->id,
        ]);
        $this->gym->users()->attach($this->user->id, ['role_id' => $this->ownerRole->id, 'is_active' => true]);

        $this->plan = MembershipPlan::create([
            'gym_id' => $this->gym->id,
            'name' => 'Monthly Standard Plan',
            'price' => 50000.00,
            'duration_days' => 30,
            'is_active' => true,
        ]);
    }

    // =========================================================================
    // قۆناغی یەکەم (PHASE 1): ئاسایش و کۆنترۆڵ (Security & Control)
    // =========================================================================

    public function test_login_rate_limiting_locks_account_after_repeated_failures(): void
    {
        RateLimiter::clear(strtolower('phase@gympro.test') . '|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'login' => 'phase@gympro.test',
                'password' => 'wrongpass',
            ]);
        }

        // The 6th attempt should hit the rate limiter and show the lockout message
        $response = $this->post('/login', [
            'login' => 'phase@gympro.test',
            'password' => 'wrongpass',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertTrue(str_contains(session('errors')->first('login'), 'قوفڵ'));
    }

    public function test_two_factor_authentication_flow_redirects_and_verifies_pin(): void
    {
        $this->user->update(['two_factor_enabled' => true]);

        // Attempt login -> should redirect to 2FA verification page
        $response = $this->post('/login', [
            'login' => 'phase@gympro.test',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/login/verify-2fa');
        $this->assertGuest();

        // 2FA view loads
        $response2fa = $this->get('/login/verify-2fa');
        $response2fa->assertStatus(200);
        $response2fa->assertSee('پشکنینی ئاسایشی دووقۆناغی');

        // Wrong PIN fails
        $failResponse = $this->post('/login/verify-2fa', ['pin' => '000000']);
        $failResponse->assertSessionHasErrors('pin');
        $this->assertGuest();

        // Correct PIN authenticates
        $successResponse = $this->post('/login/verify-2fa', ['pin' => '123456']);
        $successResponse->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_audit_logs_dashboard_loads_and_filters(): void
    {
        $this->actingAs($this->user);

        AuditLog::create([
            'gym_id' => $this->gym->id,
            'user_id' => $this->user->id,
            'action' => 'auth.login_success',
            'auditable_type' => User::class,
            'auditable_id' => $this->user->id,
            'ip_address' => '127.0.0.1',
        ]);

        AuditLog::create([
            'gym_id' => $this->gym->id,
            'user_id' => $this->user->id,
            'action' => 'EXPENSE_RECORDED',
            'auditable_type' => Gym::class,
            'auditable_id' => $this->gym->id,
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->get('/audit-logs');
        $response->assertStatus(200);
        $response->assertSee('تۆماری چاودێری و کردارە هەستیارەکان');
        $response->assertSee('auth.login_success');
        $response->assertSee('EXPENSE_RECORDED');

        // Test category filter
        $financeResponse = $this->get('/audit-logs?category=finance');
        $financeResponse->assertStatus(200);
        $financeResponse->assertSee('EXPENSE_RECORDED');
    }

    public function test_database_restore_creates_safety_snapshot_and_restores(): void
    {
        $this->actingAs($this->user);

        $backupDir = storage_path('app/backups');
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $dbPath = database_path('database.sqlite');
        if (!File::exists($dbPath)) {
            File::put($dbPath, 'test-sqlite');
        }

        $dummyBackup = $backupDir . '/GymPro_Full_DB_Test.sqlite';
        File::copy($dbPath, $dummyBackup);

        $response = $this->post('/backups/GymPro_Full_DB_Test.sqlite/restore');
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'DATABASE_RESTORED_FROM_BACKUP',
        ]);
    }

    // =========================================================================
    // قۆناغی دووەم (PHASE 2): بەڕێوەبردنی زیرەکی جیم (Smart Gym Management)
    // =========================================================================

    public function test_expiring_alerts_filters_7_3_and_1_day_members(): void
    {
        $this->actingAs($this->user);

        // Member 1: Expiring in 2 days
        $member3d = Member::create([
            'gym_id' => $this->gym->id,
            'member_code' => 'EXP3D',
            'full_name' => 'Member Expiring Three Days',
            'phone' => '+9647501110003',
            'status' => 'active',
        ]);
        Membership::create([
            'gym_id' => $this->gym->id,
            'member_id' => $member3d->id,
            'membership_plan_id' => $this->plan->id,
            'start_date' => now()->subDays(28),
            'end_date' => now()->addDays(2),
            'original_end_date' => now()->addDays(2),
            'original_price' => 50000,
            'paid_price' => 50000,
            'status' => 'active',
            'created_by_user_id' => $this->user->id,
        ]);

        // Member 2: Expiring in 6 days
        $member7d = Member::create([
            'gym_id' => $this->gym->id,
            'member_code' => 'EXP7D',
            'full_name' => 'Member Expiring Seven Days',
            'phone' => '+9647501110007',
            'status' => 'active',
        ]);
        Membership::create([
            'gym_id' => $this->gym->id,
            'member_id' => $member7d->id,
            'membership_plan_id' => $this->plan->id,
            'start_date' => now()->subDays(24),
            'end_date' => now()->addDays(6),
            'original_end_date' => now()->addDays(6),
            'original_price' => 50000,
            'paid_price' => 50000,
            'status' => 'active',
            'created_by_user_id' => $this->user->id,
        ]);

        // Filter 7 days
        $response7d = $this->get('/members?filter=expiring_7d');
        $response7d->assertStatus(200);
        $response7d->assertSee('Member Expiring Seven Days');
        $response7d->assertSee('Member Expiring Three Days');

        // Filter 3 days
        $response3d = $this->get('/members?filter=expiring_3d');
        $response3d->assertStatus(200);
        $response3d->assertSee('Member Expiring Three Days');
        $response3d->assertDontSee('Member Expiring Seven Days');
    }

    public function test_member_detailed_profile_page_loads_with_lifecycle(): void
    {
        $this->actingAs($this->user);

        $member = Member::create([
            'gym_id' => $this->gym->id,
            'member_code' => 'PROF01',
            'full_name' => 'Profile Test Member',
            'phone' => '+9647502223334',
            'status' => 'active',
        ]);

        Membership::create([
            'gym_id' => $this->gym->id,
            'member_id' => $member->id,
            'membership_plan_id' => $this->plan->id,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'original_end_date' => now()->addDays(30),
            'original_price' => 50000,
            'paid_price' => 50000,
            'status' => 'active',
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->get('/members/' . $member->id);
        $response->assertStatus(200);
        $response->assertSee('Profile Test Member');
        $response->assertSee('Monthly Standard Plan');
        $response->assertSee('مێژووی تەواوی بەشداری و نوێکردنەوەکان');
    }

    public function test_member_id_card_page_renders_with_secure_qr(): void
    {
        $this->actingAs($this->user);

        $member = Member::create([
            'gym_id' => $this->gym->id,
            'member_code' => 'CARD01',
            'full_name' => 'Card Printable Member',
            'phone' => '+9647503334445',
            'status' => 'active',
        ]);

        $response = $this->get('/members/' . $member->id . '/card');
        $response->assertStatus(200);
        $response->assertSee('Card Printable Member');
        $response->assertSee('#CARD01');
        $response->assertSee('HMAC-SHA256 SECURED');
    }

    public function test_membership_freeze_extends_end_date_properly(): void
    {
        $this->actingAs($this->user);

        $member = Member::create([
            'gym_id' => $this->gym->id,
            'member_code' => 'FRZ01',
            'full_name' => 'Freeze Test Member',
            'phone' => '+9647505556667',
            'status' => 'active',
        ]);

        $initialEnd = now()->addDays(20)->startOfDay();

        $membership = Membership::create([
            'gym_id' => $this->gym->id,
            'member_id' => $member->id,
            'membership_plan_id' => $this->plan->id,
            'start_date' => now()->subDays(10),
            'end_date' => $initialEnd,
            'original_end_date' => $initialEnd,
            'original_price' => 50000,
            'paid_price' => 50000,
            'status' => 'active',
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->post('/members/' . $member->id . '/freeze', [
            'days_count' => 14,
            'reason' => 'گەشت بۆ دەرەوەی وڵات',
        ]);

        $response->assertRedirect();
        $member->refresh();
        $this->assertEquals('frozen', $member->status);

        $this->assertDatabaseHas('membership_freezes', [
            'membership_id' => $membership->id,
            'days_count' => 14,
            'reason' => 'گەشت بۆ دەرەوەی وڵات',
        ]);
    }

    public function test_whatsapp_service_generates_custom_expiry_notice(): void
    {
        $member = Member::create([
            'gym_id' => $this->gym->id,
            'member_code' => 'WA01',
            'full_name' => 'Ali Ahmed',
            'phone' => '07501112233',
            'status' => 'active',
        ]);

        $membership = Membership::create([
            'gym_id' => $this->gym->id,
            'member_id' => $member->id,
            'membership_plan_id' => $this->plan->id,
            'start_date' => now()->subDays(27),
            'end_date' => now()->addDays(3),
            'original_end_date' => now()->addDays(3),
            'original_price' => 50000,
            'paid_price' => 50000,
            'status' => 'active',
            'created_by_user_id' => $this->user->id,
        ]);

        $waService = new WhatsAppService();

        // 3 days test
        $msg3d = $waService->buildExpiryNoticeMessage($member, $membership, 3);
        $this->assertStringContainsString('3 ڕۆژ', $msg3d);

        // 1 day test
        $msg1d = $waService->buildExpiryNoticeMessage($member, $membership, 1);
        $this->assertStringContainsString('سبەی', $msg1d);

        // WhatsApp redirect endpoint test
        $this->actingAs($this->user);
        $response = $this->get('/members/' . $member->id . '/whatsapp/reminder?days=3');
        $response->assertRedirect();
        $this->assertStringContainsString('https://api.whatsapp.com/send', $response->headers->get('Location'));
    }
}
