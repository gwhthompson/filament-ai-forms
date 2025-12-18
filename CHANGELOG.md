# Changelog

All notable changes to `filament-ai-forms` will be documented in this file.

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
