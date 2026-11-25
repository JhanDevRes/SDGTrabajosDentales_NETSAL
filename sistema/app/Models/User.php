<?php

namespace App\Models;

use App\Models\Concerns\InvalidaCacheConsultas;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario del sistema.
 *
 * El rol determina los módulos accesibles (ver puedeVerModulo) y los estados
 * de producción que puede registrar (ver estadosPermitidos). El campo "activo"
 * permite dar de baja a un usuario sin eliminar su historial.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, InvalidaCacheConsultas, Notifiable;

    /** Roles disponibles y su etiqueta para la interfaz. */
    public const ROLES = [
        'administrador' => 'Administrador',
        'recepcion' => 'Recepción',
        'disenador' => 'Técnico de diseño',
        'tecnico' => 'Técnico de fresado y acabados',
        'odontologo' => 'Odontólogo / Clínica',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'activo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function ordenesAsignadas(): HasMany
    {
        return $this->hasMany(OrdenTrabajo::class);
    }

    /** Usuarios habilitados para iniciar sesión. */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Personal que puede figurar como responsable de una orden.
     * Usa el índice compuesto users(rol, activo).
     */
    public function scopeResponsables(Builder $query): Builder
    {
        return $query->whereIn('rol', ['administrador', 'recepcion', 'disenador', 'tecnico'])
            ->where('activo', true);
    }

    /** Usuarios del portal de odontólogos (para vincular clientes). */
    public function scopeDelPortal(Builder $query): Builder
    {
        return $query->where('rol', 'odontologo');
    }

    public function rolNombre(): string
    {
        return self::ROLES[$this->rol] ?? ucfirst((string) $this->rol);
    }

    public function esAdministrador(): bool
    {
        return $this->rol === 'administrador';
    }

    public function esOdontologo(): bool
    {
        return $this->rol === 'odontologo';
    }

    /**
     * Precio del trabajo visible (lo que se cobra al cliente).
     *
     * Administración define los precios y recepción los informa al odontólogo
     * al recibir el caso, por eso ambos lo ven. Diseño y fresado trabajan sobre
     * la orden sin necesidad de conocer el importe. El odontólogo ve el precio
     * de sus propios trabajos en su estado de cuenta.
     */
    public function puedeVerPrecios(): bool
    {
        return in_array($this->rol, ['administrador', 'recepcion', 'odontologo'], true);
    }

    /**
     * Información económica interna: costo unitario de materiales, costo real
     * del trabajo y margen bruto.
     *
     * Es información de gestión del laboratorio y solo la ve administración.
     * Recepción atiende al cliente; los técnicos producen. Ninguno de los dos
     * necesita saber cuánto gana el laboratorio por trabajo.
     */
    public function puedeVerCostos(): bool
    {
        return $this->rol === 'administrador';
    }

    /**
     * Anular una orden o desactivar un cliente.
     *
     * Son operaciones con efecto contable e histórico (la orden anulada deja de
     * facturarse y el cliente deja de aparecer en el directorio), por lo que
     * quedan reservadas a administración. Recepción registra y edita, pero no
     * elimina.
     */
    public function puedeAnular(): bool
    {
        return $this->rol === 'administrador';
    }

    /**
     * Indica si el rol puede acceder a un módulo del panel.
     * La misma lista se usa en las rutas (middleware role) y en el menú.
     */
    public function puedeVerModulo(string $modulo): bool
    {
        $permisos = [
            'administrador' => ['panel', 'clientes', 'ordenes', 'materiales', 'entregas', 'reportes', 'usuarios', 'solicitudes'],
            'recepcion' => ['panel', 'clientes', 'ordenes', 'entregas', 'reportes', 'solicitudes'],
            'disenador' => ['panel', 'ordenes'],
            'tecnico' => ['panel', 'ordenes', 'materiales', 'reportes'],
            'odontologo' => ['panel', 'ordenes', 'mi-cuenta'],
        ];

        return in_array($modulo, $permisos[$this->rol] ?? [], true);
    }

    /**
     * Estados de producción que el usuario puede registrar.
     *
     * @return list<string>
     */
    public function estadosPermitidos(): array
    {
        return match ($this->rol) {
            'administrador' => array_keys(OrdenTrabajo::ESTADOS),
            'recepcion' => ['recibido', 'listo', 'entregado'],
            'disenador' => ['recibido', 'diseno', 'fresado'],
            'tecnico' => ['diseno', 'fresado', 'horno_acabado', 'control', 'listo'],
            default => [],
        };
    }
}
