<?php

namespace Tests\Feature;

use App\Models\GymShop;
use App\Models\InventoryBatch;
use App\Models\ProductVariant;
use App\Models\ShopCashShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplementShopPosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * تاقیکردنەوەی FEFO: فرۆشتنی ١٢ دانە دەبێت سەرەتا باچی ١ (١٠ دانە) تەواو بکات، پاشان لە باچی ٢ (٢ دانە) ببات
     */
    public function test_fefo_depletes_earliest_expiring_batch_first(): void
    {
        $shop = GymShop::where('code', 'SHOP_CENTRAL')->first();
        $cashier = User::where('email', 'cashier1@gympro.test')->first();
        $variant = ProductVariant::where('sku', 'ON-WHEY-CHOC-5LB')->first();

        $batch1 = InventoryBatch::where('batch_number', 'LOT-2026-001')->first();
        $batch2 = InventoryBatch::where('batch_number', 'LOT-2026-002')->first();

        $this->assertEquals(10, $batch1->quantity_available);
        $this->assertEquals(20, $batch2->quantity_available);

        // فرۆشتنی ١٢ دانە
        $response = $this->postJson(route('api.pos.checkout', ['shop' => $shop->id]), [
            'cashier_user_id' => $cashier->id,
            'items' => [
                [
                    'variant_id' => $variant->id,
                    'quantity' => 12,
                ],
            ],
            'payment_method' => 'CASH',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $batch1->refresh();
        $batch2->refresh();

        // باچی یەکەم دەبێت ببووبێتە ٠
        $this->assertEquals(0, $batch1->quantity_available);
        // باچی دووەم دەبێت لە ٢٠ بووبێتە ١٨
        $this->assertEquals(18, $batch2->quantity_available);
    }

    /**
     * تاقیکردنەوەی بلۆککردنی کاڵای بەسەرچوو: باچی بەسەرچوو هەرگیز نافرۆشرێت
     */
    public function test_expired_batches_are_strictly_blocked_from_pos(): void
    {
        $expiredBatch = InventoryBatch::where('batch_number', 'LOT-EXPIRED-TEST')->first();
        $this->assertTrue($expiredBatch->isExpired());
        $initialQty = $expiredBatch->quantity_available;

        $shop = GymShop::where('code', 'SHOP_CENTRAL')->first();
        $cashier = User::where('email', 'cashier1@gympro.test')->first();
        $variant = ProductVariant::where('sku', 'ON-WHEY-CHOC-5LB')->first();

        // فرۆشتنی ٢ دانە
        $this->postJson(route('api.pos.checkout', ['shop' => $shop->id]), [
            'cashier_user_id' => $cashier->id,
            'items' => [
                ['variant_id' => $variant->id, 'quantity' => 2],
            ],
            'payment_method' => 'CASH',
        ]);

        $expiredBatch->refresh();
        // باچی بەسەرچوو هەر وەک خۆی ٥ دانە ماوەتەوە و دەستی لێنەدراوە
        $this->assertEquals($initialQty, $expiredBatch->quantity_available);
    }

    /**
     * ڕێگری لە فرۆشتنی زیاتر لە کۆگا (Over-selling Prevention)
     */
    public function test_cannot_oversell_more_than_available_stock(): void
    {
        $shop = GymShop::where('code', 'SHOP_CENTRAL')->first();
        $cashier = User::where('email', 'cashier1@gympro.test')->first();
        $variant = ProductVariant::where('sku', 'ON-WHEY-CHOC-5LB')->first();

        // هەوڵی کڕینی ١٠٠ دانە لە کاتێکدا تەنها ٣٠ دانەی بەسەرنەچوو هەیە
        $response = $this->postJson(route('api.pos.checkout', ['shop' => $shop->id]), [
            'cashier_user_id' => $cashier->id,
            'items' => [
                ['variant_id' => $variant->id, 'quantity' => 100],
            ],
            'payment_method' => 'CASH',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * تاقیکردنەوەی دروستی هەژمارکردنی COGS، قازانج، و نوێکردنەوەی پارەی سندوقی شفت
     */
    public function test_cogs_and_shift_reconciliation(): void
    {
        $shop = GymShop::where('code', 'SHOP_CENTRAL')->first();
        $cashier = User::where('email', 'cashier1@gympro.test')->first();
        $variant = ProductVariant::where('sku', 'ON-WHEY-CHOC-5LB')->first();
        $shift = ShopCashShift::where('shop_id', $shop->id)->where('status', 'OPEN')->first();

        $initialExpectedCash = (float) $shift->expected_cash;

        // فرۆشتنی ١ دانە (نرخ: ٨٥,٠٠٠، تێچوو: ٦٠,٠٠٠، قازانج: ٢٥,٠٠٠)
        $saleResponse = $this->postJson(route('api.pos.checkout', ['shop' => $shop->id]), [
            'cashier_user_id' => $cashier->id,
            'items' => [
                ['variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 85000.00],
            ],
            'payment_method' => 'CASH',
        ]);

        $saleResponse->assertStatus(201)
            ->assertJson([
                'invoice' => [
                    'total_amount' => 85000.00,
                    'total_cogs' => 60000.00,
                    'gross_profit' => 25000.00,
                ],
            ]);

        $shift->refresh();
        $this->assertEquals($initialExpectedCash + 85000.00, (float) $shift->expected_cash);

        // کاشێر لە کۆتایی شفت پارە دەژمێرێت: پێشبینیکراو 135,000 (50,000 + 85,000)، بەڵام 130,000 ی پێیە
        $closeShiftResponse = $this->postJson(route('api.pos.shift.close'), [
            'shift_id' => $shift->id,
            'actual_cash' => 130000.00,
            'closing_notes' => 'کورتهێنانی ٥,٠٠٠ بەهۆی دڕانی دراوێک',
        ]);

        $closeShiftResponse->assertStatus(200)
            ->assertJson([
                'shift' => [
                    'actual_cash' => 130000.00,
                    'expected_cash' => 135000.00,
                    'cash_difference' => -5000.00,
                    'status' => 'CLOSED',
                ],
            ]);
    }

    /**
     * تاقیکردنەوەی دەرکردنی پسووڵەی چاپکردنی POS لە ڕێگەی وێب
     */
    public function test_web_pos_checkout_returns_thermal_receipt(): void
    {
        $shop = GymShop::where('code', 'SHOP_CENTRAL')->first();
        $variant = ProductVariant::where('sku', 'ON-WHEY-CHOC-5LB')->first();

        $response = $this->postJson(route('pos.checkout'), [
            'shop_id' => $shop->id,
            'items' => [
                [
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                    'unit_price' => 85000,
                ],
            ],
            'payment_method' => 'CASH',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'receipt' => [
                    'invoice_number',
                    'gym_name',
                    'date',
                    'time',
                    'customer_name',
                    'payment_method',
                    'subtotal',
                    'total_amount',
                    'items',
                ]
            ]);
    }

    /**
     * تاقیکردنەوەی داگرتنی ئەرشیف و باک ئەپی یاریزانە بەسەرچووەکان
     */
    public function test_expired_members_archive_and_export(): void
    {
        $response = $this->get(route('members.expired.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }
}

