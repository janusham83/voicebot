<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'language',
        'voice',
        'ai_model',
        'temperature',
        'auto_play',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'auto_play' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
