<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password'];

    public function linkedAccounts(): HasMany
    {
        return $this->hasMany(LinkedAccount::class);
    }

    // В каких гильдиях состоит юзер
    public function guildMemberships(): HasMany
    {
        return $this->hasMany(GuildMember::class);
    }
}
