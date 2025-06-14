<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;


class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles and assign them to the admin user
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'gerente']);
        Role::firstOrCreate(['name' => 'subgerente']);
        Role::firstOrCreate(['name' => 'administrador-unidad']);
        Role::firstOrCreate(['name' => 'usuario']);
    }
}
