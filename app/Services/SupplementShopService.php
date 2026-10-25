<?php

namespace App\Services;

use App\Models\GymShop;
use App\Models\InventoryBatch;
use App\Models\Member;
use App\Models\PosRefund;
use App\Models\PosRefundItem;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\ProductVariant;
use App\Models\ShopCashShift;
use App\Models\ShopTransfer;
use App\Models\ShopTransferItem;
use App\Models\AuditLog;
use App\Models\StockMovement;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class SupplementShopService
{
    /**
     * دابەشکردنی کاڵاکان بەپێی ئەلگۆریتمی FEFO (First Expired, First Out)
     */
    public function allocateFefoBatches(int $shopId, int $variantId, int $requestedQty): array
    {
        $today = now()->toDateString();

        // 1. گەڕان بەدوای وەجبە نائیکس پایەرەکان (FEFO)
        $batches = InventoryBatch::where('shop_id', $shopId)
            ->where('variant_id', $variantId)
            ->where('is_blocked', false)
            ->where(function ($q) use ($today) {
                $q->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', $today);
            })
            ->where('quantity_available', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->lockForUpdate()
            ->get();

        $totalAvailable = $batches->sum('quantity_available');

        if ($totalAvailable < $requestedQty) {
            throw new Exception("بڕی پێویست لە کۆگا بەردەست نییە! داواکراو: {$requestedQty}، بەردەست بەپێی بەرواری دروست: {$totalAvailable}");
        }

        $allocations = [];
        $remaining = $requestedQty;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $deduct = min($batch->quantity_available, $remaining);
            $allocations[] = [
                'batch' => $batch,
                'quantity' => $deduct,
                'unit_cost' => (float) $batch->unit_cost_price,
            ];

            $remaining -= $deduct;
        }

        return $allocations;
    }

    /**
     * ئەنجامدانی فرۆشتنی POS بە تەواوی مەرجەکانی ئاسایش و داتابەیس
     */
    public function processPosSale(
        int $shopId,
        int $cashierUserId,
        array $items, // [['variant_id' => 1, 'quantity' => 2, 'unit_price' => 50, 'discount' => 0], ...]
        string $paymentMethod = 'CASH',
        ?int $memberId = null,
        ?string $offlineUuid = null,
        ?int $discountApprovedBy = null
    ): PosSale {
        return DB::transaction(function () use (
            $shopId,
            $cashierUserId,
            $items,
            $paymentMethod,
            $memberId,
            $offlineUuid,
            $discountApprovedBy
        ) {
            // پشکنینی فرۆشتنی دووبارە لە کاتی Offline Sync
            if ($offlineUuid) {
                $existing = PosSale::where('offline_client_uuid', $offlineUuid)->first();
                if ($existing) {
                    return $existing;
                }
            }

            // پشکنینی شفتی کراوەی کاشێر
            $shift = ShopCashShift::where('shop_id', $shopId)
                ->where('cashier_user_id', $cashierUserId)
                ->where('status', 'OPEN')
                ->latest()
                ->first();

            // ئەگەر شفت بۆ ئەم کاشێرە نەبوو، گەڕان بۆ هەر شفتێکی کراوەی ئەم شۆپە
            if (!$shift) {
                $shift = ShopCashShift::where('shop_id', $shopId)
                    ->where('status', 'OPEN')
                    ->latest()
                    ->first();
            }

            // ئەگەر هیچ شفتێک کراوە نەبوو، خۆکارانە شفت دەکاتەوە
            if (!$shift) {
                $shift = ShopCashShift::create([
                    'shop_id' => $shopId,
                    'cashier_user_id' => $cashierUserId ?: 1,
                    'opened_at' => now(),
                    'opening_cash' => 0,
                    'expected_cash' => 0,
                    'status' => 'OPEN',
                    'opening_notes' => 'شفت بە شێوەی خۆکار لە کاتی فرۆشتن کرایەوە',
                ]);
            }

            $member = $memberId ? Member::find($memberId) : null;
            $subtotal = 0;
            $totalDiscount = 0;
            $totalCogs = 0;
            $saleLineItemsData = [];

            // دابەشکردنی فرۆشتنەکان لەسەر وەجبەکان بە FEFO
            foreach ($items as $item) {
                $variant = ProductVariant::findOrFail($item['variant_id']);
                $qty = (int) $item['quantity'];

                if ($qty <= 0) {
                    throw new Exception('بڕی داواکراو دەبێت لە سفر زیاتر بێت.');
                }

                $allocations = $this->allocateFefoBatches($shopId, $variant->id, $qty);

                $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : (
                    ($member && $variant->member_sale_price) ? (float) $variant->member_sale_price : (float) $variant->default_sale_price
                );
                $discount = isset($item['discount']) ? (float) $item['discount'] : 0.0;

                foreach ($allocations as $alloc) {
                    $batch = $alloc['batch'];
                    $allocQty = $alloc['quantity'];
                    $allocCost = $alloc['unit_cost'];
                    $lineTotal = ($unitPrice * $allocQty) - $discount;
                    $lineGrossProfit = $lineTotal - ($allocCost * $allocQty);

                    $subtotal += ($unitPrice * $allocQty);
                    $totalDiscount += $discount;
                    $totalCogs += ($allocCost * $allocQty);

                    // کەمکردنەوەی کۆگا
                    $batch->decrement('quantity_available', $allocQty);

                    $saleLineItemsData[] = [
                        'variant_id' => $variant->id,
                        'batch_id' => $batch->id,
                        'quantity' => $allocQty,
                        'unit_cost_price' => $allocCost,
                        'unit_sale_price' => $unitPrice,
                        'discount_amount' => $discount,
                        'line_total' => $lineTotal,
                        'gross_profit' => $lineGrossProfit,
                    ];
                }
            }

            $finalTotal = $subtotal - $totalDiscount;

            // پشکنینی کڕین بە قەرز بۆ ئەندام
            if ($paymentMethod === 'MEMBER_CREDIT') {
                if (!$member || !$member->allow_credit) {
                    throw new Exception('ئەم ئەندامە ڕێگەی پێندراوە بە قەرز کاڵا بکڕێت.');
                }
                if (($member->current_debt + $finalTotal) > $member->credit_limit) {
                    throw new Exception("کۆی قەرز لە سنووری ڕێگەپێدراو تێپەڕ دەکات ({$member->credit_limit} IQD).");
                }
                $member->increment('current_debt', $finalTotal);
                $paidAmount = 0.00;
                $remainingBalance = $finalTotal;
                $paymentStatus = 'DUE';
            } else {
                $paidAmount = $finalTotal;
                $remainingBalance = 0.00;
                $paymentStatus = 'PAID';
            }

            $invoiceNumber = 'POS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            // دروستکردنی فاکتۆری سەرەکی فرۆشتن
            $sale = PosSale::create([
                'shop_id' => $shopId,
                'cash_shift_id' => $shift->id,
                'cashier_id' => $cashierUserId,
                'member_id' => $memberId,
                'invoice_number' => $invoiceNumber,
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'discount_approved_by' => $discountApprovedBy,
                'total_amount' => $finalTotal,
                'total_cogs' => $totalCogs,
                'paid_amount' => $paidAmount,
                'remaining_balance' => $remainingBalance,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'offline_client_uuid' => $offlineUuid,
                'is_offline_sync' => (bool) $offlineUuid,
            ]);

            // تۆمارکردنی بڕگەکان و جووڵەی کۆگا
            foreach ($saleLineItemsData as $itemData) {
                $itemData['pos_sale_id'] = $sale->id;
                PosSaleItem::create($itemData);

                StockMovement::create([
                    'shop_id' => $shopId,
                    'batch_id' => $itemData['batch_id'],
                    'movement_type' => 'SALE',
                    'quantity' => -$itemData['quantity'],
                    'unit_cost' => $itemData['unit_cost_price'],
                    'reference_type' => 'pos_sale',
                    'reference_id' => $sale->id,
                    'performed_by_user_id' => $cashierUserId,
                    'notes' => "فرۆشتن بە فاکتۆری #{$sale->invoice_number}",
                ]);
            }

            // ئەگەر بە نەقد پارە درابێت، پارەی پێشبینیکراوی ناو سندوقی شفتەکە زیاد دەکات
            if ($paymentMethod === 'CASH') {
                $shift->increment('expected_cash', $finalTotal);
            }

            return $sale;
        });
    }

    /**
     * کردنەوەی شفتی سندوق بۆ کاشێر
     */
    public function openCashShift(int $shopId, int $cashierUserId, float $openingCash = 0, ?string $openingNotes = null): ShopCashShift
    {
        $existing = ShopCashShift::where('shop_id', $shopId)
            ->where('cashier_user_id', $cashierUserId)
            ->where('status', 'OPEN')
            ->first();

        if ($existing) {
            return $existing;
        }

        $shift = ShopCashShift::create([
            'shop_id' => $shopId,
            'cashier_user_id' => $cashierUserId,
            'opened_at' => now(),
            'opening_cash' => $openingCash,
            'expected_cash' => $openingCash,
            'status' => 'OPEN',
            'opening_notes' => $openingNotes ?? 'شفت لەلایەن کاشێر کرایەوە',
        ]);

        $shop = GymShop::find($shopId);
        $user = User::find($cashierUserId);

        AuditLog::create([
            'gym_id' => $shop?->gym_id ?? 1,
            'user_id' => $cashierUserId,
            'action' => 'shop.shift.opened',
            'auditable_type' => ShopCashShift::class,
            'auditable_id' => $shift->id,
            'new_values' => [
                'opening_cash' => $openingCash,
                'cashier' => $user?->name ?? 'کاشێر',
            ],
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'System',
        ]);

        return $shift;
    }

    /**
     * گەڕاندنەوەی کاڵا و پارەی POS بە مۆڵەت (Refund)
     */
    public function processRefund(
        int $posSaleId,
        int $authorizedByUserId,
        string $reason,
        array $itemsToRefund = [] // [['batch_id' => 1, 'quantity' => 1, 'restock' => true], ...]
    ): PosRefund {
        return DB::transaction(function () use ($posSaleId, $authorizedByUserId, $reason, $itemsToRefund) {
            $sale = PosSale::with(['items', 'member', 'shift'])->findOrFail($posSaleId);

            if ($sale->payment_status === 'REFUNDED') {
                throw new Exception('ئەم فاکتۆرە پێشتر گەڕێندراوەتەوە.');
            }

            // ئەگەر لیستی کاڵاکان بەتاڵ بوو، هەموو کاڵاکانی فاکتۆرەکە بگەڕێنەوە
            if (empty($itemsToRefund)) {
                $itemsToRefund = $sale->items->map(function ($it) {
                    return [
                        'batch_id' => $it->batch_id,
                        'quantity' => $it->quantity,
                        'restock' => true,
                    ];
                })->toArray();
            }

            $refundNumber = 'REF-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $totalRefundAmount = 0;

            $refund = PosRefund::create([
                'pos_sale_id' => $sale->id,
                'refund_invoice_number' => $refundNumber,
                'authorized_by_user_id' => $authorizedByUserId,
                'refund_amount' => 0.00,
                'reason' => $reason,
            ]);

            foreach ($itemsToRefund as $item) {
                $saleItem = $sale->items->where('batch_id', $item['batch_id'])->first();
                if (!$saleItem) {
                    throw new Exception('کاڵای دیاریکراو لە فاکتۆری بنەڕەتیدا نییە.');
                }

                $refundQty = (int) $item['quantity'];
                $itemAmount = ($saleItem->unit_sale_price * $refundQty) - ($saleItem->discount_amount * ($refundQty / $saleItem->quantity));
                $totalRefundAmount += $itemAmount;

                PosRefundItem::create([
                    'pos_refund_id' => $refund->id,
                    'batch_id' => $item['batch_id'],
                    'quantity' => $refundQty,
                    'restock_to_inventory' => $item['restock'] ?? true,
                ]);

                // ئەگەر کاڵاکە بگەڕێتەوە بۆ ناو کۆگا
                if (!empty($item['restock'])) {
                    $batch = InventoryBatch::findOrFail($item['batch_id']);
                    $batch->increment('quantity_available', $refundQty);

                    StockMovement::create([
                        'shop_id' => $sale->shop_id,
                        'batch_id' => $batch->id,
                        'movement_type' => 'SALE_REFUND',
                        'quantity' => $refundQty,
                        'unit_cost' => $saleItem->unit_cost_price,
                        'reference_type' => 'pos_refund',
                        'reference_id' => $refund->id,
                        'performed_by_user_id' => $authorizedByUserId,
                        'notes' => "گەڕاندنەوە بە پسووڵەی #{$refundNumber}",
                    ]);
                }
            }

            $refund->update(['refund_amount' => $totalRefundAmount]);

            // ئەگەر فرۆشتنەکە بە نەقد بووبێت، پارەی شفتەکە کەم دەکرێتەوە
            if ($sale->payment_method === 'CASH') {
                $shift = $sale->shift;
                if ($shift && $shift->isOpen()) {
                    $shift->decrement('expected_cash', $totalRefundAmount);
                }
            }

            // ئەگەر بە قەرز بووبێت، قەرزی سەر ئەندامەکە کەم دەکرێتەوە
            if ($sale->payment_method === 'MEMBER_CREDIT' && $sale->member) {
                $sale->member->decrement('current_debt', min((float) $sale->member->current_debt, (float) $totalRefundAmount));
                if ($sale->member->fresh()->current_debt <= 0) {
                    $sale->member->update(['has_debt' => false, 'current_debt' => 0]);
                }
            }

            // نوێکردنەوەی باری پارەدانی فاکتۆر بۆ گەڕاوە
            $sale->update(['payment_status' => 'REFUNDED']);

            $shop = GymShop::find($sale->shop_id);
            AuditLog::create([
                'gym_id' => $shop?->gym_id ?? 1,
                'user_id' => $authorizedByUserId,
                'action' => 'shop.refund.approved',
                'auditable_type' => PosRefund::class,
                'auditable_id' => $refund->id,
                'new_values' => [
                    'invoice_number' => $sale->invoice_number,
                    'refund_number' => $refundNumber,
                    'refund_amount' => $totalRefundAmount,
                    'reason' => $reason,
                ],
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'System',
            ]);

            return $refund;
        });
    }

    /**
     * داخستنی شفتی سندوق بە بەراوردی پارەی ژمێردراو بە پێشبینیکراو (Blind Cash Drawer Reconciliation)
     */
    public function closeCashShift(int $shiftId, float $actualCashCounted, ?string $closingNotes = null): ShopCashShift
    {
        $shift = ShopCashShift::with('shop')->findOrFail($shiftId);

        if (!$shift->isOpen()) {
            throw new Exception('ئەم شفتە پێشتر داخراوە.');
        }

        // کۆکردنەوەی داهاتی نەقدی ڕاستەقینەی ئەم شفتە بێ فاکتۆرە گەڕاوەکان
        $cashSales = (float) PosSale::where('cash_shift_id', $shift->id)
            ->where('payment_method', 'CASH')
            ->where('payment_status', '!=', 'REFUNDED')
            ->sum('paid_amount');

        $expected = (float) $shift->opening_cash + $cashSales;
        $difference = $actualCashCounted - $expected; // کورتێنان ئەگەر سالب بێت، زیادە ئەگەر موجەب بێت

        $shift->update([
            'closed_at' => now(),
            'expected_cash' => $expected,
            'actual_cash' => $actualCashCounted,
            'cash_difference' => $difference,
            'status' => 'CLOSED',
            'closing_notes' => $closingNotes,
        ]);

        AuditLog::create([
            'gym_id' => $shift->shop?->gym_id ?? 1,
            'user_id' => auth()->id() ?? $shift->cashier_user_id,
            'action' => 'shop.shift.closed',
            'auditable_type' => ShopCashShift::class,
            'auditable_id' => $shift->id,
            'new_values' => [
                'cashier_id' => $shift->cashier_user_id,
                'opening_cash' => (float) $shift->opening_cash,
                'expected_cash' => $expected,
                'actual_cash' => $actualCashCounted,
                'cash_difference' => $difference,
                'status' => 'CLOSED',
            ],
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'System',
        ]);

        return $shift;
    }
}
