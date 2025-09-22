<x-filament::page>
    <div x-data class="relative">

        <div
            wire:loading.delay.longer
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-25"
        >
            <div class="flex flex-col items-center gap-3 p-4 rounded-xl">
                <x-filament::loading-indicator class="w-10 h-10 text-primary-600" />
            </div>
        </div>

        {{ $this->form }}

        <x-filament::actions :actions="$this->getExportActions()" class="mt-4" />
    </div>
</x-filament::page>
