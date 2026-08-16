<?php

return [

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

    'default_ai_model' => env('VOICEBOT_DEFAULT_AI_MODEL', 'gemini-3.6-flash'),

];
