<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRankingTotalesTable extends Migration
{
    /**
     * Resumen: puntos totales por jugador, categoría y temporada (para consultar ranking rápido).
     */
    public function up()
    {
        Schema::create('ranking_totales', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('jugador_id');
            $table->unsignedTinyInteger('categoria');
            $table->unsignedSmallInteger('temporada');
            $table->unsignedInteger('puntos_totales')->default(0);
            $table->timestamps();

            $table->foreign('jugador_id')->references('id')->on('jugadores')->onDelete('cascade');
            $table->unique(['jugador_id', 'categoria', 'temporada'], 'ranking_totales_jugador_cat_temp_unique');
            $table->index(['categoria', 'temporada']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ranking_totales');
    }
}
