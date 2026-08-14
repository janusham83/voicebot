<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\StoreConversationRequest;
use App\Http\Resources\VoiceConversationResource;
use App\Models\VoiceConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConversationController extends Controller
{
    /**
     * List the authenticated user's conversations.
     */
    public function index(Request $request)
    {
        $conversations = $request->user()
            ->conversations()
            ->withCount('messages')
            ->latest('updated_at')
            ->get();

        return $this->success('', [
            'conversations' => VoiceConversationResource::collection($conversations),
        ]);
    }

    /**
     * Create a new conversation for the authenticated user.
     */
    public function store(StoreConversationRequest $request)
    {
        $conversation = $request->user()->conversations()->create([
            'title' => $request->validated('title') ?: 'New Conversation',
            'language' => $request->validated('language') ?: 'auto',
            'system_prompt' => $request->validated('system_prompt') ?: config('voicebot.default_system_prompt'),
        ]);

        return $this->success('Conversation created successfully', [
            'conversation' => new VoiceConversationResource($conversation),
        ], 201);
    }

    /**
     * Show a single conversation with its messages.
     */
    public function show(Request $request, VoiceConversation $conversation)
    {
        Gate::authorize('view', $conversation);

        $conversation->load('messages');

        return $this->success('', [
            'conversation' => new VoiceConversationResource($conversation),
        ]);
    }

    /**
     * Delete a conversation and its messages.
     */
    public function destroy(Request $request, VoiceConversation $conversation)
    {
        Gate::authorize('delete', $conversation);

        $conversation->delete();

        return $this->success('Conversation deleted successfully');
    }
}
