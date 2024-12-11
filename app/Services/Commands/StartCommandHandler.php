<?php

namespace App\Services\Commands;

use App\Models\Message;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;

class StartCommandHandler
{
    private $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function run(int $chatId)
    {
        $welcomeMessage = "Привет! 👋 Я ваш персональный ассистент. " .
            "Для начала работы, пожалуйста, предоставьте вашу электронную почту.";

        $this->telegramService->sendMessage($chatId, $welcomeMessage);

        return ['status' => 'ok'];
    }
}
