<?php

namespace App\Http\Requests\Message;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
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
            'role' => ['required', 'string', 'in:user,assistant,system'],
            'message' => ['required', 'string', 'max:8000'],
            'audio_file' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'tokens' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
