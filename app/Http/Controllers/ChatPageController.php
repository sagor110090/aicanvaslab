<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ChatPageController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $anonymousId = $this->getAnonymousId($request);

        if ($userId) {
            // For logged-in users, get their chats
            $chats = Chat::with('aiModel:id,name')
                ->where('user_id', $userId)
                ->orderBy('last_activity_at', 'desc')
                ->get(['id', 'uuid', 'title', 'ai_model_id', 'last_activity_at']);
        } else {
            // For anonymous users, show recent anonymous chats (not just current session)
            $chats = Chat::with('aiModel:id,name')
                ->whereNull('user_id')
                ->whereNotNull('anonymous_id')
                ->orderBy('last_activity_at', 'desc')
                ->limit(20) // Show last 20 anonymous chats
                ->get(['id', 'uuid', 'title', 'ai_model_id', 'last_activity_at']);
        }

        $models = AiModel::active()
            ->ordered()
            ->select('id', 'name', 'description', 'supports_images')
            ->get();

        return Inertia::render('Chat/Index', [
            'chats' => $chats,
            'models' => $models,
            'user' => Auth::user(),
        ]);
    }

    public function show(Request $request, $uuid)
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
                    // For anonymous users, allow access to any anonymous chat
                    $query->whereNull('user_id');
                }
            })
            ->firstOrFail();

        if ($userId) {
            // For logged-in users, get their chats
            $chats = Chat::with('aiModel:id,name')
                ->where('user_id', $userId)
                ->orderBy('last_activity_at', 'desc')
                ->get(['id', 'uuid', 'title', 'ai_model_id', 'last_activity_at']);
        } else {
            // For anonymous users, show recent anonymous chats
            $chats = Chat::with('aiModel:id,name')
                ->whereNull('user_id')
                ->whereNotNull('anonymous_id')
                ->orderBy('last_activity_at', 'desc')
                ->limit(20)
                ->get(['id', 'uuid', 'title', 'ai_model_id', 'last_activity_at']);
        }

        $models = AiModel::active()
            ->ordered()
            ->select('id', 'name', 'description', 'supports_images')
            ->get();

        return Inertia::render('Chat/Show', [
            'chat' => $chat,
            'chats' => $chats,
            'models' => $models,
            'user' => Auth::user(),
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
        }

        return $anonymousId;
    }
}