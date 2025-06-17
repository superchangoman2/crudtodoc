<?php

namespace App\Http\Controllers;

use App\Models\{Actividad, UnidadAdministrativa, User};
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class ExportarActividadesController extends Controller
{
    public function export(Request $request)
    {
        Carbon::setLocale('es');
        $data = $request->all();

        $query = Actividad::query();
        $usuario = auth()->user();

        if (!empty($data['propio']) && $data['propio'] === '1') {
            $query->where('user_id', $usuario->id);
        } elseif (!empty($data['usuario'])) {
            $ids = is_array($data['usuario']) ? $data['usuario'] : [$data['usuario']];
            $query->whereIn('user_id', $ids);
        }

        $rangoFechas = "Fecha no disponible";

        if (!empty($data['modo_fecha'])) {
            switch ($data['modo_fecha']) {
                case 'quincena':
                    if (!empty($data['quincena_seleccionada']) && !empty($data['year'])) {
                        [$mes, $q] = explode('-', $data['quincena_seleccionada']);
                        $inicio = date("Y-{$mes}-" . ($q == '1' ? '01' : '16'));
                        $fin = date("Y-{$mes}-" . ($q == '1' ? '15' : date("t", strtotime($inicio))));
                        $query->whereBetween('fecha', ["{$data['year']}-" . substr($inicio, 5), "{$data['year']}-" . substr($fin, 5)]);

                        $mesNombre = Carbon::createFromDate($data['year'], $mes, 1)->translatedFormat('F');
                        $quincenaTexto = $q == '1' ? 'Primera quincena de' : 'Segunda quincena de';
                        $rangoFechas = "$quincenaTexto $mesNombre {$data['year']}";
                    }
                    break;
                case 'mes':
                    if (!empty($data['mes_seleccionado']) && !empty($data['year'])) {
                        $inicio = "{$data['year']}-{$data['mes_seleccionado']}-01";
                        $fin = date("Y-m-t", strtotime($inicio));
                        $query->whereBetween('fecha', [$inicio, $fin]);

                        $rangoFechas = Carbon::createFromDate($data['year'], $data['mes_seleccionado'], 1)->translatedFormat('F \d\e Y');
                    }
                    break;
                case 'anual':
                    $query->whereYear('fecha', $data['year']);
                    $rangoFechas = $data['year'];
                    break;
                case 'personalizado':
                    if (!empty($data['fecha_inicio']) && !empty($data['fecha_fin'])) {
                        $query->whereBetween('fecha', [$data['fecha_inicio'], $data['fecha_fin']]);
                        $rangoFechas = 'Del ' . Carbon::parse($data['fecha_inicio'])->translatedFormat('j \d\e F \d\e Y') .
                            ' al ' . Carbon::parse($data['fecha_fin'])->translatedFormat('j \d\e F \d\e Y');
                    }
                    break;
            }
        }

        $actividades = $query->with('user')->orderBy('fecha')->get();

        $usuarios = $actividades->pluck('user')->unique('id');
        $autorUnico = $usuarios->count() === 1 ? $usuarios->first()->name : null;

        $titulo = $autorUnico
            ? "Reporte de actividades de {$autorUnico}"
            : "Reporte de actividades";

        return Pdf::loadView('filament.pages.actividades-pdf', compact('actividades', 'titulo', 'rangoFechas', 'autorUnico'))
            ->setPaper('letter', 'landscape')
            ->stream('reporte-actividades.pdf');
    }
}
