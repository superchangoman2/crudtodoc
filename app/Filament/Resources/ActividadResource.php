<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActividadResource\Pages;
use App\Models\Actividad;
use App\Models\Gerencia;

use Filament\Forms\Components\{DatePicker, Select, TextInput, Textarea};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\{
    Action,
    DeleteAction,
    EditAction,
    ForceDeleteAction,
    RestoreAction,
};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\{Filter, SelectFilter};
use Filament\Tables\Table;
use Filament\Notifications\Notification;

use Illuminate\Support\Carbon;

use Illuminate\Database\Eloquent\Builder;

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
        $query = static::getModel()::query();
        if (auth()->user()?->hasRole('admin')) {
            $query = $query->withTrashed();
        }
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('administrador-unidad')) {
            $unidadId = $user->pertenece_id;
            $unidadNombre = $user->unidadAdministrativa?->nombre;

            $gerencias = Gerencia::where('unidad_administrativa_id', $unidadId)
                ->pluck('nombre')
                ->toArray();

            return $query->where(function ($q) use ($user, $unidadNombre, $gerencias) {
                $q->where('user_id', $user->id)
                    ->orWhere(function ($sub) use ($unidadNombre) {
                        $sub->where('pertenencia_tipo', Actividad::TIPO_UNIDAD)
                            ->where('pertenencia_nombre', $unidadNombre);
                    })
                    ->orWhere(function ($sub) use ($gerencias) {
                        $sub->where('pertenencia_tipo', Actividad::TIPO_GERENCIA)
                            ->whereIn('pertenencia_nombre', $gerencias);
                    });
            });
        }

        if ($user->hasRole('gerente')) {
            $gerenciaNombre = Gerencia::find($user->pertenece_id)?->nombre;

            return $query->where('pertenencia_tipo', Actividad::TIPO_GERENCIA)
                ->where('pertenencia_nombre', $gerenciaNombre)
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhereHas('user.roles', fn($q2) => $q2->whereIn('name', ['usuario', 'subgerente']));
                });

        }
        if ($user->hasRole('subgerente')) {
            $gerenciaNombre = Gerencia::find($user->pertenece_id)?->nombre;

            return $query->where('pertenencia_tipo', Actividad::TIPO_GERENCIA)
                ->where('pertenencia_nombre', $gerenciaNombre)
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhereHas('user.roles', fn($q2) => $q2->whereIn('name', ['usuario']));
                });
        }

        return $query->where('user_id', $user->id);
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
                    ->maxDate(Carbon::today())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (blank($state)) {
                            return;
                        }

                        $sel = Carbon::parse($state);

                        if ($sel->isFuture()) {

                            $set('fecha', Carbon::today()->toDateString());

                            Notification::make()
                                ->title('La fecha no puede ser futura')
                                ->body('Selecciona una fecha igual o anterior a hoy.')
                                ->danger()
                                ->send();
                        }
                    })
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            ToggleColumn::make('autorizado')
                ->label('Aut.')
                ->alignCenter()
                ->disabled(fn () => ! auth()->user()?->hasAnyRole(['gerente', 'administrador-unidad', 'admin']))
                ->visible(function ($record) {
                    if (! $record) return true;
                    if (! method_exists($record, 'trashed')) return true;
                    return ! $record->trashed();
                })
                ->afterStateUpdated(function (bool $state, $record) {
                    // Auditar...
                }),
                TextColumn::make('user.name')
                    ->label('Nombre completo')
                    ->getStateUsing(fn($record) => $record->user?->name)
                    ->sortable(query: function ($query, $direction) {
                        return $query->join('users', 'users.id', '=', 'actividades.user_id')
                            ->orderBy('users.first_name', $direction)
                            ->orderBy('users.last_name', $direction)
                            ->select('actividades.*');
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('user', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('titulo')
                    ->label('Título')
                    ->sortable()
                    ->searchable(),

 /*               TextColumn::make('pertenencia_nombre')
                    ->label('Pertenencia')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn($state) => $state ?? 'Sin asignar'),
*/
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function (?string $state): string {
                        if (blank($state)) return 'Sin descripción';
                        
                        return strlen($state) > 20 ? mb_substr($state, 0, 30) . '...' : $state;
                    }),

                TextColumn::make('tipoActividad.nombre')
                    ->label('Tipo de Actividad')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                Filter::make('rango_fecha')
                    ->form([
                        DatePicker::make('from')
                            ->label('Desde'),
                        DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['from'])) {
                            $indicators[] = 'Desde: '.\Illuminate\Support\Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if (!empty($data['until'])) {
                            $indicators[] = 'Hasta: '.\Illuminate\Support\Carbon::parse($data['until'])->format('d/m/Y');
                        }
                        return $indicators;
                    })
                    ->query(function (Builder $query, array $data) {
                        $from  = !empty($data['from'])  ? \Illuminate\Support\Carbon::parse($data['from'])->startOfDay() : null;
                        $until = !empty($data['until']) ? \Illuminate\Support\Carbon::parse($data['until'])->endOfDay()   : null;

                        return $query
                            ->when($from,  fn ($q) => $q->where('fecha', '>=', $from->toDateString()))
                            ->when($until, fn ($q) => $q->where('fecha', '<=', $until->toDateString()));
                    }),

                Filter::make('trashed')
                    ->label('Registros eliminados')
                    ->visible(fn() => auth()->user()?->hasRole('admin'))
                    ->form([
                        Select::make('estado')
                            ->label('Mostrar')
                            ->options([
                                'activos'    => 'Solo activos',
                                'eliminados' => 'Solo eliminados',
                                'todos'      => 'Todos',
                            ])
                            ->default('activos'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['estado'] ?? 'activos') {
                            'eliminados' => $query->onlyTrashed(),
                            'todos'      => $query->withTrashed(),
                            default      => $query->withoutTrashed(),
                        };
                    }),

                // Filtro "Solo mis actividades"
                Filter::make('propias')
                    ->label('Solo mis actividades')
                    ->default(true)
                    ->visible(fn() => auth()->user()?->hasAnyRole(['admin', 'administrador-unidad', 'gerente', 'subgerente']))
                    ->toggle()
                    ->query(function (Builder $query, array $data) {
                        return isset($data['isActive']) && $data['isActive']
                            ? $query->where('user_id', auth()->id())
                            : $query;
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),

            RestoreAction::make()
                ->visible(fn ($record) => auth()->user()?->hasRole('admin') && ($record?->trashed() ?? false)),

            ForceDeleteAction::make()
                ->visible(fn ($record) => auth()->user()?->hasRole('admin') && ($record?->trashed() ?? false)),

            ], position: ActionsPosition::BeforeColumns)
            ->headerActions([
                Action::make('exportar')
                    ->label('Exportar actividad quincenal')
                    ->color('primary')
                    ->icon('heroicon-o-printer')
                    ->action(function () {
                        $now = now();
                        $mes = $now->month;
                        $quincena = $now->day <= 15 ? '1' : '2';
                        $quincenaActual = "$mes-$quincena";

                        $params = http_build_query([
                            'propio' => 'true',
                            'incluir_eliminados' => 'false',
                            'modo_fecha' => 'quincena',
                            'year' => $now->year,
                            'quincena_seleccionada' => $quincenaActual,
                        ]);

                        return redirect("/admin/exportar-actividades?$params");
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
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
        return 'actividades';Desc
    }
}
