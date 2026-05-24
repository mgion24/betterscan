<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla escaneo.
 *
 * "estado" como ENUM impide que el motor FastAPI escriba valores
 * fuera del dominio. Pero realmente Laravel es el único punto de
 * escritura (FastAPI hace callback a /api/internal/...).
 *
 * Se añade el campo "parametros_nmap" (JSON) respecto al diseño
 * original del PDF para guardar la configuración completa del
 * asistente de 4 pasos (plantilla, velocidad, scripts NSE, etc.).
 * Permite relanzar un escaneo idéntico desde el detalle.
 *
 * "progreso_pct" lo actualiza el motor en su callback /estado;
 * el frontend hace polling cada 2s.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('escaneo', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->string('tipo_escaneo', 200)->nullable()
                ->comment('Auditoría web, Escaneo de red, etc.');
            $table->string('plantilla_escaneo', 100)->nullable();
            $table->string('objetivo', 500)->nullable()
                ->comment('IP, rango CIDR o hostname objetivo.');
            $table->enum('velocidad', ['lento', 'normal', 'rapido', 'agresivo'])
                ->default('normal');
            $table->string('intensidad', 50)->nullable();
            $table->enum('estado', ['pendiente', 'en_proceso', 'completado', 'error'])
                ->default('pendiente');
            $table->text('exclusiones')->nullable();
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_fin')->nullable();
            // Campos añadidos en implementación.
            $table->json('parametros_nmap')->nullable()
                ->comment('Configuración completa del asistente.');
            $table->unsignedTinyInteger('progreso_pct')->default(0);
            $table->string('fase_actual', 100)->nullable()
                ->comment('Descripción legible de la fase en curso.');
            $table->text('error_mensaje')->nullable();
            $table->unsignedInteger('proyecto_id');
            $table->timestamps();

            $table->foreign('proyecto_id')->references('id')->on('proyecto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escaneo');
    }
};
