<?php

namespace App\Filament\Resources\ActividadResource\Pages;

use App\Models\Actividad;
use App\Filament\Resources\ActividadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActividad extends CreateRecord
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

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
