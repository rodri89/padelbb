<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_ventas', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_ventas', 'stock_venta_id_padre')) {
                $table->unsignedBigInteger('stock_venta_id_padre')->nullable()->after('id');
                $table->foreign('stock_venta_id_padre')->references('id')->on('stock_ventas')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_ventas', function (Blueprint $table) {
            if (Schema::hasColumn('stock_ventas', 'stock_venta_id_padre')) {
                $table->dropForeign(['stock_venta_id_padre']);
                $table->dropColumn('stock_venta_id_padre');
            }
        });
    }
};
