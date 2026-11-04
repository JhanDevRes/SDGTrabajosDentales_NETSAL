<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_trabajo_id')->unique()->constrained('ordenes_trabajo')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('tipo', ['recojo', 'envio'])->default('recojo');
            $table->date('fecha');
            $table->string('receptor', 150)->nullable();
            $table->boolean('confirmado')->default(false);
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entregas');
    }
};
