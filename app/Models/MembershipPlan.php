<?php

namespace App\Models;

use App\Traits\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    use HasFactory, BelongsToGym;

    protected $fillable = [
        'gym_id',
        'name',
        'description',
        'price',
        'duration_days',
        'visits_limit',
        'allowed_entry_start',
        'allowed_entry_end',
        'allowed_days_of_week',
        'renewal_discount_percentage',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'allowed_days_of_week' => 'array',
        'renewal_discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }
}
