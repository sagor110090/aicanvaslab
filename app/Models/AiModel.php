<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'model_id',
        'supports_images',
        'is_active',
        'max_tokens',
        'temperature',
        'description',
        'order',
    ];

    protected $casts = [
        'supports_images' => 'boolean',
        'is_active' => 'boolean',
        'temperature' => 'float',
    ];

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }
}