<?php

namespace App\Models;

use App\Models\Concerns\InvalidaCacheConsultas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Orden de trabajo del laboratorio dental.
 *
 * Una orden recorre los estados definidos en ESTADOS. El historial de cada
 * cambio se guarda en seguimiento_estados y el costo real se obtiene de los
 * materiales consumidos (consumo_materiales).
 *
 * Optimización: los scopes de este modelo encapsulan los filtros más usados
 * (pendientes, atrasadas, búsqueda y costo por orden) para que los
 * controladores no repitan SQL y para que las consultas sean consistentes.
 */
class OrdenTrabajo extends Model
{
    use HasFactory;
    use InvalidaCacheConsultas;

    /** Estados posibles y su etiqueta para la interfaz. */
    public const ESTADOS = [
        'recibido' => 'Recibido',
        'diseno' => 'Diseño',
        'fresado' => 'Fresado',
        'horno_acabado' => 'Horno / Acabado',
        'control' => 'Control de calidad',
        'listo' => 'Listo',
        'entregado' => 'Entregado',
        'anulado' => 'Anulado',
    ];

    /** Estados que cierran la orden (no admiten más movimientos). */
    public const ESTADOS_CERRADOS = ['entregado', 'anulado'];

    /** Estados de producción abiertos, usados por el scope pendientes(). */
    public const ESTADOS_ABIERTOS = [
        'recibido',
        'diseno',
        'fresado',
        'horno_acabado',
        'control',
        'listo',
    ];

    /**
     * Avance aproximado del trabajo según el estado, en porcentaje.
     * Se usa en el seguimiento público para dibujar la barra de progreso
     * sin exponer la lógica interna del laboratorio.
     */
    public const AVANCE_POR_ESTADO = [
        'recibido' => 10,
        'diseno' => 30,
        'fresado' => 50,
        'horno_acabado' => 65,
        'control' => 80,
        'listo' => 95,
        'entregado' => 100,
        'anulado' => 0,
    ];

    protected $table = 'ordenes_trabajo';

    /** @var list<string> */
    protected $fillable = [
        'codigo',
        'cliente_id',
        'user_id',
        'paciente',
        'tipo_trabajo',
        'estado',
        'fecha_ingreso',
        'fecha_entrega',
        'precio',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'fecha_entrega' => 'date',
            'precio' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // El código se genera automáticamente al crear la orden.
        static::creating(function (OrdenTrabajo $orden): void {
            if (blank($orden->codigo)) {
                $orden->codigo = static::generarCodigo();
            }
        });
    }

    /**
     * Genera un código correlativo diario (ORD-AAAAMMDD-###).
     * La consulta usa el índice único de "codigo" mediante el prefijo.
     */
    public static function generarCodigo(): string
    {
        $prefijo = 'ORD-'.now()->format('Ymd').'-';
        $ultimo = static::where('codigo', 'like', $prefijo.'%')->count();

        return $prefijo.str_pad((string) ($ultimo + 1), 3, '0', STR_PAD_LEFT);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(SeguimientoEstado::class)->orderByDesc('fecha');
    }

    public function consumos(): HasMany
    {
        return $this->hasMany(ConsumoMaterial::class);
    }

