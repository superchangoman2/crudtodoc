<x-filament::page>
    <x-filament::card>
        @if (!$this->modoEdicion)

            <div class="">
                <h2 class="text-sm font-medium">Nombre:</h2>
                <p class="text-lg font-semibold">{{ auth()->user()->name }}</p>
            </div>

            <div class="pt-4">
                <h2 class="text-sm font-medium">Correo:</h2>
                <p class="text-lg font-semibold">{{ auth()->user()->email }}</p>
            </div>

            <div class="pt-4">
                <x-filament::button wire:click="$set('modoEdicion', true)">
                    Editar datos de perfil
                </x-filament::button>
            </div>
        @else
            <form wire:submit.prevent="submit">
                {{ $this->form }}

                <div class="mt-6 flex justify-between">
                    <x-filament::button color="gray" type="button" wire:click="$set('modoEdicion', false)">
                        Cancelar
                    </x-filament::button>

                    <x-filament::button type="submit">
                        Guardar cambios
                    </x-filament::button>
                </div>
            </form>

        @endif
    </x-filament::card>
</x-filament::page>