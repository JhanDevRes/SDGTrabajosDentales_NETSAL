<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Material;
use App\Models\OrdenTrabajo;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@netsal.test'],
            ['name' => 'Administrador NETSAL', 'password' => 'password', 'rol' => 'administrador', 'activo' => true]
        );

        $recepcion = User::updateOrCreate(
            ['email' => 'recepcion@netsal.test'],
            ['name' => 'Rosa Quispe (Recepción)', 'password' => 'password', 'rol' => 'recepcion', 'activo' => true]
        );

        $disenador = User::updateOrCreate(
            ['email' => 'diseno@netsal.test'],
            ['name' => 'Luis Mamani (Diseño)', 'password' => 'password', 'rol' => 'disenador', 'activo' => true]
        );

        $tecnico = User::updateOrCreate(
            ['email' => 'tecnico@netsal.test'],
            ['name' => 'Ana Ccahuana (Fresado)', 'password' => 'password', 'rol' => 'tecnico', 'activo' => true]
        );

        $odontologo = User::updateOrCreate(
            ['email' => 'odontologo@netsal.test'],
            ['name' => 'Dr. Carlos Quispe', 'password' => 'password', 'rol' => 'odontologo', 'activo' => true]
        );

        $clienteUno = Cliente::updateOrCreate(
            ['nombre' => 'Dr. Carlos Quispe'],
            [
                'tipo' => 'odontologo',
                'telefono' => '984 111 222',
                'email' => 'odontologo@netsal.test',
                'direccion' => 'Av. El Sol 345, Cusco',
                'user_id' => $odontologo->id,
                'activo' => true,
            ]
        );

        $clienteDos = Cliente::updateOrCreate(
            ['nombre' => 'Clínica Dental Sonrisa Andina'],
            [
                'tipo' => 'clinica',
                'telefono' => '984 333 444',
                'email' => 'contacto@sonrisaandina.pe',
                'direccion' => 'Calle San Bernardo 120, Cusco',
                'activo' => true,
            ]
        );

        $clienteTres = Cliente::updateOrCreate(
            ['nombre' => 'Dra. María Huamán'],
            [
                'tipo' => 'odontologo',
                'telefono' => '984 555 666',
                'email' => 'maria.huaman@correo.pe',
                'direccion' => 'Av. de la Cultura 1500, Cusco',
                'activo' => true,
            ]
        );

        $materiales = [
            'Zirconio en bloque' => ['unidad' => 'unidad', 'costo' => 85.00, 'stock' => 40, 'stock_minimo' => 10],
            'Porcelana de recubrimiento' => ['unidad' => 'gramo', 'costo' => 12.50, 'stock' => 500, 'stock_minimo' => 100],
            'Yeso dental' => ['unidad' => 'kilogramo', 'costo' => 8.00, 'stock' => 25, 'stock_minimo' => 10],
            'Cera para modelado' => ['unidad' => 'gramo', 'costo' => 0.80, 'stock' => 180, 'stock_minimo' => 200],
            'Resina acrílica' => ['unidad' => 'mililitro', 'costo' => 0.45, 'stock' => 1200, 'stock_minimo' => 300],
            'Discos de pulido' => ['unidad' => 'unidad', 'costo' => 3.50, 'stock' => 15, 'stock_minimo' => 20],
        ];

        foreach ($materiales as $nombre => $datos) {
            Material::updateOrCreate(['nombre' => $nombre], $datos + ['activo' => true]);
        }

        if (OrdenTrabajo::query()->exists()) {
            return;
        }

        $this->crearOrden([
            'cliente_id' => $clienteUno->id,
            'user_id' => $tecnico->id,
            'paciente' => 'Juan Pérez',
            'tipo_trabajo' => 'Corona de zirconio',
            'fecha_ingreso' => now()->subDays(6)->toDateString(),
            'fecha_entrega' => now()->addDays(2)->toDateString(),
            'precio' => 450.00,
            'notas' => 'Paciente con tratamiento de conducto previo.',
        ], ['recibido', 'diseno', 'fresado', 'horno_acabado', 'control', 'listo'], [
            ['Zirconio en bloque', 1],
            ['Porcelana de recubrimiento', 12],
            ['Discos de pulido', 2],
        ]);

        $this->crearOrden([
            'cliente_id' => $clienteDos->id,
            'user_id' => $disenador->id,
            'paciente' => 'Rosa Ttito',
            'tipo_trabajo' => 'Prótesis parcial removible',
            'fecha_ingreso' => now()->subDays(10)->toDateString(),
            'fecha_entrega' => now()->subDay()->toDateString(),
            'precio' => 380.00,
            'notas' => 'Requiere ajuste de color.',
        ], ['recibido', 'diseno', 'fresado', 'horno_acabado'], [
            ['Resina acrílica', 80],
            ['Yeso dental', 2],
        ]);

        $ordenEntregada = $this->crearOrden([
            'cliente_id' => $clienteTres->id,
            'user_id' => $recepcion->id,
            'paciente' => 'Miguel Cárdenas',
            'tipo_trabajo' => 'Corona de porcelana',
            'fecha_ingreso' => now()->subDays(15)->toDateString(),
            'fecha_entrega' => now()->subDays(3)->toDateString(),
            'precio' => 320.00,
            'notas' => null,
        ], ['recibido', 'diseno', 'fresado', 'horno_acabado', 'control', 'listo', 'entregado'], [
            ['Porcelana de recubrimiento', 10],
            ['Yeso dental', 1],
        ]);

        $ordenEntregada->entrega()->create([
            'user_id' => $recepcion->id,
            'tipo' => 'recojo',
            'fecha' => now()->subDays(3)->toDateString(),
            'receptor' => 'Dra. María Huamán',
            'confirmado' => true,
            'observacion' => 'Recojo conforme en laboratorio.',
        ]);

        $ordenRecibida = $this->crearOrden([
            'cliente_id' => $clienteUno->id,
            'user_id' => $recepcion->id,
            'paciente' => 'Lucía Álvarez',
            'tipo_trabajo' => 'Carilla de porcelana',
            'fecha_ingreso' => now()->toDateString(),
            'fecha_entrega' => now()->addDays(7)->toDateString(),
            'precio' => 520.00,
            'notas' => 'Trabajo estético, revisar tonalidad.',
        ], ['recibido']);

        $ordenRecibida->entrega()->create([
            'user_id' => $recepcion->id,
            'tipo' => 'envio',
            'fecha' => now()->addDays(7)->toDateString(),
            'receptor' => 'Dr. Carlos Quispe',
            'confirmado' => false,
            'observacion' => 'Entrega programada al consultorio.',
        ]);

        $this->crearOrden([
            'cliente_id' => $clienteDos->id,
            'user_id' => $admin->id,
            'paciente' => 'Pedro Sallo',
            'tipo_trabajo' => 'Reparación de prótesis',
            'fecha_ingreso' => now()->subDays(5)->toDateString(),
            'fecha_entrega' => now()->subDay()->toDateString(),
            'precio' => 90.00,
            'notas' => 'Orden anulada por duplicidad de registro.',
        ], ['recibido', 'anulado']);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  list<string>  $estados
     * @param  list<array{0: string, 1: float|int}>  $consumos
     */
    private function crearOrden(array $datos, array $estados, array $consumos = []): OrdenTrabajo
    {
        $orden = OrdenTrabajo::create($datos + ['estado' => $estados[0] ?? 'recibido']);

        $fecha = $orden->fecha_ingreso->copy()->setTime(8, 0);

        foreach ($estados as $estado) {
            $orden->seguimientos()->create([
                'user_id' => $datos['user_id'] ?? null,
                'estado' => $estado,
                'fecha' => $fecha,
                'observacion' => 'Avance registrado: '.OrdenTrabajo::ESTADOS[$estado].'.',
            ]);

            $fecha = $fecha->copy()->addDay();
        }

        $estadoFinal = $estados[count($estados) - 1] ?? 'recibido';

        if ($orden->estado !== $estadoFinal) {
            $orden->update(['estado' => $estadoFinal]);
        }

        foreach ($consumos as [$nombre, $cantidad]) {
            $material = Material::where('nombre', $nombre)->first();

            if ($material === null) {
                continue;
            }

            $orden->consumos()->create([
                'material_id' => $material->id,
                'cantidad' => $cantidad,
                'costo_unitario' => $material->costo,
            ]);

            $material->decrement('stock', $cantidad);
        }

        return $orden;
    }
}
