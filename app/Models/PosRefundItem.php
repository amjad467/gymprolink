<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosRefundItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_refund_id',
        'batch_id',
        'quantity',
        'restock_to_inventory',
    ];

    protected $casts = [
        'restock_to_inventory' => 'boolean',
    ];

    public function refund(): BelongsTo
    {
        return $this->belongsTo(PosRefund::class, 'pos_refund_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }
}
