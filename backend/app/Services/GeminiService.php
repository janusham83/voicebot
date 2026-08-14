<?php

namespace App\Services;

use App\Exceptions\OpenAIException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Gemini AI Service - wraps Google Generative AI API calls
 */
class GeminiService
{
    protected string $apiKey;

    protected string $baseUrl;

    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key');
        $this->baseUrl = rtrim((string) config('services.gemini.base_url'), '/');
        $this->timeout = (int) config('services.gemini.timeout', 30);
    }

    /**
     * Generate an AI chat completion from a conversation message history using Gemini.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{message: string, tokens: int}
     */
    public function generateChatResponse(array $messages, array $options = []): array
    {
        // Convert OpenAI message format to Gemini format
        $contents = [];
        foreach ($messages as $message) {
            $contents[] = [
                'role' => $message['role'] === 'user' ? 'user' : 'model',
                'parts' => [
                    ['text' => $message['content']],
                ],
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.7,
                'maxOutputTokens' => $options['max_tokens'] ?? 2048,
            ],
        ];

        try {
            $model = config('services.gemini.model');
            $response = $this->client()->post(
                "{$this->baseUrl}/{$model}:generateContent",
                $payload
            );

            $this->throwIfFailed($response, 'generate chat response');

            $text = $response->json('candidates.0.content.parts.0.text');
            $tokenCount = $response->json('usageMetadata.totalTokenCount', 0);

            return [
                'message' => (string) $text,
                'tokens' => (int) $tokenCount,
            ];
        } catch (OpenAIException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            $this->logFailure('chat', $e);
            throw new OpenAIException('Sorry, the AI assistant is unavailable right now. Please try again shortly.', $e->getMessage());
        } catch (Throwable $e) {
            $this->logFailure('chat', $e);
            throw new OpenAIException('Sorry, the AI assistant is unavailable right now. Please try again shortly.', $e->getMessage());
        }
    }

    protected function client()
    {
        if ($this->apiKey === '') {
            Log::error('Gemini API key is not configured.');
            throw new OpenAIException('The AI service is not configured correctly. Please contact support.');
        }

        return Http::withHeader('x-goog-api-key', $this->apiKey)
            ->timeout($this->timeout)
            ->acceptJson();
    }

    /**
     * @throws OpenAIException
     */
    protected function throwIfFailed(Response $response, string $action): void
    {
        if ($response->successful()) {
            return;
        }

        $status = $response->status();
        $errorMessage = (string) $response->json('error.message', 'Unknown error');

        Log::error("Gemini API request failed while attempting to {$action}", [
            'status' => $status,
            'error' => $errorMessage,
        ]);

        if ($status === 429 && str_contains(strtolower($errorMessage), 'quota')) {
            throw new OpenAIException('The AI service quota has been exceeded. Please check your Gemini API billing and usage limits.', $errorMessage, $status);
        }

        if ($status === 429) {
            throw new OpenAIException('The AI service is receiving too many requests right now. Please try again in a moment.', $errorMessage, $status);
        }

        if ($status === 401 || $status === 403) {
            throw new OpenAIException('The AI service is not configured correctly. Please contact support.', $errorMessage, $status);
        }

        throw new OpenAIException("Sorry, something went wrong while trying to {$action}. Please try again.", $errorMessage, $status);
    }

    protected function logFailure(string $type, Throwable $e): void
    {
        Log::error("Gemini {$type} request failed", [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
        ]);
    }
}
