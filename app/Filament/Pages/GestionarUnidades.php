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
    public $name, $email, $role;
    public $unidad_id;

    public function mount(): void
    {
        $this->user = User::findOrFail(request()->get('user'));
        $rolUsuario = $this->user->roles->pluck('name')->first();

        if ($rolUsuario !== 'administrador-unidad') {
            redirect()->route('filament.admin.pages.user-management-panel')->send();
        }

        $this->form->fill([
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => $rolUsuario,
            'unidad_id' => $this->user->unidades_administrativas_id,
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

            Select::make('unidad_id')
                ->label('Unidad administrativa asignada')
                ->options(UnidadAdministrativa::pluck('nombre', 'id'))
                ->placeholder('Selecciona una unidad')
                ->nullable(),
        ];
    }

    public function removeKey()
    {
        $this->user->unidades_administrativas_id = null;
        $this->user->save();

        Notification::make()
            ->title('Unidad retirada del administrador')
            ->success()
            ->send();

        return redirect()->route('filament.admin.pages.user-management-panel');
    }

    public function submit()
    {
        $data = $this->form->getState();

        $this->user->unidades_administrativas_id = $data['unidad_id'];
        $this->user->save();

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
        return Auth::check() && Auth::user()?->hasAnyRole(['admin']);
    }
}
