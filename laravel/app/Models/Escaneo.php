<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Escaneo asociado a un proyecto.
 * Lo lanza el auditor desde el asistente de 4 pasos.
 *
 * "parametros_nmap" es JSON con la configuración completa para
 * poder relanzar idéntico, y se envía al motor FastAPI.
 */
class Escaneo extends Model
{
    protected $table = 'escaneo';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_escaneo',
        'plantilla_escaneo',
        'objetivo',
        'velocidad',
        'intensidad',
        'estado',
        'exclusiones',
        'fecha_inicio',
        'fecha_fin',
        'parametros_nmap',
        'progreso_pct',
        'fase_actual',
        'error_mensaje',
        'proyecto_id',
        'lanzado_por',
    ];

    protected $casts = [
        'parametros_nmap' => 'array',
        'fecha_inicio'    => 'datetime',
        'fecha_fin'       => 'datetime',
        'progreso_pct'    => 'integer',
    ];

    public const ESTADO_PENDIENTE  = 'pendiente';
    public const ESTADO_EN_PROCESO = 'en_proceso';
    public const ESTADO_COMPLETADO = 'completado';
    public const ESTADO_ERROR      = 'error';

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    // Usuario que lanzó este escaneo. NO es el auditor responsable del
    // proyecto (ese vive en Proyecto::auditor). Modelo de equipo:
    // cualquier empleado puede lanzar escaneos en cualquier proyecto,
    // pero solo el que los lanzó puede eliminarlos.
    public function lanzador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'lanzado_por');
    }

    public function activos(): HasMany
    {
        return $this->hasMany(Activo::class);
    }

    /**
     * Acceso directo a todos los puertos del escaneo (atravesando activos).
     */

    // HasManyThrough: un escaneo tiene muchos puertos a través de activos.
    public function puertos(): HasManyThrough
    {
        return $this->hasManyThrough(Puerto::class, Activo::class);
    }

    public function estaActivo(): bool
    {
        // Un escaneo se considera "activo" si está en estado pendiente o en proceso.
        return in_array(
            $this->estado,
            [self::ESTADO_PENDIENTE, self::ESTADO_EN_PROCESO]
        );
    }

    // Regla de borrado del escaneo en un solo sitio. El controller la
    // usa para el guard (abort 403) y los blades para esconder el
    // botón Eliminar.
    //
    // Solo el admin o quien lanzó el escaneo pueden borrarlo. Lanzar,
    // ver y editar siguen siendo libres para cualquier empleado del
    // equipo — el modelo "mi escaneo lo borro yo" evita que un
    // compañero tire por error el trabajo de otro.
    public function puedeBorrar(?Usuario $usuario): bool
    {
        return $usuario !== null && (
            $usuario->rol->nombre === Rol::ADMIN
            || $this->lanzado_por === $usuario->id
        );
    }
}
