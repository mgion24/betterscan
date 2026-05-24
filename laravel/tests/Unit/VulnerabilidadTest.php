<?php

namespace Tests\Unit;

use App\Models\Vulnerabilidad;
use PHPUnit\Framework\TestCase;

// Prueba unitaria: el mapeo de CVSS a severidad sigue la tabla de CVSS v3.1.
class VulnerabilidadTest extends TestCase
{
    public function test_cvss_null_devuelve_nada()
    {
        $this->assertSame('nada', Vulnerabilidad::severidadDesdeCvss(null));
    }

    public function test_cvss_cero_devuelve_nada()
    {
        $this->assertSame('nada', Vulnerabilidad::severidadDesdeCvss(0.0));
    }

    public function test_cvss_baja()
    {
        $this->assertSame('baja', Vulnerabilidad::severidadDesdeCvss(0.1));
        $this->assertSame('baja', Vulnerabilidad::severidadDesdeCvss(3.9));
    }

    public function test_cvss_media()
    {
        $this->assertSame('media', Vulnerabilidad::severidadDesdeCvss(4.0));
        $this->assertSame('media', Vulnerabilidad::severidadDesdeCvss(6.9));
    }

    public function test_cvss_alta()
    {
        $this->assertSame('alta', Vulnerabilidad::severidadDesdeCvss(7.0));
        $this->assertSame('alta', Vulnerabilidad::severidadDesdeCvss(8.9));
    }

    public function test_cvss_critica()
    {
        $this->assertSame('critica', Vulnerabilidad::severidadDesdeCvss(9.0));
        $this->assertSame('critica', Vulnerabilidad::severidadDesdeCvss(10.0));
    }
}
