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
    protected static ?string $title = 'Gestionar Usuario';
    protected static string $view = 'filament.pages.gestionar-usuarios';

    public $user;
    public $name, $email, $role, $pertenece_id;

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
            'role' => 'usuario',
            'pertenece_id' => $this->user->pertenece_id,
        ]);
    }

    public function getFormSchema(): array
    {
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
                ->default('usuario')
                ->disabled()
                ->dehydrated(false),

            Select::make('pertenece_id')
                ->label('Gerencia adscrita')
                ->options(function () {
                    $user = auth()->user();

                    if ($user->hasRole('admin')) {
                        return Gerencia::pluck('nombre', 'id');
                    }

                    if ($user->hasRole('administrador-unidad')) {
                        $unidadId = $user->pertenece_id;
                        return Gerencia::where('unidad_administrativa_id', $unidadId)->pluck('nombre', 'id');
                    }

                    if ($user->hasRole(['gerente'])) {
                        $gerencia = $user->gerenciaQueDirige;
                        return $gerencia ? [$gerencia->id => $gerencia->nombre] : [];
                    }

                    return [];
                })
                ->placeholder('Selecciona una gerencia')
                ->nullable()
                ->required(),
        ];
    }

    public function removeKey()
    {
        $this->user->update(['pertenece_id' => null]);

        Notification::make()
            ->title('Gerencia retirada del usuario')
            ->success()
            ->send();

        return redirect()->route('filament.admin.pages.user-management-panel');
    }

    public function submit()
    {
        $data = $this->form->getState();

        $this->user->update([
            'pertenece_id' => $data['pertenece_id'],
        ]);

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
        return Auth::check() && Auth::user()?->hasAnyRole(['admin', 'administrador-unidad', 'gerente', 'subgerente']);
    }
}
