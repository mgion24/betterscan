<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla rol.
 * Catálogo de roles del sistema: admin, empleado, cliente.
 * Equivale a la tabla "rol" del script betterscan_create.sql.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('rol', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 50)->unique()
                ->comment('Valores esperados: admin, empleado, cliente');
            $table->text('descripcion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol');
    }
};
