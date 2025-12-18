## Filament AI Forms This package provides AI-powered form generation for Filament v4 using OpenAI Structured Outputs.
### Key Features - **AI Schema Mixin**: Add AI capabilities to any Filament form component - **AiGenerateAction**:
3-step wizard to generate multiple form fields at once - **AiChatAction**: Conversational interface to refine individual
fields ### Adding AI to Form Components Use the `aiSchema()` method on any Filament form component:

@verbatim
        <code-snippet name="Basic AI Schema Usage" lang="php">
            use Filament\Forms\Components\TextInput;
            use Filament\Forms\Components\Textarea;

            TextInput::make('name')
            ->aiSchema(
            enabled: true,
            description: 'The company or brand name',
            prompt: 'Extract the official business name',
            required: true,
            examples: ['Acme Corp', 'TechStart Inc']
            )

            Textarea::make('description')
            ->aiSchema(
            description: 'A marketing description of the business',
            prompt: 'Write a compelling 2-3 sentence description'
            )
        </code-snippet>
@endverbatim

### AI Schema Parameters - `enabled` (bool): Enable/disable AI generation (default: true) - `description` (string): What
this field represents - `prompt` (string): Specific instructions for AI generation - `required` (bool): Whether AI must
provide a value (default: true) - `examples` (array): Example values to guide generation - `pattern` (string): Regex
pattern constraint ### Using the Generate Action Add the AI generate action to a Filament resource:

@verbatim
        <code-snippet name="Adding AiGenerateAction" lang="php">
            use Gwhthompson\FilamentAiForms\Actions\AiGenerateAction;

            protected function getHeaderActions(): array
            {
            return [
            AiGenerateAction::make()
            ->aiModel('gpt-4o')
            ->temperature(0.1)
            ->systemPrompt('You are a business data specialist...')
            ->contextProvider(fn ($action) => [
            'url' => $action->getRecord()->website_url,
            ]),
            ];
            }
        </code-snippet>
@endverbatim

### Action Configuration Methods - `aiModel(string)`: Set OpenAI model (e.g., 'gpt-4o', 'gpt-4.1-mini') -
`temperature(float)`: Set creativity 0.0-2.0 (default: 0.05) - `maxTokens(int)`: Set maximum response length -
`systemPrompt(string)`: Set custom system prompt - `useWebSearch(bool)`: Enable/disable web search -
`contextProvider(Closure)`: Provide additional context data - `beforeGeneration(Closure)`: Hook before AI generation -
`afterGeneration(Closure)`: Hook after AI generation ### Using the Chat Action for Field Refinement

@verbatim
        <code-snippet name="Adding AiChatAction to a Field" lang="php">
            use Gwhthompson\FilamentAiForms\Actions\AiChatAction;

            Textarea::make('bio')
            ->aiSchema(description: 'Professional biography')
            ->suffixAction(
            AiChatAction::make()
            ->systemPrompt('You are a professional copywriter...')
            ->initialPrompt('Help me write a compelling bio')
            )
        </code-snippet>
@endverbatim

### Panel Plugin Registration Register the plugin in your Filament panel:

@verbatim
        <code-snippet name="Panel Registration" lang="php">
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
        </code-snippet>
@endverbatim

### Configuration Publish and configure via `config/filament-ai-forms.php`: - `model`: OpenAI model (default:
gpt-4.1-mini) - `temperature`: Response creativity 0-2 (default: 0.05) - `max_output_tokens`: Response length limit
(default: 3000) - `web_search.enabled`: Enable real-time web search - `web_search.country`: Country code for web search
- `logging.enabled`: Enable generation logging - `retry.max_attempts`: Retry attempts on validation failure ###
Environment Variables

@verbatim
        <code-snippet name="Environment Configuration" lang="env">
            AI_FORMS_MODEL=gpt-4o
            AI_FORMS_TEMPERATURE=0.05
            AI_FORMS_MAX_TOKENS=3000
            AI_FORMS_WEB_SEARCH=true
            AI_FORMS_COUNTRY=GB
            AI_FORMS_LOGGING=true
        </code-snippet>
@endverbatim

### Best Practices - Set low temperature (0.05-0.1) for structured data generation - Use descriptive `aiSchema()`
descriptions for better results - Provide examples when possible for consistent formatting - Use `contextProvider()` to
pass relevant data like URLs - Enable web search when generating data about real-world entities
