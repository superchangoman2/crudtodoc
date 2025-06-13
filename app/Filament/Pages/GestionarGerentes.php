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

class GestionarGerentes extends Page implements HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationLabel = null;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $title = 'Gestionar Gerente';
    protected static string $view = 'filament.pages.gestionar-gerentes';

    public $user;
    public $name, $email, $role, $gerencia_id;

    public function mount(): void
    {
        $this->user = User::findOrFail(request()->get('user'));

        $rolUsuario = $this->user->roles->pluck('name')->first();

        if ($rolUsuario !== 'gerente') {
            redirect()->route('filament.admin.pages.user-management-panel')->send();
        }

        $this->form->fill([
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => $rolUsuario,
            'gerencia_id' => $this->user->gerencia?->id,
        ]);
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('name')->label('Nombre')->disabled()->dehydrated(false),
            TextInput::make('email')->label('Correo')->disabled()->dehydrated(false),
            TextInput::make('role')->label('Rol')->disabled()->dehydrated(false),

            Select::make('gerencia_id')
                ->label('Gerencia a dirigir')
                ->options(Gerencia::pluck('nombre', 'id'))
                ->nullable()
                ->placeholder('Selecciona una gerencia'),
        ];
    }

    public function removeKey()
    {
        Gerencia::where('user_id', $this->user->id)->update(['user_id' => null]);

        Notification::make()
            ->title('Gerencia desasignada del gerente')
            ->success()
            ->send();

        return redirect()->route('filament.admin.pages.user-management-panel');
    }

    public function submit()
    {
        $data = $this->form->getState();

        Gerencia::where('user_id', $this->user->id)->update(['user_id' => null]);

        if ($data['gerencia_id']) {
            Gerencia::where('id', $data['gerencia_id'])->update(['user_id' => $this->user->id]);
        }

        Notification::make()
            ->title('Gerencia actualizada')
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
        return Auth::check() && Auth::user()->hasAnyRole(['admin', 'administrador-unidad']);
    }
}
