<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopPromotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'title',
        'offer_type',
        'config',
        'start_date',
        'end_date',
        'is_member_only',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_member_only' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(GymShop::class, 'shop_id');
    }
}
