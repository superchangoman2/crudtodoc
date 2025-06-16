<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActividadResource\Pages;
use App\Models\Actividad;
use App\Models\Gerencia;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Enums\ActionsPosition;


class ActividadResource extends Resource
{
    protected static ?int $navigationSort = 1;
    protected static ?string $model = Actividad::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Crear Actividades';
    protected static ?string $pluralModelLabel = 'Actividades';
    protected static ?string $navigationGroup = 'Registros';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        if ($user->hasRole('administrador-unidad')) {
            $unidadNombre = $user->unidadAdministrativa?->nombre;

            $gerencias = Gerencia::where('unidad_administrativa_id', $user->pertenece_id)
                ->pluck('nombre')
                ->toArray();

            return $query->where(function ($q) use ($unidadNombre, $gerencias) {
                $q->where(function ($sub) use ($unidadNombre) {
                    $sub->where('pertenencia_tipo', Actividad::TIPO_UNIDAD)
                        ->where('pertenencia_nombre', $unidadNombre);
                })->orWhere(function ($sub) use ($gerencias) {
                    $sub->where('pertenencia_tipo', Actividad::TIPO_GERENCIA)
                        ->whereIn('pertenencia_nombre', $gerencias);
                });
            });
        }

        if ($user->hasAnyRole(['gerente', 'subgerente'])) {
            $gerenciaNombre = $user->gerencia?->nombre;

            return $query->where('pertenencia_tipo', Actividad::TIPO_GERENCIA)
                ->where('pertenencia_nombre', $gerenciaNombre);
        }

        if ($user->hasRole('usuario')) {
            return $query->where('user_id', $user->id);
        }
        return $query->whereRaw('1 = 0');
    }



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),

                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->required()
                    ->autosize()
                    ->columnSpanFull(),

                Select::make('tipo_actividad_id')
                    ->label('Tipo de Actividad')
                    ->relationship('tipoActividad', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('pertenencia_tipo')
                    ->label('Tipo de pertenencia')
                    ->disabled()
                    ->dehydrated(false)
                    ->default(
                        fn($state, $record, $component) =>
                        $component->getLivewire() instanceof \Filament\Resources\Pages\CreateRecord
                        ? auth()->user()->getPertenenciaInfo()['tipo']
                        : null
                    ),

                TextInput::make('pertenencia_nombre')
                    ->label('Pertenencia')
                    ->disabled()
                    ->dehydrated(false)
                    ->default(
                        fn($state, $record, $component) =>
                        $component->getLivewire() instanceof \Filament\Resources\Pages\CreateRecord
                        ? auth()->user()->getPertenenciaInfo()['nombre']
                        : null
                    ),

                DatePicker::make('fecha')
                    ->label('Fecha')
                    ->required()
                    ->default(today()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.first_name')
                    ->label('Nombres')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => auth()->user()?->hasRole(['admin', 'administrador-unidad', 'gerente'])),

                TextColumn::make('user.last_name')
                    ->label('Apellidos')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => auth()->user()?->hasRole(['admin', 'administrador-unidad', 'gerente'])),

                TextColumn::make('titulo')
                    ->label('Título')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('pertenencia_tipo')
                    ->label('Tipo')
                    ->sortable()
                    ->searchable()
                    ->visible(fn() => auth()->user()?->hasAnyRole(['admin', 'administrador-unidad', 'gerente'])),
                // Nota: se puede usar ->badge() para hacer cosas más bonitas y ->color()

                TextColumn::make('pertenencia_nombre')
                    ->label('Pertenencia')
                    ->sortable()
                    ->searchable()
                    ->visible(fn() => auth()->user()?->hasAnyRole(['admin', 'administrador-unidad', 'gerente']))
                    ->formatStateUsing(fn($state) => $state ?? 'Sin asignar'),

                TextColumn::make('tipoActividad.nombre')
                    ->label('Tipo de Actividad')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('fecha')
                    ->label('fecha')
                    ->sortable()
                    ->searchable(),
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
                DeleteAction::make()
                    ->visible(fn() => auth()->user()?->hasRole('admin')),

                RestoreAction::make()
                    ->visible(fn($record) => auth()->user()?->hasRole('admin') && $record->trashed()),

                ForceDeleteAction::make()
                    ->visible(fn($record) => auth()->user()?->hasRole('admin') && $record->trashed()),
            ], position: ActionsPosition::BeforeColumns)
            ->headerActions([
                Action::make('exportar')
                    ->label('Exportar PDF')
                    ->color('primary')
                    ->icon('heroicon-o-printer')
                    ->action(function () {
                        return redirect()->route('actividades.exportar-pdf');
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('admin')),
                    RestoreBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('admin')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('admin')),
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
            'index' => Pages\ListActividads::route('/'),
            'create' => Pages\CreateActividad::route('/create'),
            'edit' => Pages\EditActividad::route('/{record}/edit'),
        ];
    }
    public static function getModelLabel(): string
    {
        return 'actividad';
    }

    public static function getPluralModelLabel(): string
    {
        return 'actividades';
    }
}
