<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Gym;
use App\Models\GymShop;
use App\Models\Payment;
use App\Models\PosSale;
use App\Models\ShopCashShift;
use App\Scopes\GymScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialAuditController extends Controller
{
    /**
     * پیشاندانی شاشەی داخستنی دارایی ڕۆژانەی سندوق و ژماردن (Daily Financial Audit / Z-Report)
     */
    public function dailyClosing(Request $request): View
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $activeGym = Gym::find($activeGymId);

        $selectedDate = $request->input('date', Carbon::today()->toDateString());
        $dateObj = Carbon::parse($selectedDate);

        // ١. داهاتی بەشدارییەکانی یاریزانان (Memberships & Subscriptions)
        $memberPayments = Payment::where('gym_id', $activeGymId)
            ->whereDate('created_at', $selectedDate)
            ->with(['member', 'invoice.membership.plan'])
            ->get();

        $membershipCash = (float) $memberPayments->where('payment_method', 'cash')->sum('amount');
        $membershipCard = (float) $memberPayments->where('payment_method', 'card')->sum('amount');
        $membershipBank = (float) $memberPayments->where('payment_method', 'bank_transfer')->sum('amount');
        $membershipTotal = (float) $memberPayments->sum('amount');

        // ٢. داهاتی فرۆشتنی شۆپی تەواوکەر (POS Supplement Shop)
        $shop = GymShop::withoutGlobalScopes()->where('gym_id', $activeGymId)->where('is_active', true)->first();
        $shopId = $shop?->id ?? 1;

        $posSales = PosSale::where('shop_id', $shopId)
            ->whereDate('created_at', $selectedDate)
            ->with(['member', 'cashier', 'items.variant.product'])
            ->get();

        $posCash = (float) $posSales->where('payment_method', 'CASH')->sum('total_amount');
        $posCard = (float) $posSales->where('payment_method', 'CARD')->sum('total_amount');
        $posCredit = (float) $posSales->where('payment_method', 'MEMBER_CREDIT')->sum('total_amount');
        $posTotal = (float) $posSales->sum('total_amount');

        // ٣. خەرجییەکانی ئەم ڕۆژە (Expenses)
        $expenses = Expense::where('gym_id', $activeGymId)
            ->whereDate('spent_at', $selectedDate)
            ->get();

        $expensesTotal = (float) $expenses->sum('amount');

        // ٤. کۆی گشتی و پۆلێنکردنی دارایی سندوق
        $totalGrossIncome = $membershipTotal + $posTotal;
        $totalCashReceived = $membershipCash + $posCash;
        $totalCardReceived = $membershipCard + $posCard;
        $totalBankReceived = $membershipBank;

        // پارەی دەستی ناو سندوق بە حیساب (Net Cash in Drawer)
        $netCashExpected = $totalCashReceived - $expensesTotal;

        // قازانجی پوختەی ڕۆژەکە (Net Revenue)
        $netDayProfit = $totalGrossIncome - $expensesTotal;

        // شفتە کراوەکان یان داخراوەکانی ئەمڕۆ
        $shifts = ShopCashShift::where('shop_id', $shopId)
            ->whereDate('opened_at', $selectedDate)
            ->with('cashier')
            ->get();

        return view('financial.daily_closing', compact(
            'activeGym',
            'shop',
            'selectedDate',
            'dateObj',
            'memberPayments',
            'membershipCash',
            'membershipCard',
            'membershipBank',
            'membershipTotal',
            'posSales',
            'posCash',
            'posCard',
            'posCredit',
            'posTotal',
            'expenses',
            'expensesTotal',
            'totalGrossIncome',
            'totalCashReceived',
            'totalCardReceived',
            'totalBankReceived',
            'netCashExpected',
            'netDayProfit',
            'shifts'
        ));
    }

    /**
     * پیشاندانی لاپەڕەی چاپی تەواوی ڕاپۆرتی Z بۆ پرنتەری حراری 80mm
     */
    public function printZReport(Request $request): View
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $activeGym = Gym::find($activeGymId);

        $selectedDate = $request->input('date', Carbon::today()->toDateString());
        $actualCashCounted = $request->input('actual_cash');
        $discrepancy = $request->input('discrepancy', 0);
        $notes = $request->input('notes');

        $shop = GymShop::withoutGlobalScopes()->where('gym_id', $activeGymId)->where('is_active', true)->first();
        $shopId = $shop?->id ?? 1;

        // داتای دارایی
        $memberPayments = Payment::where('gym_id', $activeGymId)->whereDate('created_at', $selectedDate)->get();
        $membershipCash = (float) $memberPayments->where('payment_method', 'cash')->sum('amount');
        $membershipCard = (float) $memberPayments->where('payment_method', 'card')->sum('amount');
        $membershipTotal = (float) $memberPayments->sum('amount');

        $posSales = PosSale::where('shop_id', $shopId)->whereDate('created_at', $selectedDate)->get();
        $posCash = (float) $posSales->where('payment_method', 'CASH')->sum('total_amount');
        $posCard = (float) $posSales->where('payment_method', 'CARD')->sum('total_amount');
        $posCredit = (float) $posSales->where('payment_method', 'MEMBER_CREDIT')->sum('total_amount');
        $posTotal = (float) $posSales->sum('total_amount');

        $expenses = Expense::where('gym_id', $activeGymId)->whereDate('spent_at', $selectedDate)->get();
        $expensesTotal = (float) $expenses->sum('amount');

        $totalCash = $membershipCash + $posCash;
        $totalCard = $membershipCard + $posCard;
        $totalRevenue = $membershipTotal + $posTotal;
        $netExpectedCash = $totalCash - $expensesTotal;
        $netProfit = $totalRevenue - $expensesTotal;

        return view('financial.print_z_report', compact(
            'activeGym',
            'shop',
            'selectedDate',
            'membershipTotal',
            'membershipCash',
            'membershipCard',
            'posTotal',
            'posCash',
            'posCard',
            'posCredit',
            'expenses',
            'expensesTotal',
            'totalCash',
            'totalCard',
            'totalRevenue',
            'netExpectedCash',
            'netProfit',
            'actualCashCounted',
            'discrepancy',
            'notes'
        ));
    }
}
