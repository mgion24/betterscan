<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vulnerabilidad detectada en un puerto.
 *
 * El campo "severidad" es derivable de "cvss" pero se mantiene
 * por rendimiento. La asignación la centraliza el motor FastAPI
 * al parsear los resultados.
 */
class Vulnerabilidad extends Model
{
    protected $table = 'vulnerabilidad';
    public $timestamps = false;

    protected $fillable = [
        'cve_asociado',
        'descripcion',
        'cvss',
        'vector',
        'severidad',
        'remediacion',
        'referencias',
        'enriquecido_en',
        'puerto_id',
    ];

    protected $casts = [
        'cvss'           => 'float',
        'enriquecido_en' => 'datetime',
    ];

    public const SEV_NADA    = 'nada';
    public const SEV_BAJA    = 'baja';
    public const SEV_MEDIA   = 'media';
    public const SEV_ALTA    = 'alta';
    public const SEV_CRITICA = 'critica';

    public function puerto(): BelongsTo
    {
        return $this->belongsTo(Puerto::class);
    }

    // Mapea una puntuación CVSS al valor del ENUM "severidad"
    // según el estándar CVSS v3.1 (0=nada, 0.1-3.9=baja, 4-6.9=media,
    // 7-8.9=alta, 9-10=crítica).
    public static function severidadDesdeCvss(?float $cvss): string
    {
        $severidad = self::SEV_CRITICA;

        if ($cvss === null || $cvss <= 0.0) {
            $severidad = self::SEV_NADA;
        } elseif ($cvss < 4.0) {
            $severidad = self::SEV_BAJA;
        } elseif ($cvss < 7.0) {
            $severidad = self::SEV_MEDIA;
        } elseif ($cvss < 9.0) {
            $severidad = self::SEV_ALTA;
        }

        return $severidad;
    }
}
