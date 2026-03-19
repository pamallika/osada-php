<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserProfile extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'global_name', 'family_name', 'char_class', 'gear_score', 'attack', 'awakening_attack', 'defense'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
