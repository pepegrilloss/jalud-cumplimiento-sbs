<x-filament-panels::page>
    <form wire:submit="descargar" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit" icon="heroicon-o-archive-box-arrow-down">
                Descargar ZIP
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
