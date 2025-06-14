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
        $user->assignRole($usuarioRole);

        // Crear usuarios de prueba y asignar roles
        User::factory(5)->create()->each(fn($user) => $user->assignRole($adminRole));
        User::factory(10)->create()->each(fn($user) => $user->assignRole($unidadRole));
        User::factory(35)->create()->each(fn($user) => $user->assignRole($gerenteRole));
        User::factory(50)->create()->each(fn($user) => $user->assignRole($usuarioRole));
    }
}
