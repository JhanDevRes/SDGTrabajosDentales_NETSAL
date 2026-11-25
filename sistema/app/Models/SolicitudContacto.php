<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solicitud enviada desde el formulario público del sitio web.
 *
 * El sitio es un HTML estático que no comparte sesión con el sistema; por eso
 * la solicitud entra por la API pública (POST /api/publico/contacto) y queda
 * registrada aquí para que recepción la atienda desde el panel.
 */
class SolicitudContacto extends Model
{
    public const ESTADOS = [
        'nueva' => 'Nueva',
        'atendida' => 'Atendida',
        'descartada' => 'Descartada',
    ];

    protected $table = 'solicitudes_contacto';

    /** @var list<string> */
    protected $fillable = [
        'nombre',
        'telefono',
        'email',
        'servicio',
        'mensaje',
        'estado',
        'origen',
        'atendida_por',
        'atendida_en',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'atendida_en' => 'datetime',
        ];
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendida_por');
    }

    /** Solicitudes que aún no se atienden. */
    public function scopeNuevas(Builder $query): Builder
    {
        return $query->where('estado', 'nueva');
    }

    public function scopeEnEstado(Builder $query, string $estado): Builder
    {
        return $query->where('estado', $estado);
    }

    /** Búsqueda por nombre, correo o teléfono. */
    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        $comodin = '%'.$termino.'%';

        return $query->where(function (Builder $sub) use ($comodin): void {
            $sub->where('nombre', 'like', $comodin)
                ->orWhere('email', 'like', $comodin)
                ->orWhere('telefono', 'like', $comodin);
        });
    }

    public function estadoNombre(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    /** Clase CSS del distintivo de estado. */
    public function badgeClass(): string
    {
        return 'estado-'.(array_key_exists($this->estado, self::ESTADOS) ? $this->estado : 'nueva');
    }
}
