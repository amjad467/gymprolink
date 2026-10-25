<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopTransferItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'transfer_id',
        'source_batch_id',
        'quantity',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(ShopTransfer::class, 'transfer_id');
    }

    public function sourceBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'source_batch_id');
    }
}
