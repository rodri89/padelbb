<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_historial_pagos', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_historial_pagos', 'stock_detalle_venta_id')) {
                $table->unsignedBigInteger('stock_detalle_venta_id')->nullable()->after('stock_venta_participante_id');
                $table->foreign('stock_detalle_venta_id')->references('id')->on('stock_detalles_venta')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_historial_pagos', function (Blueprint $table) {
            if (Schema::hasColumn('stock_historial_pagos', 'stock_detalle_venta_id')) {
                $table->dropForeign(['stock_detalle_venta_id']);
                $table->dropColumn('stock_detalle_venta_id');
            }
        });
    }
};
