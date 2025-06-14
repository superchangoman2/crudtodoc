<x-filament::page>
    <x-filament::card>
        <dl class="text-base space-y-6">
            {{-- Nombre --}}
            <div class="mt-6">
                <dt class="font-semibold text-sm mb-2">Nombre</dt>

                @if ($editName)
                    <div class="rounded-xl p-4 border space-y-4">
                        <input type="text" wire:model.defer="first_name" placeholder="Nombre"
                            class="w-full rounded-md text-gray-700" />
                        <input type="text" wire:model.defer="last_name" placeholder="Apellido"
                            class="w-full rounded-md text-gray-700" />

                        <div class="flex gap-2 pt-2">
                            <x-filament::icon-button icon="heroicon-o-check" wire:click="saveName" />
                            <x-filament::icon-button icon="heroicon-o-x-mark" wire:click="toggleEdit('editName')"
                                color="secondary" />
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-between">
                        <dd class="text-lg">{{ $first_name }} {{ $last_name }}</dd>
                        <x-filament::icon-button icon="heroicon-o-pencil" wire:click="toggleEdit('editName')" />
                    </div>
                @endif
            </div>

            {{-- Correo --}}
            <div class="mt-6">
                <dt class="font-semibold text-sm mb-2">Correo</dt>

                @if ($editEmail)
                    <div class="rounded-xl p-4 border space-y-4">
                        <input type="email" wire:model.defer="email" placeholder="Correo electrónico"
                            class="w-full rounded-md text-gray-700" />

                        <div class="flex gap-2 pt-2">
                            <x-filament::icon-button icon="heroicon-o-check" wire:click="saveEmail" />
                            <x-filament::icon-button icon="heroicon-o-x-mark" wire:click="toggleEdit('editEmail')"
                                color="secondary" />
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-between">
                        <dd class="text-lg">{{ $email }}</dd>
                        <x-filament::icon-button icon="heroicon-o-pencil" wire:click="toggleEdit('editEmail')" />
                    </div>
                @endif
            </div>

            {{-- Rol --}}
            <div>
                <dt class="font-semibold text-sm ">Rol</dt>
                <dd class="text-lg capitalize">{{ $rol }}</dd>
            </div>

            {{-- Extra --}}
            @if ($extraLabel !== null)
                <div>
                    <dt class="font-semibold text-sm ">{{ $extraLabel }}</dt>
                    <dd class="text-lg ">{{ $extra ?? 'Sin asignar' }}</dd>
                </div>
            @endif

            {{-- Contraseña --}}
            <div class="mt-6">
                <dt class="font-semibold text-sm mb-2">Contraseña</dt>

                @if ($editPassword)
                    <div class="rounded-xl p-4 border space-y-4">
                        {{-- Contraseña actual --}}
                        <div x-data="{ show: false }" class="relative w-full">
                            <input :type="show ? 'text' : 'password'" wire:model.defer="password_actual"
                                placeholder="Contraseña actual"
                                class="w-full rounded-md text-gray-700 pr-10 pl-3 py-2 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500 transition" />

                            <button type="button" @click="show = !show"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-500">
                                <template x-if="!show">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </template>
                                <template x-if="show">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.964 9.964 0 012.442-4.042M6.1 6.1A9.961 9.961 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.958 9.958 0 01-4.018 5.1M15 12a3 3 0 00-3-3M9.88 9.88a3 3 0 104.24 4.24" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 3l18 18" />
                                    </svg>
                                </template>
                            </button>
                        </div>




                        {{-- Nueva contraseña --}}
                        <div x-data="{ show: false }" class="relative">
                            <input :type="show ? 'text' : 'password'" wire:model.defer="password_nueva"
                                placeholder="Nueva contraseña" class="w-full rounded-md text-gray-700 pr-10" />
                            <button type="button" @click="show = !show"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500">
                                <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                                <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" />
                            </button>
                        </div>

                        {{-- Confirmar nueva contraseña --}}
                        <div x-data="{ show: false }" class="relative">
                            <input :type="show ? 'text' : 'password'" wire:model.defer="password_confirmacion"
                                placeholder="Confirmar nueva contraseña" class="w-full rounded-md text-gray-700 pr-10" />
                            <button type="button" @click="show = !show"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500">
                                <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                                <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" />
                            </button>
                        </div>

                        {{-- Botones --}}
                        <div class="flex gap-2 pt-2">
                            <x-filament::button wire:click="savePassword" size="sm">Guardar</x-filament::button>
                            <x-filament::button wire:click="toggleEdit('editPassword')" size="sm" color="secondary">
                                Cancelar
                            </x-filament::button>
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-between mt-2">
                        <dd class="text-lg italic">••••••••</dd>
                        <x-filament::button wire:click="toggleEdit('editPassword')" size="sm" color="secondary">
                            Cambiar contraseña
                        </x-filament::button>
                    </div>
                @endif
            </div>

        </dl>
    </x-filament::card>
</x-filament::page>