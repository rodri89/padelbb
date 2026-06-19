<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NOTA: Esta migración fue reemplazada por 2026_06_08_000003_extend_stock_caja_aperturas.
        // Se mantiene el archivo vacío por compatibilidad con entornos donde ya fue ejecutada.
        // En nuevas instalaciones no hace nada; la tabla se unifica en stock_caja_aperturas.
        if (!Schema::hasTable('stock_caja_diaria')) {
            Schema::create('stock_caja_diaria', function (Blueprint $table) {
                $table->id();
                $table->date('fecha')->unique();
                $table->decimal('fondo_inicial', 10, 2)->default(0);
                $table->decimal('fondo_final', 10, 2)->nullable();
                $table->decimal('efectivo_real', 10, 2)->nullable();
                $table->decimal('diferencia', 10, 2)->nullable();
                $table->string('estado', 20)->default('abierta');
                $table->text('observaciones')->nullable();
                $table->string('usuario_apertura', 100)->nullable();
                $table->string('usuario_cierre', 100)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_caja_diaria');
    }
};
