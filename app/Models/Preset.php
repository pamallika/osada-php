<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Preset extends Model
{
    protected $fillable = ['guild_id', 'title', 'structure'];

    protected $casts = [
        'structure' => 'array',
    ];


    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('default_squad_name');
    }
}
