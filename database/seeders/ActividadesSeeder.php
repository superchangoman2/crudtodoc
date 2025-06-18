<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Actividad;
use App\Models\User;
use App\Models\Gerencia;
use Illuminate\Support\Carbon;

class ActividadesSeeder extends Seeder
{
    public function run(): void
    {
        $actividades = [
            [
                'titulo' => 'Supervisión de campo en zona norte',
                'descripcion' => 'Se realizaron visitas de inspección a áreas reforestadas.',
                'tipo_actividad_id' => 1,
                'pertenencia_tipo' => Actividad::TIPO_GERENCIA,
                'pertenencia_nombre' => 'Gerencia de Tecnologías de la Información y Comunicación',
                'user_id' => 4,
                'created_by_role' => 'subgerente',
                'fecha' => '2025-06-01',
            ],
            [
                'titulo' => 'Taller de capacitación interna',
                'descripcion' => 'Capacitación sobre herramientas de monitoreo forestal.',
                'tipo_actividad_id' => 2,
                'pertenencia_tipo' => Actividad::TIPO_GERENCIA,
                'pertenencia_nombre' => 'Gerencia de Tecnologías de la Información y Comunicación',
                'user_id' => 4,
                'created_by_role' => 'subgerente',
                'fecha' => '2025-06-03',
            ],
            [
                'titulo' => 'Revisión de indicadores de desempeño',
                'descripcion' => 'Análisis semestral de los indicadores operativos.',
                'tipo_actividad_id' => 1,
                'pertenencia_tipo' => Actividad::TIPO_GERENCIA,
                'pertenencia_nombre' => 'Gerencia de Tecnologías de la Información y Comunicación',
                'user_id' => 1,
                'created_by_role' => 'admin',
                'fecha' => '2025-06-05',
            ],
            [
                'titulo' => 'Borrardo suave',
                'descripcion' => 'Lo hice por error.',
                'tipo_actividad_id' => 1,
                'pertenencia_tipo' => Actividad::TIPO_GERENCIA,
                'pertenencia_nombre' => 'Gerencia de Tecnologías de la Información y Comunicación',
                'user_id' => 1,
                'deleted_at' => '2025-06-17 16:42:28',
                'deleted_by' => '1',
                'created_by_role' => 'subgerente',
                'fecha' => '2025-06-01',
            ],
            [
                'titulo' => 'Elaboración de informe mensual',
                'descripcion' => 'Informe de actividades realizadas en mayo.',
                'tipo_actividad_id' => 2,
                'pertenencia_tipo' => Actividad::TIPO_GERENCIA,
                'pertenencia_nombre' => 'Gerencia de Tecnologías de la Información y Comunicación',
                'user_id' => 1,
                'created_by_role' => 'admin',
                'fecha' => '2025-06-06',
            ],
            [
                'titulo' => 'Visita de inspección a viveros',
                'descripcion' => 'Verificación del estado de los viveros forestales.',
                'tipo_actividad_id' => 1,
                'pertenencia_tipo' => Actividad::TIPO_GERENCIA,
                'pertenencia_nombre' => 'Gerencia de Tecnologías de la Información y Comunicación',
                'user_id' => 3,
                'created_by_role' => 'gerente',
                'fecha' => '2025-06-07',
            ],
            [
                'titulo' => 'Sesión de retroalimentación técnica',
                'descripcion' => 'Discusión de resultados con el equipo técnico.',
                'tipo_actividad_id' => 2,
                'pertenencia_tipo' => Actividad::TIPO_UNIDAD,
                'pertenencia_nombre' => 'Unidad de Administración y Finanzas',
                'user_id' => 3,
                'created_by_role' => 'gerente',
                'fecha' => '2025-06-02',
            ],
            [
                'titulo' => 'Planeación estratégica trimestral',
                'descripcion' => 'Definición de objetivos para el siguiente trimestre.',
                'tipo_actividad_id' => 1,
                'pertenencia_tipo' => Actividad::TIPO_UNIDAD,
                'pertenencia_nombre' => 'Unidad de Administración y Finanzas',
                'user_id' => 2,
                'created_by_role' => 'administrador-unidad',
                'fecha' => '2025-06-04',
            ],
            [
                'titulo' => 'Validación de datos de monitoreo',
                'descripcion' => 'Verificación de integridad de datos en campo.',
                'tipo_actividad_id' => 2,
                'pertenencia_tipo' => Actividad::TIPO_UNIDAD,
                'pertenencia_nombre' => 'Unidad de Administración y Finanzas',
                'user_id' => 2,
                'created_by_role' => 'administrador-unidad',
                'fecha' => '2025-06-08',
            ],
            [
                'titulo' => 'Actualización de inventario forestal',
                'descripcion' => 'Carga de nuevos datos al sistema nacional.',
                'tipo_actividad_id' => 1,
                'pertenencia_tipo' => Actividad::TIPO_UNIDAD,
                'pertenencia_nombre' => 'Unidad de Administración y Finanzas',
                'user_id' => 2,
                'created_by_role' => 'administrador-unidad',
                'fecha' => '2025-06-09',
            ],
            [
                'titulo' => 'Reunión de coordinación con la dirección',
                'descripcion' => 'Reporte de avances y necesidades operativas.',
                'tipo_actividad_id' => 2,
                'pertenencia_tipo' => Actividad::TIPO_UNIDAD,
                'pertenencia_nombre' => 'Unidad de Administración y Finanzas',
                'user_id' => 2,
                'created_by_role' => 'administrador-unidad',
                'fecha' => '2025-06-10',
            ],
        ];
        $actividades_ejemplo = [
            [
                'titulo' => 'Integración del módulo de captura de actividades en el sistema de reportes',
                'descripcion' => 'Implementación de la funcionalidad de edición y eliminación de registros de actividades. Configuración de exportación de actividades en formato Word. Ajuste de bases de datos y adaptación del proyecto a nueva estructura y denominación.',
                'tipo_actividad_id' => 1,
                'pertenencia_tipo' => Actividad::TIPO_GERENCIA,
                'pertenencia_nombre' => 'Gerencia de Tecnologías de la Información y Comunicación',
                'user_id' => 5,
                'created_by_role' => 'usuario',
                'fecha' => '2025-04-17',
            ],
            [
                'titulo' => 'Avance en el desarrollo del sistema de reportes de actividades',
                'descripcion' => 'Despliegue de sistema en plesk bajo el dominio de https://reporte-actividades.cnf.gob.mx/login/',
                'tipo_actividad_id' => 1,
                'pertenencia_tipo' => Actividad::TIPO_GERENCIA,
                'pertenencia_nombre' => 'Gerencia de Tecnologías de la Información y Comunicación',
                'user_id' => 5,
                'created_by_role' => 'usuario',
                'fecha' => '2025-04-19',
            ],
            [
                'titulo' => 'Administración de bases de datos de sistemas institucionales existentes',
                'descripcion' => 'Se monitorearon los servidores de bases de datos, asegurando la integridad y disponibilidad de la información.',
                'tipo_actividad_id' => 2,
                'pertenencia_tipo' => Actividad::TIPO_GERENCIA,
                'pertenencia_nombre' => 'Gerencia de Tecnologías de la Información y Comunicación',
                'user_id' => 5,
                'created_by_role' => 'usuario',
                'fecha' => '2025-04-22',
            ],
            [
                'titulo' => 'Monitoreo de sistemas en producción de la GTIC',
                'descripcion' => 'Se llevó a cabo un monitoreo constante de los sistemas en producción para garantizar su correcto funcionamiento y disponibilidad. Se revisaron métricas de desempeño, tiempos de respuesta y posibles incidencias, mediante las métricas y dashboard de plesk.',
                'tipo_actividad_id' => 2,
                'pertenencia_tipo' => Actividad::TIPO_GERENCIA,
                'pertenencia_nombre' => 'Gerencia de Tecnologías de la Información y Comunicación',
                'user_id' => 5,
                'created_by_role' => 'usuario',
                'fecha' => '2025-04-27',
            ],
        ];

        foreach ($actividades as $actividad) {
            Actividad::create($actividad);
        }
        foreach ($actividades_ejemplo as $actividad) {
            Actividad::create($actividad);
        }

        for ($i = 0; $i < 300; $i++) {
            $user = User::inRandomOrder()
                ->whereBetween('id', [76, 275])
                ->whereNotNull('pertenece_id')
                ->first();

            if (!$user)
                continue;

            $gerencia = Gerencia::find($user->pertenece_id);
            if (!$gerencia)
                continue;

            Actividad::create([
                'titulo' => "{$user->id} - Actividad de prueba #{$i}",
                'descripcion' => "Descripción de prueba generada automáticamente de {$user->email}.",
                'tipo_actividad_id' => rand(1, 2),
                'pertenencia_tipo' => Actividad::TIPO_GERENCIA,
                'pertenencia_nombre' => $gerencia->nombre,
                'user_id' => $user->id,
                'created_by_role' => 'usuario',
                'fecha' => Carbon::createFromFormat('Y-m-d', '2025-01-01')->addDays(rand(0, 167)), // hasta 17 junio
            ]);
        }


    }
}
