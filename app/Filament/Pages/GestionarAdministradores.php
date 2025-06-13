<?php

namespace App\Filament\Pages;

use App\Models\Gerencia;
use App\Models\UnidadAdministrativa;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Notification;

class GestionarAdministradores extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Gestión Administrativa';
    protected static ?string $title = 'Gestión Administrativa';
    protected static string $view = 'filament.pages.gestionar-administradores';

    public $unidadAdministrativas;
    public $gerencias;
    public $asignacionesUnidad = [];
    public $asignacionesGerencia = [];

    public function mount(): void
    {
        $this->unidadAdministrativas = UnidadAdministrativa::with('user')->get();
        $this->gerencias = Gerencia::with('gerente')->get();

        foreach ($this->unidadAdministrativas as $unidad) {
            $this->asignacionesUnidad[$unidad->id] = $unidad->user_id;
        }

        foreach ($this->gerencias as $gerencia) {
            $this->asignacionesGerencia[$gerencia->id] = $gerencia->user_id;
        }
    }

    public function updatedAsignacionesUnidad($value, $key)
    {
        UnidadAdministrativa::find($key)?->update(['user_id' => $value]);
        Notification::make()
            ->title('Administrador asignado')
            ->success()
            ->send();
    }

    public function updatedAsignacionesGerencia($value, $key)
    {
        $yaAsignada = Gerencia::where('user_id', $value)->where('id', '!=', $key)->exists();
        if ($yaAsignada) {
            Notification::make()
                ->title('Este usuario ya administra otra gerencia')
                ->danger()
                ->send();
            $this->asignacionesGerencia[$key] = null;
            return;
        }

        Gerencia::find($key)?->update(['user_id' => $value]);
        Notification::make()
            ->title('Gerente asignado')
            ->success()
            ->send();
    }
}
