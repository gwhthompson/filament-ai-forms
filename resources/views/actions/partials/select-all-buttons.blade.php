<div class="flex justify-end gap-2">
    <x-filament::link
        tag="button"
        color="primary"
        size="sm"
        x-on:click="
      Object.keys($wire.mountedActions[0].data)
        .filter(k => k.startsWith('field_'))
        .forEach(k => $wire.set('mountedActions.0.data.' + k, true));
    "
    >
        Select All
    </x-filament::link>

    <span class="text-gray-400">|</span>

    <x-filament::link
        tag="button"
        color="primary"
        size="sm"
        x-on:click="
      Object.keys($wire.mountedActions[0].data)
        .filter(k => k.startsWith('field_'))
        .forEach(k => $wire.set('mountedActions.0.data.' + k, false));
    "
    >
        Deselect All
    </x-filament::link>
</div>
