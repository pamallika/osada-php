<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGearMedia extends Model
{
    use HasFactory;

    protected $table = 'user_gear_media';

    protected $fillable = [
        'user_id',
        'url',
        'label',
        'is_draft',
        'size',
    ];

    protected $casts = [
        'is_draft' => 'boolean',
        'size' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
