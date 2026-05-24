<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla hallazgo.
 * Diseñada para herramientas de capa de aplicación web (Gobuster,
 * Wfuzz, SQLMap, Nikto). Va ligada a activo (URLs), no a puerto.
 *
 * No se usa en el MVP — la tabla existe para facilitar la extensión
 * futura sin tocar el resto del esquema.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('hallazgo', function (Blueprint $table) {
            $table->increments('id');
            $table->string('herramienta', 50)->nullable()
                ->comment('gobuster, wfuzz, sqlmap, nikto...');
            $table->string('tipo', 100)->nullable();
            $table->string('recurso', 1000)->nullable()
                ->comment('Ruta o URL del recurso encontrado.');
            $table->unsignedSmallInteger('codigo_respuesta')->nullable();
            $table->text('descripcion')->nullable();
            $table->enum('severidad', ['info', 'baja', 'media', 'alta', 'critica'])
                ->nullable();
            $table->unsignedInteger('activo_id');

            $table->foreign('activo_id')->references('id')->on('activo')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hallazgo');
    }
};
