<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_trabajo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('paciente', 120)->nullable();
            $table->string('tipo_trabajo', 100);
            $table->enum('estado', [
                'recibido',
                'diseno',
                'fresado',
                'horno_acabado',
                'control',
                'listo',
                'entregado',
                'anulado',
            ])->default('recibido');
            $table->date('fecha_ingreso');
            $table->date('fecha_entrega')->nullable();
            $table->decimal('precio', 10, 2)->default(0);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('fecha_entrega');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_trabajo');
    }
};
