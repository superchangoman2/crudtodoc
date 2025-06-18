<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoActividad;

class TiposActividadesSeeder extends Seeder
{
    public function run(): void
    {
        $actividades = [
            'Sustantiva',
            'Cotidiana',
        ];

        foreach ($actividades as $nombre) {
            TipoActividad::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
