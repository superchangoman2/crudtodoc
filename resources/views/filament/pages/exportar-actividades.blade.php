<x-filament::page>
    {{ $this->form }}

    <x-filament::actions :actions="$this->getExportActions()" class="mt-4" />
</x-filament::page>