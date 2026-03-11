<div class="mb-4 flex justify-end gap-2">
    <x-filament::button
        color="gray"
        size="sm"
        x-on:click="
      let idx = $wire.mountedActions.length - 1;
      const data = $wire.mountedActions.at(-1).data;
      Object.keys(data).filter(k => k.startsWith('accept_')).forEach(k => {
        $wire.set(`mountedActions.${idx}.data.${k}`, false);
      });
    "
    >
        Reject All
    </x-filament::button>

    <x-filament::button
        color="success"
        size="sm"
        x-on:click="
      let idx = $wire.mountedActions.length - 1;
      const data = $wire.mountedActions.at(-1).data;
      Object.keys(data).filter(k => k.startsWith('accept_')).forEach(k => {
        $wire.set(`mountedActions.${idx}.data.${k}`, true);
      });
    "
    >
        Accept All
    </x-filament::button>
</div>
