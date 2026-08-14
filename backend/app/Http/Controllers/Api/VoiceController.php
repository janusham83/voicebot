<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OpenAIException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Voice\ChatRequest;
use App\Http\Requests\Voice\SynthesizeRequest;
use App\Http\Requests\Voice\TranscribeRequest;
use App\Http\Resources\VoiceMessageResource;
use App\Models\VoiceConversation;
use App\Models\VoiceMessage;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VoiceController extends Controller
{
    public function __construct(protected OpenAIService $openAIService)
    {
        //
    }

    /**
     * Convert an uploaded audio clip into text.
     */
    public function transcribe(TranscribeRequest $request)
    {
        $language = $request->validated('language');

        try {
            $result = $this->openAIService->transcribeAudio(
                $request->file('audio')->getRealPath(),
                $language === 'auto' ? null : $language
            );
        } catch (OpenAIException $e) {
            return $this->error($e->userMessage, 422);
        }

        return $this->success('Audio transcribed successfully', $result);
    }

    /**
     * Send a message to the AI and persist both sides of the exchange.
     */
    public function chat(ChatRequest $request)
    {
        $user = $request->user();
        $conversationId = $request->validated('conversation_id');
        $language = $request->validated('language') ?: 'auto';

        $conversation = $conversationId
            ? VoiceConversation::findOrFail($conversationId)
            : null;

        if ($conversation) {
            Gate::authorize('update', $conversation);
        } else {
            $conversation = $user->conversations()->create([
                'title' => (string) str($request->validated('message'))->limit(50),
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

        try {
            $result = $this->openAIService->generateChatResponse(
                $this->buildContext($conversation),
                [
                    'model' => $settings?->ai_model,
                    'temperature' => $settings?->temperature ?? 0.7,
                ]
            );
        } catch (OpenAIException $e) {
            return $this->error($e->userMessage, 422, [
                'user_message' => new VoiceMessageResource($userMessage),
            ]);
        }

        $assistantMessage = $conversation->messages()->create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'message' => $result['message'],
            'tokens' => $result['tokens'],
        ]);

        return $this->success('Response generated successfully', [
            'conversation_id' => $conversation->id,
            'message' => $result['message'],
            'user_message' => new VoiceMessageResource($userMessage),
            'assistant_message' => new VoiceMessageResource($assistantMessage),
        ]);
    }

    /**
     * Convert text to speech and store the resulting audio file.
     */
    public function synthesize(SynthesizeRequest $request)
    {
        $message = null;

        if ($messageId = $request->validated('message_id')) {
            $message = VoiceMessage::findOrFail($messageId);
            Gate::authorize('update', $message->conversation);
        }

        try {
            $audio = $this->openAIService->generateSpeech(
                $request->validated('text'),
                $request->validated('voice')
            );
        } catch (OpenAIException $e) {
            return $this->error($e->userMessage, 422);
        }

        $path = 'voice-audio/'.Str::uuid().'.mp3';
        Storage::disk('public')->put($path, $audio);

        if ($message) {
            $message->update(['audio_file' => $path]);
        }

        return $this->success('Speech generated successfully', [
            'audio_url' => Storage::url($path),
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

