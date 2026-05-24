<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla empresa.
 * Empresas clientes auditadas. Los datos de "responsable_*" son
 * contacto comercial; no se ligan al usuario del portal con FK para
 * evitar referencia circular usuario <-> empresa.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('empresa', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 150);
            $table->string('cif', 20)->unique()->nullable();
            $table->string('nombre_comercial', 150)->nullable();
            $table->string('razon_social', 200)->nullable();
            $table->string('sector', 100)->nullable();
            $table->text('direccion')->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->boolean('activo')->default(true);
            $table->string('responsable_nombre', 100)->nullable();
            $table->string('responsable_email', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa');
    }
};
