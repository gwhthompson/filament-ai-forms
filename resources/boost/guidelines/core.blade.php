## Filament AI Forms
AI-powered form generation for Filament using the Laravel AI SDK Agent pattern.

### aiSchema() Parameters
- `enabled` (bool, default: true), `description` (string), `prompt` (string), `required` (bool, default: true), `examples` (array), `pattern` (string)

@verbatim
<code-snippet name="aiSchema Usage" lang="php">
TextInput::make('name')
    ->aiSchema(
        description: 'The company name',
        prompt: 'Extract the official business name',
        examples: ['Acme Corp', 'TechStart Inc']
    )
</code-snippet>
@endverbatim

### AiGenerateAction Methods
- `agent(string|Closure)`: Custom Agent class
- `systemPrompt(string|Closure)`: System instructions
- `contextProvider(Closure)`: Additional context data
- `beforeGeneration(Closure)` / `afterGeneration(Closure)`: Lifecycle hooks
- `logEnabled(bool)` / `logPath(string)`: Logging control

### AiChatAction Methods
- `agent(string|Closure)`: Custom Agent class
- `systemPrompt(string|Closure)`: System instructions
- `initialPrompt(string|Closure)`: Pre-fill chat input
- `contextPrompt(string|Closure)`: Additional context

### Configuration
- Config keys: `agents.generation`, `agents.chat`, `logging.enabled`, `logging.path`
- Env vars: `AI_FORMS_GENERATION_AGENT`, `AI_FORMS_CHAT_AGENT`, `AI_FORMS_LOGGING`
- Plugin methods: `->agent(MyAgent::class)`, `->chatAgent(MyChatAgent::class)`

### Custom Agents
Agent classes use Laravel AI SDK attributes (`#[Provider('openai')]`, `#[Model('gpt-4o')]`, `#[Temperature(0.1)]`). Implement `HasStructuredOutput` for generation, `Conversational` for chat, `HasTools` for tools, `HasMiddleware` for middleware.

### Testing
Use `Agent::fake([$data])` and `Agent::assertPrompted(...)` to test without hitting the API. Call `Agent::preventStrayPrompts()` to catch unexpected agent calls.
