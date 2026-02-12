<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear el rol admin si no existe
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Crear el usuario administrador
        $admin = User::updateOrCreate(
            ['email' => 'diana@dilorecords.com'],
            [
                'name' => 'Diana Dilo Records',
                'password' => Hash::make('AdminD1l0*'),
                'email_verified_at' => now(),
            ]
        );

        // Asignar el rol de administrador
        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }

        $this->command->info('Usuario administrador creado: diana@dilorecords.com / AdminD1l0*');

        // Crear el segundo usuario administrador
        $santiago = User::updateOrCreate(
            ['email' => 'san7imo@gmail.com'],
            [
                'name' => 'Santiago',
                'password' => Hash::make('Oliver0227.'),
                'email_verified_at' => now(),
            ]
        );

        if (!$santiago->hasRole('admin')) {
            $santiago->assignRole($adminRole);
        }

        $this->command->info('Usuario administrador creado: san7imo@gmail.com / Oliver0227.');

        // Crear el tercer usuario administrador (Brazil)
        $brazil = User::updateOrCreate(
            ['email' => 'brazil@dilorecords.com'],
            [
                'name' => 'Brazil',
                'password' => Hash::make('Brazil123,.*'),
                'email_verified_at' => now(),
            ]
        );

        if (!$brazil->hasRole('admin')) {
            $brazil->assignRole($adminRole);
        }

        $this->command->info('Usuario administrador creado: brazil@dilorecords.com / Brazil123,.*');
    }
}