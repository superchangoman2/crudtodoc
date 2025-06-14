<?php

namespace App\Filament\Pages;

use App\Models\Gerencia;
use App\Models\UnidadAdministrativa;
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

    public string $first_name, $last_name;
    public string $email;
    public ?string $current_password = '', $new_password = '', $new_password_confirmation = '';
    public bool $modoEdicion = false;
    public int $id;
    public string $rol;
    public $extra;
    public ?string $extraLabel = null;
    public $unidad;
    public $esGerente;

    public function mount(): void
    {
        $user = auth()->user();

        $this->id = $user->id;
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->rol = $user->roles->pluck('name')->first();
        $pertenencia = $user->pertenencia?->nombre;

        switch (true) {
            case $user->hasRole(['gerente', 'subgerente']):
                $this->extraLabel = 'Gerencia dirigida';
                $this->extra = Gerencia::find($user->pertenece_id)?->nombre;
                break;
            case $user->hasRole(['admin', 'usuario']):
                $this->extraLabel = 'Gerencia adscrita';
                $this->extra = Gerencia::find($user->pertenece_id)?->nombre;
                break;
            case $user->hasRole(['administrador-unidad']):
                $this->extraLabel = 'Unidad administrada';
                $this->extra = UnidadAdministrativa::find($user->pertenece_id)?->nombre;
                break;
            default:
                $this->extraLabel = null;
                $this->extra = null;
        }

        $this->form->fill([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
        ]);
    }

    protected function getFormSchema(): array
    {
        if (!$this->modoEdicion) {
            return [];
        }

        $schema = [
            Forms\Components\TextInput::make('first_name')->label('Nombre')->required(),
            Forms\Components\TextInput::make('last_name')->label('Apellido')->required(),
            Forms\Components\TextInput::make('email')->label('Correo')->email()->required(),
        ];

        $schema[] = Forms\Components\Fieldset::make('Cambio de contraseña')->schema([
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
        ]);

        return $schema;
    }

    public function submit()
    {
        $data = $this->form->getState();
        $user = auth()->user();

        if (!empty($data['new_password'])) {
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

        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->email = $data['email'];
        $user->save();

        $this->modoEdicion = false;
        $this->editPassword = false;

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
