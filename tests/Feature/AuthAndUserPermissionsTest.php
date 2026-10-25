<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndUserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected Gym $gym;
    protected Role $ownerRole;
    protected Role $trainerRole;
    protected User $superAdmin;
    protected User $owner;
    protected User $trainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gym = Gym::create([
            'name' => 'GymPro Test Gym',
            'code' => 'TESTGYM',
            'phone' => '+9647501112233',
            'is_active' => true,
        ]);

        $this->ownerRole = Role::create([
            'name' => 'gym_owner',
            'display_name_ku' => 'خاوەنی هۆڵ',
            'display_name_ar' => 'مالك الصالة',
            'display_name_en' => 'Gym Owner',
        ]);

        $this->trainerRole = Role::create([
            'name' => 'trainer',
            'display_name_ku' => 'ڕاهێنەر',
            'display_name_ar' => 'مدرب',
            'display_name_en' => 'Trainer',
        ]);

        $perm1 = Permission::create([
            'name' => 'members.view',
            'group_name' => 'members',
            'display_name_ku' => 'بینینی ئەندامان',
            'display_name_ar' => 'عرض الأعضاء',
            'display_name_en' => 'View Members',
        ]);

        $perm2 = Permission::create([
            'name' => 'payments.create',
            'group_name' => 'finance',
            'display_name_ku' => 'تۆمارکردنی پارەدان',
            'display_name_ar' => 'تسجيل الدفع',
            'display_name_en' => 'Record Payment',
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'email' => 'super@gympro.test',
            'phone' => '+9647500000001',
            'password' => Hash::make('secret123'),
            'is_super_admin' => true,
            'is_active' => true,
            'current_gym_id' => $this->gym->id,
        ]);

        $this->owner = User::create([
            'name' => 'Gym Owner User',
            'email' => 'owner@gympro.test',
            'phone' => '+9647500000002',
            'password' => Hash::make('secret123'),
            'is_super_admin' => false,
            'is_active' => true,
            'current_gym_id' => $this->gym->id,
        ]);
        $this->gym->users()->attach($this->owner->id, ['role_id' => $this->ownerRole->id, 'is_active' => true]);

        $this->trainer = User::create([
            'name' => 'Trainer User',
            'email' => 'trainer@gympro.test',
            'phone' => '+9647500000003',
            'password' => Hash::make('secret123'),
            'is_super_admin' => false,
            'is_active' => true,
            'current_gym_id' => $this->gym->id,
        ]);
        $this->gym->users()->attach($this->trainer->id, ['role_id' => $this->trainerRole->id, 'is_active' => true]);
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('چوونەژوورەوە بۆ سیستەم');
        $response->assertSee('ئیمەیڵ یان ژمارەی مۆبایل');
    }

    public function test_login_with_valid_email_authenticates(): void
    {
        $response = $this->post('/login', [
            'login' => 'owner@gympro.test',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->owner);
    }

    public function test_login_with_valid_phone_authenticates(): void
    {
        $response = $this->post('/login', [
            'login' => '+9647500000002',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->owner);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->from('/login')->post('/login', [
            'login' => 'owner@gympro.test',
            'password' => 'wrong-pass',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->owner->update(['is_active' => false]);

        $response = $this->from('/login')->post('/login', [
            'login' => 'owner@gympro.test',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_logout_terminates_session(): void
    {
        $this->actingAs($this->owner);

        $response = $this->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_users_index_page_loads_with_users_list(): void
    {
        $this->actingAs($this->owner);

        $response = $this->get('/users');
        $response->assertStatus(200);
        $response->assertSee('Gym Owner User');
        $response->assertSee('Trainer User');
        $response->assertSee('زیادکردنی بەکارهێنەر');
    }

    public function test_user_edit_form_loads_custom_permissions(): void
    {
        $this->actingAs($this->owner);

        $response = $this->get('/users/' . $this->trainer->id . '/edit');
        $response->assertStatus(200);
        $response->assertSee('دەستکاریکردنی دەسەڵات و مۆڵەتەکانی');
        $response->assertSee('members.view');
        $response->assertSee('payments.create');
    }

    public function test_can_update_user_role_and_direct_permissions(): void
    {
        $this->actingAs($this->owner);

        $perm = Permission::where('name', 'payments.create')->first();

        $response = $this->put('/users/' . $this->trainer->id, [
            'name' => 'Trainer Updated Name',
            'email' => 'trainer_updated@gympro.test',
            'phone' => '+9647509998877',
            'gym_id' => $this->gym->id,
            'role_id' => $this->trainerRole->id,
            'is_active' => '1',
            'permissions' => [$perm->id],
        ]);

        $response->assertRedirect();
        $this->trainer->refresh();

        $this->assertEquals('Trainer Updated Name', $this->trainer->name);
        $this->assertEquals('trainer_updated@gympro.test', $this->trainer->email);
        $this->assertTrue($this->trainer->directPermissions->contains($perm->id));
        $this->assertTrue($this->trainer->hasPermission('payments.create', $this->gym->id));
    }

    public function test_can_toggle_user_active_status(): void
    {
        $this->actingAs($this->owner);

        $this->assertTrue($this->trainer->is_active);

        $response = $this->post('/users/' . $this->trainer->id . '/toggle-status');
        $response->assertRedirect();

        $this->trainer->refresh();
        $this->assertFalse($this->trainer->is_active);
    }

    public function test_can_create_new_system_user(): void
    {
        $this->actingAs($this->owner);

        $response = $this->post('/users', [
            'name' => 'New Staff User',
            'email' => 'newstaff@gympro.test',
            'phone' => '+9647504445566',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
            'role_id' => $this->trainerRole->id,
            'gym_id' => $this->gym->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'newstaff@gympro.test',
            'name' => 'New Staff User',
        ]);
    }
}
