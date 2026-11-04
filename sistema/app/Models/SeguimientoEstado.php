<?php

namespace App\Models;

use App\Models\Concerns\InvalidaCacheConsultas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro histórico de un cambio de estado de una orden.
 *
 * Cada fila responde a: qué estado se alcanzó, cuándo, quién lo registró y
 * con qué observación. El historial se muestra en la línea de tiempo del
 * detalle de la orden.
 */
class SeguimientoEstado extends Model
{
    use HasFactory;
    use InvalidaCacheConsultas;

    protected $table = 'seguimiento_estados';

    /** @var list<string> */
    protected $fillable = [
        'orden_trabajo_id',
        'user_id',
        'estado',
        'fecha',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
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

    public function estadoNombre(): string
    {
        return OrdenTrabajo::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }
}
