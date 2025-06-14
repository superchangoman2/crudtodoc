<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            TestUsersSeeder::class,
            UnidadesAdministrativasSeeder::class,
            GerenciasSeeder::class,
            TiposActividadesSeeder::class,
            ActividadesSeeder::class,
        ]);
    }
}
