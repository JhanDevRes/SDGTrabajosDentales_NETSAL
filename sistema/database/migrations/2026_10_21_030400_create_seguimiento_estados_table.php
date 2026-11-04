<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguimiento_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', [
                'recibido',
                'diseno',
                'fresado',
                'horno_acabado',
                'control',
                'listo',
                'entregado',
                'anulado',
            ]);
            $table->dateTime('fecha');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['orden_trabajo_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimiento_estados');
    }
};
