<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    private $apiKey;
    private $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    public function createThread(int $userId, int $chatId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'OpenAI-Beta' => 'assistants=v2',
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/threads');

        return $response->json('id');
    }

    public function addMessageToThread(string $threadId, string $content)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'OpenAI-Beta' => 'assistants=v2',
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . "/threads/{$threadId}/messages", [
            'role' => 'user',
            'content' => $content
        ]);

        return $response->json('id');
    }

    public function createRun(string $threadId, string $assistantId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'OpenAI-Beta' => 'assistants=v2',
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . "/threads/{$threadId}/runs", [
            'assistant_id' => $assistantId
        ]);

        return $response->json('id');
    }

    public function checkRunStatus(string $threadId, string $runId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'OpenAI-Beta' => 'assistants=v2'
        ])->get($this->baseUrl . "/threads/{$threadId}/runs/{$runId}");

        return $response->json('status');
    }

    public function getThreadMessages(string $threadId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'OpenAI-Beta' => 'assistants=v2'
        ])->get($this->baseUrl . "/threads/{$threadId}/messages");

        return $response->json('data');
    }
}
