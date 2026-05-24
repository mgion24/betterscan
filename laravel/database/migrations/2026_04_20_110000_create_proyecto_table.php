<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla proyecto.
 * Contiene los proyectos de auditoría que el auditor crea para una
 * empresa cliente.
 *
 * Campos multi-valor documentados como violación 1FN consciente (MVP):
 *   etiquetas, alcance_red, excepciones_red — separados por comas.
 *   En una versión normalizada irían a tablas pivot.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('proyecto', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->string('etiquetas', 500)->nullable()
                ->comment('Valores separados por comas. Violación 1FN documentada.');
            $table->string('tipo_auditoria', 100)->nullable();
            $table->text('alcance_red')->nullable()
                ->comment('Rangos CIDR separados por comas.');
            $table->text('excepciones_red')->nullable();
            $table->enum('visibilidad', ['privado', 'cliente'])->default('privado');
            $table->date('fecha_limite_estimada')->nullable();
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('auditor_id');
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('auditor_id')->references('id')->on('usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyecto');
    }
};
