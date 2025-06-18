<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UnidadAdministrativa;

class UnidadesAdministrativasSeeder extends Seeder
{
    public function run(): void
    {
        $unidades = [
            'Dirección General',
            'Coordinación General de Desarrollo Institucional y Proyectos Especiales',
            'Coordinación General Jurídica',
            'Coordinación General de Producción y Productividad',
            'Coordinación General de Conservación y Restauración',
            'Unidad de Administración y Finanzas',
            'Coordinación General de Planeación e Información',
            'Unidad de Asuntos Internacionales y Fomento Financiero',
            'Unidad de Género e Inclusión',
        ];

        foreach ($unidades as $nombre) {
            UnidadAdministrativa::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
