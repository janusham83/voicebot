<?php

namespace App\Http\Requests\Voice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TranscribeRequest extends FormRequest
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
        $maxSizeKb = config('voicebot.max_audio_size_kb');
        $allowedMimes = implode(',', config('voicebot.allowed_audio_mimes'));

        return [
            'audio' => ['required', 'file', "max:{$maxSizeKb}", "mimetypes:{$allowedMimes}"],
            'language' => ['nullable', 'string', 'in:si,en,auto'],
        ];
    }
}
