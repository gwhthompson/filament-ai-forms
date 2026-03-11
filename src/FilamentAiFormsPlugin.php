<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentAiFormsPlugin implements Plugin
{
    protected ?string $agent = null;

    protected ?string $chatAgent = null;

    public static function make(): static
    {
        /** @var static */
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $instance */
        $instance = app(static::class);

        /** @var static $plugin */
        $plugin = filament($instance->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'filament-ai-forms';
    }

    public function register(Panel $panel): void
    {
        // Panel-specific registration if needed
    }

    public function boot(Panel $panel): void
    {
        // Panel-specific boot logic if needed
    }

    /** Configure the default Agent class for form generation. */
    public function agent(string $class): static
    {
        $this->agent = $class;

        return $this;
    }

    /** Get the configured generation agent class. */
    public function getAgent(): ?string
    {
        if ($this->agent !== null) {
            return $this->agent;
        }

        $configValue = config('filament-ai-forms.agents.generation');

        return is_string($configValue) ? $configValue : null;
    }

    /** Configure the default Agent class for chat. */
    public function chatAgent(string $class): static
    {
        $this->chatAgent = $class;

        return $this;
    }

    /** Get the configured chat agent class. */
    public function getChatAgent(): ?string
    {
        if ($this->chatAgent !== null) {
            return $this->chatAgent;
        }

        $configValue = config('filament-ai-forms.agents.chat');

        return is_string($configValue) ? $configValue : null;
    }
}
