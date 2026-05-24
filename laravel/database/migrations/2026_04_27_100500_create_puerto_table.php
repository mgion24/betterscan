<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla puerto.
 * Descomposición del campo multivalor "puertos" del activo a una
 * tabla relacionada (cumple 1FN).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('puerto', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('numero');
            $table->enum('protocolo', ['tcp', 'udp'])->default('tcp');
            $table->enum('estado', ['open', 'closed', 'filtered'])->default('open');
            $table->string('servicio', 100)->nullable();
            $table->string('version', 255)->nullable();
            $table->unsignedInteger('activo_id');

            $table->foreign('activo_id')->references('id')->on('activo')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puerto');
    }
};
