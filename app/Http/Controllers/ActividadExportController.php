<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Actividad;

class ActividadExportController extends Controller
{
    public function exportarPdf()
    {
        $actividades = Actividad::orderBy('fecha')->get();

        // $pdf = Pdf::loadView('filament.pages.actividades-pdf', compact('actividades'));
        $pdf = Pdf::loadView('filament.pages.actividades-pdf', compact('actividades'))
            ->setPaper('letter', 'landscape');

        //return $pdf->download('reporte-actividades.pdf');
        return $pdf->stream('reporte-actividades.pdf');
    }
}
