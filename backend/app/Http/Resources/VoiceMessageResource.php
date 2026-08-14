<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VoiceMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'role' => $this->role,
            'message' => $this->message,
            'audio_file' => $this->audio_file ? Storage::url($this->audio_file) : null,
            'duration' => $this->duration,
            'tokens' => $this->tokens,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
