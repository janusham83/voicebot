<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VoiceSettingResource;
use App\Models\VoiceSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function show(Request $request)
    {
        $settings = $request->user()->voiceSettings()->firstOrCreate([], $this->defaults());

        return $this->success('', [
            'settings' => new VoiceSettingResource($settings),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'language' => ['sometimes', 'string', 'in:si,en,auto'],
            'voice' => ['sometimes', 'string', 'max:100'],
            'ai_model' => ['sometimes', 'string', 'max:100'],
            'temperature' => ['sometimes', 'numeric', 'between:0,2'],
            'auto_play' => ['sometimes', 'boolean'],
        ]);

        $settings = $request->user()->voiceSettings()->firstOrCreate([], $this->defaults());
        $settings->update($validated);

        return $this->success('Settings updated successfully', [
            'settings' => new VoiceSettingResource($settings),
        ]);
    }

    protected function defaults(): array
    {
        return [
            'language' => 'auto',
            'voice' => 'default',
            'ai_model' => config('voicebot.default_ai_model', 'gemini-3.6-flash'),
            'temperature' => 0.7,
            'auto_play' => true,
        ];
    }
}
