<?php

namespace App\Models;

use App\Traits\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberAccessCredential extends Model
{
    use HasFactory, BelongsToGym;

    protected $fillable = [
        'gym_id',
        'member_id',
        'credential_type',
        'card_number',
        'token_hash',
        'raw_token',
        'is_dynamic',
        'expires_at',
        'is_revoked',
        'revocation_reason',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'is_dynamic' => 'boolean',
        'is_revoked' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
