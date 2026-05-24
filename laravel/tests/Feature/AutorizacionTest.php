<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Pruebas de integración para comprobar que el middleware de rol
// bloquea accesos no permitidos (RNF-08 - principio de privilegio mínimo).
class AutorizacionTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private Usuario $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $rolAdmin = Rol::create(['nombre' => 'admin', 'descripcion' => 'Administrador']);
        $rolCliente = Rol::create(['nombre' => 'cliente', 'descripcion' => 'Cliente']);
        Rol::create(['nombre' => 'empleado', 'descripcion' => 'Auditor']);

        $emp = Empresa::create([
            'nombre'             => 'Acme',
            'cif'                => 'B12345678',
            'razon_social'       => 'Acme SL',
            'responsable_nombre' => 'X',
            'responsable_email'  => 'x@acme.local',
            'activo'             => true,
        ]);

        $this->admin = Usuario::create([
            'nombre' => 'Admin', 'apellido' => 'T',
            'email' => 'admin@betterscan.syncbetter.es',
            'contrasena_hash' => Hash::make('Admin1234!'),
            'rol_id' => $rolAdmin->id,
        ]);
        $this->cliente = Usuario::create([
            'nombre' => 'Cli', 'apellido' => 'T',
            'email' => 'cli@acme.local',
            'contrasena_hash' => Hash::make('Client1234!'),
            'rol_id' => $rolCliente->id,
            'empresa_id' => $emp->id,
        ]);
    }

    public function test_sin_sesion_no_se_puede_ver_dashboard()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_puede_ver_listado_usuarios()
    {
        $this->actingAs($this->admin)
             ->get('/usuarios')
             ->assertStatus(200);
    }

    public function test_cliente_no_puede_ver_listado_usuarios()
    {
        $this->actingAs($this->cliente)
             ->get('/usuarios')
             ->assertStatus(403);
    }

    public function test_cliente_es_redirigido_a_su_portal_desde_dashboard()
    {
        $this->actingAs($this->cliente)
             ->get('/dashboard')
             ->assertRedirect('/portal');
    }
}
