<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    |
    | The OpenAI model to use for form generation. Common options:
    | gpt-4.1-mini, gpt-4o, gpt-4.1
    |
    */
    'model' => env('AI_FORMS_MODEL', 'gpt-4.1-mini'),

    /*
    |--------------------------------------------------------------------------
    | Temperature
    |--------------------------------------------------------------------------
    |
    | Controls randomness in generation. Lower values (0.0-0.3) produce
    | more focused, deterministic output. Higher values increase creativity.
    |
    */
    'temperature' => env('AI_FORMS_TEMPERATURE', 0.05),

    /*
    |--------------------------------------------------------------------------
    | Max Output Tokens
    |--------------------------------------------------------------------------
    |
    | Maximum number of tokens in the generated response.
    |
    */
    'max_output_tokens' => env('AI_FORMS_MAX_TOKENS', 3000),

    /*
    |--------------------------------------------------------------------------
    | Web Search
    |--------------------------------------------------------------------------
    |
    | OpenAI web search settings for real-time data retrieval.
    |
    */
    'web_search' => [
        'enabled' => env('AI_FORMS_WEB_SEARCH', false),
        'country' => env('AI_FORMS_COUNTRY', 'GB'),
        'context_size' => 'medium',
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
