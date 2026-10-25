<?php

namespace App\Models;

use App\Traits\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipFreeze extends Model
{
    use HasFactory, BelongsToGym;

    protected $fillable = [
        'gym_id',
        'membership_id',
        'freeze_start_date',
        'freeze_end_date',
        'days_count',
        'reason',
        'approved_by_user_id',
        'status',
    ];

    protected $casts = [
        'freeze_start_date' => 'date',
        'freeze_end_date' => 'date',
    ];

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
