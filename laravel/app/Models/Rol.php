<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de roles: admin, empleado, cliente.
 */
class Rol extends Model
{
    protected $table = 'rol';
    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion'];

    // Constantes para evitar strings sueltos al comparar roles.
    public const ADMIN = 'admin';
    public const EMPLEADO = 'empleado';
    public const CLIENTE = 'cliente';

    // HasMany inversa: un rol tiene muchos usuarios.
    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }
}
