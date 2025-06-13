<?php

namespace App\Filament\Resources\TipoActividadResource\Pages;

use App\Filament\Resources\TipoActividadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoActividad extends EditRecord
{
    protected static string $resource = TipoActividadResource::class;

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
