<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TelegramChatController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'userProfile']);

    // Telegram чаты
    Route::get('/chats', [TelegramChatController::class, 'getChats']);
    Route::get('/chats/{chatId}/messages', [TelegramChatController::class, 'getMessages']);
    Route::post('/chats/{chatId}/reply', [TelegramChatController::class, 'sendReply']);
    Route::post('/chats/{chatId}/assign', [TelegramChatController::class, 'assignToMe']);
    Route::post('/chats/{chatId}/release', [TelegramChatController::class, 'releaseChat']);
});