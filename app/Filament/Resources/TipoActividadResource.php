<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipoActividadResource\Pages;
use App\Models\TipoActividad;

use Filament\Forms\Components\{DatePicker, Select, TextInput};
use Filament\Forms\Form;

use Filament\Resources\Resource;

use Filament\Tables\Actions\{
    DeleteAction,
    EditAction,
    RestoreAction,
    ForceDeleteAction,
};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\{Builder, SoftDeletingScope};

class TipoActividadResource extends Resource
{
    protected static ?string $model = TipoActividad::class;

    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Tipo de Actividades';
    protected static ?string $pluralModelLabel = 'Tipo de Actividades';
    protected static ?string $navigationGroup = 'Base de datos';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->hasRole('admin')) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query;
    }
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
                Filter::make('trashed')
                    ->label('Registros eliminados')
                    ->visible(fn() => auth()->user()?->hasRole('admin'))
                    ->form([
                        Select::make('estado')
                            ->label('Mostrar')
                            ->options([
                                'activos' => 'Solo activos',
                                'eliminados' => 'Solo eliminados',
                                'todos' => 'Todos',
                            ])
                            ->default('activos'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['estado'] ?? 'activos') {
                            'eliminados' => $query->onlyTrashed(),
                            'todos' => $query->withTrashed(),
                            default => $query->withoutTrashed(),
                        };
                    })
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make()
                    ->visible(fn($record) => $record->trashed()),
                ForceDeleteAction::make()
                    ->visible(fn($record) => $record->trashed()),
            ], position: ActionsPosition::BeforeColumns)
        ;
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
