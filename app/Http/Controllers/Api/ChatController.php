<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Chat;
use App\Services\OpenRouterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    protected OpenRouterService $openRouterService;

    public function __construct(OpenRouterService $openRouterService)
    {
        $this->openRouterService = $openRouterService;
    }

    public function getModels()
    {
        $models = AiModel::active()
            ->ordered()
            ->select('id', 'name', 'description', 'supports_images')
            ->get();

        return response()->json([
            'models' => $models,
        ]);
    }

    public function getChats(Request $request)
    {
        $userId = Auth::id();
        $anonymousId = $this->getAnonymousId($request);

        $chats = Chat::with('aiModel:id,name')
            ->forUser($userId, $anonymousId)
            ->orderBy('last_activity_at', 'desc')
            ->get(['id', 'uuid', 'title', 'ai_model_id', 'last_activity_at']);

        return response()->json([
            'chats' => $chats,
        ]);
    }

    public function getChat(Request $request, $uuid)
    {
        $userId = Auth::id();
        $anonymousId = $this->getAnonymousId($request);

        $chat = Chat::with(['aiModel', 'messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])
            ->where('uuid', $uuid)
            ->where(function ($query) use ($userId, $anonymousId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    // For anonymous users, allow access to any chat for cross-domain compatibility
                    $query->where(function ($subQuery) use ($anonymousId) {
                        $subQuery->where('anonymous_id', $anonymousId)
                                 ->orWhereNull('user_id');
                    });
                }
            })
            ->firstOrFail();

        return response()->json([
            'chat' => $chat,
        ]);
    }

    public function createChat(Request $request)
    {
        $request->validate([
            'ai_model_id' => 'required|exists:ai_models,id',
        ]);

        $userId = Auth::id();
        $anonymousId = $this->getAnonymousId($request);

        $chat = Chat::create([
            'user_id' => $userId,
            'anonymous_id' => $anonymousId,
            'ai_model_id' => $request->ai_model_id,
        ]);

        $chat->load('aiModel');

        return response()->json([
            'chat' => $chat,
        ]);
    }

    public function sendMessage(Request $request, $uuid)
    {
        // Increase execution time for message requests
        set_time_limit(120);
        
        $request->validate([
            'message' => 'required|string|max:10000',
            'images' => 'nullable|array',
            'images.*' => 'string',
        ]);

        $userId = Auth::id();
        $anonymousId = $this->getAnonymousId($request);

        $chat = Chat::with('aiModel')
            ->where('uuid', $uuid)
            ->where(function ($query) use ($userId, $anonymousId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    // For anonymous users, allow access to any chat for cross-domain compatibility
                    $query->where(function ($subQuery) use ($anonymousId) {
                        $subQuery->where('anonymous_id', $anonymousId)
                                 ->orWhereNull('user_id');
                    });
                }
            })
            ->firstOrFail();

        if (!empty($request->images) && !$chat->aiModel->supports_images) {
            return response()->json([
                'error' => 'This model does not support image inputs.',
            ], 422);
        }

        try {
            $response = $this->openRouterService->sendMessage(
                $chat,
                $request->message,
                $request->images ?? []
            );

            return response()->json([
                'response' => $response,
            ]);
        } catch (\Exception $e) {
            Log::error('Chat message error: ' . $e->getMessage());
            
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function streamMessage(Request $request, $uuid)
    {
        // Increase execution time for streaming requests
        set_time_limit(300);
        
        $request->validate([
            'message' => 'required|string|max:10000',
            'images' => 'nullable|array',
            'images.*' => 'string',
        ]);

        $userId = Auth::id();
        $anonymousId = $this->getAnonymousId($request);

        $chat = Chat::with('aiModel')
            ->where('uuid', $uuid)
            ->where(function ($query) use ($userId, $anonymousId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    // For anonymous users, allow access to any chat for cross-domain compatibility
                    $query->where(function ($subQuery) use ($anonymousId) {
                        $subQuery->where('anonymous_id', $anonymousId)
                                 ->orWhereNull('user_id');
                    });
                }
            })
            ->firstOrFail();

        if (!empty($request->images) && !$chat->aiModel->supports_images) {
            return response()->json([
                'error' => 'This model does not support image inputs.',
            ], 422);
        }

        return new StreamedResponse(function () use ($chat, $request) {
            // Disable all output buffering for real-time streaming
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set headers for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');
            
            // Send initial connection confirmation
            echo "data: " . json_encode(['type' => 'start', 'status' => 'connected']) . "\n\n";
            flush();
            
            try {
                Log::info('Starting streaming for chat: ' . $chat->uuid);
                
                // Call the OpenRouter service which handles the actual streaming
                $this->openRouterService->streamChat(
                    $chat,
                    $request->message,
                    $request->images ?? []
                );
                
                Log::info('Completed streaming for chat: ' . $chat->uuid);
                
            } catch (\Exception $e) {
                Log::error('Streaming error for chat ' . $chat->uuid . ': ' . $e->getMessage(), [
                    'exception' => $e,
                    'message' => $request->message,
                    'model' => $chat->aiModel->name
                ]);
                
                // Send error message in SSE format
                $errorData = [
                    'type' => 'error',
                    'error' => $this->getReadableErrorMessage($e->getMessage())
                ];
                
                echo "data: " . json_encode($errorData) . "\n\n";
                flush();
            }
            
            // Send completion signal
            echo "data: [DONE]\n\n";
            flush();
            
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Cache-Control',
        ]);
    }

    private function getReadableErrorMessage(string $error): string
    {
        if (str_contains($error, 'rate_limit') || str_contains($error, 'rate-limited')) {
            return 'The AI model is temporarily rate-limited. Please wait a moment and try again.';
        }
        
        if (str_contains($error, 'insufficient_quota') || str_contains($error, 'quota')) {
            return 'API quota exceeded. Please check your OpenRouter account or try again later.';
        }
        
        if (str_contains($error, 'model_not_found') || str_contains($error, 'not found')) {
            return 'The selected AI model is currently unavailable. Please try a different model.';
        }
        
        if (str_contains($error, 'timeout') || str_contains($error, 'timed out')) {
            return 'Request timed out. Please try again with a shorter message.';
        }
        
        if (str_contains($error, 'connection') || str_contains($error, 'network')) {
            return 'Connection error. Please check your internet connection and try again.';
        }
        
        if (str_contains($error, 'authentication') || str_contains($error, 'unauthorized')) {
            return 'Authentication error. Please check your API configuration.';
        }
        
        // Generic fallback
        return 'Sorry, I encountered an error while processing your request. Please try again.';
    }

    public function updateModel(Request $request, $uuid)
    {
        $request->validate([
            'ai_model_id' => 'required|exists:ai_models,id',
        ]);

        $userId = Auth::id();
        $anonymousId = $this->getAnonymousId($request);

        $chat = Chat::where('uuid', $uuid)
            ->where(function ($query) use ($userId, $anonymousId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    // For anonymous users, allow access to any chat for cross-domain compatibility
                    $query->where(function ($subQuery) use ($anonymousId) {
                        $subQuery->where('anonymous_id', $anonymousId)
                                 ->orWhereNull('user_id');
                    });
                }
            })
            ->firstOrFail();

        $chat->update([
            'ai_model_id' => $request->ai_model_id,
        ]);

        $chat->load('aiModel');

        return response()->json([
            'chat' => $chat,
            'message' => 'Model updated successfully.',
        ]);
    }

    public function deleteChat(Request $request, $uuid)
    {
        $userId = Auth::id();
        $anonymousId = $this->getAnonymousId($request);

        $chat = Chat::where('uuid', $uuid)
            ->where(function ($query) use ($userId, $anonymousId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    // For anonymous users, allow access to any chat for cross-domain compatibility
                    $query->where(function ($subQuery) use ($anonymousId) {
                        $subQuery->where('anonymous_id', $anonymousId)
                                 ->orWhereNull('user_id');
                    });
                }
            })
            ->firstOrFail();

        $chat->delete();

        return response()->json([
            'message' => 'Chat deleted successfully.',
        ]);
    }

    protected function getAnonymousId(Request $request): ?string
    {
        if (Auth::check()) {
            return null;
        }

        $anonymousId = $request->session()->get('anonymous_id');

        if (!$anonymousId) {
            $anonymousId = Str::uuid()->toString();
            $request->session()->put('anonymous_id', $anonymousId);
            Cache::put('anonymous_' . $anonymousId, true, now()->addDays(30));
        }

        return $anonymousId;
    }

    public function mergeAnonymousChats(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $anonymousId = $request->session()->get('anonymous_id');

        if ($anonymousId) {
            Chat::where('anonymous_id', $anonymousId)
                ->whereNull('user_id')
                ->update([
                    'user_id' => Auth::id(),
                    'anonymous_id' => null,
                ]);

            $request->session()->forget('anonymous_id');
            Cache::forget('anonymous_' . $anonymousId);

            return response()->json([
                'message' => 'Anonymous chats merged successfully.',
            ]);
        }

        return response()->json([
            'message' => 'No anonymous chats to merge.',
        ]);
    }
}