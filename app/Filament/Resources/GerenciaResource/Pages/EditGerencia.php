<?php

namespace App\Filament\Resources\GerenciaResource\Pages;

use App\Filament\Resources\GerenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGerencia extends EditRecord
{
    protected static string $resource = GerenciaResource::class;

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
