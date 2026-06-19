<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_detalles_venta', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_detalles_venta', 'es_division')) {
                $table->boolean('es_division')->default(false)->after('stock_venta_participante_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_detalles_venta', function (Blueprint $table) {
            if (Schema::hasColumn('stock_detalles_venta', 'es_division')) {
                $table->dropColumn('es_division');
            }
        });
    }
};
