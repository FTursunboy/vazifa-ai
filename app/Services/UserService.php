<?php

namespace App\Services;

use App\Facades\OpenAI;
use App\Facades\VazifaAPI;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserService
{
    private $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function findOrCreateFromTelegram(array $telegramData): User
    {
        return User::firstOrCreate(
            ['telegram_id' => $telegramData['message']['from']['id']],
            [
                'tg_name' => trim(($telegramData['message']['from']['first_name'] ?? '') . ' ' . ($telegramData['message']['from']['last_name'] ?? '')),
                'tg_nick' => $telegramData['message']['from']['username'] ?? rand(1, 1000000),
                'last_seen' => now(),
                'is_authed' => false,
                'state' => 'new',
                'daily_requests_used' => 0
            ]
        );
    }

    public function handleUserAuthorization(User $user, string $text, int $userId): bool
    {
        $requiredFields = [
            'name' => 'введите ваше имя',
            'position' => 'введите вашу должность',
            'workplace' => 'введите ваше место работы',
            'phone' => 'поделитесь своим номером телефона',
            'email' => 'введите вашу электронную почту'
        ];

        if ($user->state == 'new') {
            $this->telegramService->sendMessage($userId, "Пожалуйста, {$requiredFields['name']}.");
            $user->state = 'ask_name';
            $user->save();
            return true;
        }

        foreach ($requiredFields as $field => $fieldPrompt) {
            if (empty($user->{$field})) {


                if ($field === 'phone') {
                    $this->telegramService->sendContactRequest($userId, "Пожалуйста, поделитесь своим номером телефона");
                    $user->state = $field;
                    $user->save();
                    return true;
                }
                if (!$this->validateField($field, $text)) {
                    $this->telegramService->sendMessage($userId, "Неверный формат данных");
                    return true;
                }

                $user->{$field} = $text;
                $user->state = $field;
                $user->save();

                $nextField = $this->getNextEmptyField($user, array_keys($requiredFields));
                if ($nextField) {
                    if ($nextField === 'phone') {
                        $this->telegramService->sendContactRequest($userId, "Пожалуйста, поделитесь своим номером телефона");
                    } else {
                        $this->telegramService->sendMessage($userId, "Пожалуйста, {$requiredFields[$nextField]}.");
                    }
                } else {
                    $user->is_authed = true;
                    $user->save();

                    $this->checkPremiumAccess($user->email);

                    $this->telegramService->sendMessage($userId, "Вы успешно авторизованы.");
                    $this->telegramService->sendMessage($userId, "Можете задавать свои вопросы");
                }
                return true;
            }
        }

        return false;
    }
    private function getNextEmptyField(User $user, array $fields): ?string
    {
        foreach ($fields as $field) {
            if (empty($user->{$field})) {
                return $field;
            }
        }

        return null;
    }


    public function checkPremiumAccess(string $email): bool
    {
        return VazifaAPI::checkPremiumAccess($email);
    }

    private function validateField(string $field, string $value): bool
    {
        switch ($field) {
            case 'name':
            case 'position':
                return mb_strlen($value) >= 3;
            case 'phone':
                return preg_match('/^\d{9,}$/', $value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;

            default:
                return true;
        }
    }

    public function getOrCreateConversation(User $user): Conversation
    {
        return Conversation::firstOrCreate(
            ['user_id' => $user->id],
            [
                'thread_id' => OpenAI::createThread($user->id, $user->telegram_id),
                'status' => 'ready'
            ]
        );
    }

    public function createUserMessage(Conversation $conversation, string $text, array $response)
    {
        return Message::create([
            'conversation_id' => $conversation->id,
            'content' => $text,
            'role' => 'user',
            'message_id' => $response['result']['message_id']
        ]);
    }


}
