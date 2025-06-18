<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
// TODO: Revisar lo del botón que redirige a la edición avanzada
// use Filament\Tables\Actions\Action;
// use Filament\Tables\Actions\EditAction;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    // public function getTableActions(): array
    // {
    //     return [
    //         Action::make('edicionAvanzada')
    //             ->label('Avanzado')
    //             ->icon('heroicon-o-cog-6-tooth')
    //             ->url(fn($record) => route('filament.admin.pages.crear-usuario-extendido', ['user' => $record->id]))
    //             ->tooltip('Ir a edición avanzada'),
    //         EditAction::make(),
    //     ];
    // }
}
