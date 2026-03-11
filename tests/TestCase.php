<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Gwhthompson\FilamentAiForms\FilamentAiFormsServiceProvider;
use Gwhthompson\FilamentAiForms\Tests\Fixtures\AdminPanelProvider;
use Gwhthompson\FilamentAiForms\Tests\Fixtures\TestUser;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Ai\AiServiceProvider;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Livewire DataStore mechanism is registered as a singleton
        $dataStore = new \Livewire\Mechanisms\DataStore;
        $dataStore->register();

        // Authenticate user for panel tests
        $this->actingAs(TestUser::factory()->create());
    }

    protected function tearDown(): void
    {
        // Flush Livewire state to prevent test pollution
        // See: https://github.com/livewire/livewire/issues/2489
        Livewire::flushState();

        // Force garbage collection to release WeakMap references
        // This ensures Filament's DataStore entries are cleared between tests
        gc_collect_cycles();

        // Reset the DataStore mechanism with a fresh instance
        $dataStore = new \Livewire\Mechanisms\DataStore;
        $dataStore->register();

        parent::tearDown();
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            LaravelDataServiceProvider::class,
            AiServiceProvider::class,
            FilamentAiFormsServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Database configuration
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Use array cache driver (avoids needing a cache table in SQLite)
        $app['config']->set('cache.default', 'array');

        // Auth provider configuration
        $app['config']->set('auth.providers.users.model', TestUser::class);

        // Add view paths for test fixtures
        $app['config']->set('view.paths', [
            ...$app['config']->get('view.paths', []),
            __DIR__.'/resources/views',
        ]);

        // Load full package config
        $app['config']->set('filament-ai-forms', [
            'agents' => [
                'generation' => null,
                'chat' => null,
            ],
            'logging' => [
                'enabled' => true,
                'path' => sys_get_temp_dir().'/filament-ai-forms-test-logs',
            ],
        ]);
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
