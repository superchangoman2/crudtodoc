<?php

namespace App\Filament\Pages;

use App\Models\UnidadAdministrativa;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Contracts\HasForms;

class GestionarUnidades extends Page implements HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationLabel = null;
    protected static ?string $title = 'Gestionar Unidad';
    protected static string $view = 'filament.pages.gestionar-unidades';

    public $user;
    public $name, $email, $role, $pertenece_id;

    public function mount(): void
    {
        $this->user = User::findOrFail(request()->get('user'));

        $rolUsuario = $this->user->getRoleNames()->first();

        if ($rolUsuario !== 'administrador-unidad') {
            redirect()->route('filament.admin.pages.user-management-panel')->send();
        }

        $this->form->fill([
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => $rolUsuario,
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
                ->default('administrador-unidad')
                ->disabled()
                ->dehydrated(false),

            Select::make('pertenece_id')
                ->label('Unidad administrativa asignada')
                ->options(fn() => UnidadAdministrativa::pluck('nombre', 'id'))
                ->rules(['exists:unidades_administrativas,id'])
                ->placeholder('Selecciona una unidad')
                ->nullable()
                ->required(),
        ];
    }

    public function removeKey()
    {
        $this->user->update(['pertenece_id' => null]);

        Notification::make()
            ->title('Unidad retirada del administrador')
            ->success()
            ->send();

        return redirect()->route('filament.admin.pages.user-management-panel');
    }

    public function submit()
    {
        if ($this->user->getRoleNames()->first() !== 'administrador-unidad') {
            abort(403);
        }

        $data = $this->form->getState();

        $this->user->update(['pertenece_id' => $data['pertenece_id']]);

        Notification::make()
            ->title('Administrador actualizado')
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
        return Auth::check() && Auth::user()?->hasRole('admin');
    }
}
