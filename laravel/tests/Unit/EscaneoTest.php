<?php

namespace Tests\Unit;

use App\Models\Escaneo;
use PHPUnit\Framework\TestCase;

// Prueba unitaria del helper Escaneo::estaActivo().
class EscaneoTest extends TestCase
{
    public function test_pendiente_es_activo()
    {
        $e = new Escaneo(['estado' => Escaneo::ESTADO_PENDIENTE]);
        $this->assertTrue($e->estaActivo());
    }

    public function test_en_proceso_es_activo()
    {
        $e = new Escaneo(['estado' => Escaneo::ESTADO_EN_PROCESO]);
        $this->assertTrue($e->estaActivo());
    }

    public function test_completado_no_es_activo()
    {
        $e = new Escaneo(['estado' => Escaneo::ESTADO_COMPLETADO]);
        $this->assertFalse($e->estaActivo());
    }

    public function test_error_no_es_activo()
    {
        $e = new Escaneo(['estado' => Escaneo::ESTADO_ERROR]);
        $this->assertFalse($e->estaActivo());
    }
}
