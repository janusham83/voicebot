<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

Route::get('/', function () {
    return view('welcome');
});


use App\Services\GeminiService;


Route::get('/test-gemini-service', function (GeminiService $gemini) {

    try {
        $result = $gemini->generateChatResponse([
            [
                'role' => 'user',
                'content' => 'Say hello in one short sentence.',
            ],
        ]);

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);

    } catch (\Throwable $e) {

        return response()->json([
            'success' => false,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});