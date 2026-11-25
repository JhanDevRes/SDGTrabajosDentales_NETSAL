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

    /**
     * Comprueba si el contacto indicado pertenece a este cliente.
     *
     * Se usa en la consulta pública del estado de una orden: además del código
     * (que es correlativo y por tanto adivinable) se exige el correo o el
     * teléfono con el que el cliente está registrado, para que nadie pueda
     * recorrer los códigos y ver trabajos ajenos.
     *
     * La comparación del teléfono se hace sobre los últimos nueve dígitos para
     * que "+51 984 111 222", "984111222" y "984 111 222" se consideren iguales.
     */
    public function coincideConContacto(string $contacto): bool
    {
        $contacto = trim($contacto);

        if ($contacto === '') {
            return false;
        }

        if (str_contains($contacto, '@')) {
            return $this->email !== null
                && mb_strtolower($this->email) === mb_strtolower($contacto);
        }

        $ingresado = self::soloDigitos($contacto);
        $registrado = self::soloDigitos((string) $this->telefono);

        return strlen($ingresado) >= 6
            && strlen($registrado) >= 6
            && substr($ingresado, -9) === substr($registrado, -9);
    }

    /** Quita espacios, guiones, paréntesis y el prefijo + de un teléfono. */
    private static function soloDigitos(string $valor): string
    {
        return preg_replace('/\D+/', '', $valor) ?? '';
    }
}
