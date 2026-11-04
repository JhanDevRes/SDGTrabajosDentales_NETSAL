<?php

namespace App\Models;

use App\Models\Concerns\InvalidaCacheConsultas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Consumo de material asociado a una orden de trabajo.
 *
 * Guarda el costo unitario del momento del consumo para que los reportes
 * económicos sean históricamente correctos.
 */
class ConsumoMaterial extends Model
{
    use HasFactory;
    use InvalidaCacheConsultas;

    protected $table = 'consumo_materiales';

    /** @var list<string> */
    protected $fillable = [
        'orden_trabajo_id',
        'material_id',
        'cantidad',
        'costo_unitario',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'costo_unitario' => 'decimal:2',
        ];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /** Consumos de una orden concreta (usa el índice de orden_trabajo_id). */
    public function scopeDeOrden(Builder $query, int $ordenId): Builder
    {
        return $query->where('orden_trabajo_id', $ordenId);
    }

    /** Importe del consumo (cantidad por costo unitario). */
    public function subtotal(): float
    {
        return (float) $this->cantidad * (float) $this->costo_unitario;
    }
}
