<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla informe.
 *
 * Ligado a PROYECTO, no a escaneo: un informe cubre el proyecto
 * entero (suma de todos sus escaneos).
 *
 * "emitido_por" preserva el audit trail: guarda quién lo generó en
 * el momento de su creación, independientemente de reasignaciones
 * posteriores del auditor del proyecto.
 *
 * Formato: solo PDF en el MVP.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('informe', function (Blueprint $table) {
            $table->increments('id');
            $table->string('tipo_informe', 100)->nullable()
                ->comment('ejecutivo, tecnico, ...');
            $table->enum('formato', ['pdf'])->default('pdf');
            $table->string('ruta_archivo', 500)->nullable();
            $table->dateTime('fecha_creacion')->useCurrent();
            $table->unsignedInteger('proyecto_id');
            $table->unsignedInteger('emitido_por')
                ->comment('Audit trail: FK al usuario que generó el informe.');

            $table->foreign('proyecto_id')->references('id')->on('proyecto');
            $table->foreign('emitido_por')->references('id')->on('usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informe');
    }
};
