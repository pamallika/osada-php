<?php

namespace App\Models;

use App\Enums\GuildRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuildMember extends Model
{
    use SoftDeletes;

    protected $fillable = ['guild_id', 'user_id', 'role', 'joined_at', 'status'];

    // Roles: creator, admin, officer, member

    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
