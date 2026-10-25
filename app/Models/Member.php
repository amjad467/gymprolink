<?php

namespace App\Models;

use App\Traits\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes, BelongsToGym;

    protected $fillable = [
        'gym_id',
        'member_code',
        'full_name',
        'phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'national_id',
        'birth_date',
        'gender',
        'photo_url',
        'health_notes',
        'admin_notes',
        'allow_credit',
        'credit_limit',
        'current_debt',
        'status',
        'assigned_trainer_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'allow_credit' => 'boolean',
        'credit_limit' => 'decimal:2',
        'current_debt' => 'decimal:2',
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_trainer_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function activeMembership(): HasOne
    {
        return $this->hasOne(Membership::class)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now())
            ->latestOfMany();
    }

    public function accessCredentials(): HasMany
    {
        return $this->hasMany(MemberAccessCredential::class);
    }

    public function primaryCredential(): HasOne
    {
        return $this->hasOne(MemberAccessCredential::class)
            ->where('is_revoked', false)
            ->latestOfMany();
    }

    public function accessEvents(): HasMany
    {
        return $this->hasMany(AccessEvent::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(MemberAttendance::class);
    }

    public function currentAttendance(): HasOne
    {
        return $this->hasOne(MemberAttendance::class)
            ->where('status', 'INSIDE')
            ->latestOfMany();
    }
}
