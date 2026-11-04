<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices estratégicos para las consultas más frecuentes del sistema.
 *
 * Cada índice responde a una consulta real del panel; el nombre del índice
 * documenta la consulta que beneficia:
 *
 *  - clientes(activo, nombre)         -> listado de clientes activos ordenado por nombre.
 *  - ordenes(estado, fecha_entrega)   -> tablero de pendientes y reporte de atrasadas.
 *  - ordenes(cliente_id, estado)      -> historial de un cliente y aislamiento por rol.
 *  - ordenes(fecha_ingreso)           -> listado general ordenado por ingreso.
 *  - ordenes(estado, updated_at)      -> conteo de entregados en el mes.
 *  - materiales(activo, nombre)       -> inventario activo ordenado por nombre.
 *  - users(rol, activo)               -> selector de responsables y usuarios del portal.
 *  - entregas(confirmado, fecha)      -> filtro de entregas pendientes/confirmadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->index(['activo', 'nombre'], 'clientes_activo_nombre_index');
        });

        Schema::table('ordenes_trabajo', function (Blueprint $table) {
            $table->index(['estado', 'fecha_entrega'], 'ordenes_estado_fecha_entrega_index');
            $table->index(['cliente_id', 'estado'], 'ordenes_cliente_estado_index');
            $table->index('fecha_ingreso', 'ordenes_fecha_ingreso_index');
            $table->index(['estado', 'updated_at'], 'ordenes_estado_updated_index');
        });

        Schema::table('materiales', function (Blueprint $table) {
            $table->index(['activo', 'nombre'], 'materiales_activo_nombre_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['rol', 'activo'], 'users_rol_activo_index');
        });

        Schema::table('entregas', function (Blueprint $table) {
            $table->index(['confirmado', 'fecha'], 'entregas_confirmado_fecha_index');
        });
    }

    public function down(): void
    {
        Schema::table('entregas', function (Blueprint $table) {
            $table->dropIndex('entregas_confirmado_fecha_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_rol_activo_index');
        });

        Schema::table('materiales', function (Blueprint $table) {
            $table->dropIndex('materiales_activo_nombre_index');
        });

        Schema::table('ordenes_trabajo', function (Blueprint $table) {
            $table->dropIndex('ordenes_estado_fecha_entrega_index');
            $table->dropIndex('ordenes_cliente_estado_index');
            $table->dropIndex('ordenes_fecha_ingreso_index');
            $table->dropIndex('ordenes_estado_updated_index');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex('clientes_activo_nombre_index');
        });
    }
};
