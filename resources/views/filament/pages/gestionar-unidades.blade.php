<x-filament::page>
    <x-filament::card>
        <form wire:submit.prevent="submit">
            {{ $this->form }}

            <div class="mt-6 flex justify-between">
                <x-filament::button type="button" color="gray" tag="a"
                    href="{{ route('filament.admin.pages.user-management-panel') }}">
                    Cancelar
                </x-filament::button>

                <div class="flex gap-6">
                    <x-filament::button type="button" color="danger" wire:click="removeKey">
                        Quitar unidad
                    </x-filament::button>

                    <x-filament::button type="submit">
                        Guardar administrador
                    </x-filament::button>
                </div>
            </div>
        </form>
    </x-filament::card>
</x-filament::page>