<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Crear el rol admin si no existe
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $artistRole = Role::firstOrCreate([
            'name' => 'artist',
            'guard_name' => 'web',
        ]);
      

        // Buscar a Diana y asignar rol
        $diana = User::where('email', 'diana@dilorecords.com')->first();

        if ($diana) {
            $diana->assignRole($adminRole);
            $this->command->info('✅ Rol admin asignado a Diana Dilo Records');
        } else {
            $this->command->warn('⚠️ No se encontró el usuario Diana Dilo Records');
        }
    }
}
