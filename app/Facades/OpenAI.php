<?php

namespace App\Facades;

use App\Services\OpenAIService;

use Illuminate\Support\Facades\Facade;

/**
 * @method static  createThread(int $userId, int $chatId)
 * @method static  addMessageToThread(string $threadId, string $content)
 * @method static  createRun(string $threadId, string $assistantId)
 * @method static  checkRunStatus(string $threadId, string $runId)
 * @method static  getThreadMessages(string $threadId)

 * */
class OpenAI extends Facade
{
    protected static function getFacadeAccessor() :string
    {
        return OpenAIService::class;
    }
}
