# Filament AI Forms

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gwhthompson/filament-ai-forms.svg?style=flat-square)](https://packagist.org/packages/gwhthompson/filament-ai-forms)
[![codecov](https://codecov.io/gh/gwhthompson/filament-ai-forms/branch/main/graph/badge.svg?style=flat-square)](https://codecov.io/gh/gwhthompson/filament-ai-forms)
[![PHPStan](https://img.shields.io/badge/PHPStan-max-brightgreen.svg?style=flat-square)](https://phpstan.org/)
[![License](https://img.shields.io/packagist/l/gwhthompson/filament-ai-forms.svg?style=flat-square)](https://packagist.org/packages/gwhthompson/filament-ai-forms)

AI-powered form generation for Filament, built on the Laravel AI SDK.

Describe what each field represents with `aiSchema()`. An AI agent fills them in. Your field types and validation rules act as constraints, so you always get valid data back.

## Requirements

- PHP 8.3+
- Laravel 11+
- Filament v4+
- [Laravel AI SDK](https://github.com/laravel/ai)

## Installation

```bash
composer require gwhthompson/filament-ai-forms
```

Publish the config file:

```bash
php artisan vendor:publish --tag="filament-ai-forms-config"
```

Register the plugin in your panel provider:

```php
use Gwhthompson\FilamentAiForms\FilamentAiFormsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(FilamentAiFormsPlugin::make());
}
```

## Quick Start

Mark fields with `aiSchema()` and add `AiGenerateAction` to your page:

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Gwhthompson\FilamentAiForms\Actions\AiGenerateAction;

public static function form(Form $form): Form
{
    return $form->schema([
        TextInput::make('name')
            ->aiSchema(description: 'The company name'),

        Textarea::make('description')
            ->aiSchema(
                description: 'A marketing description',
                prompt: 'Write 2-3 sentences',
            ),
    ]);
}

protected function getHeaderActions(): array
{
    return [
        AiGenerateAction::make(),
    ];
}
```

The action opens a wizard: select fields, generate, review, then accept or reject each value.

## Configuration

```php
// config/filament-ai-forms.php

return [
    'agents' => [
        'generation' => env('AI_FORMS_GENERATION_AGENT'),
        'chat' => env('AI_FORMS_CHAT_AGENT'),
    ],
    'logging' => [
        'enabled' => env('AI_FORMS_LOGGING', true),
        'path' => storage_path('logs/ai-generation'),
    ],
];
```

| Env var | Purpose |
|---------|---------|
| `AI_FORMS_GENERATION_AGENT` | Agent class for bulk generation |
| `AI_FORMS_CHAT_AGENT` | Agent class for chat refinement |
| `AI_FORMS_LOGGING` | Enable/disable generation logging (default: `true`) |

You can also set agents on the plugin directly:

```php
FilamentAiFormsPlugin::make()
    ->agent(MyGenerationAgent::class)
    ->chatAgent(MyChatAgent::class)
```

## aiSchema Parameters

| Parameter | Type | Default | Purpose |
|-----------|------|---------|---------|
| `enabled` | bool | `true` | Enable/disable generation for this field |
| `description` | string | `null` | What this field represents |
| `prompt` | string | `null` | Instructions for the agent |
| `required` | bool | `true` | Whether the agent must fill this field |
| `examples` | array | `[]` | Example values to guide output |
| `pattern` | string | `null` | Regex pattern constraint |

## AiGenerateAction

Bulk-generate multiple fields at once. Add it as a header action or form action.

| Method | Purpose |
|--------|---------|
| `agent(string\|Closure)` | Custom agent class |
| `systemPrompt(string\|Closure)` | System instructions |
| `contextProvider(Closure)` | Pass context data (URLs, etc.) |
| `beforeGeneration(Closure)` | Pre-generation hook |
| `afterGeneration(Closure)` | Post-generation hook |
| `logEnabled(bool)` | Enable/disable logging |
| `tools(array)` | Pass tools to default agent (e.g., WebSearch) |
| `logPath(string)` | Custom log path |

```php
AiGenerateAction::make()
    ->systemPrompt('You are a business data specialist.')
    ->contextProvider(fn ($action) => [
        'url' => $action->getRecord()->website_url,
    ])
```

## AiChatAction

Refine a single field through a chat interface. Add it as a suffix action on any field.

| Method | Purpose |
|--------|---------|
| `agent(string\|Closure)` | Custom agent class |
| `systemPrompt(string\|Closure)` | System instructions |
| `initialPrompt(string\|Closure)` | Pre-fill the chat input |
| `contextPrompt(string\|Closure)` | Additional context |

```php
Textarea::make('bio')
    ->aiSchema(description: 'Professional biography')
    ->suffixAction(
        AiChatAction::make()
            ->systemPrompt('You are a professional copywriter.')
            ->initialPrompt('Help me write a compelling bio')
    )
```

## Custom Agents

Agents use the Laravel AI SDK. Configure model, provider, and temperature with PHP attributes.

```php
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Temperature;

#[Provider('openai')]
#[Model('gpt-4o')]
#[Temperature(0.1)]
class MyGenerationAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function tools(): array
    {
        return [
            new \Laravel\Ai\Tools\WebSearch,
        ];
    }
}
```

Key interfaces:

- `HasStructuredOutput` -- for generation agents (returns typed data)
- `Conversational` -- for chat agents (maintains message history)
- `HasTools` -- adds tool usage (web search, web fetch, etc.)
- `HasMiddleware` -- adds middleware to the agent pipeline

Agent resolution order: action-level `->agent()` > plugin-level `->agent()` > config value > built-in default.

## Testing

The Laravel AI SDK provides test helpers to fake agent responses:

```php
use Laravel\Ai\Facades\Agent;

Agent::fake([
    ['name' => 'Acme Corp', 'description' => 'A test company'],
]);

// ... trigger the action ...

Agent::assertPrompted(fn ($prompt) => str_contains($prompt, 'company name'));
```

Use `Agent::preventStrayPrompts()` to catch unexpected agent calls in your test suite.

## How It Works

The package converts your Filament form schema into a JSON schema that the agent follows. Your Laravel validation rules (required, max length, enum values) become constraints in that schema. The agent returns structured data matching your field types, and you review each value before it touches the form.

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

## Credits

- [George Thompson](https://github.com/gwhthompson)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
