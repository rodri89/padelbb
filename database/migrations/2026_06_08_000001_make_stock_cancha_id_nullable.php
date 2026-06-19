<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_ventas', function (Blueprint $table) {
            // Solo hacemos nullable si la columna existe y no es nullable ya
            $column = DB::selectOne("SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'stock_ventas' AND COLUMN_NAME = 'stock_cancha_id' AND TABLE_SCHEMA = DATABASE()");
            if ($column && $column->IS_NULLABLE === 'NO') {
                $table->foreignId('stock_cancha_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_ventas', function (Blueprint $table) {
            $table->foreignId('stock_cancha_id')->nullable(false)->change();
        });
    }
};
