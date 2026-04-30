<?php

namespace App\Services;

use App\Models\TelegramMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramPollingService
{
    private $token;
    private $baseUrl;

    public function __construct()
    {
        $this->token = env('TELEGRAM_BOT_TOKEN');
        $this->baseUrl = "https://api.telegram.org/bot{$this->token}";
    }

    public function getUpdates($offset = 0)
    {
        $response = Http::get("{$this->baseUrl}/getUpdates", [
            'offset' => $offset,
            'timeout' => 30,
            'allowed_updates' => ['message']
        ]);

        if ($response->successful()) {
            return $response->json('result');
        }

        Log::error('Telegram getUpdates failed', ['response' => $response->body()]);
        return [];
    }

    public function saveMessage($message)
    {
        if (!isset($message['message'])) {
            return null;
        }

        $msg = $message['message'];
        $from = $msg['from'] ?? [];
        $chat = $msg['chat'] ?? [];

        return TelegramMessage::updateOrCreate(
            ['message_id' => $msg['message_id']],
            [
                'chat_id' => $chat['id'] ?? null,
                'user_id' => $from['id'] ?? null,
                'username' => $from['username'] ?? null,
                'first_name' => $from['first_name'] ?? 'Unknown',
                'text' => $msg['text'] ?? '[photo/file]',
                'message_type' => isset($msg['text']) ? 'text' : 'media',
                'received_at' => now()->setTimestamp($msg['date']),
            ]
        );
    }

    public function sendReply($chatId, $text, $replyToMessageId = null)
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyToMessageId) {
            $params['reply_to_message_id'] = $replyToMessageId;
        }

        $response = Http::post("{$this->baseUrl}/sendMessage", $params);

        if ($response->successful()) {
            return $response->json('result');
        }

        Log::error('Telegram sendMessage failed', ['response' => $response->body()]);
        return null;
    }

    public function getChatHistory($chatId, $limit = 50)
    {
        return TelegramMessage::where('chat_id', $chatId)
            ->orderBy('received_at', 'desc')
            ->limit($limit)
            ->get();
    }

public function getAllChats()
{
    return TelegramMessage::select('chat_id', 'username', 'first_name')
        ->selectRaw('MAX(received_at) as last_message_at')
        ->selectRaw('COUNT(*) as total_messages')
        ->selectRaw('SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count')
        ->selectRaw('MAX(assigned_to) as assigned_to')
        ->groupBy('chat_id', 'username', 'first_name')
        ->orderBy('last_message_at', 'desc')
        ->get()
        ->map(function ($chat) {
            $chat->assigned_user = $chat->assigned_to 
                ? \App\Models\User::find($chat->assigned_to)->name ?? null 
                : null;
            return $chat;
        });
}

    public function markAsRead($chatId)
    {
        TelegramMessage::where('chat_id', $chatId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

public function assignToMe(int $chatId, int $userId)
{
    TelegramMessage::where('chat_id', $chatId)
        ->whereNull('assigned_to')
        ->update(['assigned_to' => $userId]);
    
    return $this->getChatInfo($chatId);
}

public function releaseChat(int $chatId)
{
    TelegramMessage::where('chat_id', $chatId)
        ->update(['assigned_to' => null]);
    
    return $this->getChatInfo($chatId);
}

public function getChatInfo(int $chatId)
{
    $lastMessage = TelegramMessage::where('chat_id', $chatId)->latest()->first();
    
    return [
        'chat_id' => $chatId,
        'assigned_to' => $lastMessage->assigned_to ?? null,
        'assigned_user' => $lastMessage->assignedUser->name ?? null,
    ];
}

public function isAssignedToUser(int $chatId, int $userId): bool
{
    $assigned = TelegramMessage::where('chat_id', $chatId)
        ->whereNotNull('assigned_to')
        ->value('assigned_to');
    
    return $assigned == $userId;
}

public function canReply(int $chatId, int $userId): bool
{
    $assigned = TelegramMessage::where('chat_id', $chatId)
        ->whereNotNull('assigned_to')
        ->value('assigned_to');
    
    // Можно отвечать, если чат не назначен или назначен на этого пользователя
    return is_null($assigned) || $assigned == $userId;
}

}