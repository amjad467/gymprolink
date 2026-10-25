<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\GymShop;
use App\Models\Member;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopCashShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosInvoicesAndReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createSale(): PosSale
    {
        $shop = GymShop::first();
        $shift = ShopCashShift::where('shop_id', $shop->id)->where('status', 'OPEN')->first();
        $variant = ProductVariant::where('sku', 'ON-WHEY-CHOC-5LB')->first();
        $member = Member::first();

        $service = app(\App\Services\SupplementShopService::class);
        return $service->processPosSale(
            $shop->id,
            $shift->cashier_user_id,
            [['variant_id' => $variant->id, 'quantity' => 2]],
            'CASH',
            $member ? $member->id : null
        );
    }

    public function test_sales_invoices_page_loads_with_metrics_and_table(): void
    {
        $response = $this->get(route('pos.invoices.index'));

        $response->assertStatus(200);
        $response->assertSee('لیستی فرۆشتنەکان و پسووڵەکان');
        $response->assertSee('کۆی فاکتۆرەکان');
        $response->assertSee('کۆی داهاتی فرۆش');
        $response->assertSee('چاپی پسووڵە');
    }

    public function test_sales_invoices_can_be_filtered_by_search_and_payment_method(): void
    {
        $sale = $this->createSale();

        // Search by invoice number
        $response = $this->get(route('pos.invoices.index', ['search' => $sale->invoice_number]));
        $response->assertStatus(200);
        $response->assertSee($sale->invoice_number);

        // Filter by payment method
        $response = $this->get(route('pos.invoices.index', ['payment_method' => $sale->payment_method]));
        $response->assertStatus(200);
        $response->assertSee($sale->invoice_number);
    }

    public function test_print_receipt_page_renders_80mm_thermal_layout(): void
    {
        $sale = $this->createSale();

        $response = $this->get(route('pos.invoices.print', $sale->id));

        $response->assertStatus(200);
        $response->assertSee($sale->invoice_number);
        $response->assertSee('چاپکردنی پسووڵە');
        $response->assertSee('کۆی گشتی');
        $response->assertSee('window.print()', false);
    }

    public function test_invoice_json_endpoint_returns_receipt_data_for_modal(): void
    {
        $sale = $this->createSale();

        $response = $this->get(route('pos.invoices.json', $sale->id));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'receipt' => [
                    'invoice_number' => $sale->invoice_number,
                ]
            ]);
    }
}
