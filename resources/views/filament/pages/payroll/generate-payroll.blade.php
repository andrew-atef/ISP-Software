<x-filament-panels::page>
    <form wire:submit="generate" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end gap-3">
            <x-filament::button type="submit" color="success">
                Generate Payrolls
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
