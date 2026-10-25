<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'security_pin',
        'two_factor_enabled',
        'is_super_admin',
        'is_active',
        'current_gym_id',
    ];

    protected $hidden = [
        'password',
        'security_pin',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function currentGym(): BelongsTo
    {
        return $this->belongsTo(Gym::class, 'current_gym_id');
    }

    public function gyms(): BelongsToMany
    {
        return $this->belongsToMany(Gym::class, 'gym_user')
            ->withPivot('role_id', 'is_active')
            ->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * مۆڵەتە ڕاستەوخۆکانی بەکارهێنەر جگە لە ڕۆڵەکەی
     */
    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user')->withTimestamps();
    }

    /**
     * بەدەستهێنانی ڕۆڵەکانی بەکارهێنەر لەناو هۆڵێکی دیاریکراو
     */
    public function getRoleInGym(int $gymId): ?Role
    {
        $pivot = \DB::table('gym_user')
            ->where('user_id', $this->id)
            ->where('gym_id', $gymId)
            ->where('is_active', true)
            ->first();

        return $pivot ? Role::find($pivot->role_id) : null;
    }

    /**
     * ناوی ڕۆڵی سەرەکی بەکارهێنەر بۆ پیشاندان لە UI
     */
    public function getPrimaryRoleAttribute(): ?Role
    {
        if ($this->is_super_admin) {
            return Role::where('name', 'super_admin')->first();
        }

        $activeGymId = $this->current_gym_id ?? \App\Scopes\GymScope::getActiveGymId();
        if ($activeGymId) {
            $gymRole = $this->getRoleInGym($activeGymId);
            if ($gymRole) return $gymRole;
        }

        return $this->roles()->first();
    }

    /**
     * پشکنینی ئەوەی ئایا بەکارهێنەر دەسەڵاتێکی دیاریکراوی هەیە
     */
    public function hasPermission(string $permissionName, ?int $gymId = null): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        // ١. پشکنینی مۆڵەتە ڕاستەوخۆکانی بەکارهێنەر (Direct Permissions)
        if ($this->directPermissions()->where('name', $permissionName)->exists()) {
            return true;
        }

        $targetGymId = $gymId ?? $this->current_gym_id ?? \App\Scopes\GymScope::getActiveGymId();

        if ($targetGymId) {
            // ٢. پشکنینی مۆڵەت لە ڕێگەی هۆڵەکەیەوە
            $role = $this->getRoleInGym($targetGymId);
            if ($role && $role->permissions()->where('name', $permissionName)->exists()) {
                return true;
            }
        }

        // ٣. پشکنینی ڕۆڵە گشتییەکان
        return $this->roles()->whereHas('permissions', function ($q) use ($permissionName) {
            $q->where('name', $permissionName);
        })->exists();
    }

    /**
     * پشکنینی بوونی ڕۆڵ
     */
    public function hasRole(string $roleName, ?int $gymId = null): bool
    {
        if ($this->is_super_admin && $roleName === 'super_admin') {
            return true;
        }

        $targetGymId = $gymId ?? $this->current_gym_id ?? \App\Scopes\GymScope::getActiveGymId();

        if ($targetGymId) {
            $role = $this->getRoleInGym($targetGymId);
            return $role && $role->name === $roleName;
        }

        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * هاوتای hasPermission بۆ پشکنینی مۆڵەت
     */
    public function hasPermissionTo(string $permissionName, ?int $gymId = null): bool
    {
        return $this->hasPermission($permissionName, $gymId);
    }
}
