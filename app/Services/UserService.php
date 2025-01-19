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
                'tg_nick' => $telegramData['message']['from']['username'] ?? null ,
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
            'user_type' => 'укажите ваш тип: соискатель или работодатель',
        ];

        if ($user->state === 'new') {
            $this->telegramService->sendMessage($userId, "Пожалуйста, {$requiredFields['name']}.");
            $user->state = 'ask_name';
            $user->save();
            return true;
        }

        if ($user->state === 'ask_name' && empty($user->name)) {
            $user->name = $text;
            $user->state = 'ask_user_type';

            // Отправляем кнопки выбора типа
            $keyboard = [
                'keyboard' => [
                    [['text' => 'Соискатель'], ['text' => 'Работодатель']],
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ];

            $this->telegramService->sendMessage($userId, "Выберите ваш тип:", $keyboard);
            $user->save();
            return true;
        }

        if ($user->state === 'ask_user_type' && empty($user->user_type)) {
            if (!in_array(mb_strtolower($text), ['соискатель', 'работодатель'])) {
                $this->telegramService->sendMessage($userId, "Пожалуйста, выберите корректный тип: 'Соискатель' или 'Работодатель'.");
                return true;
            }

            $user->user_type = mb_strtolower($text) === 'соискатель' ? 'employee' : 'employer';
            $user->state = $user->user_type === 'employee' ? 'ask_phone' : 'ask_position';
            $user->save();

            if ($user->user_type === 'employee') {
                $this->telegramService->sendContactRequest($userId, "Пожалуйста, поделитесь своим номером телефона.");
            } else {
                $this->telegramService->sendMessage($userId, "Пожалуйста, введите вашу должность.");
            }

            return true;
        }

        return $this->handleFieldsByUserType($user, $text, $userId);
    }
    private function handleFieldsByUserType(User $user, string $text, int $userId): bool
    {
        // Поля для каждого типа пользователя
        $employerFields = [
            'position' => 'введите вашу должность',
            'workplace' => 'введите ваше место работы',
            'phone' => 'поделитесь своим номером телефона',
            'email' => 'введите вашу электронную почту',
        ];

        $employeeFields = [
            'phone' => 'поделитесь своим номером телефона',
            'email' => 'введите вашу электронную почту',
        ];

        // Определяем, какие поля запрашивать
        $requiredFields = $user->user_type === 'employer' ? $employerFields : $employeeFields;

        // Обрабатываем поля по очереди
        foreach ($requiredFields as $field => $fieldPrompt) {
            if (empty($user->{$field})) {
                // Если это поле "phone", всегда отправляем запрос с кнопкой
                if ($field === 'phone') {
                    if ($user->state !== 'phone') {
                        // Запрашиваем контакт через кнопку
                        $this->telegramService->sendContactRequest($userId, "Пожалуйста, {$fieldPrompt}.");
                        $user->state = 'phone';
                        $user->save();
                    }
                    // Ожидаем контактного ответа — не переходим к следующему шагу
                    return true;
                }

                // Для остальных полей проверяем формат и сохраняем
                if (!$this->validateField($field, $text)) {
                    $this->telegramService->sendMessage($userId, "Неверный формат данных для поля '{$field}'.");
                    return true;
                }

                $user->{$field} = $text;
                $user->state = $field;
                $user->save();

                // Проверяем, есть ли еще пустые поля
                $nextField = $this->getNextEmptyField($user, array_keys($requiredFields));
                if ($nextField) {
                    if ($nextField === 'phone') {
                        $this->telegramService->sendContactRequest($userId, "Пожалуйста, {$requiredFields[$nextField]}.");
                    } else {
                        $this->telegramService->sendMessage($userId, "Пожалуйста, {$requiredFields[$nextField]}.");
                    }
                } else {
                    // Все данные собраны, авторизуем пользователя
                    $user->is_authed = true;
                    $user->state = 'authorized';
                    $user->daily_request_limit = 10;
                    $user->save();
                    $this->telegramService->sendMessage($userId, "Вы успешно авторизованы и можете задавать вопросы.");
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
