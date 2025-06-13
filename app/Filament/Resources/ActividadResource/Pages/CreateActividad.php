<?php

namespace App\Filament\Resources\ActividadResource\Pages;

use App\Filament\Resources\ActividadResource;
use Filament\Actions;
use App\Models\Gerencia;
use Filament\Resources\Pages\CreateRecord;

class CreateActividad extends CreateRecord
{
    protected static string $resource = ActividadResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $data['user_id'] = $user->id;

        // Determinar gerencia_id:
        if ($user->gerencia_id) {
            $data['gerencia_id'] = $user->gerencia_id;
        } elseif ($user->hasRole('gerente')) {
            $data['gerencia_id'] = Gerencia::where('user_id', $user->id)->value('id');
        } else {
            $data['gerencia_id'] = null; // o puedes abortar si es obligatorio
        }

        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
