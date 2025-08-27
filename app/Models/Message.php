<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'role',
        'content',
        'images',
        'metadata',
    ];

    protected $casts = [
        'images' => 'array',
        'metadata' => 'array',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function hasImages(): bool
    {
        return !empty($this->images);
    }

    public function getFormattedContentAttribute(): string
    {
        return nl2br(e($this->content));
    }
}