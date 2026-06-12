<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_caja_aperturas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->decimal('monto_efectivo_inicial', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_caja_aperturas');
    }
};
