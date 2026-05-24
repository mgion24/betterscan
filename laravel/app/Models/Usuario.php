<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario del sistema (admin, empleado o cliente).
 *
 * Implementa Authenticatable manualmente porque usamos el nombre de
 * tabla "usuario" (singular) y la columna "contrasena_hash" en vez
 * de los defaults "users" / "password" de Laravel.
 */
class Usuario extends Model implements AuthenticatableContract
{
    use HasFactory, Notifiable, Authenticatable;

    protected $table = 'usuario';

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'contrasena_hash',
        'avatar',
        'rol_id',
        'empresa_id',
    ];

    protected $hidden = [
        'contrasena_hash',
        'remember_token',
    ];

    /**
     * Le decimos al guard de Laravel que la columna de password se
     * llama "contrasena_hash" en vez de "password".
     */
    public function getAuthPasswordName(): string
    {
        return 'contrasena_hash';
    }

    public function getAuthPassword(): string
    {
        return $this->contrasena_hash;
    }

    // --- Relaciones ---

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function proyectosAuditados(): HasMany
    {
        return $this->hasMany(Proyecto::class, 'auditor_id');
    }

    public function informesEmitidos(): HasMany
    {
        return $this->hasMany(Informe::class, 'emitido_por');
    }

    // --- Helpers de rol ---

    public function esAdmin(): bool
    {
        return $this->rol?->nombre === Rol::ADMIN;
    }

    public function esEmpleado(): bool
    {
        return $this->rol?->nombre === Rol::EMPLEADO;
    }

    public function esCliente(): bool
    {
        return $this->rol?->nombre === Rol::CLIENTE;
    }

    public function nombreCompleto(): string
    {
        return trim($this->nombre.' '.$this->apellido);
    }

    public function iniciales(): string
    {
        return strtoupper(
            substr($this->nombre, 0, 1).substr($this->apellido, 0, 1)
        );
    }
}
