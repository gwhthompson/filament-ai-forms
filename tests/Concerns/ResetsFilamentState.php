<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests\Concerns;

use Filament\Support\Livewire\Partials\PartialsComponentHook;

/**
 * Trait to reset Filament's internal state between tests.
 *
 * This addresses a Filament 4 test isolation issue where the `hasActionsModalRendered`
 * property persists between tests, causing `RootTagMissingFromViewException` when
 * mounting action modals in subsequent tests.
 *
 * @see https://github.com/filamentphp/filament/issues/17857
 * @see https://github.com/filamentphp/filament/issues/14397
 */
trait ResetsFilamentState
{
    /**
     * Reset Filament's internal action modal state.
     *
     * Called automatically by Livewire's component lifecycle when tests refresh the application.
     * The `mount` method is called on fresh component instances, ensuring clean state.
     */
    public function resetFilamentState(): void
    {
        // Reset action modal rendered flag (InteractsWithActions trait)
        if (property_exists($this, 'hasActionsModalRendered')) {
            $this->hasActionsModalRendered = false;
        }

        // Reset cached actions
        if (property_exists($this, 'cachedActions')) {
            $this->cachedActions = [];
        }

        // Reset mounted actions cache
        if (property_exists($this, 'cachedMountedActions')) {
            $this->cachedMountedActions = null;
        }

        // Force full render to bypass partial render optimization
        // This ensures the modal is rendered fresh in tests
        app(PartialsComponentHook::class)->forceRender($this);
    }

    /**
     * Boot the trait and reset state on mount.
     *
     * This ensures state is reset when the component is freshly instantiated in tests.
     */
    public function bootResetsFilamentState(): void
    {
        $this->resetFilamentState();
    }
}
