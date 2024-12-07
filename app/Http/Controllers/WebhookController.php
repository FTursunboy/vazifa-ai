<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\ConversationService;
use App\Services\OpenAIService;
use App\Services\TelegramService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    private $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }

    public function handle(Request $request)
    {
        return response()->json(
            $this->conversationService->handleWebhook($request->all())
        );
    }
}
