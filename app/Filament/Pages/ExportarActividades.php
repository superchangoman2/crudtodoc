<?php

namespace App\Filament\Pages;

use App\Models\{Gerencia, UnidadAdministrativa, User};
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\{TextInput, Select, DatePicker, Toggle, Radio, Placeholder, Section, Hidden};
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class ExportarActividades extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.exportar-actividades';
    protected static ?string $navigationLabel = 'Exportar Actividades';
    protected static ?string $title = 'Exportar Reporte de Actividades';
    protected static ?string $navigationGroup = 'Registros';

    public array $formData = [];

    public function form(Forms\Form $form): Forms\Form
    {
        Carbon::setLocale('es');
        return $form
            ->schema(static::makeFormSchema())
            ->statePath('formData');
    }

    public function mount(): void
    {
        $mes = now()->month;
        $quincena = now()->day <= 15 ? '1' : '2';
        $quincenaActual = "$mes-$quincena";
        $mesActual = str_pad($mes, 2, '0', STR_PAD_LEFT);

        $this->form->fill([
            'incluir_eliminados' => false,
            'propio' => true,
            'unidad_administrativa' => null,
            'gerencia' => null,
            'gerencias_de_unidad' => null,
            'rol_usuario' => null,
            'usuario' => null,
            'modo_fecha' => 'quincena',
            'year' => now()->year,
            'quincena_seleccionada' => $quincenaActual,
            'mes_seleccionado' => $mesActual,
            'fecha_inicio' => now()->startOfMonth()->toDateString(),
            'fecha_fin' => now()->toDateString(),
        ]);
    }

    protected static function makeFormSchema(): array
    {
        return [
            ...self::makeControlesToggles(),
            ...self::makeCamposUnidadGerenciaUsuario(),
            ...self::makeControlesFecha(),
            ...self::makeCamposDepuracion(),
        ];
    }

    protected static function makeControlesToggles(): array
    {
        return [
            Section::make('- Principal')
                ->visible(fn() => auth()->user()->hasRole(['admin', 'administrador-unidad', 'gerente', 'subgerente']))
                ->schema([
                    Toggle::make('incluir_eliminados')
                        ->label('Incluir actividades eliminadas')
                        ->onColor('success')
                        ->visible(fn() => auth()->user()->hasRole('admin')),

                    Toggle::make('propio')
                        ->label('Reporte propio')
                        ->default(true)
                        ->live()
                        ->onColor('success')
                        ->visible(fn() => auth()->user()->hasRole(['admin', 'administrador-unidad', 'gerente', 'subgerente']))
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state === true) {
                                $set('unidad_administrativa', null);
                                $set('gerencia', null);
                                $set('rol_usuario', null);
                                $set('usuario', null);
                            }
                        }),
                ]),
        ];
    }

    protected static function makeCamposUnidadGerenciaUsuario(): array
    {
        $rol = auth()->user()->getRoleNames()->first();

        $visiblesPorRol = match ($rol) {
            'admin' => ['administrador-unidad', 'gerente', 'subgerente', 'usuario'],
            'administrador-unidad' => ['gerente', 'subgerente', 'usuario'],
            'gerente' => ['subgerente', 'usuario'],
            default => [],
        };

        return [
            Section::make('- Seleccionar usuarios')
                ->visible(fn($get) => !$get('propio') && auth()->user()->hasRole(['admin', 'administrador-unidad', 'gerente', 'subgerente']))
                ->schema([
                    Select::make('unidad_administrativa')
                        ->label('Unidad administrativa')
                        ->options(UnidadAdministrativa::orderBy('id')->pluck('nombre', 'id'))
                        ->placeholder('Todas')
                        ->searchable()
                        ->live()
                        ->visible(fn($get) => !$get('propio') && auth()->user()->hasRole('admin'))
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (blank($state)) {
                                $set('gerencia', null);
                                $set('rol_usuario', null);
                                $set('usuario', null);
                                $set('gerencias_de_unidad', null);
                            }

                            $gerenciasIds = Gerencia::where('unidad_administrativa_id', $state)
                                ->pluck('id')
                                ->toArray();

                            $set('gerencias_de_unidad', $gerenciasIds);
                        }),

                    Hidden::make('gerencias_de_unidad')
                        ->dehydrated(false)
                        ->visible(false),

                    Select::make('gerencia')
                        ->label('gerencia')
                        ->searchable()
                        ->options(function ($get) {
                            $unidadId = $get('unidad_administrativa');
                            return $unidadId
                                ? Gerencia::where('unidad_administrativa_id', $unidadId)
                                    ->orderBy('nombre')
                                    ->pluck('nombre', 'id')
                                : [];
                        })
                        ->placeholder('Todas')
                        ->visible(fn($get) => !$get('propio') && filled($get('unidad_administrativa')) && auth()->user()->hasAnyRole(['admin', 'administrador-unidad']))
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (blank($state)) {
                                $set('rol_usuario', null);
                                $set('usuario', null);
                            }
                        }),

                    Select::make('rol_usuario')
                        ->label('Filtrar por rol')
                        ->options(array_combine($visiblesPorRol, $visiblesPorRol))
                        ->placeholder('Todos')
                        ->searchable()
                        ->visible(fn() => auth()->user()->hasAnyRole(['admin', 'administrador-unidad', 'gerente']))
                        ->live(),

                    Select::make('usuario')
                        ->label('Usuario')
                        ->searchable()
                        ->options(function ($get) use ($visiblesPorRol) {
                            $rolFiltro = $get('rol_usuario');
                            $rolUsuario = auth()->user()->getRoleNames()->first();
                            $unidadId = $get('unidad_administrativa');
                            $unidadNombre = $get('unidad_administrativa_nombre');
                            $gerencia = $get('gerencia'); // id
                            $gerenciaNombre = $get('gerencia_nombre'); // nombre
                            $gerenciasDeUnidadIds = array_filter($get('gerencias_de_unidad') ?? []);

                            $query = User::query();

                            // CASO 1: Admin sin unidad -> no se filtra por actividades
                            if (!($rolUsuario === 'admin' && blank($unidadId))) {
                                $query->where(function ($q) use ($unidadId, $unidadNombre, $gerencia, $gerenciaNombre, $gerenciasDeUnidadIds, $rolUsuario) {
                                    // CASO 2: Admin con unidad pero sin gerencia
                                    if ($rolUsuario === 'admin' && filled($unidadId) && blank($gerencia)) {
                                        $q->where(function ($subQ) use ($unidadId, $gerenciasDeUnidadIds) {
                                            $subQ->where(function ($q1) use ($unidadId) {
                                                $q1->where('pertenece_id', $unidadId)
                                                    ->whereHas('roles', fn($r) => $r->where('name', 'administrador-unidad'));
                                            })->orWhere(function ($q2) use ($gerenciasDeUnidadIds) {
                                                $q2->whereIn('pertenece_id', $gerenciasDeUnidadIds)
                                                    ->whereHas('roles', fn($r) =>
                                                        $r->whereIn('name', ['gerente', 'subgerente', 'usuario']));
                                            });
                                        });
                                    }

                                    // CASO 3: Unidad y gerencia definidas
                                    if (filled($unidadId) && filled($gerencia)) {
                                        $q->where(function ($subQ) use ($unidadId, $gerencia) {
                                            $subQ->where(function ($q1) use ($unidadId) {
                                                $q1->where('pertenece_id', $unidadId)
                                                    ->whereHas('roles', fn($r) => $r->where('name', 'administrador-unidad'));
                                            })->orWhere(function ($q2) use ($gerencia) {
                                                $q2->where('pertenece_id', $gerencia)
                                                    ->whereHas('roles', fn($r) =>
                                                        $r->whereIn('name', ['gerente', 'subgerente', 'usuario']));
                                            });
                                        });
                                    }

                                });
                            }

                            // Filtro por rol
                            if ($rolFiltro) {
                                $query->role($rolFiltro);
                            } else {
                                $query->role($visiblesPorRol);
                            }

                            return $query->orderBy('id')
                                ->get()
                                ->mapWithKeys(fn($user) => [
                                    $user->id => "{$user->name}, - {$user->getRoleNames()->implode(', ')}"
                                ])
                                ->toArray();
                        })
                        ->visible(
                            fn($get) =>
                            !$get('propio') &&
                            auth()->user()->hasAnyRole(['admin', 'administrador-unidad', 'gerente'])
                        )
                        ->live(),

                ]),
        ];
    }

    protected static function makeControlesFecha(): array
    {
        $meses = collect(range(1, 12))->mapWithKeys(fn($m) => [
            str_pad($m, 2, '0', STR_PAD_LEFT) => now()->startOfMonth()->month($m)->translatedFormat('F')
        ]);

        $quincenas = collect(range(1, 12))->flatMap(function ($mes) {
            $nombreMes = now()->month($mes)->translatedFormat('F');
            return [
                "$mes-1" => "$nombreMes 1ª quincena",
                "$mes-2" => "$nombreMes 2ª quincena",
            ];
        });

        return [
            Section::make('- Seleccionar fechas')
                ->schema([
                    Radio::make('modo_fecha')
                        ->label('Rango de fechas')
                        ->options([
                            'quincena' => 'Quincenal',
                            'mes' => 'Mensual',
                            'personalizado' => 'Personalizado',
                            'anual' => 'Anual',
                        ])
                        ->default('quincena')
                        ->live(),

                    TextInput::make('year')
                        ->label('Año')
                        ->numeric()
                        ->minValue(2000)
                        ->maxValue(now()->year)
                        ->visible(fn($get) => in_array($get('modo_fecha'), ['quincena', 'mes', 'anual'])),

                    Select::make('quincena_seleccionada')
                        ->label('Selecciona una quincena')
                        ->options($quincenas)
                        ->visible(fn($get) => $get('modo_fecha') === 'quincena'),

                    Select::make('mes_seleccionado')
                        ->label('Selecciona un mes')
                        ->options($meses)
                        ->visible(fn($get) => $get('modo_fecha') === 'mes'),

                    DatePicker::make('fecha_inicio')
                        ->label('Desde')
                        ->default(now()->startOfMonth())
                        ->visible(fn($get) => $get('modo_fecha') === 'personalizado'),

                    DatePicker::make('fecha_fin')
                        ->label('Hasta')
                        ->default(now())
                        ->visible(fn($get) => $get('modo_fecha') === 'personalizado'),
                ]),
        ];
    }

    protected static function makeCamposDepuracion(): array
    {
        return [
            Section::make('-Debug-')
                ->visible(fn($get) => auth()->user()->hasRole(['admin']))
                ->schema([
                    Placeholder::make('debug_toggles')
                        ->label('🟩 Estado de toggles')
                        ->content(
                            fn($get) =>
                            "propio: " . json_encode($get('propio')) .
                            "incluir_eliminados: " . json_encode($get('incluir_eliminados'))
                        )
                        ->visible(true),

                    Placeholder::make('debug_unidad_gerencia_usuarios')
                        ->label('🟦 Unidad / Gerencias / Usuarios')
                        ->content(
                            fn($get) =>
                            "unidad_administrativa: " . json_encode($get('unidad_administrativa')) .
                            "gerencias: " . json_encode($get('gerencia')) .
                            "gerencias_de_unidad" . json_encode($get("gerencias_de_unidad")) .
                            "rol_usuario: " . json_encode($get('rol_usuario')) .
                            "usuarios: " . json_encode($get('usuario'))
                        )
                        ->visible(true),

                    Placeholder::make('debug_fechas')
                        ->label('🟨 Fechas')
                        ->content(
                            fn($get) =>
                            "modo_fecha: " . json_encode($get('modo_fecha')) .
                            "year: " . json_encode($get('year')) .
                            "quincena_seleccionada: " . json_encode($get('quincena_seleccionada')) .
                            "mes_seleccionado: " . json_encode($get('mes_seleccionado')) .
                            "fecha_inicio: " . json_encode($get('fecha_inicio')) .
                            "fecha_fin: " . json_encode($get('fecha_fin'))
                        )
                        ->visible(true),
                ]),
        ];
    }

    public function getExportActions(): array
    {
        return [
            Action::make('exportar')
                ->label('Generar PDF')
                ->color('primary')
                ->icon('heroicon-o-printer')
                ->action('exportar'),
        ];
    }
    public function exportar()
    {
        $params = http_build_query(array_filter($this->formData));
        return redirect()->to('/exportar-pdf?' . $params);
    }
}


