<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Actividad;
use App\Models\User;
use App\Models\Gerencia;
use Illuminate\Support\Carbon;

class ExportarActividadesSimpleController extends Controller
{
    public function exportarPdf()
    {
        Carbon::setLocale('es');

        $user = auth()->user();
        $query = Actividad::query();


        switch (true) {

            case $user->hasRole('administrador'):
                break;

            case $user->hasRole('jefe-unidad'):
                $gerencias = Gerencia::where('unidad_administrativa_id', $user->pertenece_id)->get();
                $gerenciaIds = $gerencias->pluck('id');
                $gerenciaNombres = $gerencias->pluck('nombre');

                $usuariosSubordinados = User::whereIn('pertenece_id', $gerenciaIds)
                    ->whereHas(
                        'roles',
                        fn($q) =>
                        $q->whereIn('name', ['gerente', 'subgerente', 'usuario'])
                    )
                    ->pluck('id');

                $query->where(function ($q) use ($user, $usuariosSubordinados, $gerenciaNombres) {
                    $q->where('user_id', $user->id)
                        ->orWhereIn('user_id', $usuariosSubordinados)
                        ->orWhere(function ($q2) use ($gerenciaNombres) {
                            $q2->where('pertenencia_tipo', Actividad::TIPO_GERENCIA)
                                ->whereIn('pertenencia_nombre', $gerenciaNombres);
                        });
                });
                break;


            case $user->hasRole('gerente'):
                $gerenciaNombre = $user->gerencia?->nombre;

                $gerenciaId = Gerencia::where('nombre', $gerenciaNombre)->value('id');

                $usuariosSubordinados = User::where('pertenece_id', $gerenciaId)
                    ->whereHas(
                        'roles',
                        fn($q) =>
                        $q->whereIn('name', ['subgerente', 'usuario'])
                    )
                    ->pluck('id');

                $query->where(function ($q) use ($user, $usuariosSubordinados, $gerenciaNombre) {
                    $q->where('user_id', $user->id)

                        ->orWhere(function ($q2) use ($usuariosSubordinados, $gerenciaNombre) {
                            $q2->whereIn('user_id', $usuariosSubordinados)
                                ->where('pertenencia_tipo', Actividad::TIPO_GERENCIA)
                                ->where('pertenencia_nombre', $gerenciaNombre);
                        })

                        ->orWhere(function ($q4) use ($gerenciaNombre) {
                            $q4->where('pertenencia_tipo', Actividad::TIPO_GERENCIA)
                                ->where('pertenencia_nombre', $gerenciaNombre);
                        });
                });
                break;


            case $user->hasRole('subgerente'):
                $gerenciaNombre = $user->gerencia?->nombre;
                $gerenciaId = Gerencia::where('nombre', $gerenciaNombre)->value('id');

                $usuariosSubordinados = User::where('pertenece_id', $gerenciaId)
                    ->whereHas(
                        'roles',
                        fn($q) =>
                        $q->where('name', 'usuario')
                    )
                    ->pluck('id');

                $query->where(function ($q) use ($user, $usuariosSubordinados, $gerenciaNombre) {
                    $q->where('user_id', $user->id)
                        ->orWhereIn('user_id', $usuariosSubordinados)
                        ->orWhere(function ($q2) use ($gerenciaNombre) {
                            $q2->where('pertenencia_tipo', Actividad::TIPO_GERENCIA)
                                ->where('pertenencia_nombre', $gerenciaNombre);
                        });
                });
                break;


            default:
                $query->where('user_id', $user->id);
                break;
        }

        if (request()->filled('user_id')) {
            $query->where('user_id', request('user_id'));
        }

        if (request()->filled('rol')) {
            $query->whereHas(
                'usuario.roles',
                fn($q) =>
                $q->where('name', request('rol'))
            );
        }

        if (request()->filled('gerencia')) {
            $query->where('pertenencia_nombre', request('gerencia'));
        }

        $actividades = $query->orderBy('fecha')->get();

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
