<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Puerto descubierto en un activo.
 */
class Puerto extends Model
{
    protected $table = 'puerto';
    public $timestamps = false;

    protected $fillable = [
        'numero',
        'protocolo',
        'estado',
        'servicio',
        'version',
        'activo_id',
    ];

    // Relaciones
    public function activo(): BelongsTo
    {
        return $this->belongsTo(Activo::class);
    }

    public function vulnerabilidades(): HasMany
    {
        return $this->hasMany(Vulnerabilidad::class);
    }
}
