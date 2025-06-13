<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipoActividadResource\Pages;
use App\Filament\Resources\TipoActividadResource\RelationManagers;
use App\Models\TipoActividad;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class TipoActividadResource extends Resource
{
    protected static ?string $model = TipoActividad::class;

    protected static ?int $navigationSort = 6;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Tipo de Actividades';
    protected static ?string $pluralModelLabel = 'Tipo de Actividades';
    protected static ?string $navigationGroup = 'Base de datos';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nombre')
                    ->label('Tipo de actividad')
                    ->placeholder('Ej: Sustantiva')
                    ->required()
                    ->minLength(2)
                    ->maxLength(255)
                    ->rule('string')
                    ->unique(ignoreRecord: true)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Registrado el')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListTipoActividads::route('/'),
            'create' => Pages\CreateTipoActividad::route('/create'),
            'edit' => Pages\EditTipoActividad::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'tipo actividad';
    }

    public static function getPluralModelLabel(): string
    {
        return 'tipos actividades';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['admin']);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasAnyRole(['admin']);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasAnyRole(['admin']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin']);
    }
}
