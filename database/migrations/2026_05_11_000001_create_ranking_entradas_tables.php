<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRankingEntradasTables extends Migration
{
    /**
     * Tablas para entradas manuales de ranking (períodos sin torneo cargado en el sistema).
     *
     * ranking_entradas      → cabecera del período (mes, categoría, temporada, tipo)
     * ranking_entradas_jugadores → detalle de puntos por jugador en esa entrada
     */
    public function up()
    {
        if (!Schema::hasTable('ranking_entradas')) {
            Schema::create('ranking_entradas', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nombre', 128)->comment('Nombre descriptivo, ej: Torneo Enero 2026');
                $table->string('tipo', 16)->default('masculino')->comment('masculino, femenino, mixto');
                $table->unsignedTinyInteger('categoria')->comment('Categoría, ej: 6 = 6ta');
                $table->unsignedSmallInteger('temporada')->comment('Año, ej: 2026');
                $table->unsignedTinyInteger('mes')->comment('Mes 1-12');
                $table->text('descripcion')->nullable()->comment('Descripción opcional');
                $table->timestamps();

                $table->index(['tipo', 'categoria', 'temporada']);
            });
        }

        if (!Schema::hasTable('ranking_entradas_jugadores')) {
            Schema::create('ranking_entradas_jugadores', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('entrada_id');
                $table->unsignedInteger('jugador_id');
                $table->unsignedInteger('puntos')->default(0);
                $table->string('referencia_codigo', 32)->default('no_clasificados')
                    ->comment('campeon, subcampeon, tercero_cuarto, cuartos, octavos, 16avos, no_clasificados');
                $table->timestamps();

                $table->foreign('entrada_id')->references('id')->on('ranking_entradas')->onDelete('cascade');
                $table->foreign('jugador_id')->references('id')->on('jugadores')->onDelete('cascade');
                $table->unique(['entrada_id', 'jugador_id'], 'rej_entrada_jugador_unique');
                $table->index('jugador_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('ranking_entradas_jugadores');
        Schema::dropIfExists('ranking_entradas');
    }
}
