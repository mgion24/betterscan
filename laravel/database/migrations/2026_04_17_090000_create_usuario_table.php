<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla usuario.
 * empresa_id es NULLABLE: solo es NOT NULL cuando rol = cliente.
 * Esta regla se valida en el FormRequest de Laravel (StoreUsuarioRequest),
 * no se puede expresar como restricción de BD pura.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('email', 150)->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('contrasena_hash', 255)
                ->comment('Bcrypt vía Hash::make() de Laravel.');
            $table->string('avatar', 500)->nullable();
            $table->unsignedInteger('rol_id');
            $table->unsignedInteger('empresa_id')->nullable()
                ->comment('NULL para admin/empleado; NOT NULL para cliente.');
            // Token recordar sesión (cookie "remember me" de Laravel).
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('rol_id')->references('id')->on('rol');
            $table->foreign('empresa_id')->references('id')->on('empresa')
                ->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};
