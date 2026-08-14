<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Voice Upload Limits
    |--------------------------------------------------------------------------
    |
    | Controls how large an uploaded audio clip may be (in kilobytes) and how
    | long it may be (in seconds), plus which MIME types are accepted.
    |
    */

    'max_audio_size_kb' => env('VOICEBOT_MAX_AUDIO_SIZE_KB', 10240), // 10 MB

    'max_audio_duration' => env('VOICEBOT_MAX_AUDIO_DURATION', 120), // seconds

    'allowed_audio_mimes' => [
        'audio/webm',
        'video/webm',
        'audio/wav',
        'audio/x-wav',
        'audio/mpeg',
        'audio/mp3',
        'audio/mp4',
        'audio/m4a',
        'audio/x-m4a',
        'audio/ogg',
    ],

    'allowed_tts_voices' => [
        'alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversation Defaults
    |--------------------------------------------------------------------------
    */

    'default_system_prompt' => env(
        'VOICEBOT_DEFAULT_SYSTEM_PROMPT',
        'You are a helpful, friendly AI Voice Assistant. Respond naturally and concisely. '
        .'If the user speaks Sinhala, respond in Sinhala. If the user speaks English, respond in English. '
        .'Maintain the context of the conversation.'
    ),

    'max_context_messages' => env('VOICEBOT_MAX_CONTEXT_MESSAGES', 20),

    'max_messages_per_user' => env('VOICEBOT_MAX_MESSAGES_PER_USER', 1000),

];
