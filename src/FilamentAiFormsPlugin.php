<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentAiFormsPlugin implements Plugin
{
    protected ?string $model = null;

    protected ?float $temperature = null;

    protected bool $webSearchEnabled = true;

    protected ?string $webSearchCountry = null;

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

    public function model(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): string
    {
        if ($this->model !== null) {
            return $this->model;
        }

        $configValue = config('filament-ai-forms.model', 'gpt-4.1-mini');

        return is_string($configValue) ? $configValue : 'gpt-4.1-mini';
    }

    public function temperature(float $temperature): static
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getTemperature(): float
    {
        if ($this->temperature !== null) {
            return $this->temperature;
        }

        $configValue = config('filament-ai-forms.temperature', 0.05);

        return is_float($configValue) ? $configValue : (is_numeric($configValue) ? (float) $configValue : 0.05);
    }

    public function webSearch(bool $enabled = true): static
    {
        $this->webSearchEnabled = $enabled;

        return $this;
    }

    public function isWebSearchEnabled(): bool
    {
        return $this->webSearchEnabled && (bool) config('filament-ai-forms.web_search.enabled', true);
    }

    public function webSearchCountry(string $country): static
    {
        $this->webSearchCountry = $country;

        return $this;
    }

    public function getWebSearchCountry(): string
    {
        if ($this->webSearchCountry !== null) {
            return $this->webSearchCountry;
        }

        $configValue = config('filament-ai-forms.web_search.country', 'GB');

        return is_string($configValue) ? $configValue : 'GB';
    }
}
