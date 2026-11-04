<?php

namespace App\Models;

use App\Models\Concerns\InvalidaCacheConsultas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrega de una orden de trabajo (recojo en laboratorio o envío).
 *
 * Existe como máximo una entrega por orden; la restricción se garantiza con
 * un índice único en orden_trabajo_id.
 */
class Entrega extends Model
{
    use HasFactory;
    use InvalidaCacheConsultas;

    /** Tipos de entrega y su etiqueta para la interfaz. */
    public const TIPOS = [
        'recojo' => 'Recojo en laboratorio',
        'envio' => 'Envío a domicilio',
    ];

    protected $table = 'entregas';

    /** @var list<string> */
    protected $fillable = [
        'orden_trabajo_id',
        'user_id',
        'tipo',
        'fecha',
        'receptor',
        'confirmado',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'confirmado' => 'boolean',
        ];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Entregas confirmadas por el cliente. */
    public function scopeConfirmadas(Builder $query): Builder
    {
        return $query->where('confirmado', true);
    }

    /** Entregas registradas que aún no se confirman. */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('confirmado', false);
    }

    /** Filtro por tipo de entrega. */
    public function scopeDeTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }

    public function tipoNombre(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst((string) $this->tipo);
    }
}
