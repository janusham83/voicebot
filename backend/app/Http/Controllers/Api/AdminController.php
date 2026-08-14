<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VoiceConversation;
use App\Models\VoiceMessage;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        abort_unless($request->user()->is_admin, 403);

        return $this->success('', [
            'stats' => [
                'users' => User::count(),
                'conversations' => VoiceConversation::count(),
                'messages' => VoiceMessage::count(),
                'tokens' => (int) VoiceMessage::sum('tokens'),
            ],
            'recent_conversations' => VoiceConversation::with('user')
                ->withCount('messages')
                ->latest('updated_at')
                ->limit(10)
                ->get()
                ->map(fn (VoiceConversation $conversation) => [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'language' => $conversation->language,
                    'user' => $conversation->user?->only(['id', 'name', 'email']),
                    'messages_count' => $conversation->messages_count,
                    'updated_at' => $conversation->updated_at,
                ]),
        ]);
    }
}
