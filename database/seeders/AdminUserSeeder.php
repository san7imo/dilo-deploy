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
    }
}
