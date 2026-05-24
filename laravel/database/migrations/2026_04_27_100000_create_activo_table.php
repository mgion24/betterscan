<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla activo (hosts descubiertos en un escaneo).
 *
 * Un activo pertenece a un escaneo concreto: snapshot en el tiempo.
 * Si el mismo host aparece en dos escaneos distintos, se crean dos
 * registros independientes para preservar el histórico.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('activo', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ip', 45)->nullable()->comment('IPv4 o IPv6.');
            $table->string('mac', 18)->nullable();
            $table->string('hostname', 255)->nullable();
            $table->string('sistema_operativo', 255)->nullable();
            $table->string('direccion_red', 50)->nullable();
            $table->unsignedInteger('escaneo_id');

            $table->foreign('escaneo_id')->references('id')->on('escaneo')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activo');
    }
};
