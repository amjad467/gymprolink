<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Gym;
use App\Models\GymShop;
use App\Models\InventoryBatch;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\ProductVariant;
use App\Scopes\GymScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $activeGym = Gym::find($activeGymId);
        $shop = GymShop::withoutGlobalScopes()->where('gym_id', $activeGymId)->where('is_active', true)->first();

        if (!$shop && $activeGym) {
            $shop = GymShop::withoutGlobalScopes()->where('gym_id', $activeGym->id)->first();
        }

        $period = $request->get('period', 'month'); // 'today', 'week', 'month', 'all'
        
        $query = PosSale::with(['items.variant.product', 'member', 'cashier', 'shift'])
            ->where('shop_id', $shop?->id ?? 0);

        // فلتەر بەپێی کات
        if ($period === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($period === 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $query->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
        }

        // گەڕان بەپێی دەق (ناوی کڕیار، ژمارەی پسووڵە)
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%{$s}%")
                    ->orWhereHas('member', function ($mq) use ($s) {
                        $mq->where('full_name', 'like', "%{$s}%")
                            ->orWhere('member_code', 'like', "%{$s}%");
                    });
            });
        }

        // هەموو فرۆشتنەکان بۆ ئەم ماوەیە
        $allSales = (clone $query)->latest()->get();

        // ژماردنی ئامارە داراییە سەرەکییەکان
        $totalSalesCount = $allSales->count();
        $totalRevenue = (float) $allSales->sum('total_amount');
        $totalCogs = (float) $allSales->sum('total_cogs');
        $totalGrossProfit = $totalRevenue - $totalCogs;
        $profitMargin = $totalRevenue > 0 ? round(($totalGrossProfit / $totalRevenue) * 100, 1) : 0;

        // داهاتی بەشدارییەکانی یاریزانان و خەرجییەکان بۆ ئەم ماوەیە
        $membershipQuery = Payment::where('gym_id', $activeGymId);
        $expensesQuery = Expense::where('gym_id', $activeGymId);

        if ($period === 'today') {
            $membershipQuery->whereDate('created_at', now()->toDateString());
            $expensesQuery->whereDate('spent_at', now()->toDateString());
        } elseif ($period === 'week') {
            $membershipQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            $expensesQuery->whereBetween('spent_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $membershipQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
            $expensesQuery->whereMonth('spent_at', now()->month)->whereYear('spent_at', now()->year);
        }

        $membershipRevenue = (float) $membershipQuery->sum('amount');
        $totalExpenses = (float) $expensesQuery->sum('amount');
        $totalDebts = (float) Member::where('gym_id', $activeGymId)->sum('current_debt');

        // قازانجی پوختەی ڕاستەقینە: (قازانجی نەخالیسی مارکێت + داهاتی بەشدارییەکان) - خەرجییەکان
        $trueNetProfit = ($totalGrossProfit + $membershipRevenue) - $totalExpenses;
        
        $totalItemsSold = 0;
        foreach ($allSales as $s) {
            $totalItemsSold += $s->items->sum('quantity');
        }

        // پڕفرۆشترین و کەمفرۆشترین کاڵاکان
        $saleItemsQuery = PosSaleItem::with(['variant.product'])
            ->whereHas('sale', function ($sq) use ($shop, $period) {
                $sq->where('shop_id', $shop?->id ?? 0);
                if ($period === 'today') {
                    $sq->whereDate('created_at', now()->toDateString());
                } elseif ($period === 'week') {
                    $sq->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                } elseif ($period === 'month') {
                    $sq->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                }
            })->get();

        $productSalesStats = [];
        foreach ($saleItemsQuery as $item) {
            $vid = $item->variant_id;
            if (!isset($productSalesStats[$vid])) {
                $pName = ($item->variant->product->name_ku ?? $item->variant->product->name_en ?? 'کاڵا');
                if ($item->variant->flavor) {
                    $pName .= ' (' . $item->variant->flavor . ')';
                }
                $productSalesStats[$vid] = [
                    'name' => $pName,
                    'sku' => $item->variant->sku,
                    'quantity' => 0,
                    'revenue' => 0,
                    'profit' => 0,
                ];
            }
            $productSalesStats[$vid]['quantity'] += $item->quantity;
            $productSalesStats[$vid]['revenue'] += (float) $item->line_total;
            $productSalesStats[$vid]['profit'] += (float) $item->gross_profit;
        }

        // ڕیزبەندی پڕفرۆش (Top 5)
        $topSelling = collect($productSalesStats)->sortByDesc('quantity')->take(5);

        // ڕیزبەندی کەمفرۆش (Bottom 5 کە فرۆشراون)
        $leastSelling = collect($productSalesStats)->sortBy('quantity')->take(5);

        // شیکاری ئاستی کۆگا (کەمترین بڕ و زۆرترین بڕ لە کۆگادا)
        $variantsWithStock = ProductVariant::with(['product'])
            ->where('is_active', true)
            ->get()
            ->map(function ($v) use ($shop) {
                $v->total_stock = $v->getAvailableStockInShop($shop?->id ?? 0);
                return $v;
            });

        $lowStockVariants = $variantsWithStock->filter(fn($v) => $v->total_stock <= 5)->sortBy('total_stock')->values();
        $highStockVariants = $variantsWithStock->sortByDesc('total_stock')->take(5)->values();

        // بەرواری بەسەرچوونی نزیک و بەسەرچوو (FEFO Batch Expiry Tracker)
        $today = now()->toDateString();
        $expiredBatches = InventoryBatch::with(['variant.product'])
            ->where('shop_id', $shop?->id ?? 0)
            ->where('quantity_available', '>', 0)
            ->whereDate('expiry_date', '<=', $today)
            ->orderBy('expiry_date')
            ->get();

        $nearExpiryBatches = InventoryBatch::with(['variant.product'])
            ->where('shop_id', $shop?->id ?? 0)
            ->where('quantity_available', '>', 0)
            ->whereDate('expiry_date', '>', $today)
            ->whereDate('expiry_date', '<=', now()->addDays(90)->toDateString())
            ->orderBy('expiry_date')
            ->get();

        // پەڕەبەندی فرۆشتنەکان بۆ خشتەی سەرەکی
        $salesPaginated = $query->latest()->paginate(15)->withQueryString();

        return view('analytics.sales', compact(
            'activeGym',
            'shop',
            'period',
            'totalSalesCount',
            'totalRevenue',
            'totalCogs',
            'totalGrossProfit',
            'profitMargin',
            'totalItemsSold',
            'topSelling',
            'leastSelling',
            'lowStockVariants',
            'highStockVariants',
            'expiredBatches',
            'nearExpiryBatches',
            'salesPaginated',
            'membershipRevenue',
            'totalExpenses',
            'totalDebts',
            'trueNetProfit'
        ));
    }

    /**
     * هەناردەکردنی ڕاپۆرتی فرۆشتنەکان بۆ فایلی ئێکسڵ / CSV
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $shop = GymShop::withoutGlobalScopes()->where('gym_id', $activeGymId)->where('is_active', true)->first();

        $sales = PosSale::with(['items.variant.product', 'member', 'cashier'])
            ->where('shop_id', $shop?->id ?? 0)
            ->latest()
            ->get();

        $fileName = 'GymPro_Sales_Report_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($sales) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($file, [
                'ژمارەی پسووڵە',
                'بەروار و کات',
                'کڕیار',
                'کاشێر',
                'کاڵاکان',
                'بڕی گشتی کاڵا',
                'تێچووی کڕین (COGS)',
                'نرخی فرۆش (Revenue)',
                'قازانجی نەخالیس (Profit)',
                'شێوازی پارەدان'
            ]);

            foreach ($sales as $s) {
                $itemsStr = $s->items->map(function ($it) {
                    $name = $it->variant->product->name_ku ?? $it->variant->product->name_en ?? 'کاڵا';
                    return "{$name} (x{$it->quantity})";
                })->implode(' | ');

                fputcsv($file, [
                    $s->invoice_number,
                    $s->created_at->format('Y-m-d H:i'),
                    $s->member ? "{$s->member->full_name} ({$s->member->member_code})" : 'کڕیاری ئاسایی (میوان)',
                    $s->cashier->name ?? 'کاشێر',
                    $itemsStr,
                    $s->items->sum('quantity'),
                    number_format($s->total_cogs, 0) . ' IQD',
                    number_format($s->total_amount, 0) . ' IQD',
                    number_format($s->gross_profit, 0) . ' IQD',
                    $s->payment_method
                ]);
            }

            fclose($file);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
