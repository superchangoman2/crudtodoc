<?php

namespace App\Filament\Pages;

use App\Models\Gerencia;
use App\Models\UnidadAdministrativa;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

class AdministrarUsuarios extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationLabel = null;
    protected static ?string $title = 'Administrar Usuario';
    protected static string $view = 'filament.pages.administrar-usuarios';

    public $user;
    public $name, $email, $role;
    public $unidad_id, $gerencia_id;

    public function mount(): void
    {
        $this->user = User::findOrFail(request()->get('user'));

        $this->form->fill([
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => $this->user->roles->pluck('name')->first(),
            'unidad_id' => $this->user->unidad_administrativa_id,
            'gerencia_id' => $this->user->gerencia_id,
        ]);
    }

    public function getFormSchema(): array
    {
        $esAdmin = Auth::user()?->hasRole('admin');
        $esUnidad = Auth::user()?->hasRole('administrador-unidad');
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

            Select::make('unidad_id')
                ->label('Unidad Administrativa')
                ->options(UnidadAdministrativa::pluck('nombre', 'id'))
                ->hidden(!($rolUsuario === 'administrador-unidad' && $esAdmin)),

            Select::make('gerencia_id')
                ->label(
                    $rolUsuario === 'gerente' ? 'Gerencia a dirigir' :
                    ($rolUsuario === 'usuario' ? 'Gerencia adscrita' : 'Gerencia')
                )
                ->options(Gerencia::pluck('nombre', 'id'))
                ->hidden(!(
                    ($rolUsuario === 'gerente' && ($esAdmin || $esUnidad)) ||
                    ($rolUsuario === 'usuario' && ($esAdmin || $esUnidad))
                )),
        ];
    }

    public function submit()
    {
        $data = $this->form->getState();

        if ($this->user->hasRole('administrador-unidad')) {
            $this->user->unidad_administrativa_id = $data['unidad_id'];
        }

        if ($this->user->hasRole('gerente') || $this->user->roles->isEmpty()) {
            $this->user->gerencia_id = $data['gerencia_id'];
        }

        $this->user->save();

        Notification::make()
            ->title('Usuario actualizado')
            ->success()
            ->send();

        return redirect()->route('filament.admin.pages.user-admin-panel');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->hasRole('admin');
    }
}
