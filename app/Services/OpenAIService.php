<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService {
    private $client;

    public function __construct() {
        $this->client = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            'OpenAI-Beta' => 'assistants=v2 ',
            'Content-Type' => 'application/json'
        ]);
    }

    public function createThread(): string {
        $response = $this->client->post('https://api.openai.com/v1/threads');
        return $response->json('id');
    }

    public function addMessage(string $threadId, string $content): string {
        $response = $this->client->post("https://api.openai.com/v1/threads/{$threadId}/messages", [
            'role' => 'user',
            'content' => $content
        ]);
        return $response->json('id');
    }

    public function createRun(string $threadId, string $assistantId): string {
        $response = $this->client->post("https://api.openai.com/v1/threads/{$threadId}/runs", [
            'assistant_id' => $assistantId
        ]);
        return $response->json('id');
    }

    public function checkRunStatus(string $threadId, string $runId): string {
        $response = $this->client->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}");
        return $response->json('status');
    }

    public function getLastMessage(string $threadId): ?string {
        $response = $this->client->get("https://api.openai.com/v1/threads/{$threadId}/messages");
        $messages = $response->json('data');
        return $messages[0]['content'][0]['text']['value'] ?? null;
    }
}
