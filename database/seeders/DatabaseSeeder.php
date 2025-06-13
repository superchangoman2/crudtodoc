<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            TestUsersSeeder::class,
            GerenciasSeeder::class,
            UnidadesAdministrativasSeeder::class,
            TiposActividadesSeeder::class,
        ]);
    }
}
