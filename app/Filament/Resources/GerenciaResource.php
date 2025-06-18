<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GerenciaResource\Pages;
use App\Models\Gerencia;
use App\Models\User;

use Filament\Forms\Form;
use Filament\Forms\Components\{DatePicker, Select, TextInput, Placeholder};

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

class GerenciaResource extends Resource
{
    protected static ?string $model = Gerencia::class;

    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Gerencias';
    protected static ?string $pluralModelLabel = 'Gerencias';
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
                    ->required()
                    ->searchable()
                    ->preload(),

                Placeholder::make('gerente')
                    ->label('Gerente asignado')
                    ->content(function ($record) {
                        if (!$record || !$record->exists)
                            return '—';
                        $gerente = User::where('pertenece_id', $record->id)
                            ->whereHas('roles', fn($q) => $q->where('name', 'gerente'))
                            ->first();

                        return $gerente
                            ? "{$gerente->name} ({$gerente->email})"
                            : 'Sin gerente asignado.';
                    }),

                Placeholder::make('subgerente')
                    ->label('Subgerente asignado')
                    ->content(function ($record) {
                        if (!$record || !$record->exists)
                            return '—';
                        $subgerente = User::where('pertenece_id', $record->id)
                            ->whereHas('roles', fn($q) => $q->where('name', 'subgerente'))
                            ->first();

                        return $subgerente
                            ? "{$subgerente->name} ({$subgerente->email})"
                            : 'Sin subgerente asignado.';
                    }),
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

                TextColumn::make('unidadAdministrativa.nombre')
                    ->label('Unidad Administrativa')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('users_count')
                    ->label('#Usuarios')
                    ->sortable()
                    ->counts('users'),

                TextColumn::make('gerente_visible')
                    ->label('Correo del responsable')
                    ->getStateUsing(function ($record) {
                        $gerente = User::where('pertenece_id', $record->id)
                            ->whereHas('roles', fn($q) => $q->where('name', 'gerente'))
                            ->first();

                        return $gerente?->email ?? '—';
                    }),
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
        return [];
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