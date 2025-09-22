<?php

namespace App\Http\Controllers;

use App\Models\{Actividad, UnidadAdministrativa, User, Gerencia};
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\TemplateProcessor;

class ExportarActividadesController extends Controller
{
public function exportPdf(Request $request)
{
    $data = $this->buildExport($request);

    $actividades = $data['query']
        ->with(['user' => function ($q) {
            if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses(\App\Models\User::class))) {
                $q->withTrashed();
            }
        }])
        ->orderBy('fecha')
        ->get();

    $usuarios   = $actividades->pluck('user')->filter()->unique('id');
    $autorUnico = $usuarios->count() === 1 ? optional($usuarios->first())->name : null;
    $titulo     = $this->generarTitulo($data['modo_fecha'], $autorUnico);

    return Pdf::loadView('filament.pages.actividades-pdf', [
        'actividades' => $actividades,
        'titulo'      => $titulo,
        'rangoFechas' => $data['rangoFechas'],
        'autorUnico'  => $autorUnico,
    ])
        ->setPaper('letter', 'landscape')
        ->stream('reporte-actividades.pdf');
}

private function buildExport(Request $request): array
{
    Carbon::setLocale('es');
    $data = $request->all();

    $usuario   = auth()->user();
    $rolActual = $usuario->getRoleNames()->first();
    $jerarquia = User::jerarquiaRoles();

    $usuariosPermitidos    = $this->obtenerUsuariosPermitidos($data, $usuario, $rolActual);
    $pertenenciasPermitidas = $this->obtenerPertenenciasPermitidas($data);

    $query = Actividad::query();

    if ($rolActual === 'admin' && !empty($data['incluir_eliminados']) && $data['incluir_eliminados'] === '1') {
        $query->withTrashed();
    }

    if (!empty($usuariosPermitidos)) {
        $query->whereIn('user_id', $usuariosPermitidos);
    }

    $isPropio             = !empty($data['propio']) && $data['propio'] === '1';
    $esUsuarioEspecifico  = !empty($data['usuario']);

    if (!$isPropio && !empty($pertenenciasPermitidas)) {
        $query->whereIn('pertenencia_nombre', $pertenenciasPermitidas);
    }

    if (!($isPropio || $esUsuarioEspecifico)) {
        $query->where('autorizado', true);
    }

    $this->aplicarFiltrosJerarquia($query, $data, $rolActual, $jerarquia);

    // Fechas
    $rangoFechas = $this->aplicarFiltroFechas($query, $data);

    return [
        'query'       => $query,
        'modo_fecha'  => $data['modo_fecha'] ?? null,
        'rangoFechas' => $rangoFechas,
    ];
}

