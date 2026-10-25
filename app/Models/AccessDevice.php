<?php

namespace App\Models;

use App\Traits\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessDevice extends Model
{
    use HasFactory, BelongsToGym;

    protected $fillable = [
        'gym_id',
        'name',
        'device_identifier',
        'device_type',
        'direction',
        'ip_address',
        'port',
        'api_key',
        'secret_token',
        'relay_pulse_ms',
        'is_active',
        'last_heartbeat_at',
    ];

    protected $hidden = [
        'secret_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_heartbeat_at' => 'datetime',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(AccessEvent::class);
    }
}
