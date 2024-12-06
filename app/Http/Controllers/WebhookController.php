<?php

namespace App\Http\Controllers;

use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// app/Http/Controllers/WebhookController.php
class WebhookController extends Controller {
    public function __construct(
        private ConversationService $conversationService
    ) {}

    public function handleTelegramWebhook(Request $request) :JsonResponse
    {
        $conversation = $this->conversationService->processIncomingMessage($request->all());
        return response()->json(['status' => 'ok', 'conversation_id' => $conversation->id]);
    }
}
