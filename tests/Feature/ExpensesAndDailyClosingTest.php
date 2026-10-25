<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Gym;
use App\Models\GymShop;
use App\Models\Member;
use App\Models\PosSale;
use App\Models\ProductVariant;
use App\Models\ShopCashShift;
use App\Models\User;
use App\Services\SupplementShopService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensesAndDailyClosingTest extends TestCase
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
        $variant = ProductVariant::first();
        $member = Member::first();

        $service = app(SupplementShopService::class);
        return $service->processPosSale(
            $shop->id,
            $shift->cashier_user_id,
            [['variant_id' => $variant->id, 'quantity' => 1]],
            'CASH',
            $member ? $member->id : null
        );
    }

    /**
     * تاقیکردنەوەی باربوونی لاپەڕەی خەرجییەکان
     */
    public function test_expenses_page_loads_with_kpis(): void
    {
        $response = $this->get(route('expenses.index'));

        $response->assertStatus(200)
            ->assertSee('بەڕێوەبردنی خەرجییە گشتییەکانی هۆڵ')
            ->assertSee('تۆمارکردنی خەرجی نوێ')
            ->assertSee('کرێی هۆڵ')
            ->assertSee('مۆلیدە و کارەبا');
    }

    /**
     * تاقیکردنەوەی زیادکردنی خەرجی نوێ و تۆماربوونی لە لۆگ
     */
    public function test_can_create_new_expense(): void
    {
        $gym = Gym::first();

        $postData = [
            'category' => 'electricity',
            'title' => 'پارەی مۆلیدەی مانگی ١٠',
            'amount' => 450000,
            'spent_at' => now()->format('Y-m-d'),
            'paid_to' => 'خاوەن مۆلیدە کاک ئارام',
            'notes' => 'ئەمپێری ١٥ هەزار'
        ];

        $response = $this->post(route('expenses.store'), $postData);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('expenses', [
            'gym_id' => $gym->id,
            'category' => 'electricity',
            'amount' => 450000,
            'paid_to' => 'خاوەن مۆلیدە کاک ئارام'
        ]);

        // Audit log check
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'expense.created',
            'gym_id' => $gym->id
        ]);
    }

    /**
     * تاقیکردنەوەی سڕینەوەی خەرجی
     */
    public function test_can_delete_expense(): void
    {
        $gym = Gym::first();
        $expense = Expense::create([
            'gym_id' => $gym->id,
            'category' => 'rent',
            'title' => 'کرێی هۆڵ',
            'amount' => 500000,
            'spent_at' => now()->toDateString(),
            'recorded_by_user_id' => 1,
        ]);

        $this->assertNotNull($expense);

        $response = $this->delete(route('expenses.destroy', $expense->id));

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('expenses', [
            'id' => $expense->id
        ]);
    }

    /**
     * تاقیکردنەوەی لاپەڕەی داخستنی سندوق (Daily Financial Closing)
     */
    public function test_daily_closing_page_loads_and_calculates_totals(): void
    {
        $response = $this->get(route('financial.closing'));

        $response->assertStatus(200)
            ->assertSee('داخستنی دارایی ڕۆژانەی سندوق')
            ->assertSee('سافی پارەی ناو سندوق')
            ->assertSee('بەراوردکاری و ژماردنی فیعلی سندوق')
            ->assertSee('چاپی فەرمی Z-Report');
    }

    /**
     * تاقیکردنەوەی لاپەڕەی چاپی پسووڵەی 80mm Z-Report
     */
    public function test_print_z_report_loads(): void
    {
        $response = $this->get(route('financial.closing.print', [
            'date' => now()->format('Y-m-d'),
            'actual_cash' => 250000
        ]));

        $response->assertStatus(200)
            ->assertSee('DAILY Z-REPORT')
            ->assertSee('واژووی کاشێر')
            ->assertSee('واژووی بەڕێوەبەر');
    }

    /**
     * تاقیکردنەوەی خزمەتگوزاری WhatsApp و دروستکردنی لینکی پسووڵەی فرۆشتن
     */
    public function test_whatsapp_service_and_pos_redirect(): void
    {
        $sale = $this->createSale();

        $response = $this->get(route('pos.invoices.whatsapp', $sale->id));

        // Redirects to api.whatsapp.com
        $response->assertStatus(302);
        $targetUrl = $response->headers->get('Location');

        $this->assertStringContainsString('api.whatsapp.com/send', $targetUrl);
        $this->assertStringContainsString('text=', $targetUrl);
    }

    /**
     * تاقیکردنەوەی وەسڵی بەشداری لە ڕێگەی WhatsApp
     */
    public function test_whatsapp_member_receipt_redirect(): void
    {
        $member = Member::has('memberships')->first();
        $this->assertNotNull($member);

        $response = $this->get(route('members.whatsapp.receipt', $member->id));

        $response->assertStatus(302);
        $targetUrl = $response->headers->get('Location');

        $this->assertStringContainsString('api.whatsapp.com/send', $targetUrl);
        $this->assertStringContainsString('text=', $targetUrl);
    }

    /**
     * تاقیکردنەوەی نامەی ئاگاداری نوێکردنەوە بۆ یاریزانانی بەسەرچوو بە WhatsApp
     */
    public function test_whatsapp_member_reminder_redirect(): void
    {
        $member = Member::has('memberships')->first();
        $this->assertNotNull($member);

        $response = $this->get(route('members.whatsapp.reminder', $member->id));

        $response->assertStatus(302);
        $targetUrl = $response->headers->get('Location');

        $this->assertStringContainsString('api.whatsapp.com/send', $targetUrl);
        $this->assertStringContainsString('text=', $targetUrl);
    }

    /**
     * تاقیکردنەوەی فۆرماتکردنی ژمارەی مۆبایلی عێراقی بۆ نێودەوڵەتی (0770xxxx -> 964770xxxx)
     */
    public function test_whatsapp_phone_sanitization(): void
    {
        $service = new WhatsAppService();

        $this->assertEquals('9647701234567', $service->sanitizePhone('07701234567'));
        $this->assertEquals('9647501234567', $service->sanitizePhone('0750 123 4567'));
        $this->assertEquals('9647801234567', $service->sanitizePhone('+9647801234567'));
        $this->assertEquals('9647709876543', $service->sanitizePhone('009647709876543'));
    }
}
