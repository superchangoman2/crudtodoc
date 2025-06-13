<?php

namespace App\Filament\Resources\UnidadAdministrativaResource\Pages;

use App\Filament\Resources\UnidadAdministrativaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnidadAdministrativas extends ListRecords
{
    protected static string $resource = UnidadAdministrativaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
