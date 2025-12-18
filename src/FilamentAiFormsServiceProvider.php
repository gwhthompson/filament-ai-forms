<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms;

use Filament\Schemas\Components\Component;
use Gwhthompson\FilamentAiForms\Livewire\AiChatInterface;
use Gwhthompson\FilamentAiForms\Mixins\AiSchemaMixin;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentAiFormsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-ai-forms';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasAssets();
    }

    public function packageBooted(): void
    {
        // Register mixin on all Filament schema components
        Component::mixin(new AiSchemaMixin);

        // Register Livewire component with package namespace
        // Note: Component only loads when rendered, not on every page
        Livewire::component('filament-ai-forms::ai-chat-interface', AiChatInterface::class);
    }
}
