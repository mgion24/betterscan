<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Host/activo descubierto en un escaneo.
 */
class Activo extends Model
{
    protected $table = 'activo';
    public $timestamps = false;

    protected $fillable = [
        'ip',
        'mac',
        'hostname',
        'sistema_operativo',
        'direccion_red',
        'escaneo_id',
    ];

    public function escaneo(): BelongsTo
    {
        return $this->belongsTo(Escaneo::class);
    }

    public function puertos(): HasMany
    {
        return $this->hasMany(Puerto::class);
    }

    public function hallazgos(): HasMany
    {
        return $this->hasMany(Hallazgo::class);
    }
}
