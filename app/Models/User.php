<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;
    protected $fillable = ['email', 'password'];

    protected $appends = ['name'];

    public function getNameAttribute(): string
    {
        $profile = $this->profile;
        if (!$profile) {
            return 'User_' . $this->id;
        }

        return !empty($profile->family_name) 
            ? $profile->family_name 
            : (!empty($profile->global_name) ? $profile->global_name : 'User_' . $this->id);
    }

    public function linkedAccounts(): HasMany
    {
        return $this->hasMany(LinkedAccount::class);
    }

    public function linked_accounts(): HasMany
    {
        return $this->linkedAccounts();
    }

    public function profile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function guildMemberships(): HasMany
    {
        return $this->hasMany(GuildMember::class);
    }

    public function gearMedia(): HasMany
    {
        return $this->hasMany(UserGearMedia::class);
    }
}
