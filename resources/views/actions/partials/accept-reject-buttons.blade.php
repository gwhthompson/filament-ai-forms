<div class="mb-4 flex justify-end gap-2">
    <x-filament::button
        color="gray"
        size="sm"
        x-on:click="
      const data = $wire.mountedActions[0].data;
      Object.keys(data).filter(k => k.startsWith('accept_')).forEach(k => {
        $wire.set('mountedActions.0.data.' + k, false);
      });
    "
    >
        Reject All
    </x-filament::button>

    <x-filament::button
        color="success"
        size="sm"
        x-on:click="
      const data = $wire.mountedActions[0].data;
      Object.keys(data).filter(k => k.startsWith('accept_')).forEach(k => {
        $wire.set('mountedActions.0.data.' + k, true);
      });
    "
    >
        Accept All
    </x-filament::button>
</div>
