<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Gym;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtManagementAndGateAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * تاقیکردنەوەی باربوونی لاپەڕەی بەڕێوەبردنی قەرزەکان بە سەرکەوتوویی
     */
    public function test_debts_index_loads_with_kpis(): void
    {
        $member = Member::first();
        $member->update(['current_debt' => 75000, 'allow_credit' => true, 'credit_limit' => 150000]);

        $response = $this->get(route('debts.index'));

        $response->assertStatus(200)
            ->assertSee('بەڕێوەبردنی قەرزەکان و کەشف حیساب')
            ->assertSee('کۆی گشتی قەرزەکان')
            ->assertSee('یاریزانانی قەرزدار')
            ->assertSee('دانەوەی قەرز');
    }

    /**
     * تاقیکردنەوەی دانەوەی بەشەکی قەرز (Partial Repayment)
     */
    public function test_debt_can_be_repaid_partially(): void
    {
        $member = Member::first();
        $member->update(['current_debt' => 80000]);

        $postData = [
            'amount' => 30000,
            'payment_method' => 'cash',
            'notes' => 'قیستی یەکەمی مانگ'
        ];

        $response = $this->post(route('debts.pay', $member->id), $postData);

        $response->assertRedirect(route('debts.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('last_payment_id');

        $member->refresh();
        $this->assertEquals(50000, (float) $member->current_debt);

        // Verification of Payment record
        $this->assertDatabaseHas('payments', [
            'member_id' => $member->id,
            'amount' => 30000,
            'payment_method' => 'debt_settlement',
            'notes' => 'قیستی یەکەمی مانگ'
        ]);

        // Verification of Settlement Invoice
        $this->assertDatabaseHas('invoices', [
            'member_id' => $member->id,
            'total_amount' => 30000,
            'paid_amount' => 30000,
            'status' => 'paid'
        ]);

        // Verification of Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'debt.repaid',
            'auditable_id' => $member->id
        ]);
    }

    /**
     * تاقیکردنەوەی دانەوەی تەواوەتی قەرز (Full Repayment)
     */
    public function test_debt_can_be_repaid_fully(): void
    {
        $member = Member::first();
        $member->update(['current_debt' => 45000]);

        $response = $this->post(route('debts.pay', $member->id), [
            'amount' => 45000,
            'payment_method' => 'card',
            'notes' => 'پاکتاوی تەواوی قەرز'
        ]);

        $response->assertRedirect(route('debts.index'));
        $response->assertSessionHas('success');

        $member->refresh();
        $this->assertEquals(0, (float) $member->current_debt);
    }

    /**
     * تاقیکردنەوەی ڕێگریکردن لە پێدانی بڕێک کە لە قەرزەکە زیاتر بێت
     */
    public function test_debt_repayment_validates_amount_does_not_exceed_debt(): void
    {
        $member = Member::first();
        $member->update(['current_debt' => 20000]);

        $response = $this->post(route('debts.pay', $member->id), [
            'amount' => 25000, // لە ٢٠ هەزار زیاترە
            'payment_method' => 'cash'
        ]);

        $response->assertSessionHasErrors(['amount']);
        $member->refresh();
        $this->assertEquals(20000, (float) $member->current_debt);
    }

    /**
     * تاقیکردنەوەی لاپەڕەی کەشف حیسابی یاریزان (Member Statement)
     */
    public function test_statement_page_loads_with_ledger(): void
    {
        $member = Member::first();
        $member->update(['current_debt' => 35000]);

        $response = $this->get(route('debts.statement', $member->id));

        $response->assertStatus(200)
            ->assertSee('کەشف حیسابی دارایی')
            ->assertSee($member->full_name)
            ->assertSee($member->member_code)
            ->assertSee('35,000');
    }

    /**
     * تاقیکردنەوەی بینینی پسووڵەی 80mm دانەوەی قەرز بۆ چاپ
     */
    public function test_print_repayment_receipt_loads(): void
    {
        $member = Member::first();
        $member->update(['current_debt' => 40000]);

        // ئەنجامدانی دانەوە
        $this->post(route('debts.pay', $member->id), [
            'amount' => 15000,
            'payment_method' => 'cash'
        ]);

        $payment = Payment::where('member_id', $member->id)->where('payment_method', 'debt_settlement')->latest('id')->first();
        $this->assertNotNull($payment);

        $response = $this->get(route('debts.payments.print', $payment->id));

        $response->assertStatus(200)
            ->assertSee('DEBT SETTLEMENT RECEIPT')
            ->assertSee('وەسڵی وەرگرتن و پاکتاوی قەرز')
            ->assertSee('15,000')
            ->assertSee('واژووی کاشێری سندوق');
    }

    /**
     * تاقیکردنەوەی ناردنی نامەی وەبیرهێنانەوەی قەرز بە WhatsApp
     */
    public function test_whatsapp_debt_reminder_redirects(): void
    {
        $member = Member::first();
        $member->update(['current_debt' => 60000, 'phone' => '07701234567']);

        $response = $this->get(route('debts.whatsapp.reminder', $member->id));

        $response->assertStatus(302);
        $url = $response->headers->get('Location');

        $this->assertStringContainsString('api.whatsapp.com/send', $url);
        $this->assertStringContainsString('9647701234567', $url);
        $this->assertStringContainsString('text=', $url);
    }

    /**
     * تاقیکردنەوەی ناردنی وەسڵی دانەوەی قەرز بە WhatsApp
     */
    public function test_whatsapp_repayment_receipt_redirects(): void
    {
        $member = Member::first();
        $member->update(['current_debt' => 50000, 'phone' => '07509876543']);

        $this->post(route('debts.pay', $member->id), [
            'amount' => 20000,
            'payment_method' => 'cash'
        ]);

        $payment = Payment::where('member_id', $member->id)->where('payment_method', 'debt_settlement')->latest('id')->first();

        $response = $this->get(route('debts.payments.whatsapp', $payment->id));

        $response->assertStatus(302);
        $url = $response->headers->get('Location');

        $this->assertStringContainsString('api.whatsapp.com/send', $url);
        $this->assertStringContainsString('9647509876543', $url);
    }

    /**
     * تاقیکردنەوەی ئاگاداری قەرز لەسەر دەرگا و هاتنی ڕۆژانە (Gate Access Debt Alert)
     */
    public function test_gate_access_detects_indebted_member(): void
    {
        $member = Member::with('activeMembership')->first();
        $this->assertNotNull($member);

        // دانانی قەرز بۆ یاریزانەکە
        $member->update(['current_debt' => 25000]);

        $response = $this->postJson(route('attendance.punch'), [
            'scanned_code' => $member->member_code,
            'mode' => 'entry'
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertTrue($data['member']['has_debt']);
        $this->assertEquals(25000, $data['member']['current_debt']);
    }
}
