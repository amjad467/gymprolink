<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'batch_id',
        'movement_type',
        'quantity',
        'unit_cost',
        'reference_type',
        'reference_id',
        'performed_by_user_id',
        'notes',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(GymShop::class, 'shop_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
