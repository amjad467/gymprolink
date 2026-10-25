<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'variant_id',
        'supplier_id',
        'batch_number',
        'expiry_date',
        'unit_cost_price',
        'quantity_received',
        'quantity_available',
        'is_blocked',
        'block_reason',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'unit_cost_price' => 'decimal:2',
        'is_blocked' => 'boolean',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(GymShop::class, 'shop_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }
}
