<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Informe generado para un proyecto.
 * "emitido_por" guarda quién lo creó para mantener trazabilidad.
 */
class Informe extends Model
{
    protected $table = 'informe';
    public $timestamps = false;

    protected $fillable = [
        'tipo_informe',
        'formato',
        'ruta_archivo',
        'fecha_creacion',
        'proyecto_id',
        'emitido_por',
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'emitido_por');
    }

    // Regla de borrado del informe en un solo sitio. El controller la
    // usa para el guard (abort 403) y el blade la usa para esconder el
    // botón Eliminar. Si la regla cambia, solo se toca aquí — no en
    // dos sitios distintos como antes.
    //
    // Tres caminos para borrar:
    //   - admin (siempre),
    //   - auditor responsable del proyecto al que pertenece el informe,
    //   - emisor del informe (quien pulsó "Generar PDF").
    public function puedeBorrar(?Usuario $usuario): bool
    {
        return $usuario !== null && (
            $usuario->rol->nombre === Rol::ADMIN
            || $this->proyecto->auditor_id === $usuario->id
            || $this->emitido_por === $usuario->id
        );
    }
}
