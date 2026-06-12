<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_detalles_venta', function (Blueprint $table) {
            $table->string('estado_pago', 20)->default('pendiente')->after('es_division');
        });

        Schema::table('stock_historial_pagos', function (Blueprint $table) {
            $table->foreignId('stock_detalle_venta_id')->nullable()->after('stock_venta_participante_id')
                ->constrained('stock_detalles_venta')->nullOnDelete();
        });

        DB::statement("
            UPDATE stock_detalles_venta d
            INNER JOIN stock_ventas v ON d.stock_venta_id = v.id
            SET d.estado_pago = 'pagado'
            WHERE v.estado_pago = 'pagado'
        ");
    }

    public function down(): void
    {
        Schema::table('stock_historial_pagos', function (Blueprint $table) {
            $table->dropForeign(['stock_detalle_venta_id']);
            $table->dropColumn('stock_detalle_venta_id');
        });

        Schema::table('stock_detalles_venta', function (Blueprint $table) {
            $table->dropColumn('estado_pago');
        });
    }
};
