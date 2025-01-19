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

    public function sendMessage(int $chatId, string $text, array $keyboard = null)
    {
        $formattedText = $this->escapeMarkdown($text);

        $data = [
            'chat_id' => $chatId,
            'text' => $formattedText,
            'parse_mode' => 'MarkdownV2',
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return Http::post("{$this->baseUrl}/sendMessage", $data)->json();
    }

    public function sendContactRequest(int $chatId, string $text)
    {
        $keyboard = [
            'keyboard' => [[
                [
                    'text' => 'Поделиться номером телефона',
                    'request_contact' => true
                ]
            ]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ];

        return $this->sendMessage($chatId, $text, $keyboard);
    }

    public function removeKeyboard(int $chatId, string $text)
    {
        $keyboard = [
            'remove_keyboard' => true
        ];

        return $this->sendMessage($chatId, $text, $keyboard);
    }

    public function sendTypingAction(int $chatId)
    {
        Http::post("{$this->baseUrl}/sendChatAction", [
            'chat_id' => $chatId,
            'action' => 'typing'
        ]);
    }

    private function escapeMarkdown(string $text): string
    {
        // Сначала сохраняем части с {{BOLD}} маркерами
        $text = preg_replace('/\{\{BOLD\}\}(.*?)\{\{\/BOLD\}\}/', '{{PRESERVED}}$1{{/PRESERVED}}', $text);

        // Экранируем специальные символы
        $specialChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        $text = str_replace($specialChars, array_map(fn($char) => "\\$char", $specialChars), $text);

        // Заменяем сохраненные маркеры на markdown жирный текст
        $text = preg_replace('/\{\{PRESERVED\}\}(.*?)\{\{\/PRESERVED\}\}/', '**$1**', $text);

        return $text;
    }


    private function formatText(string $text): string {
        return preg_replace('/(\d+\.\s*)([^-]*?)(\s*-)/', '$1**$2**$3', $text);
    }

    private function escapeMarkdownV2(string $text): string
    {
        $text = $this->formatText($text);
        $specialChars = ['[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        $escapedText = str_replace($specialChars, array_map(fn($char) => '\\' . $char, $specialChars), $text);

          $escapedText = preg_replace_callback('/\*\*(.*?)\*\*/u', function($matches) {
            $innerText = $matches[1];
            $specialChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
            $escapedInner = str_replace($specialChars, array_map(fn($char) => '\\' . $char, $specialChars), $innerText);
            return "*" . $escapedInner . "*";
        }, $escapedText);

        return $escapedText;
    }

    /**
     * Редактирование текста сообщения
     */
    public function editMessageText(int $chatId, int $messageId, string $text)
    {
        $escapedText = $this->escapeMarkdownV2($text);

        return Http::post("{$this->baseUrl}/editMessageText", [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $escapedText,
            'parse_mode' => 'MarkdownV2'
        ])->json();
    }

    public function sendKeyboard(int $chatId, string $text, array $keyboard)
    {
        $data = [
            'chat_id' => $chatId,
            'text' => $this->escapeMarkdown($text),
            'parse_mode' => 'MarkdownV2',
            'reply_markup' => json_encode($keyboard),
        ];

        return Http::post("{$this->baseUrl}/sendMessage", $data)->json();
    }

}
