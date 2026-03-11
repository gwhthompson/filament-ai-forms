<div class="flex justify-end gap-2">
    <x-filament::link
        tag="button"
        color="primary"
        size="sm"
        x-on:click="
      let idx = $wire.mountedActions.length - 1;
      Object.keys($wire.mountedActions.at(-1).data)
        .filter(k => k.startsWith('field_'))
        .forEach(k => $wire.set(`mountedActions.${idx}.data.${k}`, true));
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
      let idx = $wire.mountedActions.length - 1;
      Object.keys($wire.mountedActions.at(-1).data)
        .filter(k => k.startsWith('field_'))
        .forEach(k => $wire.set(`mountedActions.${idx}.data.${k}`, false));
    "
    >
        Deselect All
    </x-filament::link>
</div>
