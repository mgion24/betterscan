<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Proyecto de auditoría: pertenece a una empresa cliente y a un
 * auditor (usuario con rol empleado).
 */
class Proyecto extends Model
{
    protected $table = 'proyecto';

    protected $fillable = [
        'nombre',
        'descripcion',
        'etiquetas',
        'tipo_auditoria',
        'alcance_red',
        'excepciones_red',
        'visibilidad',
        'fecha_limite_estimada',
        'empresa_id',
        'auditor_id',
    ];

    protected $casts = [
        'fecha_limite_estimada' => 'date',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'auditor_id');
    }

    public function escaneos(): HasMany
    {
        return $this->hasMany(Escaneo::class);
    }

    public function informes(): HasMany
    {
        return $this->hasMany(Informe::class);
    }

    /**
     * Devuelve la lista de etiquetas como array, partiendo el campo
     * multivalor por comas (violación 1FN documentada en el PDF).
     */
    public function etiquetasArray(): array
    {
        return $this->etiquetas ? array_filter(array_map('trim', explode(',', $this->etiquetas))) : [];
    }

    // Regla de gestión del proyecto en un solo sitio. El controller la
    // usa para el guard (abort 403) y los blades para esconder los
    // botones Editar y Eliminar del proyecto.
    //
    // Solo el admin o el auditor responsable pueden modificar la
    // metadata del proyecto (edit / update / destroy). Las operaciones
    // del equipo (escaneos, informes) NO usan esta regla — cualquier
    // empleado puede lanzar escaneos en cualquier proyecto.
    public function puedeGestionar(?Usuario $usuario): bool
    {
        return $usuario !== null && (
            $usuario->rol->nombre === Rol::ADMIN
            || $this->auditor_id === $usuario->id
        );
    }
}
