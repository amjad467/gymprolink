<?php

namespace App\Models;

use App\Traits\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use HasFactory, BelongsToGym;

    protected $fillable = [
        'gym_id',
        'code',
        'title',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'usage_limit',
        'times_used',
        'min_purchase_amount',
        'is_for_new_members_only',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_for_new_members_only' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(PromotionRedemption::class);
    }
}
