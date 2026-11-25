<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_contacto', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('telefono', 30);
            $table->string('email', 150)->nullable();
            $table->string('servicio', 100)->nullable();
            $table->text('mensaje');
            $table->enum('estado', ['nueva', 'atendida', 'descartada'])->default('nueva');
            $table->enum('origen', ['sitio', 'sistema'])->default('sitio');
            $table->foreignId('atendida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('atendida_en')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            // Bandeja de recepción: filtra por estado y ordena por fecha.
            $table->index(['estado', 'created_at']);
            $table->index('email');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_contacto');
    }
};
