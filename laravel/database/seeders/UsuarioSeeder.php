<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        // Contraseñas hasheadas con bcrypt en tiempo de ejecución.
        // Coste = el configurado en .env (BCRYPT_ROUNDS=10).
        $usuarios = [
            [
                'id' => 1, 'nombre' => 'Admin', 'apellido' => 'BetterScan',
                'email' => 'admin@betterscan.syncbetter.es', 'telefono' => '600000001',
                'contrasena_hash' => Hash::make('Admin1234!'),
                'rol_id' => 1, 'empresa_id' => null,
            ],
            [
                'id' => 2, 'nombre' => 'Alejandro', 'apellido' => 'Ruiz Garcia',
                'email' => 'auditor@betterscan.syncbetter.es', 'telefono' => '600000002',
                'contrasena_hash' => Hash::make('Audit1234!'),
                'rol_id' => 2, 'empresa_id' => null,
            ],
            [
                'id' => 3, 'nombre' => 'Sofia', 'apellido' => 'Martin Lopez',
                'email' => 'auditor2@betterscan.syncbetter.es', 'telefono' => '600000003',
                'contrasena_hash' => Hash::make('Audit1234!'),
                'rol_id' => 2, 'empresa_id' => null,
            ],
            [
                'id' => 4, 'nombre' => 'Juan', 'apellido' => 'Perez',
                'email' => 'cliente@acmecorp.com', 'telefono' => '600000004',
                'contrasena_hash' => Hash::make('Client1234!'),
                'rol_id' => 3, 'empresa_id' => 1,
            ],
            [
                'id' => 5, 'nombre' => 'Maria', 'apellido' => 'Gonzalez',
                'email' => 'cliente@retailmax.es', 'telefono' => '600000005',
                'contrasena_hash' => Hash::make('Client1234!'),
                'rol_id' => 3, 'empresa_id' => 3,
            ],
        ];

        foreach ($usuarios as $u) {
            Usuario::updateOrCreate(['id' => $u['id']], $u);
        }
    }
}
