<?php

namespace App\Models;

use App\Traits\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessEvent extends Model
{
    use HasFactory, BelongsToGym;

    protected $fillable = [
        'gym_id',
        'access_device_id',
        'member_id',
        'membership_id',
        'scanned_code',
        'status',
        'denial_reason',
        'client_ip',
        'verification_time_ms',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(AccessDevice::class, 'access_device_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }
}
