<?php

namespace App\Filament\Pages;

use App\Models\Gerencia;
use Filament\Pages\Page;
use Filament\Forms;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class MiPerfil extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'Mi perfil';
    protected static ?string $title = 'Mi perfil';
    protected static string $view = 'filament.pages.mi-perfil';

    public $name;
    public $email;
    public $current_password;
    public $new_password;
    public $new_password_confirmation;
    public bool $modoEdicion = false;

    public $id;
    public $rol;
    public $extra;
    public string $extraLabel = '';
    public $unidad;
    public $esGerente;


    public function mount(): void
    {
        $user = auth()->user();

        $this->id = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->rol = $user->roles->pluck('name')->first();

        if ($user->hasRole('gerente')) {
            $gerencia = $user->gerenciaQueDirige;
            $this->extraLabel = 'Gerencia dirigida';
            $this->extra = $gerencia?->nombre;

        } elseif ($user->hasRole('usuario')) {
            $gerencia = $user->gerencia;
            $this->extraLabel = 'Gerencia adscrita';
            $this->extra = $gerencia?->nombre;

        } elseif ($user->hasRole('administrador-unidad')) {
            $this->extraLabel = 'Unidad administrada';
            $this->extra = $user->unidadAdministrativa?->nombre;
        } else {
            $this->extraLabel = '---';
            $this->extra = null;
        }

        $this->form->fill([
            'name' => $this->name,
            'email' => $this->email,
        ]);
    }
    protected function getFormSchema(): array
    {
        if (!$this->modoEdicion) {
            return [];
        }

        return [
            Forms\Components\TextInput::make('name')->label('Nombre')->required(),
            Forms\Components\TextInput::make('email')->label('Correo')->email()->required(),

            Forms\Components\TextInput::make('current_password')
                ->label('Contraseña actual')
                ->password()
                ->revealable()
                ->requiredWith('new_password'),

            Forms\Components\TextInput::make('new_password')
                ->label('Nueva contraseña')
                ->password()
                ->revealable()
                ->minLength(8)
                ->same('new_password_confirmation'),

            Forms\Components\TextInput::make('new_password_confirmation')
                ->label('Confirmar nueva contraseña')
                ->password()
                ->revealable()
                ->extraAttributes(['onpaste' => 'return false']),
        ];
    }

    public function submit()
    {
        $data = $this->form->getState();
        $user = auth()->user();

        if ($data['new_password']) {
            if (!Hash::check($data['current_password'], $user->password)) {
                Notification::make()
                    ->title('Contraseña actual incorrecta')
                    ->danger()
                    ->send();
                return;
            }

            $user->password = bcrypt($data['new_password']);
            $user->save();

            Notification::make()
                ->title('Contraseña actualizada')
                ->success()
                ->send();

            return redirect()->route('filament.admin.pages.dashboard');
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->save();

        $this->modoEdicion = false;

        Notification::make()
            ->title('Perfil actualizado')
            ->success()
            ->send();
    }

    protected function messages(): array
    {
        return [
            'new_password.same' => 'La nueva contraseña y su confirmación no coinciden.',
            'new_password.min' => 'La nueva contraseña debe tener al menos :min caracteres.',
            'email.email' => 'El correo electrónico no es válido.',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}
