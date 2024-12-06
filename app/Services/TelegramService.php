<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class TelegramService {
    private $botToken;

    public function __construct() {
        $this->botToken = config('services.telegram.bot_token');
    }

    public function sendMessage(string $chatId, string $text) {
        Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }
}