    public function entrega(): HasOne
    {
        return $this->hasOne(Entrega::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes de consulta
    |--------------------------------------------------------------------------
    */

    /** Órdenes abiertas (aún en producción). Usa IN sobre el índice de estado. */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->whereIn('estado', self::ESTADOS_ABIERTOS);
    }

    /**
     * Órdenes abiertas con fecha de entrega vencida.
     * Se compara la columna DATE directamente en lugar de usar whereDate(),
     * porque DATE(fecha_entrega) anularía el uso del índice.
     */
    public function scopeAtrasadas(Builder $query): Builder
    {
        return $query->pendientes()
            ->whereNotNull('fecha_entrega')
            ->where('fecha_entrega', '<', now()->toDateString());
    }

    /** Filtra por cliente(s); acepta un id o un arreglo de ids. */
    public function scopeDelCliente(Builder $query, int|array $clienteIds): Builder
    {
        return $query->whereIn('cliente_id', (array) $clienteIds);
    }

    /**
     * Aísla las órdenes que pertenecen a un odontólogo del portal.
     * Se resuelve con una subconsulta para no ejecutar un SELECT previo
     * (evita una ida y vuelta adicional a la base de datos).
     */
    public function scopeParaUsuarioPortal(Builder $query, int $userId): Builder
    {
        return $query->whereIn('cliente_id', function ($sub) use ($userId): void {
            $sub->select('id')->from('clientes')->where('user_id', $userId);
        });
    }

    /** Filtra por estado concreto. */
    public function scopeEnEstado(Builder $query, string $estado): Builder
    {
        return $query->where('estado', $estado);
    }

    /**
     * Búsqueda de texto del listado de órdenes.
     *
     * El código se compara por prefijo (aprovecha el índice único). Los demás
     * campos usan LIKE con comodín al inicio: ese patrón no puede usar índice,
     * por eso el listado siempre va paginado y con columnas acotadas.
     */
    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        return $query->where(function (Builder $sub) use ($termino): void {
            $sub->where('codigo', 'like', $termino.'%')
                ->orWhere('paciente', 'like', "%{$termino}%")
                ->orWhere('tipo_trabajo', 'like', "%{$termino}%")
                ->orWhereHas('cliente', fn (Builder $cliente) => $cliente->where('nombre', 'like', "%{$termino}%"));
        });
    }

    /**
     * Agrega la columna calculada "costo_materiales" mediante una subconsulta
     * correlacionada. Evita cargar todos los consumos en memoria solo para
     * sumarlos y sustituye al patrón N+1 (una consulta por orden).
     */
    public function scopeConCostoMateriales(Builder $query): Builder
    {
        $subconsulta = ConsumoMaterial::query()
            ->selectRaw('COALESCE(SUM(cantidad * costo_unitario), 0)')
            ->whereColumn('orden_trabajo_id', 'ordenes_trabajo.id');

        return $query->addSelect(['costo_materiales' => $subconsulta]);
    }

    /*
    |--------------------------------------------------------------------------
    | Reglas de negocio
    |--------------------------------------------------------------------------
    */

    public function estaCerrada(): bool
    {
        return in_array($this->estado, self::ESTADOS_CERRADOS, true);
    }

    public function estaAtrasada(): bool
    {
        return ! $this->estaCerrada()
            && $this->fecha_entrega !== null
            && $this->fecha_entrega->isPast()
            && ! $this->fecha_entrega->isToday();
    }

    public function estadoNombre(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    /** Avance del trabajo en porcentaje, según el estado actual. */
    public function avance(): int
    {
        return self::AVANCE_POR_ESTADO[$this->estado] ?? 0;
    }

    /** Clase CSS del distintivo de estado. */
    public static function badgeClass(string $estado): string
    {
        return 'estado-'.(array_key_exists($estado, self::ESTADOS) ? $estado : 'recibido');
    }

    /**
     * Costo real de los materiales de la orden.
     * Si la relación ya fue cargada (eager loading) se calcula en memoria;
     * en caso contrario se resuelve con una única agregación en la base.
     */
    public function costoMateriales(): float
    {
        if ($this->relationLoaded('consumos')) {
            return (float) $this->consumos->sum(
                fn (ConsumoMaterial $consumo) => (float) $consumo->cantidad * (float) $consumo->costo_unitario
            );
        }

        $fila = $this->consumos()
            ->selectRaw('COALESCE(SUM(cantidad * costo_unitario), 0) AS total')
            ->first();

        return (float) ($fila->total ?? 0);
    }

    /** Margen estimado = precio del trabajo - costo de materiales. */
    public function margen(): float
    {
        return (float) $this->precio - $this->costoMateriales();
    }
}
