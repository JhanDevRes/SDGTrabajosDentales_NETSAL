<?php

namespace App\Models;

use App\Models\Concerns\InvalidaCacheConsultas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Material del inventario del laboratorio.
 *
 * El costo unitario se copia a consumo_materiales al registrar un consumo,
 * de modo que los reportes históricos no cambien si el precio se actualiza.
 */
class Material extends Model
{
    use HasFactory;
    use InvalidaCacheConsultas;

    protected $table = 'materiales';

    /** @var list<string> */
    protected $fillable = [
        'nombre',
        'unidad',
        'costo',
        'stock',
        'stock_minimo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'costo' => 'decimal:2',
            'stock' => 'decimal:2',
            'stock_minimo' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function consumos(): HasMany
    {
        return $this->hasMany(ConsumoMaterial::class);
    }

    /** Materiales disponibles para asignar a una orden. */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /** Materiales en o por debajo del stock mínimo (alerta de reposición). */
    public function scopeStockBajo(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'stock_minimo');
    }

    /** Búsqueda por nombre con coincidencia desde el inicio (usa índice). */
    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        return $query->where('nombre', 'like', $termino.'%');
    }

    public function tieneStockBajo(): bool
    {
        return (float) $this->stock <= (float) $this->stock_minimo;
    }
}
