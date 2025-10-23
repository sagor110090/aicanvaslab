<?php

namespace Database\Seeders;

use App\Models\AiModel;
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
                'name' => 'DeepSeek Reasoner',
                'model_id' => 'deepseek-reasoner',
                'supports_images' => false,
                'is_active' => true,
                'max_tokens' => 8192,
                'temperature' => 0.7,
                'description' => 'DeepSeek\'s reasoning model with enhanced logical thinking capabilities',
                'order' => 1,
            ],
            [
                'name' => 'DeepSeek Chat',
                'model_id' => 'deepseek-chat',
                'supports_images' => false,
                'is_active' => true,
                'max_tokens' => 8192,
                'temperature' => 0.7,
                'description' => 'DeepSeek\'s optimized chat model for conversational AI',
                'order' => 2,
            ],
            [
                'name' => 'DeepSeek Coder',
                'model_id' => 'deepseek-coder',
                'supports_images' => false,
                'is_active' => true,
                'max_tokens' => 8192,
                'temperature' => 0.7,
                'description' => 'Specialized DeepSeek model for programming and code generation',
                'order' => 3,
            ],
        ];

        foreach ($models as $model) {
            AiModel::create($model);
        }
    }
}
