<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AiServiceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Voice\ChatRequest;
use App\Http\Resources\VoiceMessageResource;
use App\Models\VoiceConversation;
use App\Services\AiServiceFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class VoiceController extends Controller
{
    public function __construct(protected AiServiceFactory $aiServiceFactory)
    {
        //
    }

    /**
     * Send a message to the AI and persist both sides of the exchange.
     */
    public function chat(ChatRequest $request)
    {
        $user = $request->user();
        $conversationId = $request->validated('conversation_id');
        $language = 'en';

        $conversation = $conversationId
            ? VoiceConversation::findOrFail($conversationId)
            : null;

        if ($conversation) {
            Gate::authorize('update', $conversation);
        } else {
            // Create a new conversation if no ID was provided.
            $conversation = $user->conversations()->create([
                'title' => 'New Conversation', // A default title
                'language' => $language,
                'system_prompt' => config('voicebot.default_system_prompt'),
            ]);
        }

        $userMessage = $conversation->messages()->create([
            'user_id' => $user->id,
            'role' => 'user',
            'message' => $request->validated('message'),
        ]);

        $settings = $user->voiceSettings;

        // Use the factory to get the correct AI service based on user settings
        $aiService = $this->aiServiceFactory->make($user);

        try {
            $context = $this->buildContext($conversation);

            // STEP 3: Add detailed controller logging (Before)
            Log::info('VOICEBOT BEFORE GEMINI', [
                'user_id' => auth()->id(),
                'conversation_id' => $conversation->id ?? null,
                'messages' => $context,
            ]);

            $result = $aiService->generateChatResponse(
                $context,
                [
                    'temperature' => $settings?->temperature ?? 0.7,
                ]
            );

            // STEP 3: Add detailed controller logging (After)
            Log::info('VOICEBOT AFTER GEMINI', [
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            // STEP 4: Catch the real exception
            Log::error('VOICEBOT REAL ERROR', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // For local development, return a detailed error.
            if (app()->environment('local')) {
                return response()->json([
                    'success' => false,
                    'debug' => true,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], 500);
            }

            // For production, return a generic error.
            $userMessage = $e instanceof AiServiceException ? $e->userMessage : 'Sorry, something went wrong while trying to generate chat response. Please try again.';
            return $this->error($userMessage, 500);
        }

        $assistantMessage = $conversation->messages()->create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'message' => $result['message'],
            'tokens' => $result['tokens'],
        ]);

        // STEP 3: Add detailed controller logging (Saved)
        Log::info('VOICEBOT AI MESSAGE SAVED', [
            'conversation_id' => $conversation->id ?? null,
            'result' => $result,
        ]);

        return $this->success('Response generated successfully', [
            'conversation_id' => $conversation->id,
            'message' => $result['message'],
            'user_message' => new VoiceMessageResource($userMessage),
            'assistant_message' => new VoiceMessageResource($assistantMessage),
        ]);
    }

    /**
     * Build the system prompt + trimmed message history sent to the AI.
     */
    protected function buildContext(VoiceConversation $conversation): array
    {
        $limit = (int) config('voicebot.max_context_messages');

        $history = $conversation->messages()
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $messages = [
            ['role' => 'system', 'content' => $conversation->system_prompt ?: config('voicebot.default_system_prompt')],
        ];

        foreach ($history as $message) {
            $messages[] = ['role' => $message->role, 'content' => $message->message];
        }

        return $messages;
    }
}
