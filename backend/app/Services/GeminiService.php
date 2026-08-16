<?php

namespace App\Services;

use App\Exceptions\AiServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Gemini AI Service - wraps Google Generative AI API calls
 */
class GeminiService implements AiServiceInterface
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

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function transcribeAudio(string $filePath, ?string $language = null): array
    {
        // Gemini API via this service does not support STT.
        throw new AiServiceException('Audio transcription is not supported by the configured AI service.');
    }

    public function generateSpeech(string $text, ?string $voice = null): string
    {
        // Gemini API via this service does not support TTS.
        throw new AiServiceException('Speech synthesis is not supported by the configured AI service.');
    }


    /**
     * Generate an AI chat completion from a conversation message history using Gemini.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{message: string, tokens: int}
     */
    public function generateChatResponse(array $messages, array $options = []): array
    {
        // Clone the array to prevent modifying the original array by reference.
        $messages = array_map(fn ($message) => $message, $messages);

        $systemPrompt = null;
        if (isset($messages[0]) && $messages[0]['role'] === 'system') {
            $systemPrompt = array_shift($messages)['content'];
        }

        $contents = [];
        foreach ($messages as $message) {
            // Gemini API expects 'user' or 'model' roles.
            // We map 'assistant' (our internal role) to 'model'.
            $role = match ($message['role']) {
                'user' => 'user',
                'assistant' => 'model',
                default => null,
            };

            if ($role === null) {
                continue; // Skip system messages if any are still present
            }

            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $message['content']],
                ],
            ];
        }

        $payload = $this->buildChatPayload($contents, $systemPrompt, $options);

        // STEP 2: Add temporary detailed logging (Request)
        Log::info('Gemini Chat Request', [
            'model' => config('services.gemini.model'),
            'url' => $this->generateContentUrl(config('services.gemini.model')),
            'payload' => $payload,
        ]);

        try {
            $model = config('services.gemini.model'); // FORCE the model to prevent overrides.
            $response = $this->client()->post(
                $this->generateContentUrl((string) $model),
                $payload
            );

            // STEP 2: Add temporary detailed logging (Response)
            Log::info('Gemini Chat Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $this->throwIfFailed($response, 'generate chat response');

            if (empty($response->json('candidates'))) {
                $finishReason = $response->json('promptFeedback.blockReason');
                throw new AiServiceException('The AI could not generate a response to your message. Please try rephrasing it.', 'The response was blocked, likely due to safety settings. Reason: '.$finishReason);
            }

            // STEP 7: Check the Gemini response parser
            $text = $response->json('candidates.0.content.parts.0.text');
            if (!is_string($text) || trim($text) === '') {
                Log::error('Gemini returned empty text', ['response' => $response->json()]);
                throw new AiServiceException('The AI returned an empty response.', 'Gemini response did not contain candidates.0.content.parts.0.text');
            }
            $tokenCount = $response->json('usageMetadata.totalTokenCount', 0);

            return [
                'message' => (string) $text,
                'tokens' => (int) $tokenCount,
            ];
        } catch (AiServiceException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            $this->logFailure('chat', $e);
            throw new AiServiceException('Sorry, the AI assistant is unavailable right now. Please try again shortly.', $e->getMessage());
        // STEP 3: Fix exception handling
        } catch (Throwable $e) {
            Log::error('Gemini generateChatResponse EXCEPTION', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw the original exception for better debugging in development
            if (app()->environment('local')) {
                throw $e;
            }

            // In production, throw a generic user-friendly exception
            throw new AiServiceException('Sorry, the AI assistant is unavailable right now. Please try again shortly.', $e->getMessage());
        }
    }

    /**
     * Build the request payload for the Gemini API.
     */
    protected function buildChatPayload(array $contents, ?string $systemPrompt, array $options): array
    {
        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => (float) ($options['temperature'] ?? 0.7),
                'maxOutputTokens' => (int) ($options['max_tokens'] ?? 2048), // Fix: Ensure value is an integer
            ],
        ];

        if ($systemPrompt) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ];
        }

        return $payload;
    }

    protected function client()
    {
        if ($this->apiKey === '') {
            Log::error('Gemini API key is not configured.');
            throw new AiServiceException('The AI service is not configured correctly. Please contact support.', 'Gemini API key is not configured.');
        }

        return Http::withHeaders([
            'x-goog-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->acceptJson();
    }

    protected function generateContentUrl(string $model): string
    {
        $model = trim($model, '/');

        if (! str_starts_with($model, 'models/')) {
            $model = "models/{$model}";
        }

        return "{$this->baseUrl}/{$model}:generateContent";
    }

    /**
     * @throws AiServiceException
     */
    protected function throwIfFailed(Response $response, string $action): void
    {
        if ($response->successful()) {
            return;
        }

        $status = $response->status();
        $rawBody = $response->body();
        $jsonBody = $response->json();
        $errorMessage = (string) data_get($jsonBody, 'error.message', data_get($jsonBody, 'error.errors.0.message', $rawBody ?: 'Unknown error'));

        Log::error("Gemini API request failed while attempting to {$action}", [
            'status' => $status,
            'response_body' => $rawBody,
            'error' => $errorMessage,
            'json' => $jsonBody,
            'action' => $action,
        ]);

        $userMessage = match ($status) {
            400 => 'There was an issue with the request sent to the AI. Please try rephrasing your message.',
            401, 403 => 'The AI service is not configured correctly. Please contact support.',
            429 => str_contains(strtolower($errorMessage), 'quota')
                ? 'The AI service quota has been exceeded. Please check your Gemini API billing and usage limits.'
                : 'The AI service is receiving too many requests right now. Please try again in a moment.',
            500, 503 => 'The AI service is currently unavailable. Please try again later.',
            default => "Sorry, something went wrong while trying to {$action}. Please try again.",
        };

        if ($status >= 400 && $status < 500) {
            // For 4xx errors, the developer message is often the most useful.
            throw new AiServiceException($userMessage, "Gemini API Error: {$errorMessage}", $status);
        }

        // For 5xx and other errors.
        throw new AiServiceException($userMessage, "Gemini API Error: {$errorMessage}", $status);
    }

    protected function logFailure(string $type, Throwable $e): void
    {
        Log::error("Gemini {$type} request failed", [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
        ]);
    }
}
