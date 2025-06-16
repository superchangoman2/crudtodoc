<x-filament::page>
    {{ $this->form }}

    <x-filament::actions :actions="$this->getActions()" class="mt-4" />
</x-filament::page>