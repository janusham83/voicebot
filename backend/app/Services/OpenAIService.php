<?php

namespace App\Services;

use App\Exceptions\AiServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Wraps all OpenAI API calls (STT, chat, TTS) behind a single, swappable service.
 */
class OpenAIService implements AiServiceInterface
{
    protected string $apiKey;

    protected string $baseUrl;

    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = (string) config('services.openai.api_key');
        $this->baseUrl = rtrim((string) config('services.openai.base_url'), '/');
        $this->timeout = (int) config('services.openai.timeout', 30);
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Transcribe an audio file to text using OpenAI's speech-to-text model.
     *
     * @param  string  $filePath  Absolute path to the audio file on disk.
     * @param  string|null  $language  ISO-639-1 language hint (e.g. "si", "en"), null for auto-detect.
     * @return array{text: string, language: ?string}
     */
    public function transcribeAudio(string $filePath, ?string $language = null): array
    {
        if (! is_readable($filePath)) {
            throw new AiServiceException(
                'Sorry, I couldn\'t process that audio. Please try again.',
                "Audio file not readable: {$filePath}",
            );
        }

        $payload = [
            'model' => config('services.openai.stt_model'),
        ];

        if ($language && $language !== 'auto') {
            $payload['language'] = $language;
        }

        try {
            $response = $this->client()
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post("{$this->baseUrl}/audio/transcriptions", $payload);

            $this->throwIfFailed($response, 'transcribe audio');

            return [
                'text' => (string) $response->json('text'),
                'language' => $language,
            ];
        } catch (AiServiceException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            $this->logFailure('stt', $e);
            throw new AiServiceException("Sorry, I couldn't understand the audio. Please try again.", $e->getMessage());
        } catch (Throwable $e) {
            $this->logFailure('stt', $e);
            throw new AiServiceException("Sorry, I couldn't understand the audio. Please try again.", $e->getMessage());
        }
    }

    /**
     * Generate an AI chat completion from a conversation message history.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{message: string, tokens: int}
     */
    public function generateChatResponse(array $messages, array $options = []): array
    {
        $options = array_filter($options, fn ($value) => $value !== null);

        $payload = array_merge([
            'model' => $options['model'] ?? config('services.openai.model'),
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
        ], array_diff_key($options, ['temperature' => null]));

        try {
            $response = $this->client()->post("{$this->baseUrl}/chat/completions", $payload);

            $this->throwIfFailed($response, 'generate chat response');

            return [
                'message' => (string) $response->json('choices.0.message.content'),
                'tokens' => (int) $response->json('usage.total_tokens', 0),
            ];
        } catch (AiServiceException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            $this->logFailure('chat', $e);
            throw new AiServiceException('Sorry, the AI assistant is unavailable right now. Please try again shortly.', $e->getMessage());
        } catch (Throwable $e) {
            $this->logFailure('chat', $e);
            throw new AiServiceException('Sorry, the AI assistant is unavailable right now. Please try again shortly.', $e->getMessage());
        }
    }

    /**
     * Convert text to speech audio and return the raw binary audio content.
     */
    public function generateSpeech(string $text, ?string $voice = null): string
    {
        $payload = [
            'model' => config('services.openai.tts_model'),
            'voice' => $voice ?: config('services.openai.tts_voice'),
            'input' => $text,
        ];

        try {
            $response = $this->client()->post("{$this->baseUrl}/audio/speech", $payload);

            $this->throwIfFailed($response, 'generate speech');

            return $response->body();
        } catch (AiServiceException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            $this->logFailure('tts', $e);
            throw new AiServiceException('Sorry, I could not generate the voice response. Please try again.', $e->getMessage());
        } catch (Throwable $e) {
            $this->logFailure('tts', $e);
            throw new AiServiceException('Sorry, I could not generate the voice response. Please try again.', $e->getMessage());
        }
    }

    protected function client()
    {
        if ($this->apiKey === '') {
            Log::error('OpenAI API key is not configured.');
            throw new AiServiceException('The AI service is not configured correctly. Please contact support.', 'OpenAI API key is not configured.');
        }

        return Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->acceptJson();
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
        $errorMessage = (string) $response->json('error.message', 'Unknown error');

        Log::error("OpenAI API request failed while attempting to {$action}", [
            'status' => $status,
            'error' => $errorMessage,
        ]);

        $userMessage = match ($status) {
            400 => 'There was an issue with the request sent to the AI. Please try rephrasing your message.',
            401 => 'The AI service is not configured correctly. Please contact support.',
            429 => str_contains(strtolower($errorMessage), 'quota')
                ? 'The AI service quota has been exceeded. Please check the OpenAI billing plan and usage limits.'
                : 'The AI service is receiving too many requests right now. Please try again in a moment.',
            500, 503 => 'The AI service is currently unavailable. Please try again later.',
            default => "Sorry, something went wrong while trying to {$action}. Please try again.",
        };

        if ($status >= 400 && $status < 500) {
            // For 4xx errors, the developer message is often the most useful.
            throw new AiServiceException($userMessage, "OpenAI API Error: {$errorMessage}", $status);
        }

        // For 5xx and other errors.
        throw new AiServiceException($userMessage, "OpenAI API Error: {$errorMessage}", $status);
    }

    protected function logFailure(string $type, Throwable $e): void
    {
        Log::error("OpenAI {$type} request failed", [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
        ]);
    }
}
