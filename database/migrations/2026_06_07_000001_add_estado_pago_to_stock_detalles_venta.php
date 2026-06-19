<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_detalles_venta', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_detalles_venta', 'estado_pago')) {
                $table->string('estado_pago', 20)->default('pendiente')->after('subtotal');
            }
            if (!Schema::hasColumn('stock_detalles_venta', 'stock_historial_pago_id')) {
                $table->foreignId('stock_historial_pago_id')->nullable()->after('estado_pago')->constrained('stock_historial_pagos')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_detalles_venta', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_detalles_venta', function (Blueprint $table) {
            if (Schema::hasColumn('stock_detalles_venta', 'stock_historial_pago_id')) {
                $table->dropForeign(['stock_historial_pago_id']);
                $table->dropColumn('stock_historial_pago_id');
            }
            if (Schema::hasColumn('stock_detalles_venta', 'estado_pago')) {
                $table->dropColumn('estado_pago');
            }
            if (Schema::hasColumn('stock_detalles_venta', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
