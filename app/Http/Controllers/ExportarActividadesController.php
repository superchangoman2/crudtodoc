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

        $usuariosPermitidos = $this->obtenerUsuariosPermitidos($data, $usuario, $rolActual);
        $pertenenciasPermitidas = $this->obtenerPertenenciasPermitidas($data);

        $query = Actividad::query();

        if ($rolActual === 'admin' && !empty($data['incluir_eliminados']) && $data['incluir_eliminados'] === '1') {
            $query->withTrashed();
        }

        if (!empty($usuariosPermitidos)) {
            $query->whereIn('user_id', $usuariosPermitidos);
        }

        if (!empty($pertenenciasPermitidas)) {
            $query->whereIn('pertenencia_nombre', $pertenenciasPermitidas);
        }

        $this->aplicarFiltrosJerarquia($query, $data, $rolActual, $jerarquia);

        $rangoFechas = $this->aplicarFiltroFechas($query, $data);

        $actividades = $query->with('user')->orderBy('fecha')->get();

        $usuarios = $actividades->pluck('user')->unique('id');
        $autorUnico = $usuarios->count() === 1 ? $usuarios->first()->name : null;

        $titulo = $this->generarTitulo($data['modo_fecha'] ?? null, $autorUnico);

        return Pdf::loadView('filament.pages.actividades-pdf', compact('actividades', 'titulo', 'rangoFechas', 'autorUnico'))
            ->setPaper('letter', 'landscape')
            ->stream('reporte-actividades.pdf');
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
            $gerencias = Gerencia::whereIn('id', $data['gerencias_de_unidad'] ?? [])->pluck('id');
            return User::where(function ($q) use ($data, $gerencias) {
                $q->where('pertenece_id', $data['unidad_administrativa'])
                    ->orWhereIn('pertenece_id', $gerencias);
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
        if (!empty($data['rol_usuario'])) {
            $query->where('created_by_role', $data['rol_usuario']);
            return;
        }

        if (empty($data['propio']) || $data['propio'] !== '1') {
            $rolesVisibles = collect($jerarquia)
                ->filter(fn($nivel) => $nivel >= $jerarquia[$rolActual])
                ->keys()
                ->toArray();

            $query->whereIn('created_by_role', $rolesVisibles);
        }
    }

    private function aplicarFiltroFechas($query, array $data): string
    {
        $rangoFechas = "Fecha no disponible";

        switch ($data['modo_fecha'] ?? null) {
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
}
