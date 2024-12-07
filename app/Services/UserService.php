<?php

namespace App\Services;

use App\Models\User;

class UserService {
    public function findOrCreateFromTelegram(array $telegramData): User {
        return User::firstOrCreate(
            ['telegram_id' => $telegramData['message']['from']['id']],
            [
                'name' => trim(($telegramData['message']['from']['first_name'] ?? '') . ' ' . ($telegramData['message']['from']['last_name'] ?? '')),
                'tg_nick' => $telegramData['message']['from']['username'],
                'last_seen' => now()
            ]
        );
    }
}
