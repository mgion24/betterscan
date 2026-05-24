<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Pruebas de integración del flujo de login.
class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Rol::create(['nombre' => 'admin', 'descripcion' => 'Administrador']);
        Rol::create(['nombre' => 'empleado', 'descripcion' => 'Auditor']);
        Rol::create(['nombre' => 'cliente', 'descripcion' => 'Cliente']);
    }

    public function test_pagina_login_responde_200()
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_login_con_credenciales_validas_redirige_a_dashboard()
    {
        $admin = Rol::where('nombre', 'admin')->first();
        Usuario::create([
            'nombre'          => 'Admin',
            'apellido'        => 'Test',
            'email'           => 'admin@betterscan.syncbetter.es',
            'contrasena_hash' => Hash::make('Admin1234!'),
            'rol_id'          => $admin->id,
        ]);

        $resp = $this->post('/login', [
            'email'    => 'admin@betterscan.syncbetter.es',
            'password' => 'Admin1234!',
        ]);

        $resp->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_login_con_password_invalida_devuelve_error()
    {
        $admin = Rol::where('nombre', 'admin')->first();
        Usuario::create([
            'nombre'          => 'Admin',
            'apellido'        => 'Test',
            'email'           => 'admin@betterscan.syncbetter.es',
            'contrasena_hash' => Hash::make('Admin1234!'),
            'rol_id'          => $admin->id,
        ]);

        $resp = $this->post('/login', [
            'email'    => 'admin@betterscan.syncbetter.es',
            'password' => 'mal',
        ]);

        $resp->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logout_cierra_sesion()
    {
        $admin = Rol::where('nombre', 'admin')->first();
        $u = Usuario::create([
            'nombre'          => 'Admin',
            'apellido'        => 'Test',
            'email'           => 'admin@betterscan.syncbetter.es',
            'contrasena_hash' => Hash::make('Admin1234!'),
            'rol_id'          => $admin->id,
        ]);

        $this->actingAs($u)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
