<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopCashShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'cashier_user_id',
        'opened_at',
        'closed_at',
        'opening_cash',
        'expected_cash',
        'actual_cash',
        'cash_difference',
        'status',
        'closing_notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(GymShop::class, 'shop_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(PosSale::class, 'cash_shift_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'OPEN';
    }
}
