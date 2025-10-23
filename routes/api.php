<?php

use App\Http\Controllers\Api\ChatController;

use App\Http\Controllers\Api\SystemPromptController;
use Illuminate\Support\Facades\Route;

Route::prefix('chats')->group(function () {
    Route::get('models', [ChatController::class, 'getModels']);
    Route::get('/', [ChatController::class, 'getChats'])->middleware('rate.limit:30,1');
    Route::post('/', [ChatController::class, 'createChat'])->middleware('rate.limit:10,1');
    Route::get('/{uuid}', [ChatController::class, 'getChat'])->middleware('rate.limit:60,1');
    Route::post('/{uuid}/message', [ChatController::class, 'sendMessage'])->middleware('rate.limit:20,5');
    Route::match(['get', 'post'], '/{uuid}/stream', [ChatController::class, 'streamMessage'])->middleware('rate.limit:15,5');
    Route::put('/{uuid}/model', [ChatController::class, 'updateModel'])->middleware('rate.limit:20,1');
    Route::put('/{uuid}/system-prompt', [ChatController::class, 'updateSystemPrompt'])->middleware('rate.limit:20,1');
    Route::delete('/{uuid}', [ChatController::class, 'deleteChat'])->middleware('rate.limit:30,1');
    Route::post('/merge-anonymous', [ChatController::class, 'mergeAnonymousChats'])->middleware('rate.limit:5,1');
});

Route::prefix('system-prompts')->group(function () {
    Route::get('/', [SystemPromptController::class, 'index']);
    Route::get('/{id}', [SystemPromptController::class, 'show']);
});


