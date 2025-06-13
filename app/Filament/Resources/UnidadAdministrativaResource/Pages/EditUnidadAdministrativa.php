<?php

namespace App\Filament\Resources\UnidadAdministrativaResource\Pages;

use App\Filament\Resources\UnidadAdministrativaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnidadAdministrativa extends EditRecord
{
    protected static string $resource = UnidadAdministrativaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
