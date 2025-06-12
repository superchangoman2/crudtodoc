<x-filament::page>
    <x-filament::card>
        <form wire:submit.prevent="submit">
            {{ $this->form }}

            <div class="mt-6 flex justify-between">
                <x-filament::button type="button" color="gray" tag="a"
                    href="{{ route('filament.admin.pages.user-admin-panel') }}">
                    Cancelar
                </x-filament::button>

                <x-filament::button type="submit">
                    Guardar usuario
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>
</x-filament::page>