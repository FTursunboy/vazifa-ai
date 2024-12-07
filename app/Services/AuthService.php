<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class AuthService
{
    private TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function isUserAuthorized(User $user) :bool
    {
        return $user->email !== null;
    }

    public function requestAuthorization(int $chatId, User $user) :bool
    {
        $this->telegramService->sendMessage(
            $chatId,
            "Пожалуйста, предоставьте вашу электронную почту для авторизации:"
        );


        $user->state = 'ask_email';
        $user->save();

        return true;
    }

    public function validateEmail(User $user, string $email) :bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $this->telegramService->sendMessage(
                $user->telegram_id,
                "Пожалуйста, валидный адрес электронной почты"
            );

            return false;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->telegramService->sendMessage($user->telegram_id, 'Пользователь с таким адресом электронной почты уже зарегестрирован');
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-AppApiToken' => config('services.vazifa.api_key') ,
            ])->post('https://vazifa.tj/api/checkUserRole', [
                'email' => $email
            ]);

            if ($response->status() === 422) {
                $this->telegramService->sendMessage(
                    $user->telegram_id,
                    "Пожалуйста, сначала зарегистрируйтесь на vazifa.tj"
                );
                return false;
            }

            $userData = $response->json();

            $user->email = $email;
            $user->state = 'registered';
            $user->save();

            $this->updateUserRequestLimit($user, $userData['tariff']);

            $this->telegramService->sendMessage($user->telegram_id, "Поздравляю, Вы успешно авторизовались. У вас {$user->daily_request_limit} в день");
            $this->telegramService->sendMessage($user->telegram_id, "Можете задавать свои вопросы");

            return true;
        } catch (\Exception $e) {
           dd($e->getMessage());
        }
    }

    public function checkRequestLimit(User $user) :bool
    {
        return $user->daily_requests_used < $user->daily_request_limit;
    }

    private function updateUserRequestLimit(User $user, string $tariff) :void
    {
        switch ($tariff) {
            case '3':
                $user->daily_request_limit = 50;
                break;
            default:
                $user->daily_request_limit = 3;
                break;
        }

        $user->daily_requests_reset_at = now()->addDay();
        $user->daily_requests_used = 0;
        $user->save();
    }

    public function incrementRequestCount(User $user) :void
    {
        $user->increment('daily_requests_used');
    }

    public function checkCode(User $user, string $email)
    {

    }
}
