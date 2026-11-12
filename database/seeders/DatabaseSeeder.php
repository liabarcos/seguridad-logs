<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Usuarios de prueba para el panel y el login.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Administrador de seguridad',
            'email' => 'admin@seguridad.test',
            'password' => 'password',
            'is_admin' => true,
        ]);

        User::factory()->create([
            'name' => 'Analista',
            'email' => 'analista@seguridad.test',
            'password' => 'password',
            'is_admin' => false,
        ]);
    }
}