public function exportDoc(Request $request)
{
    $data = $this->buildExport($request);

    $actividades = $data['query']
        ->with(['user' => function ($q) {
            if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses(\App\Models\User::class))) {
                $q->withTrashed();
            }
        }])
        ->orderBy('fecha')
        ->get();

    $usuarios   = $actividades->pluck('user')->filter()->unique('id');
    $autorUnico = $usuarios->count() === 1 ? optional($usuarios->first())->name : null;
    $titulo     = $this->generarTitulo($data['modo_fecha'], $autorUnico);

    $templateSingle   = resource_path('docs/actividades_single.docx');
    $templateMultiple = resource_path('docs/actividades_multiple.docx');
    $templatePath     = $autorUnico ? $templateSingle : $templateMultiple;

    if (!file_exists($templatePath)) {
        abort(404, 'No se encontró el template: ' . $templatePath);
    }

    $tp = new TemplateProcessor($templatePath);

    $tp->setValue('titulo', e($titulo));
    $tp->setValue('rango', e($data['rangoFechas'] ?? ''));

    if ($autorUnico) {
        $rows = $actividades->map(function ($a) {
            return [
                'pertenencia'            => $a->pertenencia_nombre ?? '',
                'tipo'                   => ($a->tipo_actividad_id ?? null) == 1 ? 'Sustantiva' : 'Cotidiana',
                'actividad_titulo'      => $a->titulo ?? '',
                'actividad_descripcion' => $a->descripcion ?? '',
                'fecha'                  => \Carbon\Carbon::parse($a->fecha)->format('d/m/Y'),
            ];
        })->all();

        if (count($rows) > 0) {
            $tp->cloneRowAndSetValues('pertenencia', $rows);
        } else {
            $tp->cloneRow('pertenencia', 0);
        }
    } else {
        // CON columna Usuario → placeholders: usuario, pertenencia, tipo, actividad_titulo, actividad_descripcion, fecha
        $rows = $actividades->map(function ($a) {
            return [
                'usuario'                => optional($a->user)->name ?? 'Sin usuario',
                'pertenencia'            => $a->pertenencia_nombre ?? '',
                'tipo'                   => ($a->tipo_actividad_id ?? null) == 1 ? 'Sustantiva' : 'Cotidiana',
                'actividad_titulo'      => $a->titulo ?? '',
                'actividad_descripcion' => $a->descripcion ?? '',
                'fecha'                  => \Carbon\Carbon::parse($a->fecha)->format('d/m/Y'),
            ];
        })->all();

        if (count($rows) > 0) {
            $tp->cloneRowAndSetValues('usuario', $rows);
        } else {
            $tp->cloneRow('usuario', 0);
        }
    }

    $tmp = tempnam(sys_get_temp_dir(), 'docx');
    $tp->saveAs($tmp);

    return response()->download(
        $tmp,
        'reporte-actividades.docx',
        ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
    )->deleteFileAfterSend(true);
}


    private function obtenerUsuariosPermitidos(array $data, $usuario, string $rolActual): array
    {
        if (!empty($data['propio']) && $data['propio'] === '1' || $rolActual === 'usuario') {
            return [$usuario->id];
        }

        if (!empty($data['usuario'])) {
            return [intval($data['usuario'])];
        }

        if (!empty($data['gerencia'])) {
            $vista = DB::table('vista_gerencias_extendida')->where('id', $data['gerencia'])->first();

            if (!$vista) {
                return [];
            }

            $usuarios = explode(',', $vista->usuarios_user_ids ?? '');

            if ($rolActual === 'gerente') {
                $usuarios[] = $vista->subgerente_id;
            }

            if ($rolActual === 'administrador-unidad') {
                $usuarios[] = $vista->subgerente_id;
                $usuarios[] = $vista->gerente_id;
            }

            return array_filter(array_map('intval', $usuarios));
        }

        if (!empty($data['unidad_administrativa'])) {
            $unidadId = (int) $data['unidad_administrativa'];
            $gerencias = Gerencia::where('unidad_administrativa_id', $unidadId)->pluck('id');

            return User::where(function ($q) use ($unidadId, $gerencias) {
                $q->where(function ($q1) use ($unidadId) {
                    $q1->where('pertenece_id', $unidadId)
                    ->whereHas('roles', fn($r) => $r->where('name', 'administrador-unidad'));
                })->orWhere(function ($q2) use ($gerencias) {
                    $q2->whereIn('pertenece_id', $gerencias)
                    ->whereHas('roles', fn($r) => $r->whereIn('name', ['gerente', 'subgerente', 'usuario']));
                });
            })->pluck('id')->toArray();
        }

        $rolesInferiores = User::rolesInferioresA($rolActual);
        $rolesAutorizados = array_merge([$rolActual], $rolesInferiores);

        return User::whereHas('roles', function ($q) use ($rolesAutorizados) {
            $q->whereIn('name', $rolesAutorizados);
        })->pluck('id')->toArray();
    }

    private function obtenerPertenenciasPermitidas(array $data): array
    {
        $permitidas = [];

        if (!empty($data['unidad_administrativa'])) {
            $unidad = UnidadAdministrativa::find($data['unidad_administrativa']);
            if ($unidad) {
                $permitidas[] = $unidad->nombre;
            }
        }

        if (!empty($data['gerencias_de_unidad'])) {
            $gerencias = Gerencia::whereIn('id', $data['gerencias_de_unidad'])->pluck('nombre')->toArray();
            $permitidas = array_merge($permitidas, $gerencias);
        }

        if (!empty($data['gerencia'])) {
            $gerencia = Gerencia::find($data['gerencia']);
            if ($gerencia) {
                $permitidas = [$gerencia->nombre];
            }
        }

        return $permitidas;
    }

    private function aplicarFiltrosJerarquia($query, array $data, string $rolActual, array $jerarquia): void
    {
        if (!empty($data['usuario'])) {
            if (!empty($data['rol_usuario'])) {
                $query->whereHas('user.roles', function ($q) use ($data) {
                    $q->where('name', $data['rol_usuario']);
                });
            }
            return;
        }

        if (!empty($data['rol_usuario'])) {
            $query->whereHas('user.roles', function ($q) use ($data) {
                $q->where('name', $data['rol_usuario']);
            });
            return;
        }

        if (empty($data['propio']) || $data['propio'] !== '1') {
            $rolesVisibles = collect($jerarquia)
                ->filter(fn($nivel) => $nivel >= $jerarquia[$rolActual])
                ->keys()
                ->toArray();

            $query->whereHas('user.roles', function ($q) use ($rolesVisibles) {
                $q->whereIn('name', $rolesVisibles);
            });
        }
    }

    private function aplicarFiltroFechas($query, array $data): string
    {
        $rangoFechas = "Fecha no disponible";

        switch ($data['modo_fecha'] ?? null) {
            case 'quincena':
                if (!empty($data['quincena_seleccionada']) && !empty($data['year'])) {
                    [$mes, $q] = explode('-', $data['quincena_seleccionada']);
                    $mes = (int)$mes;

                    if ((int)$q === 1) {
                        $inicio = Carbon::create($data['year'], $mes, 1)->startOfDay();
                        $fin    = Carbon::create($data['year'], $mes, 15)->endOfDay();
                        $quincenaTexto = 'Primera quincena de';
                    } else {
                        $inicio = Carbon::create($data['year'], $mes, 16)->startOfDay();
                        $fin    = Carbon::create($data['year'], $mes, 1)->endOfMonth()->endOfDay();
                        $quincenaTexto = 'Segunda quincena de';
                    }

                    $query->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()]);

                    $mesNombre = $inicio->translatedFormat('F');
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
                $year = (int) ($data['year'] ?? 0);
                $min  = 2000;
                $max  = (int) now()->year;

                if ($year >= $min && $year <= $max) {
                    $query->whereYear('fecha', $year);
                    $rangoFechas = (string) $year;
                } else {
                    $query->whereRaw('1 = 0');
                    $rangoFechas = 'Año inválido';
                }
                break;
            case 'personalizado':
                if (!empty($data['fecha_inicio']) && !empty($data['fecha_fin'])) {
                    $query->whereBetween('fecha', [$data['fecha_inicio'], $data['fecha_fin']]);
                    $rangoFechas = 'Del ' . Carbon::parse($data['fecha_inicio'])->translatedFormat('j \d\e F \d\e Y') .
                        ' al ' . Carbon::parse($data['fecha_fin'])->translatedFormat('j \d\e F \d\e Y');
                }
                break;
        }

        return $rangoFechas;
    }

    private function generarTitulo(?string $modoFecha, ?string $autorUnico): string
    {
        $titulo = match ($modoFecha) {
            'quincena' => 'Reporte de actividades quincenal',
            'mes' => 'Reporte de actividades mensual',
            'anual' => 'Reporte de actividades anual',
            default => 'Reporte de actividades',
        };

        if ($autorUnico) {
            $titulo .= " de {$autorUnico}";
        }

        return $titulo;
    }

    private function obtenerPertenenciasPermitidasIds(array $data): array
    {
        $ids = [];

        if (!empty($data['gerencia'])) {
            return [(int) $data['gerencia']];
        }

        if (!empty($data['unidad_administrativa'])) {
            $unidadId = (int) $data['unidad_administrativa'];

            $ids[] = $unidadId;
            $ids = array_merge(
                $ids,
                Gerencia::where('unidad_administrativa_id', $unidadId)->pluck('id')->toArray()
            );

            return array_values(array_unique(array_map('intval', $ids)));
        }

        if (!empty($data['gerencias_de_unidad'])) {
            $ids = array_merge(
                $ids,
                Gerencia::whereIn('id', $data['gerencias_de_unidad'])->pluck('id')->toArray()
            );
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }


}
