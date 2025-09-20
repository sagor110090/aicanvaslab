<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\SystemPromptController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;

Route::prefix('chats')->group(function () {
    Route::get('models', [ChatController::class, 'getModels']);
    Route::get('/', [ChatController::class, 'getChats']);
    Route::post('/', [ChatController::class, 'createChat']);
    Route::get('/{uuid}', [ChatController::class, 'getChat']);
    Route::post('/{uuid}/message', [ChatController::class, 'sendMessage']);
    Route::post('/{uuid}/stream', [ChatController::class, 'streamMessage']);
    Route::put('/{uuid}/model', [ChatController::class, 'updateModel']);
    Route::put('/{uuid}/system-prompt', [ChatController::class, 'updateSystemPrompt']);
    Route::delete('/{uuid}', [ChatController::class, 'deleteChat']);
    Route::post('/merge-anonymous', [ChatController::class, 'mergeAnonymousChats']);
});

Route::prefix('system-prompts')->group(function () {
    Route::get('/', [SystemPromptController::class, 'index']);
    Route::get('/{id}', [SystemPromptController::class, 'show']);
});

