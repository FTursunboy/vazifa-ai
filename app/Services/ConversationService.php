<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class ConversationService {
    public function __construct(
        private UserService $userService,
        private OpenAIService $openAIService,
        private TelegramService $telegramService
    ) {}

    public function processIncomingMessage(array $telegramData) :Conversation
    {
        $user = $this->userService->findOrCreateFromTelegram($telegramData);
        $message = $telegramData['message']['text'];

        $conversation = $user->conversations()->first();
        if(!$conversation) {
            $conversation = $user->conversations()->create([
                'thread_id' => $this->openAIService->createThread()
            ]);
        }

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $message
        ]);

        $this->openAIService->addMessage($conversation->thread_id, $message);
        $runId = $this->openAIService->createRun(
            $conversation->thread_id,
            config('services.openai.assistant_id')
        );

        $this->telegramService->sendMessage(
            $user->telegram_id,
            "Бот изучает ваш вопрос..."
        );

        return $conversation;
    }

    public function processOpenAIResponse(Conversation $conversation) {
        $threadId = $conversation->thread_id;
        $runId = $this->openAIService->createRun(
            $threadId,
            config('services.openai.assistant_id')
        );

        $status = $this->openAIService->checkRunStatus($threadId, $runId);

        if ($status == 'completed') {
            $response = $this->openAIService->getLastMessage($threadId);

            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $response
            ]);

            $this->telegramService->sendMessage(
                $conversation->user->telegram_chat_id,
                $response
            );
        }
    }
}
