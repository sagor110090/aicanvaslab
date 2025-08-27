<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🤖 AI Model Testing Script\n";
echo "========================\n\n";

// Get API key
$apiKey = config('services.openrouter.api_key');
if (!$apiKey) {
    echo "❌ Error: OPENROUTER_API_KEY not configured in .env file\n";
    exit(1);
}

echo "✅ API Key found: " . substr($apiKey, 0, 10) . "...\n\n";

// Get all models from database
$models = DB::table('ai_models')->where('is_active', true)->orderBy('name')->get();

if ($models->isEmpty()) {
    echo "❌ No active models found in database\n";
    exit(1);
}

echo "📊 Found " . $models->count() . " active models to test\n\n";

$testMessage = "Hello! Please respond with exactly: 'Test successful for [MODEL_NAME]'";
$results = [];

foreach ($models as $model) {
    echo "🧪 Testing: {$model->name} ({$model->model_id})\n";
    echo "   Description: {$model->description}\n";
    echo "   Supports Images: " . ($model->supports_images ? 'Yes' : 'No') . "\n";
    
    try {
        // Test the model with a simple request
        $payload = [
            'model' => $model->model_id,
            'messages' => [
                ['role' => 'user', 'content' => str_replace('[MODEL_NAME]', $model->name, $testMessage)]
            ],
            'max_tokens' => 100,
            'temperature' => 0.7,
            'stream' => false
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'X-Title' => 'AI Canvas Lab Test',
        ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', $payload);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? 'No content';
            $usage = $data['usage'] ?? null;
            
            echo "   ✅ SUCCESS\n";
            echo "   📝 Response: " . substr($content, 0, 100) . (strlen($content) > 100 ? '...' : '') . "\n";
            
            if ($usage) {
                echo "   💰 Usage: {$usage['prompt_tokens']} prompt + {$usage['completion_tokens']} completion = {$usage['total_tokens']} total tokens\n";
            }
            
            $results[] = [
                'model' => $model->name,
                'model_id' => $model->model_id,
                'status' => 'SUCCESS',
                'response' => $content,
                'usage' => $usage
            ];
        } else {
            $errorBody = $response->body();
            $errorData = json_decode($errorBody, true);
            $errorMessage = $errorData['error']['message'] ?? $errorBody;
            
            echo "   ❌ FAILED (HTTP {$response->status()})\n";
            echo "   📝 Error: {$errorMessage}\n";
            
            $results[] = [
                'model' => $model->name,
                'model_id' => $model->model_id,
                'status' => 'FAILED',
                'error' => $errorMessage,
                'http_status' => $response->status()
            ];
        }
        
    } catch (Exception $e) {
        echo "   ❌ EXCEPTION\n";
        echo "   📝 Error: {$e->getMessage()}\n";
        
        $results[] = [
            'model' => $model->name,
            'model_id' => $model->model_id,
            'status' => 'EXCEPTION',
            'error' => $e->getMessage()
        ];
    }
    
    echo "\n";
    sleep(1); // Rate limiting - wait 1 second between requests
}

// Summary
echo "📊 TEST SUMMARY\n";
echo "===============\n\n";

$successful = array_filter($results, fn($r) => $r['status'] === 'SUCCESS');
$failed = array_filter($results, fn($r) => $r['status'] !== 'SUCCESS');

echo "✅ Successful: " . count($successful) . " models\n";
echo "❌ Failed: " . count($failed) . " models\n\n";

if (!empty($successful)) {
    echo "🟢 WORKING MODELS:\n";
    foreach ($successful as $result) {
        echo "   • {$result['model']} ({$result['model_id']})\n";
    }
    echo "\n";
}

if (!empty($failed)) {
    echo "🔴 FAILED MODELS:\n";
    foreach ($failed as $result) {
        $error = $result['error'] ?? 'Unknown error';
        echo "   • {$result['model']} ({$result['model_id']}) - {$error}\n";
    }
    echo "\n";
}

// Save detailed results to file
$timestamp = date('Y-m-d_H-i-s');
$reportFile = __DIR__ . "/model_test_report_{$timestamp}.json";
file_put_contents($reportFile, json_encode($results, JSON_PRETTY_PRINT));

echo "📄 Detailed report saved to: {$reportFile}\n";

// Update database to disable failed models (optional)
echo "\n❓ Would you like to disable the failed models in the database? (y/N): ";
$handle = fopen("php://stdin", "r");
$input = trim(fgets($handle));
fclose($handle);

if (strtolower($input) === 'y' || strtolower($input) === 'yes') {
    $failedModelIds = array_column($failed, 'model_id');
    if (!empty($failedModelIds)) {
        DB::table('ai_models')
            ->whereIn('model_id', $failedModelIds)
            ->update(['is_active' => false, 'updated_at' => now()]);
        
        echo "✅ Disabled " . count($failedModelIds) . " failed models in database\n";
    }
} else {
    echo "ℹ️  Models left unchanged in database\n";
}

echo "\n🎉 Testing completed!\n";