<?php

namespace Tests\Unit;

use App\Models\Rol;
use App\Models\Usuario;
use PHPUnit\Framework\TestCase;

// Pruebas unitarias de los helpers del modelo Usuario.
class UsuarioTest extends TestCase
{
    public function test_nombre_completo()
    {
        $u = new Usuario(['nombre' => 'Marian', 'apellido' => 'Ion']);
        $this->assertSame('Marian Ion', $u->nombreCompleto());
    }

    public function test_iniciales()
    {
        $u = new Usuario(['nombre' => 'Marian', 'apellido' => 'Ion']);
        $this->assertSame('MI', $u->iniciales());
    }

    public function test_es_admin_cuando_rol_admin()
    {
        $u = new Usuario(['nombre' => 'X', 'apellido' => 'Y']);
        $u->setRelation('rol', new Rol(['nombre' => 'admin']));
        $this->assertTrue($u->esAdmin());
        $this->assertFalse($u->esEmpleado());
        $this->assertFalse($u->esCliente());
    }

    public function test_es_cliente_cuando_rol_cliente()
    {
        $u = new Usuario(['nombre' => 'A', 'apellido' => 'B']);
        $u->setRelation('rol', new Rol(['nombre' => 'cliente']));
        $this->assertTrue($u->esCliente());
        $this->assertFalse($u->esAdmin());
    }

    public function test_sin_rol_los_helpers_devuelven_false()
    {
        $u = new Usuario(['nombre' => 'A', 'apellido' => 'B']);
        // Forzamos la relación a null para no tocar la BD.
        $u->setRelation('rol', null);
        $this->assertFalse($u->esAdmin());
        $this->assertFalse($u->esEmpleado());
        $this->assertFalse($u->esCliente());
    }
}
