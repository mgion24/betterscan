<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'nombre' => 'admin',    'descripcion' => 'Administrador con acceso total al sistema'],
            ['id' => 2, 'nombre' => 'empleado', 'descripcion' => 'Auditor: crea proyectos, lanza escaneos y genera informes'],
            ['id' => 3, 'nombre' => 'cliente',  'descripcion' => 'Acceso de solo lectura al portal de su empresa'],
        ];
        foreach ($roles as $r) {
            Rol::updateOrCreate(['id' => $r['id']], $r);
        }
    }
}
