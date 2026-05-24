<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder maestro: ejecuta el resto en orden correcto (respeta las FKs).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            EmpresaSeeder::class,
            UsuarioSeeder::class,
            ProyectoDemoSeeder::class,
        ]);
    }
}
