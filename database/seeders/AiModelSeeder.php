<?php

namespace Database\Seeders;

use App\Models\AiModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AiModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $models = [
            [
                'name' => 'GPT-4 Vision',
                'model_id' => 'openai/gpt-4-vision-preview',
                'provider' => 'openrouter',
                'supports_images' => true,
                'is_active' => true,
                'max_tokens' => 4096,
                'temperature' => 0.7,
                'description' => 'Most capable GPT-4 model with vision capabilities',
                'order' => 1,
            ],
            [
                'name' => 'GPT-4 Turbo',
                'model_id' => 'openai/gpt-4-turbo',
                'provider' => 'openrouter',
                'supports_images' => false,
                'is_active' => true,
                'max_tokens' => 4096,
                'temperature' => 0.7,
                'description' => 'Latest GPT-4 Turbo model with improved performance',
                'order' => 2,
            ],
            [
                'name' => 'Claude 3 Opus',
                'model_id' => 'anthropic/claude-3-opus',
                'provider' => 'openrouter',
                'supports_images' => true,
                'is_active' => true,
                'max_tokens' => 4096,
                'temperature' => 0.7,
                'description' => 'Most powerful Claude model with vision support',
                'order' => 3,
            ],
            [
                'name' => 'Claude 3 Sonnet',
                'model_id' => 'anthropic/claude-3-sonnet',
                'provider' => 'openrouter',
                'supports_images' => true,
                'is_active' => true,
                'max_tokens' => 4096,
                'temperature' => 0.7,
                'description' => 'Balanced performance and cost Claude model',
                'order' => 4,
            ],
            [
                'name' => 'GPT-3.5 Turbo',
                'model_id' => 'openai/gpt-3.5-turbo',
                'provider' => 'openrouter',
                'supports_images' => false,
                'is_active' => true,
                'max_tokens' => 4096,
                'temperature' => 0.7,
                'description' => 'Fast and cost-effective model for most tasks',
                'order' => 5,
            ],
            [
                'name' => 'Llama 3 70B',
                'model_id' => 'meta-llama/llama-3-70b-instruct',
                'provider' => 'openrouter',
                'supports_images' => false,
                'is_active' => true,
                'max_tokens' => 4096,
                'temperature' => 0.7,
                'description' => 'Open source high-performance model',
                'order' => 6,
            ],
            [
                'name' => 'Llama 3.2 3B (Free)',
                'model_id' => 'meta-llama/llama-3.2-3b-instruct:free',
                'provider' => 'openrouter',
                'supports_images' => false,
                'is_active' => true,
                'max_tokens' => 4096,
                'temperature' => 0.7,
                'description' => 'Free tier Llama model - reliable and fast',
                'order' => 7,
            ],
            [
                'name' => 'Mixtral 8x7B (Free)',
                'model_id' => 'mistralai/mixtral-8x7b-instruct:free',
                'provider' => 'openrouter',
                'supports_images' => false,
                'is_active' => true,
                'max_tokens' => 4096,
                'temperature' => 0.7,
                'description' => 'Free tier Mixtral model - good performance',
                'order' => 8,
            ],
        ];

        foreach ($models as $model) {
            AiModel::create($model);
        }
    }
}