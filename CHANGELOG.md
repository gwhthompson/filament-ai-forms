# Changelog

All notable changes to `filament-ai-forms` will be documented in this file.

## [2.0.0] - 2026-03-11

### Breaking Changes
- Replaced `openai-php/laravel` dependency with `laravel/ai` SDK
- Removed `StreamingResponseHandler` class
- Removed enums: `ReasoningEffort`, `ServiceTier`, `Verbosity`
- PHP 8.3+ now required (was 8.2+)

### Added
- `FormGenerationAgent` — Laravel AI SDK agent for structured form generation
- `ChatStreamAgent` — Laravel AI SDK agent for conversational streaming
- `JsonSchemaConverter` — converts raw JSON Schema to Laravel AI SDK builder format
- Custom exception hierarchy: `AiGenerationException`, `AiServiceTimeoutException`, `AiResponseParseException`
- `tools(array)` method on `AiGenerateAction` for passing tools to default agent
- `AiFieldMetadata` data object for field metadata
- Filament v5 / Livewire 4 support

### Changed
- `AiFormGenerationService` now uses Agent pattern instead of direct OpenAI calls
- `AiChatInterface` uses Laravel AI SDK streaming via `Conversational` interface
- `FilamentAiFormsPlugin` config keys changed from `model`/`api_key` to `agents.generation`/`agents.chat`
- Config file restructured around agents and logging

## [1.0.0] - 2025-12-18

### Added
- Initial release of Filament AI Forms
- `AiSchemaMixin` - Add AI generation capabilities to any Filament form component
- `AiGenerateAction` - 3-step wizard for bulk form field generation
- `AiChatAction` - Conversational interface for individual field refinement
- `AiChatInterface` - Livewire component for streaming chat responses
- `AiFormGenerationService` - Core service for OpenAI Structured Outputs integration
- `FilamentToAiSchemaMapper` - Converts Filament components to OpenAI JSON Schema
- `StreamingResponseHandler` - Handles OpenAI streaming responses
- `AiGenerationLogger` - Optional markdown logging for debugging
- `FilamentAiFormsPlugin` - Filament v4 plugin for panel configuration
- Laravel Boost AI guidelines for improved AI-assisted development
- Support for web search integration
- Automatic retry on schema validation failures
- PHPStan max level static analysis

### Requirements
- PHP 8.2+
- Laravel 11+
- Filament v4.0+
- OpenAI API key (via `openai-php/laravel`)
