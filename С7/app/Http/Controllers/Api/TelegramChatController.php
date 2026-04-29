<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramPollingService;
use Illuminate\Http\Request;

class TelegramChatController extends Controller
{
    private $telegramService;

    public function __construct()
    {
        $this->telegramService = new TelegramPollingService();
    }

    public function getChats()
    {
        $chats = $this->telegramService->getAllChats();
        return response()->json($chats);
    }

    public function getMessages($chatId)
    {
        $this->telegramService->markAsRead($chatId);
        $messages = $this->telegramService->getChatHistory($chatId);
        $chatInfo = $this->telegramService->getChatInfo($chatId);
        
        return response()->json([
            'messages' => $messages,
            'assigned_to' => $chatInfo['assigned_to'],
            'assigned_user' => $chatInfo['assigned_user'],
            'can_reply' => $this->telegramService->canReply($chatId, auth()->id()),
        ]);
    }

    public function sendReply(Request $request, $chatId)
    {
        $userId = auth()->id();
        
        // Проверяем, может ли пользователь отвечать
        if (!$this->telegramService->canReply($chatId, $userId)) {
            return response()->json([
                'error' => 'Этот чат уже взят в работу другим оператором'
            ], 403);
        }

        $request->validate([
            'text' => 'required|string',
            'reply_to_message_id' => 'nullable|integer'
        ]);

        // Автоматически назначаем чат на отвечающего
        $this->telegramService->assignToMe($chatId, $userId);

        $result = $this->telegramService->sendReply(
            $chatId,
            $request->text,
            $request->reply_to_message_id
        );

        \App\Models\TelegramMessage::where('chat_id', $chatId)
            ->where('message_id', $request->reply_to_message_id)
            ->update([
                'is_replied' => true,
                'reply_text' => $request->text
            ]);

        return response()->json(['status' => 'sent', 'result' => $result]);
    }

    public function assignToMe($chatId)
    {
        $userId = auth()->id();
        
        $assigned = $this->telegramService->isAssignedToUser($chatId, $userId);
        if ($assigned) {
            return response()->json(['message' => 'Чат уже ваш']);
        }
        
        // Проверяем, не назначен ли уже другому
        $chatInfo = $this->telegramService->getChatInfo($chatId);
        if ($chatInfo['assigned_to'] && $chatInfo['assigned_to'] != $userId) {
            return response()->json([
                'error' => 'Чат уже взят оператором ' . $chatInfo['assigned_user']
            ], 403);
        }

        $result = $this->telegramService->assignToMe($chatId, $userId);
        return response()->json($result);
    }

    public function releaseChat($chatId)
    {
        $result = $this->telegramService->releaseChat($chatId);
        return response()->json($result);
    }
}