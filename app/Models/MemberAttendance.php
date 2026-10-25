<?php

namespace App\Models;

use App\Traits\BelongsToGym;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberAttendance extends Model
{
    use HasFactory, BelongsToGym;

    protected $fillable = [
        'gym_id',
        'member_id',
        'membership_id',
        'access_device_id',
        'check_in_at',
        'check_out_at',
        'duration_minutes',
        'status',
        'check_in_method',
        'notes',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(AccessDevice::class, 'access_device_id');
    }

    /**
     * چەند کاتژمێر یان خولەکە لە هۆڵدایە یان لە کاتی خۆیدا چەند مابووەوە
     */
    public function getDurationFormattedAttribute(): string
    {
        $minutes = $this->duration_minutes;

        if ($this->status === 'INSIDE' && !$this->check_out_at) {
            $minutes = (int) $this->check_in_at->diffInMinutes(now());
        }

        if ($minutes === null || $minutes < 0) {
            return 'تازە هاتووە';
        }

        $hours = floor($minutes / 60);
        $remainingMins = $minutes % 60;

        if ($hours > 0 && $remainingMins > 0) {
            return "{$hours} کاتژمێر و {$remainingMins} خولەک";
        } elseif ($hours > 0) {
            return "{$hours} کاتژمێر";
        } else {
            return "{$remainingMins} خولەک";
        }
    }

    /**
     * ژمارەی وردی کاتژمێر و خولەک بۆ پیشاندانی زیندوو
     */
    public function getLiveMinutesAttribute(): int
    {
        if ($this->status === 'INSIDE' && !$this->check_out_at) {
            return (int) $this->check_in_at->diffInMinutes(now());
        }
        return $this->duration_minutes ?? 0;
    }
}
