<?php

namespace App\Models;

use GuildIntegration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guild extends Model
{
    protected $fillable = ['name', 'slug', 'owner_id', 'logo_url'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(GuildIntegration::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(GuildMember::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(GuildInvite::class);
    }
}
