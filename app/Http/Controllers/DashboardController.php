<?php

namespace App\Http\Controllers;

use App\Models\AccessEvent;
use App\Models\Gym;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\PosSale;
use App\Scopes\GymScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $gyms = Gym::where('is_active', true)->get();
        $activeGymId = GymScope::getActiveGymId() ?? $gyms->first()?->id;

        if ($request->has('switch_gym')) {
            $activeGymId = (int) $request->get('switch_gym');
            Session::put('active_gym_id', $activeGymId);
            return redirect()->route('dashboard');
        }

        $activeGym = Gym::find($activeGymId);

        // ئامارە سەرەکییەکان بە کاش (Cached KPIs for 60 seconds)
        $cachedStats = \Illuminate\Support\Facades\Cache::remember("gym_dashboard_stats_{$activeGymId}", 60, function () use ($activeGymId, $activeGym) {
            $totalMembers = Member::where('gym_id', $activeGymId)->count();
            $activeMembers = Member::where('gym_id', $activeGymId)->where('status', 'active')->count();
            $expiredMembers = Member::where('gym_id', $activeGymId)->where('status', 'expired')->count();
            $frozenMembers = Member::where('gym_id', $activeGymId)->where('status', 'frozen')->count();

            // ئاماری دەرگای زیرەکی ئەمڕۆ
            $todayScansCount = AccessEvent::where('gym_id', $activeGymId)
                ->whereDate('scanned_at', now()->toDateString())
                ->count();

            $todayGrantedCount = AccessEvent::where('gym_id', $activeGymId)
                ->where('status', 'GRANTED')
                ->whereDate('scanned_at', now()->toDateString())
                ->count();

            $todayDeniedCount = AccessEvent::where('gym_id', $activeGymId)
                ->where('status', 'DENIED')
                ->whereDate('scanned_at', now()->toDateString())
                ->count();

            // دارایی هۆڵ (جیا لە شۆپ)
            $membershipRevenueMonth = Invoice::where('gym_id', $activeGymId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('paid_amount');

            // دارایی شۆپ و POS
            $shop = $activeGym?->shops()->first();
            $shopRevenueMonth = 0;
            $shopGrossProfitMonth = 0;

            if ($shop) {
                $shopRevenueMonth = PosSale::where('shop_id', $shop->id)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('total_amount');

                $shopCogsMonth = PosSale::where('shop_id', $shop->id)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('total_cogs');

                $shopGrossProfitMonth = $shopRevenueMonth - $shopCogsMonth;
            }

            return compact(
                'totalMembers',
                'activeMembers',
                'expiredMembers',
                'frozenMembers',
                'todayScansCount',
                'todayGrantedCount',
                'todayDeniedCount',
                'membershipRevenueMonth',
                'shopRevenueMonth',
                'shopGrossProfitMonth'
            );
        });

        extract($cachedStats);

        $shop = $activeGym?->shops()->first();

        // کۆتا ڕووداوەکانی دەرگا
        $recentAccessEvents = AccessEvent::with(['member', 'device'])
            ->where('gym_id', $activeGymId)
            ->latest('scanned_at')
            ->take(8)
            ->get();

        // کاڵاکانی نزیک بە بەسەرچوون یان کەمبوونەوە
        $expiringBatches = [];
        if ($shop) {
            $expiringBatches = \App\Models\InventoryBatch::with('variant.product')
                ->where('shop_id', $shop->id)
                ->where('quantity_available', '>', 0)
                ->whereDate('expiry_date', '<=', now()->addDays(90))
                ->orderBy('expiry_date', 'asc')
                ->take(5)
                ->get();
        }

        return view('dashboard.index', compact(
            'gyms',
            'activeGym',
            'totalMembers',
            'activeMembers',
            'expiredMembers',
            'frozenMembers',
            'todayScansCount',
            'todayGrantedCount',
            'todayDeniedCount',
            'membershipRevenueMonth',
            'shopRevenueMonth',
            'shopGrossProfitMonth',
            'recentAccessEvents',
            'expiringBatches'
        ));
    }
}
