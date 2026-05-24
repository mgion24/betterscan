<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla vulnerabilidad.
 *
 * cve_asociado es NULLABLE: no toda vulnerabilidad tiene CVE.
 *
 * "severidad" es derivable de "cvss" según CVSS v3.1 (es una
 * desnormalización documentada, 3FN). Se mantiene como campo
 * independiente por rendimiento en consultas de filtrado.
 *
 * "enriquecido_en" lo rellena el motor cuando consulta NVD/MITRE.
 * Si está a NULL significa que la info viene solo del escaneo.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('vulnerabilidad', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cve_asociado', 20)->nullable();
            $table->text('descripcion')->nullable();
            $table->decimal('cvss', 3, 1)->nullable()
                ->comment('Puntuación CVSS v3.1 (0.0 - 10.0).');
            $table->string('vector', 255)->nullable()
                ->comment('Ej: CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H');
            $table->enum('severidad', ['nada', 'baja', 'media', 'alta', 'critica'])
                ->nullable();
            $table->text('remediacion')->nullable();
            $table->text('referencias')->nullable()
                ->comment('URLs separadas por salto de línea (NVD, vendor, etc.).');
            $table->dateTime('enriquecido_en')->nullable()
                ->comment('Timestamp del último lookup contra NVD/MITRE.');
            $table->unsignedInteger('puerto_id');

            $table->foreign('puerto_id')->references('id')->on('puerto')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vulnerabilidad');
    }
};
