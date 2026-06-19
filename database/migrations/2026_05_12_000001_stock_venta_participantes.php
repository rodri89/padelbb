<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stock_venta_participantes')) {
            Schema::create('stock_venta_participantes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_venta_id')->constrained('stock_ventas')->cascadeOnDelete();
                $table->unsignedTinyInteger('slot')->comment('1-4');
                $table->string('nombre', 100);
                $table->unsignedInteger('jugador_id')->nullable();
                $table->string('estado_pago', 20)->default('pendiente'); // pendiente | pagado
                $table->string('metodo_pago', 20)->nullable();
                $table->date('fecha_pago')->nullable();
                $table->timestamps();

                $table->unique(['stock_venta_id', 'slot']);
                $table->foreign('jugador_id')->references('id')->on('jugadores')->nullOnDelete();
            });
        }

        Schema::table('stock_detalles_venta', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_detalles_venta', 'stock_venta_participante_id')) {
                $table->foreignId('stock_venta_participante_id')->nullable()->after('stock_venta_id')->constrained('stock_venta_participantes')->nullOnDelete();
            }
        });

        Schema::table('stock_historial_pagos', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_historial_pagos', 'stock_venta_participante_id')) {
                $table->foreignId('stock_venta_participante_id')->nullable()->after('stock_venta_id')->constrained('stock_venta_participantes')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_historial_pagos', function (Blueprint $table) {
            if (Schema::hasColumn('stock_historial_pagos', 'stock_venta_participante_id')) {
                $table->dropForeign(['stock_venta_participante_id']);
                $table->dropColumn('stock_venta_participante_id');
            }
        });

        Schema::table('stock_detalles_venta', function (Blueprint $table) {
            if (Schema::hasColumn('stock_detalles_venta', 'stock_venta_participante_id')) {
                $table->dropForeign(['stock_venta_participante_id']);
                $table->dropColumn('stock_venta_participante_id');
            }
        });

        Schema::dropIfExists('stock_venta_participantes');
    }
};
