<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class ConversationService
{
    private $openAIService;
    private $telegramService;
    private $userService;
    private $authService;

    public function __construct(
        OpenAIService $openAIService,
        TelegramService $telegramService,
        UserService $userService,
        AuthService $authService
    ) {
        $this->openAIService = $openAIService;
        $this->telegramService = $telegramService;
        $this->userService = $userService;
        $this->authService = $authService;
    }

    public function handleWebhook(array $update) :bool
    {
        if (!isset($update['message']['text'])) {
            return false;
        }

        $userId = $update['message']['from']['id'];
        $text = $update['message']['text'];


        if (str_starts_with($text, '/')) {
            return $this->handleCommand($text,  $userId);
        }

        $user = $this->userService->findOrCreateFromTelegram($update);

        if ($user->state == 'ask_email')
        {
            return $this->authService->validateEmail($user, $text);
        }
        if ($user->state == 'ask_code')
        {
            return $this->authService->checkCode($user, $text);
        }

        if (!$this->authService->isUserAuthorized($user)) {
            return $this->authService->requestAuthorization($userId, $user);
        }

        if (!$this->authService->checkRequestLimit($user)) {
            $this->telegramService->sendMessage(
                $userId,
                "Вы исчерпали дневной лимит запросов. Пожалуйста, вернитесь завтра."
            );
            return true;
        }

        $conversation = $this->getOrCreateConversation($user);

        if ($conversation->status === 'processing') {
            $this->telegramService->sendMessage(
                $userId,
                "⏳ Подождите, пожалуйста. Обрабатываю предыдущий запрос..."
            );
            return true;
        }

        $response = $this->telegramService->sendMessage(
            $userId,
            "Бот изучает ваш вопрос"
        );

        $userMessage = $this->createUserMessage($conversation, $text, $response);
        $this->telegramService->sendTypingAction($userId);
        $conversation->update(['status' => 'processing']);

        $this->processOpenAIRequest($conversation, $userId, $text);

        $this->authService->incrementRequestCount($user);

        return true;
    }

    private function handleCommand(string $command, int $chatId)
    {
        $commandHandlerClass = "App\\Services\\Commands\\" . ucfirst(substr($command, 1)) . "CommandHandler";

        if (class_exists($commandHandlerClass)) {
            $handler = app($commandHandlerClass);
            return $handler->run($chatId);
        }

        $this->telegramService->sendMessage(
            $chatId,
            "Извините, неизвестная команда."
        );

        return true;
    }

    private function getOrCreateConversation(User $user)
    {
        $conversation = Conversation::where('user_id', $user->id)->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'thread_id' => $this->openAIService->createThread($user->id, $user->telegram_id),
                'status' => 'ready'
            ]);
        }

        return $conversation;
    }

    private function createUserMessage(Conversation $conversation, string $text, array $response)
    {
        return Message::create([
            'conversation_id' => $conversation->id,
            'content' => $text,
            'role' => 'user',
            'message_id' => $response['result']['message_id']
        ]);
    }

    private function processOpenAIRequest(Conversation $conversation, int $chatId, string $text) :void
    {
        $this->openAIService->addMessageToThread(
            $conversation->thread_id,
            $text
        );

        $runId = $this->openAIService->createRun(
            $conversation->thread_id,
            config('services.openai.assistant_id')
        );

        $conversation->update(['last_run_id' => $runId]);

        dispatch(function() use ($conversation, $chatId) {
            $this->processRun($conversation, $chatId);
        });
    }

    private function processRun(Conversation $conversation, int $chatId) :void
    {
        $maxAttempts = 20;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $status = $this->openAIService->checkRunStatus(
                $conversation->thread_id,
                $conversation->last_run_id
            );

            if ($status === 'completed') {
                $this->handleCompletedRun($conversation, $chatId);
                break;
            }

            if (in_array($status, ['failed', 'cancelled'])) {
                $this->handleFailedRun($conversation, $chatId);
                break;
            }

            sleep(1);
            $attempt++;
        }

        $this->handleRunTimeout($conversation, $chatId, $attempt, $maxAttempts);
    }

    private function handleCompletedRun(Conversation $conversation, int $chatId) :void
    {
        $messages = $this->openAIService->getThreadMessages($conversation->thread_id);
        $assistantMessage = collect($messages)
            ->where('role', 'assistant')
            ->first();

        if ($assistantMessage) {
            $botMessageText = $assistantMessage['content'][0]['text']['value'];
            $lastUserMessage = $conversation->messages()
                ->where('role', 'user')
                ->latest()
                ->first();

            if ($lastUserMessage) {
                $this->telegramService->editMessageText($chatId, $lastUserMessage->message_id, $botMessageText);
            }

            Message::create([
                'conversation_id' => $conversation->id,
                'content' => $botMessageText,
                'role' => 'assistant',
                'is_bot_message' => true,
                'message_id' => $lastUserMessage->message_id
            ]);
        }

        $conversation->update(['status' => 'ready']);
    }

    private function handleFailedRun(Conversation $conversation, int $chatId) :void
    {
        $this->telegramService->sendMessage(
            $chatId,
            "❌ Произошла ошибка при обработке запроса"
        );
        $conversation->update(['status' => 'ready']);
    }

    private function handleRunTimeout(Conversation $conversation, int $chatId, int $attempt, int $maxAttempts) :void
    {
        if ($attempt >= $maxAttempts) {
            $this->telegramService->sendMessage(
                $chatId,
                "⏰ Превышено время ожидания ответа"
            );
            $conversation->update(['status' => 'ready']);
        }
    }
}
