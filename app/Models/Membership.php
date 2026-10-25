<?php

namespace App\Models;

use App\Traits\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membership extends Model
{
    use HasFactory, BelongsToGym;

    protected $fillable = [
        'gym_id',
        'member_id',
        'membership_plan_id',
        'start_date',
        'end_date',
        'original_end_date',
        'visits_limit',
        'visits_used',
        'original_price',
        'paid_price',
        'status',
        'created_by_user_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'original_end_date' => 'date',
        'original_price' => 'decimal:2',
        'paid_price' => 'decimal:2',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function freezes(): HasMany
    {
        return $this->hasMany(MembershipFreeze::class);
    }

    public function isExpired(): bool
    {
        return $this->end_date->isPast();
    }

    public function isFrozen(): bool
    {
        return $this->status === 'frozen';
    }

    public function canAccess(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($this->visits_limit !== null && $this->visits_used >= $this->visits_limit) {
            return false;
        }

        return true;
    }
}
