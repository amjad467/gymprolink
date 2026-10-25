<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GymShop;
use App\Models\ProductVariant;
use App\Services\SupplementShopService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(
        protected SupplementShopService $shopService
    ) {}

    /**
     * هێنانەوەی کاڵاکانی بەردەست لە شۆپی دیاریکراو بۆ شاشەی POS
     */
    public function products(Request $request, GymShop $shop): JsonResponse
    {
        $today = now()->toDateString();

        $variants = ProductVariant::with(['product.brand', 'product.category'])
            ->where('is_active', true)
            ->whereHas('batches', function ($q) use ($shop, $today) {
                $q->where('shop_id', $shop->id)
                    ->where('is_blocked', false)
                    ->where('quantity_available', '>', 0)
                    ->whereDate('expiry_date', '>', $today);
            })
            ->get()
            ->map(function ($variant) use ($shop) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'product_name' => [
                        'ku' => $variant->product->name_ku,
                        'ar' => $variant->product->name_ar,
                        'en' => $variant->product->name_en,
                    ],
                    'brand' => $variant->product->brand?->name,
                    'category' => $variant->product->category?->name_ku,
                    'flavor' => $variant->flavor,
                    'size' => $variant->size,
                    'sale_price' => (float) $variant->default_sale_price,
                    'member_price' => $variant->member_sale_price ? (float) $variant->member_sale_price : null,
                    'stock_available' => $variant->getAvailableStockInShop($shop->id),
                ];
            });

        return response()->json([
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'gym_id' => $shop->gym_id,
            ],
            'products' => $variants,
        ]);
    }

    /**
     * جێبەجێکردنی فرۆشتن لە POS
     */
    public function checkout(Request $request, GymShop $shop): JsonResponse
    {
        $validated = $request->validate([
            'cashier_user_id' => 'required|integer|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:CASH,CARD,SPLIT,MEMBER_CREDIT',
            'member_id' => 'nullable|integer|exists:members,id',
            'offline_uuid' => 'nullable|string',
            'discount_approved_by' => 'nullable|integer|exists:users,id',
        ]);

        try {
            $sale = $this->shopService->processPosSale(
                $shop->id,
                $validated['cashier_user_id'],
                $validated['items'],
                $validated['payment_method'],
                $validated['member_id'] ?? null,
                $validated['offline_uuid'] ?? null,
                $validated['discount_approved_by'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'فرۆشتن بە سەرکەوتوویی ئەنجامدرا.',
                'invoice' => [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'total_amount' => (float) $sale->total_amount,
                    'total_cogs' => (float) $sale->total_cogs,
                    'gross_profit' => (float) $sale->gross_profit,
                    'payment_method' => $sale->payment_method,
                    'created_at' => $sale->created_at->toIso8601String(),
                ],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * گەڕاندنەوەی کاڵا لە POS
     */
    public function refund(Request $request, GymShop $shop): JsonResponse
    {
        $validated = $request->validate([
            'pos_sale_id' => 'required|integer|exists:pos_sales,id',
            'authorized_by_user_id' => 'required|integer|exists:users,id',
            'reason' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.batch_id' => 'required|integer|exists:inventory_batches,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.restock' => 'boolean',
        ]);

        try {
            $refund = $this->shopService->processRefund(
                $validated['pos_sale_id'],
                $validated['authorized_by_user_id'],
                $validated['reason'],
                $validated['items']
            );

            return response()->json([
                'success' => true,
                'message' => 'کاڵا و پارەکە بە سەرکەوتوویی گەڕێنرایەوە.',
                'refund' => [
                    'id' => $refund->id,
                    'refund_number' => $refund->refund_invoice_number,
                    'refund_amount' => (float) $refund->refund_amount,
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
     * داخستنی شفتی سندوق
     */
    public function closeShift(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shift_id' => 'required|integer|exists:shop_cash_shifts,id',
            'actual_cash' => 'required|numeric|min:0',
            'closing_notes' => 'nullable|string',
        ]);

        try {
            $shift = $this->shopService->closeCashShift(
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
                    'status' => $shift->status,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
