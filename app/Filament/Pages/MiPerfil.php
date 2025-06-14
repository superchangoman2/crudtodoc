<?php

namespace App\Filament\Pages;

use App\Models\Gerencia;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class MiPerfil extends Page
{
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'Mi perfil';
    protected static ?string $title = 'Mi perfil';
    protected static string $view = 'filament.pages.mi-perfil';
    protected static ?string $navigationGroup = 'Usuarios y Accesos';

    // Campos visibles/editables
    public string $first_name, $last_name, $email;
    public string $password_actual = '', $password_nueva = '', $password_confirmacion = '';

    // Estados de edición
    public bool $editName = false, $editEmail = false, $editPassword = false;
    public bool $showPassword = false;
    // Otros datos del perfil
    public string $rol = '';
    public string $extraLabel = '';
    public $extra;

    public function mount(): void
    {
        $user = auth()->user();

        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->rol = $user->roles->pluck('name')->first();

        if ($user->hasRole(['gerente', 'subgerente'])) {
            $gerencia = $user->gerenciaQueDirige;
            $this->extraLabel = 'Gerencia dirigida';
            $this->extra = $gerencia?->nombre;
        } elseif ($user->hasRole(['admin', 'usuario'])) {
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
    }

    public function toggleEdit(string $field): void
    {
        $this->{$field} = !$this->{$field};

        if (!$this->{$field}) {
            $user = auth()->user();

            if ($field === 'editName') {
                $this->first_name = $user->first_name;
                $this->last_name = $user->last_name;
            }

            if ($field === 'editEmail') {
                $this->email = auth()->user()->email;
            }

            if ($field === 'editPassword') {
                $this->reset(['password_actual', 'password_nueva', 'password_confirmacion']);
            }
        }
    }


    public function saveName(): void
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]);

        auth()->user()->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
        ]);

        $this->editName = false;

        Notification::make()
            ->title('Nombre actualizado correctamente')
            ->success()
            ->send();
    }

    public function saveEmail(): void
    {
        $this->validate([
            'email' => 'required|email|unique:users,email,' . auth()->id(),
        ]);

        auth()->user()->update([
            'email' => $this->email,
        ]);

        $this->editEmail = false;

        Notification::make()
            ->title('Correo actualizado correctamente')
            ->success()
            ->send();
    }


    public function savePassword(): void
    {
        $this->validate([
            'password_actual' => 'required|current_password',
            'password_nueva' => 'required|min:8|same:password_confirmacion',
        ], $this->messages());

        auth()->user()->update([
            'password' => bcrypt($this->password_nueva),
        ]);

        $this->editPassword = false;
        $this->reset(['password_actual', 'password_nueva', 'password_confirmacion']);

        Notification::make()
            ->title('Contraseña actualizada')
            ->success()
            ->send();
    }


    protected function messages(): array
    {
        return [
            'password_nueva.same' => 'La nueva contraseña y su confirmación no coinciden.',
            'password_nueva.min' => 'La nueva contraseña debe tener al menos :min caracteres.',
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
