<?php

namespace App\Http\Controllers;

use App\Models\Gym;
use App\Models\GymShop;
use App\Models\Member;
use App\Models\PosSale;
use App\Models\ProductVariant;
use App\Models\ShopCashShift;
use App\Scopes\GymScope;
use App\Services\SupplementShopService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosViewController extends Controller
{
    public function index()
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $activeGym = Gym::find($activeGymId);
        $shop = GymShop::withoutGlobalScopes()->where('gym_id', $activeGymId)->where('is_active', true)->first();

        if (!$shop && $activeGym) {
            $shop = GymShop::withoutGlobalScopes()->firstOrCreate(
                ['gym_id' => $activeGym->id],
                [
                    'name' => 'شۆپی تەواوکەری ' . $activeGym->name,
                    'code' => 'SHOP_' . ($activeGym->code ?? $activeGym->id),
                    'is_active' => true,
                    'location_details' => 'ناو هۆڵی ' . $activeGym->name,
                ]
            );
        }

        if (!$shop) {
            return view('pos.no_shop', compact('activeGym'));
        }

        // شفتی هەنووکەیی تایبەت بە کاشێری چوونەژوورەوە
        $user = auth()->user() ?? \App\Models\User::first();
        $userId = $user ? $user->id : 1;

        $activeShift = ShopCashShift::where('shop_id', $shop->id)
            ->where('cashier_user_id', $userId)
            ->where('status', 'OPEN')
            ->latest()
            ->first();

        if (!$activeShift) {
            $activeShift = ShopCashShift::create([
                'shop_id' => $shop->id,
                'cashier_user_id' => $userId,
                'opened_at' => now(),
                'opening_cash' => 0,
                'expected_cash' => 0,
                'status' => 'OPEN',
                'opening_notes' => 'شفت بە شێوەی خۆکارانە کرایەوە بۆ کاشێر',
            ]);
        }

        // کاڵاکانی بەردەست بە وەجبەی FEFO
        $today = now()->toDateString();
        $variants = ProductVariant::with(['product.brand', 'product.category', 'batches'])
            ->where('is_active', true)
            ->get()
            ->map(function ($v) use ($shop, $today) {
                $stock = $v->batches
                    ->where('shop_id', $shop->id)
                    ->where('is_blocked', false)
                    ->where('quantity_available', '>', 0)
                    ->filter(fn($b) => $b->expiry_date->isFuture())
                    ->sum('quantity_available');

                $v->available_stock = $stock;
                return $v;
            });

        // ئەندامانی چالاک یان ئەوانەی ماوەی بەسەرچوونیان لە 15 ڕۆژ کەمترە بۆ خێرایی و پاکوخاوێنی POS
        $cutoffDate = now()->subDays(15)->toDateString();
        $members = Member::where('gym_id', $activeGymId)
            ->where(function ($q) use ($cutoffDate) {
                $q->where('status', 'active')
                    ->orWhereHas('memberships', function ($mq) use ($cutoffDate) {
                        $mq->whereDate('end_date', '>=', $cutoffDate);
                    });
            })
            ->orderBy('full_name')
            ->get(['id', 'member_code', 'full_name', 'phone', 'allow_credit', 'credit_limit', 'current_debt']);

        return view('pos.index', compact('activeGym', 'shop', 'activeShift', 'variants', 'members'));
    }

    /**
     * جێبەجێکردنی فرۆشتن لە شاشەی POS و دەرکردنی داتای پسووڵە بۆ چاپکردن
     */
    public function checkout(Request $request, SupplementShopService $shopService): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => 'required|integer|exists:gym_shops,id',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:CASH,CARD,SPLIT,MEMBER_CREDIT',
            'member_id' => 'nullable|integer|exists:members,id',
        ]);

        $shop = GymShop::withoutGlobalScopes()->findOrFail($validated['shop_id']);

        // دڵنیابوونەوە لە هەبوونی شفتی کراوە
        $activeShift = ShopCashShift::where('shop_id', $shop->id)
            ->where('status', 'OPEN')
            ->latest()
            ->first();

        if (!$activeShift) {
            $user = auth()->user() ?? \App\Models\User::first();
            $activeShift = ShopCashShift::create([
                'shop_id' => $shop->id,
                'cashier_user_id' => $user ? $user->id : 1,
                'opened_at' => now(),
                'opening_cash' => 0,
                'expected_cash' => 0,
                'status' => 'OPEN',
                'opening_notes' => 'شفت بە شێوەی ئۆتۆماتیک کرایەوە',
            ]);
        }

        $cashierUserId = $activeShift->cashier_user_id;

        try {
            $sale = $shopService->processPosSale(
                $shop->id,
                $cashierUserId,
                $validated['items'],
                $validated['payment_method'],
                $validated['member_id'] ?? null
            );

            $sale->load(['items.variant.product', 'member', 'cashier', 'shop.gym']);

            $paymentLabels = [
                'CASH' => 'نەقد (کاش)',
                'CARD' => 'کارت / POS',
                'MEMBER_CREDIT' => 'بە قەرز (سەر ئەندامێتی)',
                'SPLIT' => 'نیوە بە نیوە',
            ];

            return response()->json([
                'success' => true,
                'message' => 'فرۆشتن بە سەرکەوتوویی ئەنجامدرا و پسووڵە دەرکرا.',
                'receipt' => [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'gym_name' => $sale->shop->gym->name ?? 'GymPro Fitness Club',
                    'gym_phone' => $sale->shop->gym->phone ?? '0770 000 0000',
                    'shop_name' => $sale->shop->name,
                    'location' => $sale->shop->location_details ?? 'سلێمانی',
                    'date' => $sale->created_at->format('Y-m-d'),
                    'time' => $sale->created_at->format('h:i A'),
                    'cashier_name' => $sale->cashier->name ?? 'کاشێری هۆڵ',
                    'customer_name' => $sale->member ? $sale->member->full_name : 'کڕیاری ئاسایی (میوان)',
                    'customer_code' => $sale->member ? $sale->member->member_code : 'GUEST',
                    'customer_phone' => $sale->member ? $sale->member->phone : '',
                    'payment_method' => $paymentLabels[$sale->payment_method] ?? $sale->payment_method,
                    'subtotal' => (float) $sale->subtotal,
                    'discount' => (float) $sale->discount_amount,
                    'total_amount' => (float) $sale->total_amount,
                    'items' => $sale->items->map(function ($item) {
                        $prodName = $item->variant->product->name_ku ?? $item->variant->product->name_en ?? 'کاڵا';
                        if ($item->variant->flavor) {
                            $prodName .= ' (' . $item->variant->flavor . ')';
                        }
                        return [
                            'name' => $prodName,
                            'qty' => (int) $item->quantity,
                            'unit_price' => (float) $item->unit_sale_price,
                            'total' => (float) $item->line_total,
                        ];
                    }),
                ]
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * کردنەوەی شفتی نوێ بۆ کاشێر لە ڕێگەی وێب
     */
    public function openShift(Request $request, SupplementShopService $shopService): JsonResponse
    {
        $validated = $request->validate([
            'opening_cash' => 'nullable|numeric|min:0',
            'opening_notes' => 'nullable|string|max:500',
        ]);

        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $shop = GymShop::withoutGlobalScopes()->where('gym_id', $activeGymId)->where('is_active', true)->first();

        if (!$shop) {
            return response()->json(['success' => false, 'message' => 'هیچ شۆپێکی چالاک بەردەست نییە.'], 404);
        }

        $userId = auth()->id() ?? 1;

        try {
            $shift = $shopService->openCashShift(
                $shop->id,
                $userId,
                (float) ($validated['opening_cash'] ?? 0),
                $validated['opening_notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'شفتی نوێ بە سەرکەوتوویی کرایەوە.',
                'shift' => $shift,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * داخستنی شفت لە ڕێگەی فۆرمی وێب بە بەراوردی پارەی ژمێردراو بە پێشبینیکراو
     */
    public function closeShift(Request $request, SupplementShopService $shopService): JsonResponse
    {
        $validated = $request->validate([
            'shift_id' => 'required|integer|exists:shop_cash_shifts,id',
            'actual_cash' => 'required|numeric|min:0',
            'closing_notes' => 'nullable|string',
        ]);

        try {
            $shift = $shopService->closeCashShift(
                $validated['shift_id'],
                (float) $validated['actual_cash'],
                $validated['closing_notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'شفت بە سەرکەوتوویی داخرا.',
                'shift' => [
                    'id' => $shift->id,
                    'opening_cash' => (float) $shift->opening_cash,
                    'expected_cash' => (float) $shift->expected_cash,
                    'actual_cash' => (float) $shift->actual_cash,
                    'cash_difference' => (float) $shift->cash_difference,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * گەڕاندنەوەی فاکتۆری فرۆشتنی POS و ڕاستکردنەوەی کۆگا و قەرز بە مۆڵەت
     */
    public function refund(Request $request, PosSale $sale, SupplementShopService $shopService)
    {
        $user = auth()->user();

        // مۆڵەت: سوپەر ئەدمین یان خاوەن هۆڵ یان هەبوونی مۆڵەتی shop.refund.approve
        $hasPermission = $user?->is_super_admin 
            || ($user && method_exists($user, 'hasRole') && $user->hasRole('gym_owner'))
            || ($user && method_exists($user, 'hasPermission') && $user->hasPermission('shop.refund.approve'));

        if (!$hasPermission) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'مۆڵەتی پێویستت نییە بۆ پەسەندکردنی گەڕاندنەوەی کاڵا.'], 403);
            }
            return back()->with('error', 'مۆڵەتی پێویستت نییە بۆ پەسەندکردنی گەڕاندنەوەی کاڵا.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $refund = $shopService->processRefund(
                $sale->id,
                $user ? $user->id : 1,
                $validated['reason'],
                []
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "فاکتۆری #{$sale->invoice_number} بە سەرکەوتوویی گەڕێندرایەوە و بڕی کاڵاکان بۆ کۆگا زیادکرانەوە.",
                    'refund' => $refund,
                ]);
            }

            return back()->with('success', "فاکتۆری #{$sale->invoice_number} بە سەرکەوتوویی گەڕێندرایەوە و بڕی کاڵاکان بۆ کۆگا زیادکرانەوە.");
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', 'هەڵە لە گەڕاندنەوەی کاڵا: ' . $e->getMessage());
        }
    }

    /**
     * لیستی هەموو فرۆشتنەکان و فاکتۆرەکانی POS بە گەڕان و فلتەر و چاپی پسووڵە
     */
    public function invoices(Request $request): View
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $activeGym = Gym::find($activeGymId);
        $shop = GymShop::withoutGlobalScopes()->where('gym_id', $activeGymId)->where('is_active', true)->first();

        $query = PosSale::with(['shop.gym', 'shift', 'cashier', 'member', 'items.variant.product', 'refund.authorizer']);

        if ($shop) {
            $query->where('shop_id', $shop->id);
        }

        // گەڕان بە ژمارەی فاکتۆر، ناوی ئەندام، کۆد، مۆبایل، یان ناوی کاشێر
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($mq) use ($search) {
                        $mq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('member_code', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('cashier', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // فلتەری شێوازی پارەدان
        if ($paymentMethod = $request->input('payment_method')) {
            if ($paymentMethod !== 'ALL') {
                $query->where('payment_method', $paymentMethod);
            }
        }

        // فلتەری بەروار
        $dateFilter = $request->input('date_filter', 'all');
        if ($dateFilter === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($dateFilter === 'this_week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($dateFilter === 'this_month') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        // کۆکردنەوەی داتای ئاماری بۆ کارتی خێرا
        $statsQuery = clone $query;
        $totalSalesCount = (clone $statsQuery)->count();
        $totalRevenue = (clone $statsQuery)->sum('total_amount');
        $todayRevenue = (clone $statsQuery)->whereDate('created_at', now()->toDateString())->sum('total_amount');
        $totalDiscount = (clone $statsQuery)->sum('discount_amount');

        $invoices = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        return view('pos.invoices', compact(
            'invoices',
            'activeGym',
            'shop',
            'totalSalesCount',
            'totalRevenue',
            'todayRevenue',
            'totalDiscount'
        ));
    }

    /**
     * پیشاندانی لاپەڕەی چاپی تەواوی پسووڵەی حراری (80mm)
     */
    public function printReceipt(PosSale $sale): View
    {
        $sale->load(['shop.gym', 'shift', 'cashier', 'member', 'items.variant.product']);
        return view('pos.print', compact('sale'));
    }

    /**
     * دەرکردنی داتای پسووڵە بە JSON بۆ مۆداڵی خێرای وێب
     */
    public function invoiceJson(PosSale $sale): JsonResponse
    {
        $sale->load(['shop.gym', 'shift', 'cashier', 'member', 'items.variant.product']);

        $paymentLabels = [
            'CASH' => 'نەقد (کاش)',
            'CARD' => 'کارت / POS',
            'MEMBER_CREDIT' => 'بە قەرز (سەر ئەندامێتی)',
            'SPLIT' => 'نیوە بە نیوە',
        ];

        return response()->json([
            'success' => true,
            'receipt' => [
                'invoice_number' => $sale->invoice_number,
                'gym_name' => $sale->shop->gym->name ?? 'GymPro Fitness Club',
                'gym_phone' => $sale->shop->gym->phone ?? '0770 000 0000',
                'shop_name' => $sale->shop->name ?? 'شۆپی هۆڵ',
                'location' => $sale->shop->location_details ?? 'سلێمانی',
                'date' => $sale->created_at->format('Y-m-d'),
                'time' => $sale->created_at->format('h:i A'),
                'cashier_name' => $sale->cashier->name ?? 'کاشێری هۆڵ',
                'customer_name' => $sale->member ? $sale->member->full_name : 'کڕیاری ئاسایی (میوان)',
                'customer_code' => $sale->member ? $sale->member->member_code : 'GUEST',
                'customer_phone' => $sale->member ? $sale->member->phone : '',
                'payment_method' => $paymentLabels[$sale->payment_method] ?? $sale->payment_method,
                'subtotal' => (float) $sale->subtotal,
                'discount' => (float) $sale->discount_amount,
                'total_amount' => (float) $sale->total_amount,
                'items' => $sale->items->map(function ($item) {
                    $prodName = $item->variant->product->name_ku ?? $item->variant->product->name_en ?? 'کاڵا';
                    if ($item->variant->flavor) {
                        $prodName .= ' (' . $item->variant->flavor . ')';
                    }
                    return [
                        'name' => $prodName,
                        'qty' => (int) $item->quantity,
                        'unit_price' => (float) $item->unit_sale_price,
                        'total' => (float) $item->line_total,
                    ];
                }),
            ]
        ]);
    }

    /**
     * دەرکردنی لینکی ناردنی پسووڵەی فرۆشتن لە WhatsApp
     */
    public function whatsappUrl(PosSale $sale, \App\Services\WhatsAppService $whatsAppService)
    {
        $sale->load(['shop.gym', 'member', 'items.variant.product']);
        $phone = $sale->member?->phone;
        $message = $whatsAppService->buildPosSaleMessage($sale);
        $url = $whatsAppService->generateWhatsAppUrl($phone, $message);

        return redirect()->away($url);
    }
}
