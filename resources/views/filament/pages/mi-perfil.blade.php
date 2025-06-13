<x-filament::page>
    <x-filament::card>
        @if (!$this->modoEdicion)
            <dl class="text-base text-white/90 space-y-4">
                <div>
                    <dt class="font-semibold text-sm text-white/70">Nombre</dt>
                    <dd class="text-lg">{{ $name }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-sm text-white/70">Correo</dt>
                    <dd class="text-lg">{{ $email }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-sm text-white/70">Rol</dt>
                    <dd class="text-lg capitalize">{{ $rol }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-sm text-white/70">{{ $extraLabel }}</dt>
                    <dd class="text-lg">{{ $extra ?? 'Sin asignar' }}</dd>
                </div>
            </dl>

            <x-filament::button wire:click="$set('modoEdicion', true)" class="mt-6">
                Editar perfil
            </x-filament::button>
        @else
            <form wire:submit.prevent="submit">
                {{ $this->form }}

                <x-filament::button type="submit" class="mt-4">
                    Guardar cambios
                </x-filament::button>

                <x-filament::button color="secondary" type="button" wire:click="$set('modoEdicion', false)"
                    class="mt-2 ml-2">
                    Cancelar
                </x-filament::button>
            </form>
        @endif
    </x-filament::card>
</x-filament::page>