<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hallazgo de herramientas de capa de aplicación (Gobuster, etc.).
 * No se usa en el MVP — solo está definida la entidad.
 */
class Hallazgo extends Model
{
    protected $table = 'hallazgo';
    public $timestamps = false;

    protected $fillable = [
        'herramienta',
        'tipo',
        'recurso',
        'codigo_respuesta',
        'descripcion',
        'severidad',
        'activo_id',
    ];

    public function activo(): BelongsTo
    {
        return $this->belongsTo(Activo::class);
    }
}
