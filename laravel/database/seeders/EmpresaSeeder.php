<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        $empresas = [
            [
                'id' => 1, 'nombre' => 'ACME Corporation S.L.', 'cif' => 'B12345678',
                'nombre_comercial' => 'ACME Corp', 'razon_social' => 'ACME Corporation Sociedad Limitada',
                'sector' => 'Tecnologia', 'direccion' => 'Calle Gran Via 1, 28013 Madrid',
                'activo' => true, 'responsable_nombre' => 'Carlos Martinez',
                'responsable_email' => 'c.martinez@acmecorp.com',
            ],
            [
                'id' => 2, 'nombre' => 'Seguridad Global S.A.', 'cif' => 'A87654321',
                'nombre_comercial' => 'SegGlobal', 'razon_social' => 'Seguridad Global Sociedad Anonima',
                'sector' => 'Ciberseguridad', 'direccion' => 'Av. Diagonal 200, 08013 Barcelona',
                'activo' => true, 'responsable_nombre' => 'Ana Torres',
                'responsable_email' => 'a.torres@segglobal.es',
            ],
            [
                'id' => 3, 'nombre' => 'RetailMax S.L.', 'cif' => 'B55512349',
                'nombre_comercial' => 'RetailMax', 'razon_social' => 'RetailMax Sociedad Limitada',
                'sector' => 'Comercio', 'direccion' => 'Calle Serrano 44, 28001 Madrid',
                'activo' => true, 'responsable_nombre' => 'Pedro Lopez',
                'responsable_email' => 'p.lopez@retailmax.es',
            ],
        ];
        foreach ($empresas as $e) {
            Empresa::updateOrCreate(['id' => $e['id']], $e);
        }
    }
}
