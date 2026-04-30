<?php

namespace App\Services;

use App\Models\TelegramMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramPollingService
{
    private string $token;
    private string $baseUrl;

    public function __construct()
    {
        $this->token = config('telegram.bot_token');
        $this->baseUrl = config('telegram.api_url') . $this->token;
    }

    public function getUpdates(int $offset = 0): array
    {
        try {
            $response = Http::timeout(35)->get("{$this->baseUrl}/getUpdates", [
                'offset' => $offset,
                'timeout' => 30,
                'allowed_updates' => ['message']
            ]);

            if ($response->successful()) {
                return $response->json('result');
            }

            Log::error('Telegram getUpdates failed', ['response' => $response->body()]);
            return [];
        } catch (\Exception $e) {
            Log::error('Telegram getUpdates exception: ' . $e->getMessage());
            return [];
        }
    }

    public function saveMessage(array $update): ?TelegramMessage
    {
        if (!isset($update['message'])) {
            return null;
        }

        $msg = $update['message'];
        $from = $msg['from'] ?? [];
        $chat = $msg['chat'] ?? [];

        return TelegramMessage::updateOrCreate(
            ['message_id' => $msg['message_id']],
            [
                'chat_id' => $chat['id'] ?? null,
                'user_id' => $from['id'] ?? null,
                'username' => $from['username'] ?? null,
                'first_name' => $from['first_name'] ?? 'Unknown',
                'text' => $msg['text'] ?? '[медиа]',
                'message_type' => isset($msg['text']) ? 'text' : 'media',
                'received_at' => now()->setTimestamp($msg['date']),
            ]
        );
    }

    public function sendReply(int $chatId, string $text, ?int $replyToMessageId = null): ?array
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyToMessageId) {
            $params['reply_to_message_id'] = $replyToMessageId;
        }

        try {
            $response = Http::post("{$this->baseUrl}/sendMessage", $params);

            if ($response->successful()) {
                return $response->json('result');
            }

            Log::error('Telegram sendMessage failed', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Telegram sendMessage exception: ' . $e->getMessage());
            return null;
        }
    }

    public function getChatHistory(int $chatId, int $limit = 50)
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

    public function markAsRead(int $chatId): void
    {
        TelegramMessage::where('chat_id', $chatId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function assignToMe(int $chatId, int $userId): array
    {
        TelegramMessage::where('chat_id', $chatId)
            ->whereNull('assigned_to')
            ->update(['assigned_to' => $userId]);

        return $this->getChatInfo($chatId);
    }

    public function releaseChat(int $chatId): array
    {
        TelegramMessage::where('chat_id', $chatId)
            ->update(['assigned_to' => null]);

        return $this->getChatInfo($chatId);
    }

    public function getChatInfo(int $chatId): array
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

        return is_null($assigned) || $assigned == $userId;
    }
}