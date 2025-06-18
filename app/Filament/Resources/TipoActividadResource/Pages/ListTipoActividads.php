<?php

namespace App\Filament\Resources\TipoActividadResource\Pages;

use App\Filament\Resources\TipoActividadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoActividads extends ListRecords
{
    protected static string $resource = TipoActividadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
