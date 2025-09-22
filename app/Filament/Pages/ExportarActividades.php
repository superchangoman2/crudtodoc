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
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';
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

        $rol = auth()->user()->getRoleNames()->first();
        $pertenenciaId = auth()->user()->pertenece_id;

        $unidadId = null;
        if (in_array($rol, ['gerente', 'subgerente'])) {
            $unidadId = \DB::table('vista_gerencias_extendida')->where('id', $pertenenciaId)->value('unidad_administrativa_id');
        }

        $gerenciasIds = null;
        $unidadConsulta = $rol === 'administrador-unidad' ? $pertenenciaId : $unidadId;
        if ($unidadConsulta) {
            $idsString = \DB::table('vista_unidades_extendida')->where('id', $unidadConsulta)->value('gerencias_ids');
            $gerenciasIds = $idsString ? array_filter(explode(',', $idsString)) : null;
        }

        $datosIniciales = [
            'incluir_eliminados' => false,
            'propio' => true,
            'unidad_administrativa' => $rol === 'administrador-unidad' ? $pertenenciaId : $unidadId,
            'gerencia' => in_array($rol, ['gerente', 'subgerente']) ? $pertenenciaId : null,
            'gerencias_de_unidad' => $gerenciasIds,
            'rol_usuario' => null,
            'usuario' => null,
            'modo_fecha' => 'quincena',
            'year' => now()->year,
            'quincena_seleccionada' => $quincenaActual,
            'mes_seleccionado' => $mesActual,
            'fecha_inicio' => now()->startOfMonth()->toDateString(),
            'fecha_fin' => now()->toDateString(),
        ];

        $this->form->fill($datosIniciales);
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
                        ->extraAttributes(['wire:loading.attr' => 'disabled'])
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
            'subgerente' => ['usuario'],
            default => [],
        };

        return [
            Section::make('- Seleccionar usuario')
                ->visible(fn($get) => !$get('propio') && auth()->user()->hasRole(['admin', 'administrador-unidad', 'gerente', 'subgerente']))
                ->schema([
                    Select::make('unidad_administrativa')
                        ->label('Unidad administrativa')
                        ->options(UnidadAdministrativa::orderBy('id')->pluck('nombre', 'id'))
                        ->placeholder('Todas')
                        ->searchable()
                        ->live()
                        ->extraAttributes(['wire:loading.attr' => 'disabled'])
                        ->visible(fn($get) => !$get('propio') && auth()->user()->hasRole('admin'))
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('gerencias_de_unidad', null);
                            $set('gerencia', null);
                            $set('rol_usuario', null);
                            $set('usuario', null);

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
                        ->extraAttributes(['wire:loading.attr' => 'disabled'])
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('rol_usuario', null);
                            $set('usuario', null);
                        }),

                    Select::make('rol_usuario')
                        ->label('Filtrar por rol')
                        ->options(array_combine($visiblesPorRol, $visiblesPorRol))
                        ->placeholder('Todos')
                        ->searchable()
                        ->visible(fn() => auth()->user()->hasAnyRole(['admin', 'administrador-unidad', 'gerente']))
                        ->live()
                        ->extraAttributes(['wire:loading.attr' => 'disabled'])->afterStateUpdated(function ($state, callable $set) {
                            $set('usuario', null);
                        }),

                    Select::make('usuario')
                        ->label('Usuario')
                        ->searchable()
                        ->options(function ($get) use ($visiblesPorRol) {
                            $rolFiltro = $get('rol_usuario');
                            $rolUsuario = auth()->user()->getRoleNames()->first();
                            $unidadId = $get('unidad_administrativa');
                            $unidadNombre = $get('unidad_administrativa_nombre');
                            $gerencia = $get('gerencia');
                            $gerenciaNombre = $get('gerencia_nombre');
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
                            auth()->user()->hasAnyRole(['admin', 'administrador-unidad', 'gerente', 'subgerente'])
                        )
                        ->live()
                        ->extraAttributes(['wire:loading.attr' => 'disabled']),

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
                        ->live()
                        ->extraAttributes(['wire:loading.attr' => 'disabled']),

                    TextInput::make('year')
                        ->label('Año')
                        ->numeric()
                        ->minValue(2000)
                        ->maxValue(now()->year)
                        ->visible(fn($get) => in_array($get('modo_fecha'), ['quincena', 'mes', 'anual']))
                        ->live()
                        ->extraAttributes(['wire:loading.attr' => 'disabled']),

                    Select::make('quincena_seleccionada')
                    ->label('Selecciona una quincena')
                    ->options($quincenas)
                    ->visible(fn($get) => $get('modo_fecha') === 'quincena')
                    ->live()
                        ->extraAttributes(['wire:loading.attr' => 'disabled']),

                    Select::make('mes_seleccionado')
                        ->label('Selecciona un mes')
                        ->options($meses)
                        ->visible(fn($get) => $get('modo_fecha') === 'mes')
                        ->live()
                        ->extraAttributes(['wire:loading.attr' => 'disabled']),

                    DatePicker::make('fecha_inicio')
                        ->label('Desde')
                        ->default(now()->startOfMonth())
                        ->visible(fn($get) => $get('modo_fecha') === 'personalizado')
                        ->live()
                        ->extraAttributes(['wire:loading.attr' => 'disabled']),

                    DatePicker::make('fecha_fin')
                        ->label('Hasta')
                        ->default(now())
                        ->visible(fn($get) => $get('modo_fecha') === 'personalizado')
                        ->live()
                        ->extraAttributes(['wire:loading.attr' => 'disabled']),
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

                    Placeholder::make('debug_unidad_gerencia_usuario')
                        ->label('🟦 Unidad / Gerencias / Usuario')
                        ->content(
                            fn($get) =>
                            "unidad_administrativa: " . json_encode($get('unidad_administrativa')) .
                            "gerencia: " . json_encode($get('gerencia')) .
                            "gerencias_de_unidad" . json_encode($get("gerencias_de_unidad")) .
                            "rol_usuario: " . json_encode($get('rol_usuario')) .
                            "usuario: " . json_encode($get('usuario'))
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
            Action::make('exportar_pdf')
                ->label('Generar PDF')
                ->color('primary')
                ->icon('heroicon-o-printer')
                ->url(function () {
                    $state  = $this->form->getState();
                    $params = $this->paramsNormalizados($state);
                    return url('/exportar-pdf') . '?' . http_build_query($params);
                })
                ->openUrlInNewTab(true),

            Action::make('exportar_doc')
                ->label('Generar Word')
                ->color('primary')
                ->icon('heroicon-o-document-text')
                ->url(function () {
                    $state  = $this->form->getState();
                    $params = $this->paramsNormalizados($state);
                    return url('/exportar-doc') . '?' . http_build_query($params);
                })
                ->openUrlInNewTab(true),
        ];
    }

    public function exportar()
    {
        $data = $this->formData;

        match ($data['modo_fecha']) {
            'quincena' => [
                $data['mes_seleccionado'] = null,
                $data['fecha_inicio'] = null,
                $data['fecha_fin'] = null,
            ],
            'mes' => [
                $data['quincena_seleccionada'] = null,
                $data['fecha_inicio'] = null,
                $data['fecha_fin'] = null,
            ],
            'anual' => [
                $data['quincena_seleccionada'] = null,
                $data['mes_seleccionado'] = null,
                $data['fecha_inicio'] = null,
                $data['fecha_fin'] = null,
            ],
            'personalizado' => [
                $data['quincena_seleccionada'] = null,
                $data['mes_seleccionado'] = null,
                $data['year'] = null,
            ],
            default => [],
        };

        $params = http_build_query(array_filter($data));
        return redirect()->to('/exportar-pdf?' . $params);
    }

    private function paramsNormalizados(array $data): array
    {
        switch ($data['modo_fecha'] ?? null) {
            case 'quincena':
                $data['mes_seleccionado'] = null;
                $data['fecha_inicio']     = null;
                $data['fecha_fin']        = null;
                break;
            case 'mes':
                $data['quincena_seleccionada'] = null;
                $data['fecha_inicio']          = null;
                $data['fecha_fin']             = null;
                break;
            case 'anual':
                $data['quincena_seleccionada'] = null;
                $data['mes_seleccionado']      = null;
                $data['fecha_inicio']          = null;
                $data['fecha_fin']             = null;
                break;
            case 'personalizado':
                $data['quincena_seleccionada'] = null;
                $data['mes_seleccionado']      = null;
                $data['year']                  = null;
                break;
        }

        return array_filter($data, function ($v) {
            if (is_array($v)) {
                return count(array_filter($v, fn($x) => $x !== null && $x !== '')) > 0;
            }
            return $v !== null && $v !== '' && $v !== false;
        });
    }


}


