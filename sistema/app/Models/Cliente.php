<?php

namespace App\Models;

use App\Models\Concerns\InvalidaCacheConsultas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cliente del laboratorio: odontólogo o clínica.
 *
 * Un cliente puede estar vinculado a un usuario con rol "odontologo" para que
 * consulte únicamente sus propios trabajos desde el portal.
 */
class Cliente extends Model
{
    use HasFactory;
    use InvalidaCacheConsultas;

    protected $table = 'clientes';

    /** @var list<string> */
    protected $fillable = [
        'tipo',
        'nombre',
        'telefono',
        'email',
        'direccion',
        'user_id',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenTrabajo::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Solo clientes habilitados para recibir nuevas órdenes. */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Búsqueda del directorio de clientes.
     *
     * Nota de rendimiento: LIKE con comodín al inicio ('%texto%') no puede
     * aprovechar el índice, por lo que el listado siempre va paginado. Si el
     * volumen creciera, se debería migrar a un índice FULLTEXT o a búsqueda
     * por prefijo.
     */
    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        return $query->where(function (Builder $sub) use ($termino): void {
            $sub->where('nombre', 'like', "%{$termino}%")
                ->orWhere('email', 'like', "%{$termino}%")
                ->orWhere('telefono', 'like', "%{$termino}%");
        });
    }

    public function tipoNombre(): string
    {
        return $this->tipo === 'clinica' ? 'Clínica' : 'Odontólogo';
    }
}
