<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    protected WebSearchService $webSearchService;

    public function __construct(WebSearchService $webSearchService)
    {
        $this->webSearchService = $webSearchService;
    }

    public function streamChat(Chat $chat, string $userMessage, array $images = []): void
    {
        set_time_limit(600);

        $aiModel = $chat->aiModel;
        $messages = $this->prepareMessages($chat, $userMessage, $images);

        // Save user message
        Message::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => $userMessage,
            'images' => $images,
        ]);

        $chat->updateActivity();

        if (! $chat->title) {
            $chat->generateTitle();
        }

        $assistantMessage = '';

        try {
            $payload = [
                'model' => $aiModel->model_id,
                'messages' => $messages,
                'max_tokens' => min($aiModel->max_tokens ?? 4096, 4096),
                'temperature' => $aiModel->temperature ?? 0.7,
                'stream' => true,
            ];

            Log::info('Starting DeepSeek streaming for chat: '.$chat->id, [
                'model' => $aiModel->model_id,
                'message_count' => count($messages),
                'payload' => $payload,
            ]);

            if (! config('services.deepseek.api_key')) {
                throw new \Exception('DeepSeek API key is not configured');
            }

            $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

            // Use cURL for streaming
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $baseUrl.'/chat/completions',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer '.config('services.deepseek.api_key'),
                    'Content-Type: application/json',
                ],
                CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$assistantMessage) {
                    static $buffer = '';
                    $buffer .= $data;

                    // Process complete lines
                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line = substr($buffer, 0, $pos);
                        $buffer = substr($buffer, $pos + 1);

                        $line = trim($line);
                        if (empty($line) || strpos($line, 'data: ') !== 0) {
                            continue;
                        }

                        $jsonData = substr($line, 6);

                        if ($jsonData === '[DONE]') {
                            Log::info('Received [DONE] signal');

                            return strlen($data);
                        }

                        try {
                            $json = json_decode($jsonData, true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                continue;
                            }

                            if (isset($json['choices'][0]['delta']['content'])) {
                                $content = $json['choices'][0]['delta']['content'];
                                $assistantMessage .= $content;

                                // Send clean content chunk as JSON
                                echo 'data: '.json_encode(['content' => $content])."\n\n";
                                if (ob_get_level()) {
                                    ob_flush();
                                }
                                flush();
                            }

                            // Check for errors in the stream
                            if (isset($json['error'])) {
                                Log::error('DeepSeek streaming error in response: '.json_encode($json['error']));
                                throw new \Exception('API Error: '.($json['error']['message'] ?? 'Unknown error'));
                            }

                        } catch (\Exception $e) {
                            Log::warning('Failed to parse DeepSeek streaming chunk: '.$e->getMessage());
                        }
                    }

                    return strlen($data);
                },
                CURLOPT_TIMEOUT => 600,
                CURLOPT_CONNECTTIMEOUT => 30,
            ]);

            Log::info('Starting DeepSeek cURL streaming request');
            $result = curl_exec($ch);

            if ($result === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new \Exception('cURL error: '.$error);
            }

            curl_close($ch);

            Log::info('DeepSeek streaming completed, message length: '.strlen($assistantMessage));

            // Save assistant message
            if (! empty($assistantMessage)) {
                Message::create([
                    'chat_id' => $chat->id,
                    'role' => 'assistant',
                    'content' => $assistantMessage,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('DeepSeek streaming error: '.$e->getMessage(), [
                'chat_id' => $chat->id,
                'model' => $aiModel->model_id,
                'exception_class' => get_class($e),
            ]);

            // If streaming fails, try fallback to non-streaming
            if (empty($assistantMessage) && ! str_contains($e->getMessage(), 'rate-limited')) {
                Log::info('Attempting fallback to non-streaming mode');
                try {
                    $fallbackResponse = $this->sendMessage($chat, $userMessage, $images);
                    if (! empty($fallbackResponse)) {
                        echo 'data: '.$fallbackResponse."\n\n";
                        flush();
                        Log::info('Fallback successful');

                        return;
                    }
                } catch (\Exception $fallbackError) {
                    Log::warning('Fallback also failed: '.$fallbackError->getMessage());
                }
            }

            $errorMessage = 'Sorry, I encountered an error while processing your request.';

            if (str_contains($e->getMessage(), 'rate-limited') || str_contains($e->getMessage(), 'Rate limit')) {
                $errorMessage = 'The AI model is temporarily rate-limited. Please try again in a few minutes.';
            } elseif (str_contains($e->getMessage(), 'insufficient_quota')) {
                $errorMessage = 'API quota exceeded. Please check your DeepSeek account.';
            } elseif (str_contains($e->getMessage(), 'model_not_found')) {
                $errorMessage = 'The selected AI model is not available.';
            } elseif (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'Operation timed out')) {
                $errorMessage = 'The AI service is taking longer than expected due to large search results. Please try a more specific question or wait a moment before trying again.';
            } elseif (str_contains($e->getMessage(), 'cURL error')) {
                $errorMessage = 'Network connection issue. Please check your internet connection and try again.';
            } elseif (str_contains($e->getMessage(), 'Authentication failed') || str_contains($e->getMessage(), 'API key')) {
                $errorMessage = 'Authentication failed. Please check your API key configuration.';
            } elseif (str_contains($e->getMessage(), 'not configured')) {
                $errorMessage = 'DeepSeek API key is not configured. Please add DEEPSEEK_API_KEY to your .env file.';
            }

            echo 'data: '.json_encode(['error' => $errorMessage])."\n\n";
            flush();
        }
    }

    public function sendMessage(Chat $chat, string $userMessage, array $images = []): string
    {
        set_time_limit(300);

        $aiModel = $chat->aiModel;
        $messages = $this->prepareMessages($chat, $userMessage, $images);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => $userMessage,
            'images' => $images,
        ]);

        $chat->updateActivity();

        if (! $chat->title) {
            $chat->generateTitle();
        }

        try {
            $payload = [
                'model' => $aiModel->model_id,
                'messages' => $messages,
                'max_tokens' => min($aiModel->max_tokens ?? 4096, 4096),
                'temperature' => $aiModel->temperature ?? 0.7,
                'stream' => false,
            ];

            Log::info('DeepSeek request', [
                'model' => $aiModel->model_id,
                'message_count' => count($messages),
                'chat_id' => $chat->id,
                'new_message_preview' => substr($userMessage, 0, 50).'...',
            ]);

            $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

            // Try with shorter timeout first
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('services.deepseek.api_key'),
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($baseUrl.'/chat/completions', $payload);

            if ($response->failed()) {
                throw new \Exception('DeepSeek API request failed: '.$response->body());
            }

            $responseData = $response->json();
            $assistantMessage = $responseData['choices'][0]['message']['content'] ?? '';

            Message::create([
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => $assistantMessage,
            ]);

            return $assistantMessage;

        } catch (\Exception $e) {
            Log::error('DeepSeek API error: '.$e->getMessage());

            $errorMessage = 'Sorry, I encountered an error while processing your request.';

            if (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'Operation timed out')) {
                $errorMessage = 'The AI service is taking longer than expected due to large search results. Please try a more specific question or wait a moment before trying again.';
            } elseif (str_contains($e->getMessage(), 'cURL error')) {
                $errorMessage = 'Network connection issue. Please check your internet connection and try again.';
            } elseif (str_contains($e->getMessage(), 'rate-limited')) {
                $errorMessage = 'The AI model is temporarily rate-limited. Please try again in a few moments.';
            } elseif (str_contains($e->getMessage(), 'insufficient_quota')) {
                $errorMessage = 'API quota exceeded. Please check your DeepSeek account.';
            } elseif (str_contains($e->getMessage(), 'Model Not Exist') || str_contains($e->getMessage(), 'model_not_found')) {
                $errorMessage = 'The selected AI model is temporarily unavailable. Please try a different model.';
            }

            throw new \Exception($errorMessage);
        }
    }

    protected function prepareMessages(Chat $chat, string $currentMessage, array $images = []): array
    {
        $messages = [];

        // Enhanced system prompt with web search capabilities
        $systemPrompt = $chat->systemPrompt ? $chat->systemPrompt->prompt : '';

        // Add web search instructions to system prompt
        $enhancedSystemPrompt = $systemPrompt."\n\n".$this->webSearchService->getEnhancedSystemPrompt();

        $messages[] = [
            'role' => 'system',
            'content' => $enhancedSystemPrompt,
        ];

        // ALWAYS search for current information for every message
        Log::info('Performing search for message', [
            'message' => substr($currentMessage, 0, 100),
        ]);

        $searchResults = $this->webSearchService->performSearch($currentMessage);

        if ($searchResults) {
            Log::info('Search results found', [
                'results_length' => strlen($searchResults),
            ]);

            // Add search results as context - force AI to use it
            $messages[] = [
                'role' => 'system',
                'content' => "🚨 **CRITICAL - USE THIS DATA ONLY** 🚨\n\nLATEST SEARCH RESULTS:\n\n".$searchResults."\n\n⚠️ **MANDATORY**: You MUST answer using ONLY this current information. Do NOT use your general knowledge. Base your entire response on these real-time search results.",
            ];
        } else {
            Log::warning('No search results found');

            // Even if no search results, tell AI to acknowledge this
            $messages[] = [
                'role' => 'system',
                'content' => '⚠️ **NO SEARCH RESULTS AVAILABLE** - Current market data could not be retrieved. Please acknowledge this limitation in your response.',
            ];
        }

        // No previous chat context - only respond to current message
        // This ensures each query is treated independently

        $currentMessageData = [
            'role' => 'user',
            'content' => $currentMessage,
        ];

        if (! empty($images) && $chat->aiModel->supports_images) {
            $currentMessageData['content'] = [
                ['type' => 'text', 'text' => $currentMessage],
            ];

            foreach ($images as $image) {
                $currentMessageData['content'][] = [
                    'type' => 'image_url',
                    'image_url' => ['url' => $image],
                ];
            }
        }

        $messages[] = $currentMessageData;

        return $messages;
    }
}
