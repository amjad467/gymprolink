<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CashAccount;
use App\Models\Gym;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PosSale;
use App\Models\ShopCashShift;
use App\Scopes\GymScope;
use App\Services\WhatsAppService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DebtController extends Controller
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * پیشاندانی بەڕێوەبردنی سەرجەم قەرزەکان و لیستی قەرزداران
     */
    public function index(Request $request): View
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $activeGym = Gym::find($activeGymId);

        $query = Member::where('gym_id', $activeGymId)
            ->where('current_debt', '>', 0);

        // گەڕان بەپێی ناو یان مۆبایل یان کۆد
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('member_code', 'like', "%{$s}%");
            });
        }

        // فلتەری بڕی قەرز
        if ($request->filled('range')) {
            if ($request->range === 'small') {
                $query->where('current_debt', '<=', 25000);
            } elseif ($request->range === 'medium') {
                $query->whereBetween('current_debt', [25001, 100000]);
            } elseif ($request->range === 'large') {
                $query->where('current_debt', '>', 100000);
            }
        }

        // KPI Metrics
        $totalDebt = Member::where('gym_id', $activeGymId)->where('current_debt', '>', 0)->sum('current_debt');
        $debtorCount = Member::where('gym_id', $activeGymId)->where('current_debt', '>', 0)->count();
        $recoveredThisMonth = Payment::where('gym_id', $activeGymId)
            ->where('payment_method', 'debt_settlement')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        $maxDebt = Member::where('gym_id', $activeGymId)->where('current_debt', '>', 0)->max('current_debt') ?? 0;

        $debtors = $query->orderBy('current_debt', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('debts.index', compact(
            'activeGym',
            'debtors',
            'totalDebt',
            'debtorCount',
            'recoveredThisMonth',
            'maxDebt'
        ));
    }

    /**
     * دانەوە و وەرگرتنی پارەی قەرز (تەواو یان بەشەکی)
     */
    public function pay(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:' . (float) $member->current_debt,
            'payment_method' => 'required|string|in:cash,card,bank_transfer',
            'notes' => 'nullable|string|max:500',
        ], [
            'amount.max' => 'بڕی دانەوە ناتوانێت لە کۆی قەرزی یاریزان زیاتر بێت (' . number_format($member->current_debt) . ' IQD).',
        ]);

        $amount = (float) $validated['amount'];
        $paymentMethod = $validated['payment_method'];
        $notes = $validated['notes'] ?? null;
        $activeGymId = $member->gym_id;

        try {
            $payment = DB::transaction(function () use ($member, $amount, $paymentMethod, $notes, $activeGymId, $request) {
                // دابەزاندنی قەرزی ئەندامەکە
                $member->decrement('current_debt', $amount);
                $member->refresh();

                // سندوقی کاش
                $cashAccount = CashAccount::where('gym_id', $activeGymId)->first();

                // فاکتۆری پاکتاوی قەرز بۆ ڕێکی حسابات
                $invNumber = 'DEBT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $invoice = Invoice::create([
                    'gym_id' => $activeGymId,
                    'invoice_number' => $invNumber,
                    'member_id' => $member->id,
                    'subtotal' => $amount,
                    'total_amount' => $amount,
                    'paid_amount' => $amount,
                    'remaining_balance' => 0.00,
                    'status' => 'paid',
                    'created_by_user_id' => auth()->id() ?? 1,
                    'notes' => 'وەسڵی دانەوەی قەرز (Debt Repayment)' . ($notes ? " - {$notes}" : ''),
                ]);

                // پسووڵەی پارەدان
                $refNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $paymentRecord = Payment::create([
                    'gym_id' => $activeGymId,
                    'invoice_id' => $invoice->id,
                    'member_id' => $member->id,
                    'cash_account_id' => $cashAccount?->id,
                    'amount' => $amount,
                    'payment_method' => 'debt_settlement',
                    'reference_number' => $refNumber,
                    'received_by_user_id' => auth()->id() ?? 1,
                    'notes' => $notes,
                ]);

                // ئەگەر شفتێکی کراوەی سندوق هەبێت و پارەکە نەقد بێت، سندوقەکە دەوڵەمەند دەکات
                if ($paymentMethod === 'cash') {
                    $openShift = ShopCashShift::where('status', 'OPEN')->latest()->first();
                    if ($openShift) {
                        $openShift->increment('expected_cash', $amount);
                    }
                }

                // تۆمار لە لۆگی سیستەم
                AuditLog::create([
                    'gym_id' => $activeGymId,
                    'user_id' => auth()->id() ?? 1,
                    'action' => 'debt.repaid',
                    'auditable_type' => Member::class,
                    'auditable_id' => $member->id,
                    'new_values' => [
                        'amount_paid' => $amount,
                        'remaining_debt' => $member->current_debt,
                        'payment_method' => $paymentMethod,
                        'receipt_number' => $refNumber,
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return $paymentRecord;
            });

            return redirect()->route('debts.index')->with([
                'success' => "بڕی " . number_format($amount) . " IQD وەکو دانەوەی قەرز لە یاریزان '{$member->full_name}' وەرگیرا. قەرزی ماوە: " . number_format($member->current_debt) . " IQD.",
                'last_payment_id' => $payment->id,
                'last_member_id' => $member->id,
            ]);
        } catch (Exception $e) {
            return redirect()->route('debts.index')->with('error', 'هەڵەیەک ڕوویدا لە تۆمارکردنی دانەوەی قەرز: ' . $e->getMessage());
        }
    }

    /**
     * کەشف حیسابی تەواوی ئەندام (Detailed Account Statement & Ledger)
     */
    public function statement(Member $member): View
    {
        $activeGymId = GymScope::getActiveGymId() ?? $member->gym_id;

        // کڕینەکانی شۆپ بە قەرز
        $creditSales = PosSale::where('member_id', $member->id)
            ->where('payment_method', 'MEMBER_CREDIT')
            ->latest()
            ->get();

        // فاکتۆرە کۆنەکان
        $dueInvoices = Invoice::where('member_id', $member->id)
            ->where('remaining_balance', '>', 0)
            ->latest()
            ->get();

        // هەموو پارەدان و دانەوەکانی قەرز
        $payments = Payment::where('member_id', $member->id)
            ->latest()
            ->get();

        return view('debts.statement', compact(
            'member',
            'creditSales',
            'dueInvoices',
            'payments'
        ));
    }

    /**
     * چاپی وەسڵی فەرمی 80mm حراری بۆ دانەوەی قەرز
     */
    public function printRepaymentReceipt(Payment $payment): View
    {
        $payment->load(['member', 'receiver', 'invoice']);
        $member = $payment->member;
        $gym = Gym::find($payment->gym_id);

        return view('debts.print_repayment_receipt', compact('payment', 'member', 'gym'));
    }

    /**
     * ناردنی نامەی وەبیرهێنانەوەی قەرز بە WhatsApp
     */
    public function whatsappReminder(Member $member): RedirectResponse
    {
        $msg = $this->whatsAppService->buildDebtReminderMessage($member);
        $url = $this->whatsAppService->generateWhatsAppUrl($member->phone, $msg);

        return redirect()->away($url);
    }

    /**
     * ناردنی وەسڵی دانەوەی قەرز بە WhatsApp
     */
    public function whatsappRepaymentReceipt(Payment $payment): RedirectResponse
    {
        $payment->load('member');
        $member = $payment->member;
        $msg = $this->whatsAppService->buildDebtRepaymentMessage($payment, $member, (float) $member->current_debt);
        $url = $this->whatsAppService->generateWhatsAppUrl($member->phone, $msg);

        return redirect()->away($url);
    }
}
