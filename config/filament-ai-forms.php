<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Agent Classes
    |--------------------------------------------------------------------------
    |
    | Configure custom Agent classes for form generation and chat.
    | When null, the package's built-in agents are used.
    | Agent classes carry model/provider/temperature config via PHP attributes.
    |
    */
    'agents' => [
        'generation' => env('AI_FORMS_GENERATION_AGENT'),
        'chat' => env('AI_FORMS_CHAT_AGENT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable detailed logging of AI generation requests and responses
    | for debugging and auditing purposes.
    |
    */
    'logging' => [
        'enabled' => env('AI_FORMS_LOGGING', true),
        'path' => storage_path('logs/ai-generation'),
    ],
];
