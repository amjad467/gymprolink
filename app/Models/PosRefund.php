<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosRefund extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_sale_id',
        'refund_invoice_number',
        'authorized_by_user_id',
        'refund_amount',
        'reason',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosRefundItem::class);
    }
}
