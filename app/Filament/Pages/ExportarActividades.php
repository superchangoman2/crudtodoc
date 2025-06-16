<?php

namespace App\Filament\Pages;

use App\Models\Gerencia;
use App\Models\UnidadAdministrativa;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\{TextInput, Select, DatePicker, Toggle, Radio, Placeholder};
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Carbon;

class ExportarActividades extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.exportar-actividades';
    protected static ?string $navigationLabel = 'Exportar Actividades';
    protected static ?string $title = 'Exportar Reporte de Actividades';

    public array $formData = [];

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema(static::makeFormSchema())
            ->statePath('formData');
    }

    public function mount(): void
    {
        $this->form->fill([
            'incluir_eliminados' => false,
            'propio' => true,
            'modo_fecha' => 'quincena',
            'year' => now()->year,
        ]);
    }

    protected static function makeFormSchema(): array
    {
        Carbon::setLocale('es');
        $user = auth()->user();

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
            Toggle::make('incluir_eliminados')
                ->label('Incluir actividades eliminadas')
                ->visible(fn() => auth()->user()->hasRole('admin'))
                ->onColor('success'),

            Placeholder::make('debug_incluir_eliminados')
                ->label('Valor actual de "incluir_eliminados"')
                ->content(fn($get) => json_encode($get('incluir_eliminados')))
                ->visible(true),

            Toggle::make('propio')
                ->label('Reporte propio')
                ->default(true)
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state === true) {
                        $set('unidad_administrativa', null);
                        $set('gerencias', []);
                        $set('usuarios', []);
                    }
                }),

            Placeholder::make('debug_otros')
                ->label('Valor actual de "otros"')
                ->content(fn($get) => json_encode($get('propio')))
                ->visible(true),

            Select::make('unidad_administrativa')
                ->label('Unidad administrativa')
                ->options(UnidadAdministrativa::orderBy('id')->pluck('nombre', 'id'))
                ->searchable()
                ->visible(fn($get) => !$get('propio') && auth()->user()->hasRole('admin'))
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if (blank($state)) {
                        $set('gerencias', []);
                        $set('usuarios', []);
                    }
                }),

            Placeholder::make('debug_unidad_administrativa')
                ->label('Valor actual de "unidad_administrativa"')
                ->content(fn($get) => json_encode($get('unidad_administrativa')))
                ->visible(true),

            Select::make('gerencias')
                ->label('Gerencias')
                ->options(function ($get) {
                    $query = Gerencia::query();

                    if ($unidadId = $get('unidad_administrativa')) {
                        $query->where('unidad_administrativa_id', $unidadId);
                    }

                    return $query->orderBy('nombre')->pluck('nombre', 'nombre');
                })
                ->searchable()
                ->visible(fn($get) => !$get('propio') && filled($get('unidad_administrativa')) && auth()->user()->hasAnyRole(['admin', 'administrador-unidad']))
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if (blank($state)) {
                        $set('usuarios', []);
                    }
                }),

            Placeholder::make('debug_gerencias')
                ->label('Valor actual de "gerencias"')
                ->content(fn($get) => json_encode($get('gerencias')))
                ->visible(true),

            Select::make('usuarios')
                ->label('Usuarios')
                ->options(function ($get) {
                    $gerenciasSeleccionadas = $get('gerencias');
                    if (empty($gerenciasSeleccionadas)) {
                        return [];
                    }
                    return User::whereHas('gerencia', fn($q) => $q->whereIn('nombre', $gerenciasSeleccionadas))
                        ->orderBy('id')
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->visible(
                    fn($get) =>
                    !$get('propio')
                    && filled($get('gerencias'))
                    && auth()->user()->hasAnyRole(['administrador', 'administrador-unidad', 'gerente', 'subgerente'])
                )
                ->live(),

            Placeholder::make('debug_usuarios')
                ->label('Valor actual de "usuarios"')
                ->content(fn($get) => json_encode($get('usuarios')))
                ->visible(true),

            Radio::make('modo_fecha')
                ->label('Rango de fechas')
                ->options([
                    'quincena' => 'Quincenal',
                    'mes' => 'Mensual',
                    'personalizado' => 'Personalizado',
                ])
                ->live(),

            Placeholder::make('modo_fecha')
                ->label('Valor actual de "modo_fecha"')
                ->content(fn($get) => json_encode($get('modo_fecha')))
                ->visible(true),

            TextInput::make('year')
                ->label('Año')
                ->numeric()
                ->minValue(2000) // si deseas, se puede consultar `Actividad::min('fecha')`
                ->maxValue(now()->year),

            Placeholder::make('debug_year')
                ->label('Valor actual de "year"')
                ->content(fn($get) => json_encode($get('year')))
                ->visible(true),


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
        ];
    }

    public function formActions(): array
    {
        return [
            Action::make('exportar')
                ->label('Generar PDF')
                ->color('primary')
                ->action('exportar')
                ->icon('heroicon-o-printer'),
        ];
    }

    public function exportar()
    {
        $params = http_build_query(array_filter($this->formData));
        return redirect()->to('/exportar-pdf?' . $params);
    }
}
