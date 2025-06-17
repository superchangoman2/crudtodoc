<?php

namespace App\Http\Controllers;

use App\Models\{Actividad, UnidadAdministrativa, User, Gerencia};
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExportarActividadesController extends Controller
{
    public function export(Request $request)
    {
        Carbon::setLocale('es');
        $data = $request->all();

        $usuario = auth()->user();
        $rolActual = $usuario->getRoleNames()->first();
        $jerarquia = User::jerarquiaRoles();
        $nivelRolActual = $jerarquia[$rolActual] ?? PHP_INT_MAX;

        // Determinar IDs válidos
        $usuariosPermitidos = [];
        $pertenenciasPermitidas = [];

        // Caso propio
        if (!empty($data['propio']) && $data['propio'] === '1') {
            $usuariosPermitidos = [$usuario->id];
        } else {
            // Obtener roles inferiores y el propio
            $rolesInferiores = User::rolesInferioresA($rolActual);
            $rolesAutorizados = array_merge([$rolActual], $rolesInferiores);

            // Obtener usuarios permitidos según rol
            $usuariosPermitidos = User::whereHas('roles', function ($q) use ($rolesAutorizados) {
                $q->whereIn('name', $rolesAutorizados);
            })->pluck('id')->toArray();

            // Filtrar por unidad o gerencia según rol
            if ($rolActual === 'admin') {
                if (!empty($data['unidad_administrativa'])) {
                    $unidad = UnidadAdministrativa::find($data['unidad_administrativa']);
                    if ($unidad) {
                        $pertenenciasPermitidas[] = $unidad->nombre;
                    }
                }

                if (!empty($data['gerencias_de_unidad'])) {
                    $gerencias = Gerencia::whereIn('id', $data['gerencias_de_unidad'])->pluck('nombre')->toArray();
                    $pertenenciasPermitidas = array_merge($pertenenciasPermitidas, $gerencias);
                }

                if (!empty($data['gerencia'])) {
                    $gerencia = Gerencia::find($data['gerencia']);
                    if ($gerencia) {
                        $pertenenciasPermitidas = [$gerencia->nombre];

                        if (!empty($data['usuario'])) {
                            $usuariosPermitidos = [intval($data['usuario'])];
                        } else {
                            $usuariosPermitidos = User::where('pertenece_id', $data['gerencia'])->pluck('id')->toArray();
                        }
                    }
                }
            } elseif ($rolActual === 'administrador-unidad') {
                $unidad = DB::table('vista_unidades_extendida')->where('id', $usuario->pertenece_id)->first();
                $pertenenciasPermitidas = array_merge(
                    [$unidad->nombre ?? ''],
                    explode(',', $unidad->gerencias_nombres ?? '')
                );

                $usuariosPermitidos = [
                    $unidad->administrador_id,
                    $unidad->usuario_gerente_id,
                    $unidad->usuario_subgerente_id,
                    ...explode(',', $unidad->usuarios_user_ids ?? '')
                ];
            } elseif (in_array($rolActual, ['gerente', 'subgerente'])) {
                $vista = DB::table('vista_gerencias_extendida')->where('id', $usuario->pertenece_id)->first();
                $pertenenciasPermitidas = [$vista->nombre ?? ''];

                $usuariosPermitidos = [
                    $vista->gerente_id,
                    $vista->subgerente_id,
                    ...explode(',', $vista->usuarios_user_ids ?? '')
                ];
            } elseif ($rolActual === 'usuario') {
                $usuariosPermitidos = [$usuario->id];
            }
        }

        // Iniciar query
        $query = Actividad::query();

        // Incluir eliminados solo para admin
        if ($rolActual === 'admin' && !empty($data['incluir_eliminados']) && $data['incluir_eliminados'] === '1') {
            $query->withTrashed();
        }

        // Filtrar por IDs y pertenencia
        if (!empty($usuariosPermitidos)) {
            $query->whereIn('user_id', $usuariosPermitidos);
        }

        if (!empty($pertenenciasPermitidas)) {
            $query->whereIn('pertenencia_nombre', $pertenenciasPermitidas);
        }

        if (!empty($data['rol_usuario'])) {
            $query->where('created_by_role', $data['rol_usuario']);
        }

        // Filtro por fechas
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

        $titulo = match ($data['modo_fecha'] ?? null) {
            'quincena' => 'Reporte de actividades quincenal',
            'mes' => 'Reporte de actividades mensual',
            'anual' => 'Reporte de actividades anual',
            default => 'Reporte de actividades',
        };

        if ($autorUnico) {
            $titulo .= " de {$autorUnico}";
        }

        return Pdf::loadView('filament.pages.actividades-pdf', compact('actividades', 'titulo', 'rangoFechas', 'autorUnico'))
            ->setPaper('letter', 'landscape')
            ->stream('reporte-actividades.pdf');
    }
}
