<?php

namespace App\Filament\Resources\ActividadResource\Pages;

use App\Filament\Resources\ActividadResource;
use Filament\Actions;
use App\Models\Actividad;
use Filament\Resources\Pages\EditRecord;

class EditActividad extends EditRecord
{
    protected static string $resource = ActividadResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        $data['user_id'] = $user->id;

        if ($user->hasRole('administrador-unidad')) {
            $unidad = $user->unidadAdministrativa;
            $data['pertenencia_nombre'] = $unidad?->nombre;
            $data['pertenencia_tipo'] = Actividad::TIPO_UNIDAD;
        } elseif ($user->hasAnyRole(['admin', 'gerente', 'subgerente', 'usuario'])) {
            $gerencia = $user->gerenciaQueDirige ?? $user->gerencia ?? null;
            $data['pertenencia_nombre'] = $gerencia?->nombre;
            $data['pertenencia_tipo'] = Actividad::TIPO_GERENCIA;
        } else {
            $data['pertenencia_nombre'] = 'Sin asignar';
            $data['pertenencia_tipo'] = Actividad::TIPO_OTRO;
        }

        return $data;
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
