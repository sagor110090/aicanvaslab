<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemPrompt;
use Illuminate\Http\Request;

class SystemPromptController extends Controller
{
    public function index()
    {
        $prompts = SystemPrompt::active()->ordered()->get(['id', 'name', 'description', 'prompt']);
        
        return response()->json([
            'prompts' => $prompts
        ]);
    }

    public function show($id)
    {
        $prompt = SystemPrompt::active()->findOrFail($id);
        
        return response()->json([
            'prompt' => $prompt
        ]);
    }
}