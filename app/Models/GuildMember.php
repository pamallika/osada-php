<?php

namespace App\Models;

use App\Enums\GuildRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuildMember extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'guild_id', 
        'user_id', 
        'role', 
        'joined_at', 
        'status',
        'verification_status',
        'verified_by',
        'verified_at'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'joined_at' => 'datetime',
    ];

    // Roles: creator, admin, officer, member

    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
