<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_shop_id',
        'destination_shop_id',
        'status',
        'created_by_user_id',
        'approved_by_user_id',
        'received_by_user_id',
        'notes',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function sourceShop(): BelongsTo
    {
        return $this->belongsTo(GymShop::class, 'source_shop_id');
    }

    public function destinationShop(): BelongsTo
    {
        return $this->belongsTo(GymShop::class, 'destination_shop_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopTransferItem::class, 'transfer_id');
    }
}
