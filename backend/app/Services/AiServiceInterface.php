<?php

namespace App\Services;

interface AiServiceInterface
{
    /**
     * Generate an AI chat completion from a conversation message history.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{message: string, tokens: int}
     */
    public function generateChatResponse(array $messages, array $options = []): array;

    /**
     * Check if the service is configured with an API key.
     */
    public function isConfigured(): bool;
}