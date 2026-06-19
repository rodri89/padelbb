<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockTurnosFijosTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('stock_turnos_fijos')) {
            Schema::create('stock_turnos_fijos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('stock_cancha_id');
                $table->tinyInteger('dia_semana'); // 1=lunes, 7=domingo
                $table->time('hora');
                $table->string('nombre_grupo', 100);
                $table->boolean('activo')->default(true);
                $table->timestamps();

                $table->foreign('stock_cancha_id')
                    ->references('id')
                    ->on('stock_canchas')
                    ->onDelete('cascade');

                $table->unique(['stock_cancha_id', 'dia_semana', 'hora'], 'turno_fijo_unico');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('stock_turnos_fijos');
    }
}
