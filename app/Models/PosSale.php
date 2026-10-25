<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PosSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'cash_shift_id',
        'cashier_id',
        'member_id',
        'invoice_number',
        'subtotal',
        'discount_amount',
        'discount_approved_by',
        'total_amount',
        'total_cogs',
        'paid_amount',
        'remaining_balance',
        'payment_method',
        'payment_status',
        'offline_client_uuid',
        'is_offline_sync',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_cogs' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'is_offline_sync' => 'boolean',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(GymShop::class, 'shop_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(ShopCashShift::class, 'cash_shift_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class);
    }

    public function refund(): HasOne
    {
        return $this->hasOne(PosRefund::class);
    }

    /**
     * قازانجی نەخالیس (Gross Profit)
     */
    public function getGrossProfitAttribute(): float
    {
        return (float) ($this->total_amount - $this->total_cogs);
    }
}
