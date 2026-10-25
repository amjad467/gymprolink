<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Gym;
use App\Models\GymShop;
use App\Models\InventoryBatch;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\ShopCashShift;
use App\Models\User;
use App\Services\SupplementShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Phase3FinanceAndSalesTest extends TestCase
{
    use RefreshDatabase;

    protected Gym $gym;
    protected GymShop $shop;
    protected User $adminUser;
    protected User $cashierUser;
    protected User $trainerUser;
    protected ProductVariant $variant;
    protected InventoryBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gym = Gym::create([
            'name' => 'Olympia Fitness Sulaymaniyah',
            'code' => 'OLYM-SUL',
            'is_active' => true,
        ]);

        $this->shop = GymShop::create([
            'gym_id' => $this->gym->id,
            'name' => 'Olympia Supplement Corner',
            'code' => 'OLYM-SHOP-1',
            'is_active' => true,
        ]);

        $ownerRole = Role::create([
            'name' => 'gym_owner',
            'display_name_ku' => 'خاوەن هۆڵ',
            'display_name_ar' => 'مالك الصالة',
            'display_name_en' => 'Gym Owner',
        ]);

        $cashierRole = Role::create([
            'name' => 'cashier',
            'display_name_ku' => 'کاشێر',
            'display_name_ar' => 'كاشير',
            'display_name_en' => 'Cashier',
        ]);

        $trainerRole = Role::create([
            'name' => 'trainer',
            'display_name_ku' => 'ڕاهێنەر',
            'display_name_ar' => 'مدرب',
            'display_name_en' => 'Trainer',
        ]);

        $refundPerm = Permission::create([
            'name' => 'shop.refund.approve',
            'group_name' => 'shop',
            'display_name_ku' => 'پەسەندکردنی گەڕاندنەوەی کاڵا',
            'display_name_ar' => 'الموافقة على الإرجاع',
            'display_name_en' => 'Approve Shop Refund',
        ]);

        $ownerRole->permissions()->attach($refundPerm->id);

        $this->adminUser = User::create([
            'name' => 'Admin Owner',
            'email' => 'admin_phase3@test.com',
            'password' => bcrypt('password'),
            'current_gym_id' => $this->gym->id,
            'is_active' => true,
        ]);
        $this->gym->users()->attach($this->adminUser->id, ['role_id' => $ownerRole->id, 'is_active' => true]);

        $this->cashierUser = User::create([
            'name' => 'Cashier User',
            'email' => 'cashier_phase3@test.com',
            'password' => bcrypt('password'),
            'current_gym_id' => $this->gym->id,
            'is_active' => true,
        ]);
        $this->gym->users()->attach($this->cashierUser->id, ['role_id' => $cashierRole->id, 'is_active' => true]);

        $this->trainerUser = User::create([
            'name' => 'Trainer User',
            'email' => 'trainer_phase3@test.com',
            'password' => bcrypt('password'),
            'current_gym_id' => $this->gym->id,
            'is_active' => true,
        ]);
        $this->gym->users()->attach($this->trainerUser->id, ['role_id' => $trainerRole->id, 'is_active' => true]);

        $product = Product::create([
            'name_ku' => 'پڕۆتینی گۆڵد ستاندارد',
            'name_ar' => 'بروتين جولد ستاندرد',
            'name_en' => 'Gold Standard Whey',
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'OPT-WHEY-CHOC',
            'barcode' => '748927028669',
            'flavor' => 'Double Rich Chocolate',
            'default_cost_price' => 45000,
            'default_sale_price' => 65000,
            'member_sale_price' => 60000,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $this->batch = InventoryBatch::create([
            'shop_id' => $this->shop->id,
            'variant_id' => $this->variant->id,
            'batch_number' => 'BATCH-2026-001',
            'quantity_received' => 20,
            'quantity_available' => 20,
            'unit_cost_price' => 45000,
            'expiry_date' => now()->addMonths(12)->toDateString(),
            'is_blocked' => false,
        ]);
    }

    public function test_cashier_can_open_shift_with_drawer_cash(): void
    {
        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('pos.shift.open'), [
                'opening_cash' => 250000,
                'opening_notes' => 'شفت دەستی پێکرد بە 250 هەزار',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'shift' => [
                    'opening_cash' => 250000,
                    'status' => 'OPEN',
                ],
            ]);

        $this->assertDatabaseHas('shop_cash_shifts', [
            'cashier_user_id' => $this->cashierUser->id,
            'opening_cash' => 250000,
            'status' => 'OPEN',
        ]);
    }

    public function test_cash_reconciliation_calculates_difference_accurately_on_close(): void
    {
        $shopService = app(SupplementShopService::class);

        $shift = $shopService->openCashShift($this->shop->id, $this->cashierUser->id, 100000);

        // فرۆشتنێکی نەقد
        $sale = $shopService->processPosSale(
            $this->shop->id,
            $this->cashierUser->id,
            [['variant_id' => $this->variant->id, 'quantity' => 1, 'unit_price' => 65000]],
            'CASH'
        );

        // Expected cash should be 100,000 + 65,000 = 165,000
        // Cashier counts 160,000 (Shortage of 5,000)
        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('pos.shift.close'), [
                'shift_id' => $shift->id,
                'actual_cash' => 160000,
                'closing_notes' => 'کورتهێنانی 5 هەزار بەهۆی نەبوونی وردە',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'shift' => [
                    'id' => $shift->id,
                    'expected_cash' => 165000,
                    'actual_cash' => 160000,
                    'cash_difference' => -5000,
                ],
            ]);

        $this->assertDatabaseHas('shop_cash_shifts', [
            'id' => $shift->id,
            'status' => 'CLOSED',
            'expected_cash' => 165000,
            'actual_cash' => 160000,
            'cash_difference' => -5000,
        ]);
    }

    public function test_unauthorized_user_cannot_refund_pos_invoice(): void
    {
        $shopService = app(SupplementShopService::class);
        $shift = $shopService->openCashShift($this->shop->id, $this->cashierUser->id, 0);

        $sale = $shopService->processPosSale(
            $this->shop->id,
            $this->cashierUser->id,
            [['variant_id' => $this->variant->id, 'quantity' => 1, 'unit_price' => 65000]],
            'CASH'
        );

        // Trainer does not have shop.refund.approve permission
        $response = $this->actingAs($this->trainerUser)
            ->postJson(route('pos.invoices.refund', $sale->id), [
                'reason' => 'کڕیار پەشیمان بووەوە',
            ]);

        $response->assertStatus(403);
    }

    public function test_manager_can_refund_sale_restocks_inventory_and_reduces_debt(): void
    {
        $shopService = app(SupplementShopService::class);
        $shift = $shopService->openCashShift($this->shop->id, $this->cashierUser->id, 0);

        $member = Member::create([
            'gym_id' => $this->gym->id,
            'member_code' => 'MEM-REF-01',
            'full_name' => 'هێمن ئەحمەد',
            'phone' => '07701112233',
            'allow_credit' => true,
            'credit_limit' => 200000,
            'current_debt' => 0,
            'status' => 'active',
        ]);

        $initialBatchStock = $this->batch->fresh()->quantity_available;

        // فرۆشتن بە قەرز بۆ ئەندام
        $sale = $shopService->processPosSale(
            $this->shop->id,
            $this->cashierUser->id,
            [['variant_id' => $this->variant->id, 'quantity' => 2, 'unit_price' => 60000]],
            'MEMBER_CREDIT',
            $member->id
        );

        $member->refresh();
        $this->assertEquals(120000, $member->current_debt);
        $this->assertEquals($initialBatchStock - 2, $this->batch->fresh()->quantity_available);

        // بەڕێوەبەر گەڕاندنەوە ئەنجام دەدات
        $response = $this->actingAs($this->adminUser)
            ->post(route('pos.invoices.refund', $sale->id), [
                'reason' => 'کاڵاکە نەکرایەوە و گەڕێندرایەوە',
            ]);

        $response->assertSessionHas('success');

        // پشکنینی گەڕانەوەی کاڵا بۆ کۆگا
        $this->assertEquals($initialBatchStock, $this->batch->fresh()->quantity_available);

        // پشکنینی کەمکردنەوەی قەرزی ئەندام
        $member->refresh();
        $this->assertEquals(0, $member->current_debt);
        $this->assertFalse((bool) $member->has_debt);

        // پشکنینی باری پارەدانی فاکتۆر
        $sale->refresh();
        $this->assertEquals('REFUNDED', $sale->payment_status);

        // پشکنینی تۆماری گەڕاندنەوە
        $this->assertDatabaseHas('pos_refunds', [
            'pos_sale_id' => $sale->id,
            'refund_amount' => 120000,
        ]);
    }

    public function test_inventory_low_stock_filters_correctly(): void
    {
        // Set variant quantity available to 3 (which is below min_stock_alert: 5)
        $this->batch->update(['quantity_available' => 3]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory.index', ['filter' => 'low_stock']));

        $response->assertStatus(200);
        $response->assertSee('ئاگاداری کەمبوونەوەی کاڵا لە کۆگادا');
        $response->assertSee('کەمبووەوە (3)');
    }

    public function test_sales_analytics_calculates_true_net_profit_and_expenses(): void
    {
        $shopService = app(SupplementShopService::class);
        $shift = $shopService->openCashShift($this->shop->id, $this->cashierUser->id, 0);

        // POS Sale: Price 65,000, Cost 45,000 -> Gross Profit 20,000
        $shopService->processPosSale(
            $this->shop->id,
            $this->cashierUser->id,
            [['variant_id' => $this->variant->id, 'quantity' => 1, 'unit_price' => 65000]],
            'CASH'
        );

        // Membership Payment: 50,000
        $plan = MembershipPlan::create([
            'gym_id' => $this->gym->id,
            'name' => 'Monthly Plan',
            'price' => 50000,
            'duration_days' => 30,
        ]);

        $member = Member::create([
            'gym_id' => $this->gym->id,
            'member_code' => 'MEM-PAY-01',
            'full_name' => 'کاروان ساڵح',
            'phone' => '07705556677',
            'status' => 'active',
        ]);

        $invoice = \App\Models\Invoice::create([
            'gym_id' => $this->gym->id,
            'member_id' => $member->id,
            'invoice_number' => 'INV-TEST-001',
            'subtotal' => 50000,
            'total_amount' => 50000,
            'paid_amount' => 50000,
            'status' => 'paid',
            'created_by_user_id' => $this->adminUser->id,
        ]);

        Payment::create([
            'gym_id' => $this->gym->id,
            'invoice_id' => $invoice->id,
            'member_id' => $member->id,
            'payment_method' => 'cash',
            'amount' => 50000,
            'received_by_user_id' => $this->adminUser->id,
        ]);

        // Operating Expense: 30,000
        Expense::create([
            'gym_id' => $this->gym->id,
            'category' => 'electricity',
            'title' => 'مۆلیدەی هۆڵ',
            'amount' => 30000,
            'spent_at' => now(),
            'recorded_by_user_id' => $this->adminUser->id,
        ]);

        // Expected True Net Profit:
        // (POS Gross Profit 20,000 + Membership 50,000) - Expense 30,000 = 40,000
        $response = $this->actingAs($this->adminUser)
            ->get(route('analytics.sales', ['period' => 'today']));

        $response->assertStatus(200);
        $response->assertSee('ڕاپۆرتی قازانجی ڕاستەقینەی هۆڵ، قەرزەکان و خەرجی');
        $response->assertSee('40,000');
    }

    public function test_daily_closing_z_report_displays_all_shifts_and_breakdowns(): void
    {
        $shopService = app(SupplementShopService::class);
        $shift = $shopService->openCashShift($this->shop->id, $this->cashierUser->id, 50000);

        $response = $this->actingAs($this->adminUser)
            ->get(route('financial.closing', ['date' => now()->toDateString()]));

        $response->assertStatus(200);
        $response->assertSee('بەراوردکاری و ژماردنی فیعلی سندوق');
        $response->assertSee('شفت و بەراوردی سندوق');
        $response->assertSee($this->cashierUser->name);
    }

    public function test_dashboard_kpis_are_cached_for_performance(): void
    {
        Cache::flush();

        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertStatus(200);

        $this->assertTrue(Cache::has("gym_dashboard_stats_{$this->gym->id}"));
    }
}
