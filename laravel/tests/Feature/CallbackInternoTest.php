<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Escaneo;
use App\Models\Proyecto;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Pruebas de integración para el endpoint interno
// /api/internal/escaneo/{id}/estado que llama el motor FastAPI.
class CallbackInternoTest extends TestCase
{
    use RefreshDatabase;

    private Escaneo $escaneo;
    private string $token = 'token-de-pruebas-1234567890';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.fastapi.token', $this->token);

        $rolEmp = Rol::create(['nombre' => 'empleado', 'descripcion' => '']);
        Rol::create(['nombre' => 'admin', 'descripcion' => '']);
        Rol::create(['nombre' => 'cliente', 'descripcion' => '']);

        $emp = Empresa::create([
            'nombre' => 'Acme', 'cif' => 'B12345678',
            'razon_social' => 'Acme SL', 'responsable_nombre' => 'X',
            'responsable_email' => 'x@acme.local', 'activo' => true,
        ]);
        $auditor = Usuario::create([
            'nombre' => 'A', 'apellido' => 'A',
            'email' => 'a@a.com',
            'contrasena_hash' => Hash::make('Audit1234!'),
            'rol_id' => $rolEmp->id,
        ]);
        $proyecto = Proyecto::create([
            'nombre' => 'P1', 'tipo_auditoria' => 'red',
            'visibilidad' => 'cliente',
            'empresa_id' => $emp->id, 'auditor_id' => $auditor->id,
        ]);
        $this->escaneo = Escaneo::create([
            'nombre' => 'E1', 'objetivo' => '127.0.0.1',
            'velocidad' => 'normal', 'estado' => 'pendiente',
            'proyecto_id' => $proyecto->id,
        ]);
    }

    public function test_sin_token_devuelve_401()
    {
        $resp = $this->postJson("/api/internal/escaneo/{$this->escaneo->id}/estado", [
            'estado' => 'en_proceso',
        ]);
        $resp->assertStatus(401);
    }

    public function test_token_incorrecto_devuelve_401()
    {
        $resp = $this->withHeaders(['Authorization' => 'Bearer token-malo'])
            ->postJson("/api/internal/escaneo/{$this->escaneo->id}/estado", [
                'estado' => 'en_proceso',
            ]);
        $resp->assertStatus(401);
    }

    public function test_token_correcto_actualiza_estado()
    {
        $resp = $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->postJson("/api/internal/escaneo/{$this->escaneo->id}/estado", [
                'estado'       => 'en_proceso',
                'progreso_pct' => 50,
                'fase_actual'  => 'Nmap',
            ]);
        $resp->assertStatus(200)->assertJson(['ok' => true]);

        $this->escaneo->refresh();
        $this->assertSame('en_proceso', $this->escaneo->estado);
        $this->assertSame(50, $this->escaneo->progreso_pct);
        $this->assertSame('Nmap', $this->escaneo->fase_actual);
    }

    public function test_estado_invalido_devuelve_422()
    {
        $resp = $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->postJson("/api/internal/escaneo/{$this->escaneo->id}/estado", [
                'estado' => 'estado-inventado',
            ]);
        $resp->assertStatus(422);
    }
}
