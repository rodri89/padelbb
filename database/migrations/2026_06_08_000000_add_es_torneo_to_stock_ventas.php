<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_ventas', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_ventas', 'es_torneo')) {
                $table->boolean('es_torneo')->default(false)->after('stock_venta_id_padre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_ventas', function (Blueprint $table) {
            if (Schema::hasColumn('stock_ventas', 'es_torneo')) {
                $table->dropColumn('es_torneo');
            }
        });
    }
};
