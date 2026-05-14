<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@nexaerp.test'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
        );
        $admin->syncRoles(['Admin']);

        $vendedor = User::firstOrCreate(
            ['email' => 'vendedor@nexaerp.test'],
            [
                'name' => 'Vendedor Demo',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
        );
        $vendedor->syncRoles(['Vendedor']);

        $almacen = User::firstOrCreate(
            ['email' => 'almacen@nexaerp.test'],
            [
                'name' => 'Almacén Demo',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
        );
        $almacen->syncRoles(['Almacén']);
    }
}
