<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Http\Resources\VoiceMessageResource;
use App\Models\VoiceConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    /**
     * List messages belonging to a conversation.
     */
    public function index(Request $request, VoiceConversation $conversation)
    {
        Gate::authorize('view', $conversation);

        $messages = $conversation->messages()->oldest()->get();

        return $this->success('', [
            'messages' => VoiceMessageResource::collection($messages),
        ]);
    }

    /**
     * Manually append a message to a conversation.
     */
    public function store(StoreMessageRequest $request, VoiceConversation $conversation)
    {
        Gate::authorize('update', $conversation);

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'role' => $request->validated('role'),
            'message' => $request->validated('message'),
            'audio_file' => $request->validated('audio_file'),
            'duration' => $request->validated('duration'),
            'tokens' => $request->validated('tokens'),
        ]);

        return $this->success('Message created successfully', [
            'message' => new VoiceMessageResource($message),
        ], 201);
    }
}
