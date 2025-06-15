<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Actividad;
use Illuminate\Support\Carbon;

class ActividadExportController extends Controller
{
    public function exportarPdf()
    {
        Carbon::setLocale('es');
        $actividades = Actividad::orderBy('fecha')->get();
        $minFecha = $actividades->min('fecha') ? Carbon::parse($actividades->min('fecha')) : null;
        $maxFecha = $actividades->max('fecha') ? Carbon::parse($actividades->max('fecha')) : null;

        $titulo = 'Reporte de actividades';
        $rangoFechas = null;

        if ($minFecha && $maxFecha) {
            if (
                $minFecha->month === $maxFecha->month &&
                $minFecha->year === $maxFecha->year
            ) {
                if (
                    ($minFecha->day === 1 && $maxFecha->day <= 15) ||
                    ($minFecha->day >= 16 && $maxFecha->isLastOfMonth())
                ) {
                    $titulo = 'Reporte de actividades Quincenal';
                } else {
                    $titulo = 'Reporte de actividades Mensual';
                }
            }

            if ($minFecha->year !== $maxFecha->year) {
                $rangoFechas = $minFecha->translatedFormat('j \d\e F \d\e Y') . ' al ' .
                    $maxFecha->translatedFormat('j \d\e F \d\e Y');
            } elseif ($minFecha->month !== $maxFecha->month) {
                $rangoFechas = $minFecha->translatedFormat('j \d\e F') . ' al ' .
                    $maxFecha->translatedFormat('j \d\e F \d\e Y');
            } elseif ($minFecha->day !== $maxFecha->day) {
                $rangoFechas = $minFecha->day . ' al ' .
                    $maxFecha->translatedFormat('j \d\e F \d\e Y');
            } else {
                $rangoFechas = $minFecha->translatedFormat('j \d\e F \d\e Y');
            }
        }

        $pdf = Pdf::loadView('filament.pages.actividades-pdf', compact('actividades', 'titulo', 'rangoFechas'))
            ->setPaper('letter', 'landscape')
            ->stream('reporte-actividades.pdf');

        return $pdf;
    }
}
