<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'size',
        'weight_grams',
        'flavor',
        'default_cost_price',
        'default_sale_price',
        'member_sale_price',
        'min_stock_alert',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'default_cost_price' => 'decimal:2',
        'default_sale_price' => 'decimal:2',
        'member_sale_price' => 'decimal:2',
        'weight_grams' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'variant_id');
    }

    /**
     * بەدەستهێنانی بڕی گشتی بەردەست لە کۆگای شۆپێکی دیاریکراو
     */
    public function getAvailableStockInShop(int $shopId): int
    {
        return $this->batches()
            ->where('shop_id', $shopId)
            ->where('is_blocked', false)
            ->whereDate('expiry_date', '>', now())
            ->sum('quantity_available');
    }
}
