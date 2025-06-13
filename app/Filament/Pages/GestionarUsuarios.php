<?php

namespace App\Filament\Pages;

use App\Models\Gerencia;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Contracts\HasForms;

class GestionarUsuarios extends Page implements HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationLabel = null;
    protected static ?string $title = 'Gestionar Gerencia';
    protected static string $view = 'filament.pages.gestionar-usuarios';


    public $user;
    public $name, $email, $role;
    public $unidad_id, $gerencia_id;

    public function mount(): void
    {
        $this->user = User::findOrFail(request()->get('user'));

        $rolUsuario = $this->user->roles->pluck('name')->first();

        if ($rolUsuario !== 'usuario') {
            redirect()->route('filament.admin.pages.user-management-panel')->send();
        }

        $this->form->fill([
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => $rolUsuario,
            'unidad_id' => $this->user->unidad_administrativa_id,
            'gerencia_id' => $this->user->gerencia_id,
        ]);
    }

    public function getFormSchema(): array
    {
        $rolUsuario = $this->user->roles->pluck('name')->first();

        return [
            TextInput::make('name')
                ->label('Nombre')
                ->disabled()
                ->dehydrated(false),

            TextInput::make('email')
                ->label('Correo')
                ->disabled()
                ->dehydrated(false),

            TextInput::make('role')
                ->label('Rol')
                ->default($rolUsuario)
                ->disabled()
                ->dehydrated(false),

            Select::make('gerencia_id')
                ->label('Gerencia adscrita')
                ->options(function () {
                    $user = auth()->user();

                    if ($user->hasRole('admin')) {
                        return Gerencia::pluck('nombre', 'id');
                    }

                    if ($user->hasRole('administrador-unidad')) {
                        $unidadId = $user->unidadAdministrativa?->id;

                        if (!$unidadId) {
                            return [];
                        }

                        return Gerencia::where('unidad_administrativa_id', $unidadId)->pluck('nombre', 'id');
                    }

                    if ($user->hasRole('gerente')) {
                        $gerencia = $user->gerenciaQueDirige;

                        if (!$gerencia) {
                            return [];
                        }

                        return Gerencia::where('id', $gerencia->id)->pluck('nombre', 'id');
                    }

                    return [];
                })
                ->disabled(function () {
                    $user = auth()->user();

                    if ($user->hasRole('admin')) {
                        return false;
                    }

                    if ($user->hasRole('administrador-unidad')) {
                        return $user->unidadAdministrativa === null;
                    }

                    if ($user->hasRole('gerente')) {
                        return $user->gerenciaQueDirige === null;
                    }

                    return true;
                })
                ->placeholder('Selecciona una gerencia')
                ->nullable(),

        ];
    }

    public function removeKey()
    {
        $this->user->gerencia_id = null;
        $this->user->save();

        Notification::make()
            ->title('Gerencia retirada del usuario')
            ->success()
            ->send();

        return redirect()->route('filament.admin.pages.user-management-panel');
    }

    public function submit()
    {
        $data = $this->form->getState();

        // Solo actualiza la gerencia_id, que puede ser null
        $this->user->gerencia_id = $data['gerencia_id'];
        $this->user->save();

        Notification::make()
            ->title('Usuario actualizado')
            ->success()
            ->send();

        return redirect()->route('filament.admin.pages.user-management-panel');

    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->hasAnyRole(['admin', 'administrador-unidad', 'gerente']);
    }

}
