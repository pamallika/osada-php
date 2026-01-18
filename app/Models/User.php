<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Model
{
    protected $fillable = ['discord_id', 'username', 'global_name', 'avatar'];

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function presets(): BelongsToMany
    {
        return $this->belongsToMany(Preset::class)->withPivot('default_squad_name');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }
}
