<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VoiceConversation;
use Illuminate\Auth\Access\Response;

class VoiceConversationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VoiceConversation $voiceConversation): bool
    {
        return $user->id === $voiceConversation->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VoiceConversation $voiceConversation): bool
    {
        return $user->id === $voiceConversation->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VoiceConversation $voiceConversation): bool
    {
        return $user->id === $voiceConversation->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VoiceConversation $voiceConversation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VoiceConversation $voiceConversation): bool
    {
        return false;
    }
}
