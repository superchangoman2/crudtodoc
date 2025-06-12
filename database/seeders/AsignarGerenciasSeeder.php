<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UnidadAdministrativa;
use App\Models\Gerencia;

class AsignarGerenciasSeeder extends Seeder
{
    public function run(): void
    {
        $asignaciones = [
            'Dirección General' => [
                'Oficinas de Representación Estatal',
                'Unidad de Vinculación Interinstitucional',
                'Gerencia de Gestión y Seguimiento Institucional',
            ],
            'Coordinación General de Desarrollo Institucional y Proyectos Especiales' => [
                'Gerencia de Comunicación y Producción',
                'Gerencia Técnica',
                'Gerencia de Desarrollo Institucional',
                'Gerencia de Proyectos Especiales',
                'Unidad de Operación Regional',
                'Gerencia de Control Operativo',
                'Gerencia Técnica y de Participación Social',
            ],
            'Coordinación General Jurídica' => [
                'Gerencia de Normatividad y Consulta',
                'Gerencia de lo Contencioso, Administrativo y Judicial',
                'Gerencia de Instrumentación de Procesos Jurídicos',
            ],
            'Coordinación General de Producción y Productividad' => [
                'Gerencia de Manejo Forestal Comunitario',
                'Gerencia de Plantaciones Forestales Comerciales',
                'Gerencia de Abasto, Transformación y Mercados',
                'Unidad de Educación y Desarrollo Tecnológico',
                'Gerencia de Desarrollo y Transferencia de Tecnología',
            ],
            'Coordinación General de Conservación y Restauración' => [
                'Gerencia de Reforestación y Restauración de Cuencas Hidrográficas',
                'Gerencia de Sanidad Forestal',
                'Gerencia de Manejo del Fuego',
                'Gerencia de Servicios Ambientales del Bosque y Conservación de la Biodiversidad',
            ],
            'Unidad de Administración y Finanzas' => [
                'Gerencia de Programación y Presupuesto',
                'Gerencia de Recursos Humanos',
                'Gerencia de Recursos Materiales',
                'Gerencia de Tecnologías de la Información y Comunicación',
            ],
            'Coordinación General de Planeación e Información' => [
                'Gerencia de Planeación y Evaluación',
                'Gerencia de Sistema Nacional de Monitoreo Forestal',
                'Gerencia de Información Forestal',
                'Gerencia Técnica del Sistema de Medición, Reporte y Verificación',
            ],
            'Unidad de Asuntos Internacionales y Fomento Financiero' => [
                'Gerencia de Cooperación Internacional',
                'Gerencia de Financiamiento',
                'Gerencia de Bosques y Cambio Climático',
            ],
            'Unidad de Género e Inclusión' => [
                'Gerencia de Inclusión y No Discriminación',
            ],
        ];

        foreach ($asignaciones as $unidadNombre => $gerencias) {
            $unidad = UnidadAdministrativa::where('nombre', $unidadNombre)->first();

            if (!$unidad) {
                $this->command->warn("Unidad no encontrada: $unidadNombre");
                continue;
            }

            foreach ($gerencias as $nombreGerencia) {
                $gerencia = Gerencia::where('nombre', $nombreGerencia)->first();

                if ($gerencia) {
                    $gerencia->unidad_administrativa_id = $unidad->id;
                    $gerencia->save();
                } else {
                    $this->command->warn("Gerencia no encontrada: $nombreGerencia");
                }
            }
        }

        $this->command->info('Gerencias asignadas a sus Unidades Administrativas.');
    }
}
