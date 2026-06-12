<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRankingPuntosTable extends Migration
{
    /**
     * Detalle: puntos que sumó cada jugador en cada torneo puntuable finalizado.
     */
    public function up()
    {
        Schema::create('ranking_puntos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('jugador_id');
            $table->unsignedInteger('torneo_id');
            $table->unsignedTinyInteger('categoria')->comment('Categoría del torneo (ej. 6 = 6ta)');
            $table->unsignedInteger('puntos')->comment('Puntos obtenidos en este torneo');
            $table->string('referencia_codigo', 32)->comment('campeon, subcampeon, semi, cuartos, octavos, 16avos, no_clasificados');
            $table->unsignedSmallInteger('temporada')->comment('Año del torneo (ej. 2026)');
            $table->timestamps();

            $table->foreign('jugador_id')->references('id')->on('jugadores')->onDelete('cascade');
            $table->foreign('torneo_id')->references('id')->on('torneos')->onDelete('cascade');
            $table->unique(['jugador_id', 'torneo_id'], 'ranking_puntos_jugador_torneo_unique');
            $table->index(['categoria', 'temporada']);
            $table->index(['jugador_id', 'categoria', 'temporada']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ranking_puntos');
    }
}
