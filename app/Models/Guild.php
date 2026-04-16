<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guild extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'owner_id', 'logo_url', 'invite_slug', 'status'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(self::class . 'Integration');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GuildMember::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(GuildInvite::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(GuildPost::class);
    }
}
