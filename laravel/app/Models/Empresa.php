<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Empresa cliente que se audita.
 * No tiene FK directa hacia su usuario-portal para evitar ciclos
 * usuario <-> empresa. La relación va al revés (usuario.empresa_id).
 */
class Empresa extends Model
{
    protected $table = 'empresa';

    protected $fillable = [
        'nombre',
        'cif',
        'nombre_comercial',
        'razon_social',
        'sector',
        'direccion',
        'logo_path',
        'activo',
        'responsable_nombre',
        'responsable_email',
    ];

    // Casts para convertir 'activo' a booleano automáticamente.
    protected $casts = [
        'activo' => 'boolean',
    ];

    public function usuariosCliente(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }

    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class);
    }
}
