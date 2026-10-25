<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Member;
use App\Models\User;
use App\Scopes\GymScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class MultiGymTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * تاقیکردنەوەی فلتەرکردنی داتای هۆڵەکان: کارمەندی هۆڵی ١ نابێت ئەندامانی هۆڵی ٢ ببینێت
     */
    public function test_tenant_scope_isolates_members_between_gyms(): void
    {
        $gym1 = Gym::where('code', 'CENTRAL')->first();
        $gym2 = Gym::where('code', 'SORAN')->first();

        // ڕێکخستنی Context بۆ هۆڵی ١
        Session::put('active_gym_id', $gym1->id);

        $gym1Members = Member::all();
        $this->assertNotEmpty($gym1Members);

        foreach ($gym1Members as $member) {
            $this->assertEquals($gym1->id, $member->gym_id);
            $this->assertNotEquals($gym2->id, $member->gym_id);
        }

        // ڕێکخستنی Context بۆ هۆڵی ٢
        Session::put('active_gym_id', $gym2->id);

        $gym2Members = Member::all();
        $this->assertNotEmpty($gym2Members);

        foreach ($gym2Members as $member) {
            $this->assertEquals($gym2->id, $member->gym_id);
            $this->assertNotEquals($gym1->id, $member->gym_id);
        }
    }

    /**
     * تاقیکردنەوەی ڕێگری لە دەستکاریکردنی gym_id لە ڕێگەی داواکاری (IDOR Prevention)
     */
    public function test_unauthorized_user_cannot_access_another_gym_data(): void
    {
        $gym2 = Gym::where('code', 'SORAN')->first();
        $gym1Manager = User::where('email', 'manager1@gympro.test')->first();

        // بەڕێوەبەری هۆڵی ١ هەوڵ دەدات خۆی بخاتە ناو هۆڵی ٢
        $gym2Shop = \App\Models\GymShop::withoutGlobalScopes()->where('gym_id', $gym2->id)->first();
        $this->assertNotNull($gym2Shop);

        $response = $this->actingAs($gym1Manager)
            ->withHeaders(['X-Gym-Id' => $gym2->id])
            ->getJson('/api/v1/pos/shops/' . $gym2Shop->id . '/products');

        // بەهۆی نەبوونی مۆڵەت لە هۆڵی ٢ پێویستە 403 Forbidden بێت
        $response->assertStatus(403);
    }
}
