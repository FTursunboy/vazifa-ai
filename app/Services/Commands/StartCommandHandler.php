<?php

namespace App\Services\Commands;

use App\Models\Message;
use App\Models\User;
use App\Services\TelegramService;
use App\Services\UserService;
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
        $welcomeMessage = "Привет! 👋 Я ваш персональный ассистент. Чтобы пользоваться ботом, пожалуйста, зарегистрируйтесь.";

        $this->telegramService->sendMessage($chatId, $welcomeMessage);

        $userService = new UserService($this->telegramService);

        $user = User::where('telegram_id', $chatId)->first();
        if (!$user->is_atuhed) {
            $userService->handleUserAuthorization($user, '', $chatId);
        }
        return ['status' => 'ok'];
    }
}
