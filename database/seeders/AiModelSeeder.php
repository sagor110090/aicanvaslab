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
                'name' => 'DeepSeek R1',
                'model_id' => 'deepseek-r1',
                'supports_images' => false,
                'is_active' => true,
                'max_tokens' => 8192,
                'temperature' => 0.7,
                'description' => 'Latest DeepSeek model with enhanced reasoning and coding capabilities',
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
