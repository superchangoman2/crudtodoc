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
            ]
        );

        $unidad = User::firstOrCreate(
            ['email' => 'unidad@example.com'],
            [
                'first_name' => 'administrador',
                'last_name' => 'unidad',
                'password' => bcrypt('unidad123'),
            ]
        );
        $gerente = User::firstOrCreate(
            ['email' => 'gerente@example.com'],
            [
                'first_name' => 'gerente',
                'last_name' => 'operativo',
                'password' => bcrypt('gerente123'),
            ]
        );
        $subgerente = User::firstOrCreate(
            ['email' => 'subgerente@example.com'],
            [
                'first_name' => 'subgerente',
                'last_name' => 'trabajador',
                'password' => bcrypt('subgerente123'),
            ]
        );
        $user = User::firstOrCreate(
            ['email' => 'usuario@example.com'],
            [
                'first_name' => 'usuario',
                'last_name' => 'normal',
                'password' => bcrypt('usuario123'),
            ]
        );

        // Asignar roles a los usuarios creados
        $admin->assignRole($adminRole);
        $unidad->assignRole($unidadRole);
        $gerente->assignRole($gerenteRole);
        $subgerente->assignRole($subgerenteRole);
        $user->assignRole($usuarioRole);

        // Admins de prueba
        User::factory(5)->create()->each(fn($u) => $u->assignRole($adminRole));

        // Administradores de unidad: usar 2–9 (8 unidades), saltar una aleatoria
        $unidadIds = collect(range(2, 9))->shuffle()->take(8)->values();
        User::factory(10)->create()->each(function ($u, $i) use ($unidadRole, $unidadIds) {
            $u->assignRole($unidadRole);
            if ($i < count($unidadIds)) {
                $u->pertenece_id = $unidadIds[$i];
                $u->save();
            }
        });

        // Gerentes: 2–34, saltar 2 gerencias
        $gerenciaIdsG = collect(range(2, 34))->shuffle()->slice(0, 32)->values(); // 32 = 34 - 2 saltadas
        User::factory(35)->create()->each(function ($u, $i) use ($gerenteRole, $gerenciaIdsG) {
            $u->assignRole($gerenteRole);
            if ($i < count($gerenciaIdsG)) {
                $u->pertenece_id = $gerenciaIdsG[$i];
                $u->save();
            }
        });

        // Subgerentes: 2–34, saltar 2 gerencias (otras diferentes)
        $gerenciaIdsSG = collect(range(2, 34))->shuffle()->slice(0, 32)->values();
        User::factory(20)->create()->each(function ($u, $i) use ($subgerenteRole, $gerenciaIdsSG) {
            $u->assignRole($subgerenteRole);
            if ($i < count($gerenciaIdsSG)) {
                $u->pertenece_id = $gerenciaIdsSG[$i];
                $u->save();
            }
        });

        // Usuarios: 1–34, pero 5 no tendrán pertenencia
        $gerenciaIdsU = collect(range(1, 34))->shuffle()->take(45)->values();
        User::factory(50)->create()->each(function ($u, $i) use ($usuarioRole, $gerenciaIdsU) {
            $u->assignRole($usuarioRole);
            if ($i < count($gerenciaIdsU)) {
                $u->pertenece_id = $gerenciaIdsU[$i];
                $u->save();
            }
        });

    }
}
