# Filament AI Forms

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gwhthompson/filament-ai-forms.svg?style=flat-square)](https://packagist.org/packages/gwhthompson/filament-ai-forms)
[![codecov](https://codecov.io/gh/gwhthompson/filament-ai-forms/branch/main/graph/badge.svg?style=flat-square)](https://codecov.io/gh/gwhthompson/filament-ai-forms)
[![PHPStan](https://img.shields.io/badge/PHPStan-max-brightgreen.svg?style=flat-square)](https://phpstan.org/)
[![License](https://img.shields.io/packagist/l/gwhthompson/filament-ai-forms.svg?style=flat-square)](https://packagist.org/packages/gwhthompson/filament-ai-forms)

Use OpenAI to generate form field values in Filament v4.

You describe what each field represents. The model fills them in. Your existing validation rules and field types become constraints that the model follows, so you always get valid data back.

## Features

- **Bulk generation** - Fill multiple fields at once, review before applying
- **Field refinement** - Improve any field through a chat interface
- **Type-safe output** - The model follows your field types and validation rules
- **Web search** - Optionally fetch live data for up-to-date information
- **Automatic retry** - Retries when the output fails validation

## Requirements

- PHP 8.2+
- Laravel 11+
- Filament v4.0+
- OpenAI API key

## Installation

Install the package via Composer:

```bash
composer require gwhthompson/filament-ai-forms
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="filament-ai-forms-config"
```

Add your OpenAI API key to `.env`:

```env
OPENAI_API_KEY=sk-your-api-key
```

## Panel Registration

Register the plugin in your Filament panel provider:

```php
use Gwhthompson\FilamentAiForms\FilamentAiFormsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(
            FilamentAiFormsPlugin::make()
                ->model('gpt-4o')
                ->temperature(0.05)
                ->webSearch(true)
                ->webSearchCountry('US')
        );
}
```

## Quick Start

Add the `aiSchema()` method to any form field to enable generation:

```php
TextInput::make('name')
    ->aiSchema(description: 'The company name')

Textarea::make('description')
    ->aiSchema(
        description: 'A marketing description of the business',
        prompt: 'Write 2-3 sentences'
    )
```

Then add the generate action to your page:

```php
use Gwhthompson\FilamentAiForms\Actions\AiGenerateAction;

protected function getHeaderActions(): array
{
    return [
        AiGenerateAction::make(),
    ];
}
```

## Schema Parameters

| Parameter | Type | Default | |
|-----------|------|---------|---|
| `enabled` | bool | `true` | Enable or disable generation for this field |
| `description` | string | `null` | What this field represents |
| `prompt` | string | `null` | Instructions for the model |
| `required` | bool | `true` | Whether the model must fill this field |
| `examples` | array | `[]` | Example values to guide the output |
| `pattern` | string | `null` | Regex pattern constraint |

## Full Example

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;

public static function form(Form $form): Form
{
    return $form->schema([
        TextInput::make('name')
            ->aiSchema(
                description: 'The company or brand name',
                prompt: 'Extract the official business name',
                examples: ['Acme Corp', 'TechStart Inc']
            ),

        Textarea::make('description')
            ->aiSchema(
                description: 'A marketing description of the business',
                prompt: 'Write a compelling 2-3 sentence description'
            ),

        Select::make('industry')
            ->options([
                'tech' => 'Technology',
                'retail' => 'Retail',
                'healthcare' => 'Healthcare',
            ])
            ->aiSchema(
                description: 'The primary industry sector',
                prompt: 'Select the most appropriate category'
            ),
    ]);
}
```

## Generate Action

Add the bulk generate action to your resource pages:

```php
use Gwhthompson\FilamentAiForms\Actions\AiGenerateAction;

protected function getHeaderActions(): array
{
    return [
        AiGenerateAction::make()
            ->aiModel('gpt-4o')
            ->temperature(0.1)
            ->systemPrompt('You are a business data specialist.')
            ->contextProvider(fn ($action) => [
                'url' => $action->getRecord()->website_url,
            ]),
    ];
}
```

### Action Methods

| Method | |
|--------|---|
| `aiModel(string $model)` | Set the OpenAI model (e.g., 'gpt-4o', 'gpt-4.1-mini') |
| `temperature(float $temp)` | Control randomness (0.0 = deterministic, 2.0 = creative) |
| `maxTokens(int $tokens)` | Set maximum response length |
| `systemPrompt(string $prompt)` | Set custom system instructions |
| `useWebSearch(bool $enabled)` | Enable or disable web search |
| `contextProvider(Closure $provider)` | Pass additional context to the model |
| `beforeGeneration(Closure $callback)` | Run code before generation |
| `afterGeneration(Closure $callback)` | Run code after generation |

## Chat Action

Add a chat interface to refine individual fields:

```php
use Gwhthompson\FilamentAiForms\Actions\AiChatAction;

Textarea::make('bio')
    ->aiSchema(description: 'Professional biography')
    ->suffixAction(
        AiChatAction::make()
            ->systemPrompt('You are a professional copywriter.')
            ->initialPrompt('Help me write a compelling bio')
    )
```

## Configuration

### Environment Variables

```env
AI_FORMS_MODEL=gpt-4.1-mini
AI_FORMS_TEMPERATURE=0.05
AI_FORMS_MAX_TOKENS=3000
AI_FORMS_WEB_SEARCH=false
AI_FORMS_COUNTRY=GB
AI_FORMS_LOGGING=true
```

### Configuration File

```php
// config/filament-ai-forms.php

return [
    // OpenAI model
    'model' => env('AI_FORMS_MODEL', 'gpt-4.1-mini'),

    // Temperature: 0.0 = deterministic, 2.0 = creative
    'temperature' => env('AI_FORMS_TEMPERATURE', 0.05),

    // Maximum response tokens
    'max_output_tokens' => env('AI_FORMS_MAX_TOKENS', 3000),

    // Web search
    'web_search' => [
        'enabled' => env('AI_FORMS_WEB_SEARCH', false),
        'country' => env('AI_FORMS_COUNTRY', 'GB'),
        'context_size' => 'medium',
    ],

    // Logging
    'logging' => [
        'enabled' => env('AI_FORMS_LOGGING', true),
        'path' => storage_path('logs/ai-generation'),
    ],

    // Retry on validation failures
    'retry' => [
        'max_attempts' => 2,
        'validate_schema' => true,
    ],
];
```

## How It Works

1. The package converts your Filament form schema to an OpenAI JSON schema
2. Your Laravel validation rules become constraints (required, max length, enum values)
3. OpenAI's structured output mode ensures the response matches your schema
4. If validation fails, the package retries with feedback
5. You review the generated values before applying them

## Best Practices

- Use low temperature (0.05-0.1) for consistent, deterministic output
- Write clear `description` values - this is what the model sees
- Include `examples` for consistent formatting
- Use `contextProvider()` to pass URLs or reference content

## Custom Themes

If you have a custom Filament theme, add the package views to your theme's `@source` directive:

```css
@source '../../../../vendor/gwhthompson/filament-ai-forms/resources/views';
```

The chat interface uses named CSS classes (`fi-ai-chat-*`) for easy customisation:

```css
/* Custom user bubble colour */
.fi-ai-chat-message-user .fi-ai-chat-message-content {
    @apply bg-indigo-600;
}

/* Hide delete buttons */
.fi-ai-chat-message-delete-btn {
    @apply hidden;
}
```

## Testing

```bash
composer test
```

## Static Analysis

```bash
composer analyse
```

## Code Style

```bash
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [George Thompson](https://github.com/gwhthompson)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
