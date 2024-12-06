<?php
return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'assistant_id' => env('OPENAI_ASSISTANT_ID')
    ],
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN')
    ]
];
