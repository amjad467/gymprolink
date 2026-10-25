<?php

namespace App\Models;

use App\Traits\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GymShop extends Model
{
    use HasFactory, BelongsToGym;

    protected $fillable = [
        'gym_id',
        'name',
        'code',
        'location_details',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'shop_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'shop_id');
    }

    public function cashShifts(): HasMany
    {
        return $this->hasMany(ShopCashShift::class, 'shop_id');
    }

    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class, 'shop_id');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(ShopPromotion::class, 'shop_id');
    }
}
