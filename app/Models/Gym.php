<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gym extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'logo_url',
        'phone',
        'email',
        'address',
        'opening_hours',
        'default_credit_limit',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_credit_limit' => 'decimal:2',
        'settings' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'gym_user')
            ->withPivot('role_id', 'is_active')
            ->withTimestamps();
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function membershipPlans(): HasMany
    {
        return $this->hasMany(MembershipPlan::class);
    }

    public function accessDevices(): HasMany
    {
        return $this->hasMany(AccessDevice::class);
    }

    public function accessEvents(): HasMany
    {
        return $this->hasMany(AccessEvent::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function shops(): HasMany
    {
        return $this->hasMany(GymShop::class);
    }
}
