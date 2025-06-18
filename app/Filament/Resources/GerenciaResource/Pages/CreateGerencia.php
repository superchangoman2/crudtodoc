<?php

namespace App\Filament\Resources\GerenciaResource\Pages;

use App\Filament\Resources\GerenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGerencia extends CreateRecord
{
    protected static string $resource = GerenciaResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
