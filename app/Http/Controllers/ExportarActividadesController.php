<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\UnidadAdministrativa;
use App\Models\Gerencia;
use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;


class ExportarActividadesController extends Controller
{
    public function export(Request $request)
    {
        Carbon::setLocale('es');
        $data = $request->all();

        // Construir query base
        $query = Actividad::query();


        // Filtros clave
        if (!empty($data['usuario'])) {
            $query->whereIn('user_id', is_array($data['usuario']) ? $data['usuario'] : [$data['usuario']]);
        }

        if (!empty($data['incluir_eliminados'])) {
            $query->withTrashed();
        }

        // Filtro por fechas
        if ($data['modo_fecha'] === 'quincena' && !empty($data['quincena_seleccionada']) && !empty($data['year'])) {
            [$mes, $q] = explode('-', $data['quincena_seleccionada']);
            $inicio = date("Y-{$mes}-" . ($q == '1' ? '01' : '16'));
            $fin = date("Y-{$mes}-" . ($q == '1' ? '15' : date("t", strtotime($inicio))));
            $query->whereBetween('fecha', [$data['year'] . '-' . substr($inicio, 5), $data['year'] . '-' . substr($fin, 5)]);
        } elseif ($data['modo_fecha'] === 'mes' && !empty($data['mes_seleccionado']) && !empty($data['year'])) {
            $inicio = "{$data['year']}-{$data['mes_seleccionado']}-01";
            $fin = date("Y-m-t", strtotime($inicio));
            $query->whereBetween('fecha', [$inicio, $fin]);
        } elseif ($data['modo_fecha'] === 'anual') {
            $inicio = "{$data['year']}-01-01";
            $fin = "{$data['year']}-12-31";
            $query->whereBetween('fecha', [$inicio, $fin]);
        } elseif ($data['modo_fecha'] === 'personalizado') {
            $query->whereBetween('fecha', [$data['fecha_inicio'], $data['fecha_fin']]);
        }

        // Obtener actividades
        $actividades = $query->with('user')->get();
        $usuarios = $actividades->pluck('user')->unique('id');
        $autorUnico = $usuarios->count() === 1 ? $usuarios->first()->name : null;

        // Construir título
        $tituloModo = match ($data['modo_fecha']) {
            'anual' => 'Anual',
            'quincena' => 'Quincenal',
            'mes' => 'Mensual',
            'personalizado' => 'Personalizado',
            default => null,
        };

        $titulo = 'Reporte de actividades';
        if ($tituloModo) {
            $titulo .= " $tituloModo";
        }

        if (!empty($data['unidad_administrativa'])) {
            $unidad = UnidadAdministrativa::find($data['unidad_administrativa']);
            $titulo .= ' - ' . $unidad?->nombre;
        }
        if ($actividades->isEmpty()) {
            $titulo .= ' (Sin actividades registradas)';
        }

        $mesNombre = null;
        $quincenaTexto = null;

        if ($data['modo_fecha'] === 'quincena' && !empty($data['quincena_seleccionada'])) {
            [$mesNum, $q] = explode('-', $data['quincena_seleccionada']);
            $mesNombre = Carbon::createFromDate($data['year'], $mesNum, 1)->translatedFormat('F');
            $quincenaTexto = $q == '1' ? 'Primera quincena de' : 'Segunda quincena de';
        }

        $rangoFechas = match ($data['modo_fecha']) {
            'anual' => $data['year'],
            'quincena' => "$quincenaTexto $mesNombre {$data['year']}",
            'mes' => Carbon::createFromDate($data['year'], $data['mes_seleccionado'], 1)->translatedFormat('F \d\e Y'),
            'personalizado' => 'Del ' . Carbon::parse($data['fecha_inicio'])->translatedFormat('j \d\e F \d\e Y') .
            ' al ' . Carbon::parse($data['fecha_fin'])->translatedFormat('j \d\e F \d\e Y'),
            default => null,
        };

        $pdf = Pdf::loadView('filament.pages.actividades-pdf', compact('actividades', 'titulo', 'rangoFechas', 'autorUnico'))
            ->setPaper('letter', 'landscape')
            ->stream('reporte-actividades.pdf');

        return $pdf;
    }
}
