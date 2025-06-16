<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Asegurar que los roles existen
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $unidadRole = Role::firstOrCreate(['name' => 'administrador-unidad']);
        $gerenteRole = Role::firstOrCreate(['name' => 'gerente']);
        $subgerenteRole = Role::firstOrCreate(['name' => 'subgerente']);
        $usuarioRole = Role::firstOrCreate(['name' => 'usuario']);

        // Crear usuarios de prueba fijos
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'admin',
                'last_name' => 'super',
                'password' => bcrypt('admin123'),
                'pertenece_id' => 6,
            ]
        );

        $unidad = User::firstOrCreate(
            ['email' => 'unidad@example.com'],
            [
                'first_name' => 'administrador',
                'last_name' => 'unidad',
                'password' => bcrypt('unidad123'),
                'pertenece_id' => 6,
            ]
        );
        $gerente = User::firstOrCreate(
            ['email' => 'gerente@example.com'],
            [
                'first_name' => 'gerente',
                'last_name' => 'operativo',
                'password' => bcrypt('gerente123'),
                'pertenece_id' => 26,
            ]
        );
        $subgerente = User::firstOrCreate(
            ['email' => 'subgerente@example.com'],
            [
                'first_name' => 'subgerente',
                'last_name' => 'trabajador',
                'password' => bcrypt('subgerente123'),
                'pertenece_id' => 26,
            ]
        );
        $user = User::firstOrCreate(
            ['email' => 'usuario@example.com'],
            [
                'first_name' => 'usuario',
                'last_name' => 'normal',
                'password' => bcrypt('usuario123'),
                'pertenece_id' => 26,
            ]
        );

        // Asignar roles a los usuarios creados
        $admin->assignRole($adminRole);
        $unidad->assignRole($unidadRole);
        $gerente->assignRole($gerenteRole);
        $subgerente->assignRole($subgerenteRole);
        $user->assignRole($usuarioRole);

        $adminIds = collect(range(1, 34))->shuffle()->take(4)->values();
        User::factory(5)->create()->shuffle()->values()->each(function ($u, $i) use ($adminRole, $adminIds) {
            $u->assignRole($adminRole);
            if ($i < count($adminIds)) {
                $u->pertenece_id = $adminIds[$i];
            }
            $u->save();
        });


        $unidadIds = collect(range(1, 9))->reject(fn($id) => $id === 6)->shuffle()->take(8)->values();
        User::factory(10)->create()->shuffle()->values()->each(function ($u, $i) use ($unidadRole, $unidadIds) {
            $u->assignRole($unidadRole);
            if ($i < count($unidadIds)) {
                $u->pertenece_id = $unidadIds[$i];
            }
            $u->save();
        });


        $gerenteIds = collect(range(1, 34))->reject(fn($id) => $id === 26)->shuffle()->take(32)->values();
        $omitG = collect(range(0, 34))->shuffle()->take(2)->toArray();

        User::factory(35)->create()->shuffle()->values()->each(function ($u, $i) use ($gerenteRole, $gerenteIds, $omitG) {
            $u->assignRole($gerenteRole);
            if (!in_array($i, $omitG)) {
                $u->pertenece_id = $gerenteIds->get($i);
            }
            $u->save();
        });

        $subgerenteIds = collect(range(1, 34))->reject(fn($id) => $id === 26)->shuffle()->slice(0, 32)->values();
        User::factory(20)->create()->shuffle()->values()->each(function ($u, $i) use ($subgerenteRole, $subgerenteIds) {
            $u->assignRole($subgerenteRole);
            if ($i < count($subgerenteIds)) {
                $u->pertenece_id = $subgerenteIds[$i];
                $u->save();
            }
        });

        $usuarioIds = collect(range(1, 34))->shuffle()->take(45)->values();
        User::factory(50)->create()->shuffle()->values()->each(function ($u, $i) use ($usuarioRole, $usuarioIds) {
            $u->assignRole($usuarioRole);
            if ($i < count($usuarioIds)) {
                $u->pertenece_id = $usuarioIds[$i];
                $u->save();
            }
        });

    }
}
