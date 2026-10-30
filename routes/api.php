<?php

use App\Http\Controllers\Api\ChatbotApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'check.2fa'])->group(function () {
    Route::post('/chatbot/send', [ChatbotApiController::class, 'sendMessage'])
        ->name('api.chatbot.send');

    Route::get('/chatbot/history', [ChatbotApiController::class, 'getHistory'])
        ->name('api.chatbot.history');
});
