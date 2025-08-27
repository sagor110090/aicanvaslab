<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'anonymous_id',
        'ai_model_id',
        'title',
        'last_activity_at',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($chat) {
            $chat->uuid = Str::uuid();
            $chat->last_activity_at = now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aiModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function scopeForUser($query, $userId = null, $anonymousId = null)
    {
        if ($userId) {
            return $query->where('user_id', $userId);
        }

        if ($anonymousId) {
            return $query->where('anonymous_id', $anonymousId);
        }

        return $query;
    }

    public function updateActivity()
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function generateTitle()
    {
        $firstUserMessage = $this->messages()
            ->where('role', 'user')
            ->first();

        if ($firstUserMessage) {
            $this->update([
                'title' => Str::limit($firstUserMessage->content, 50)
            ]);
        }
    }
}