<?php

namespace App\Filament\Resources\TipoActividadResource\Pages;

use App\Filament\Resources\TipoActividadResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTipoActividad extends CreateRecord
{
    protected static string $resource = TipoActividadResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

