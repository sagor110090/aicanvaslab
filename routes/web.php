<?php

use App\Http\Controllers\ChatPageController;
use App\Models\AiModel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

Route::get('/', function () {
    return redirect('/chat');
});

Route::get('/chat', [ChatPageController::class, 'index'])->name('chat.index');
Route::get('/chat/{uuid}', [ChatPageController::class, 'show'])->name('chat.show');

// Model testing routes
Route::get('/test-models', function () {
    $models = AiModel::active()->ordered()->get();
    $apiKey = config('services.openrouter.api_key');

    return view('test-models', compact('models', 'apiKey'));
});

Route::post('/test-models', function () {
    $models = AiModel::active()->ordered()->get();
    $apiKey = config('services.openrouter.api_key');
    $results = [];

    if (!$apiKey) {
        return response()->json(['error' => 'OpenRouter API key not configured']);
    }

    $testMessage = "Hello! Please respond with exactly: 'Test successful'";

    foreach ($models as $model) {
        try {
            $payload = [
                'model' => $model->model_id,
                'messages' => [
                    ['role' => 'user', 'content' => $testMessage]
                ],
                'max_tokens' => 50,
                'temperature' => 0.7,
                'stream' => false
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'X-Title' => 'AI Canvas Lab Test',
            ])->timeout(15)->post('https://openrouter.ai/api/v1/chat/completions', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? 'No content';

                $results[] = [
                    'model' => $model->name,
                    'model_id' => $model->model_id,
                    'status' => 'success',
                    'response' => $content,
                    'supports_images' => $model->supports_images
                ];
            } else {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $response->body();

                $results[] = [
                    'model' => $model->name,
                    'model_id' => $model->model_id,
                    'status' => 'failed',
                    'error' => $errorMessage,
                    'supports_images' => $model->supports_images
                ];
            }

        } catch (Exception $e) {
            $results[] = [
                'model' => $model->name,
                'model_id' => $model->model_id,
                'status' => 'exception',
                'error' => $e->getMessage(),
                'supports_images' => $model->supports_images
            ];
        }

        // Small delay to respect rate limits
        usleep(500000); // 0.5 seconds
    }

    return response()->json(['results' => $results]);
});


Route::get('/test-openrouter', function () {
  $response = Http::withToken(env('OPENROUTER_API_KEY'))
      ->post('https://openrouter.ai/api/v1/chat/completions', [
          'model' => 'qwen/qwen3-coder:free',
          'messages' => [
              ['role' => 'user', 'content' => 'Say: This is a free model test response.'],
          ],
      ]);

  return $response->json();
});
