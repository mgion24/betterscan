<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Añade lanzado_por a la tabla escaneo.
 *
 * Quién lanzó el escaneo (no quién es el auditor responsable del
 * proyecto, ese vive en proyecto.auditor_id). Sirve para restringir
 * el delete: el modelo de equipo permite a cualquier empleado lanzar
 * escaneos en cualquier proyecto, pero solo el que los lanzó puede
 * eliminarlos.
 *
 * Los escaneos que existían antes de esta migración (seed + cualquier
 * cosa creada antes) se rellenan con el auditor_id del proyecto al
 * que pertenecen, así no quedan escaneos huérfanos sin owner que
 * solo el admin podría borrar.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('escaneo', function (Blueprint $table) {
            $table->unsignedInteger('lanzado_por')->nullable()->after('proyecto_id');
            $table->foreign('lanzado_por')->references('id')->on('usuario')
                ->nullOnDelete();
        });

        // Sintaxis ANSI (subquery escalar) compatible con MariaDB y
        // SQLite (este último lo usan los tests Feature con
        // RefreshDatabase). MariaDB también soporta UPDATE ... JOIN
        // pero esa forma no es estándar y rompería los tests.
        DB::statement(
            'UPDATE escaneo
             SET lanzado_por = (
                 SELECT auditor_id FROM proyecto WHERE proyecto.id = escaneo.proyecto_id
             )
             WHERE lanzado_por IS NULL'
        );
    }

    public function down(): void
    {
        Schema::table('escaneo', function (Blueprint $table) {
            $table->dropForeign(['lanzado_por']);
            $table->dropColumn('lanzado_por');
        });
    }
};
