<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumo_materiales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materiales')->restrictOnDelete();
            $table->decimal('cantidad', 10, 2);
            $table->decimal('costo_unitario', 10, 2)->default(0);
            $table->timestamps();

            $table->index('orden_trabajo_id');
            $table->index('material_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumo_materiales');
    }
};
