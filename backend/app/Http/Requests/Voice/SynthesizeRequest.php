<?php

namespace App\Http\Requests\Voice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SynthesizeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:4000'],
            'voice' => ['nullable', 'string', Rule::in(config('voicebot.allowed_tts_voices'))],
            'message_id' => ['nullable', 'integer', 'exists:voice_messages,id'],
        ];
    }
}
