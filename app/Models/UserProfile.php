<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = ['user_id', 'family_name', 'char_class', 'gear_score', 'attack', 'awakening_attack', 'defense', 'level'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
