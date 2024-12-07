<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class TelegramService
{
    private $botToken;
    private $baseUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->baseUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    public function sendMessage(int $chatId, string $text)
    {
        return Http::post("{$this->baseUrl}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text
        ])->json();
    }

    public function sendTypingAction(int $chatId)
    {
        Http::post("{$this->baseUrl}/sendChatAction", [
            'chat_id' => $chatId,
            'action' => 'typing'
        ]);
    }

    public function editMessageText(int $chatId, int $messageId, string $text)
    {
        return Http::post("{$this->baseUrl}/editMessageText", [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text
        ])->json();
    }


}
