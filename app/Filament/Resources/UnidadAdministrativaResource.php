<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnidadAdministrativaResource\Pages;
use App\Models\UnidadAdministrativa;

use Filament\Forms\Form;
use Filament\Forms\Components\{DatePicker, Placeholder, Select, TextInput};

use Filament\Resources\Resource;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\Filter;

use Filament\Tables\Actions\{
    DeleteAction,
    EditAction,
    RestoreAction,
    ForceDeleteAction,
};

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UnidadAdministrativaResource extends Resource
{
    protected static ?string $model = UnidadAdministrativa::class;

    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Unidades Administrativas';
    protected static ?string $pluralModelLabel = 'Unidades Administrativas';
    protected static ?string $navigationGroup = 'Base de datos';

    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query()->withTrashed();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nombre')
                    ->label('Nombre de UA')
                    ->placeholder('Ej: Coordinación General Jurídica')
                    ->required()
                    ->minLength(5)
                    ->maxLength(255)
                    ->rule('string')
                    ->unique(ignoreRecord: true),

                Placeholder::make('gerencias_list')
                    ->label('Gerencias asignadas')
                    ->content(function ($record) {
                        if (!$record || !$record->exists) {
                            return null;
                        }

                        if ($record->gerencias->isEmpty()) {
                            return 'Sin gerencias asignadas.';
                        }

                        return $record->gerencias
                            ->pluck('nombre')
                            ->map(fn($g) => "• $g ")
                            ->implode("\n");
                    })
                    ->columnSpanFull(),
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

                TextColumn::make('gerencias_count')
                    ->label('#Gerencias')
                    ->sortable()
                    ->counts('gerencias'),

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
                    }),
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
            'index' => Pages\ListUnidadAdministrativas::route('/'),
            'create' => Pages\CreateUnidadAdministrativa::route('/create'),
            'edit' => Pages\EditUnidadAdministrativa::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'unidad administrativa';
    }

    public static function getPluralModelLabel(): string
    {
        return 'unidades administrativas';
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
