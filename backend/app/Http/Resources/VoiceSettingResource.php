<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoiceSettingResource extends JsonResource
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
            'language' => $this->language,
            'voice' => $this->voice,
            'ai_model' => $this->ai_model,
            'temperature' => $this->temperature,
            'auto_play' => $this->auto_play,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
