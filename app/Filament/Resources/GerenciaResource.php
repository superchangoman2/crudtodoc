<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GerenciaResource\Pages;
use App\Filament\Resources\GerenciaResource\RelationManagers;
use App\Models\Gerencia;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GerenciaResource extends Resource
{
    protected static ?string $model = Gerencia::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nombre')
                    ->label('Nombre de la Gerencia')
                    ->placeholder('Ej: Gerencia de Tecnología de la Información y Comunicación')
                    ->required()
                    ->minLength(5)
                    ->maxLength(255)
                    ->rule('string')
                    ->unique(ignoreRecord: true),
                Select::make('unidad_administrativa_id')
                    ->label('Unidad Administrativa')
                    ->relationship('unidadAdministrativa', 'nombre')
                    ->searchable()
                    ->preload()
                    ->nullable()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado el')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Desde'),
                        Forms\Components\DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGerencias::route('/'),
            'create' => Pages\CreateGerencia::route('/create'),
            'edit' => Pages\EditGerencia::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'gerencia';
    }

    public static function getPluralModelLabel(): string
    {
        return 'gerencias';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'administrador-unidad', 'gerente']);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'administrador-unidad', 'gerente']);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'administrador-unidad', 'gerente']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'administrador-unidad', 'gerente']);
    }
}
