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
        $admin->rol_cache = 'admin';
        $admin->save();

        $unidad->assignRole($unidadRole);
        $unidad->rol_cache = 'administrador-unidad';
        $unidad->save();

        $gerente->assignRole($gerenteRole);
        $gerente->rol_cache = 'gerente';
        $gerente->save();

        $subgerente->assignRole($subgerenteRole);
        $subgerente->rol_cache = 'subgerente';
        $subgerente->save();

        $user->assignRole($usuarioRole);
        $user->rol_cache = 'usuario';
        $user->save();

        // Crear usuarios de prueba y asignar roles
        User::factory(5)->create()->each(function ($user) use ($adminRole) {
            $user->assignRole($adminRole);
            $user->update(['rol_cache' => 'admin']);
        });

        User::factory(10)->create()->each(function ($user) use ($unidadRole) {
            $user->assignRole($unidadRole);
            $user->update(['rol_cache' => 'administrador-unidad']);
        });

        User::factory(20)->create()->each(function ($user) use ($subgerenteRole) {
            $user->assignRole($subgerenteRole);
            $user->update(['rol_cache' => 'subgerente']);
        });

        User::factory(35)->create()->each(function ($user) use ($gerenteRole) {
            $user->assignRole($gerenteRole);
            $user->update(['rol_cache' => 'gerente']);
        });

        User::factory(50)->create()->each(function ($user) use ($usuarioRole) {
            $user->assignRole($usuarioRole);
            $user->update(['rol_cache' => 'usuario']);
        });

    }
}
