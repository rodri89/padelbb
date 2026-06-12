<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePuntosRankingReferenciaTable extends Migration
{
    /**
     * Tabla de referencia: cuántos puntos se otorgan por cada posición/ronda en un torneo puntuable.
     * Campeón 100, Sub 75, 3ro/4to 50, Cuartos 25, Octavos 15, 16avos 10, No clasificados 5.
     */
    public function up()
    {
        Schema::create('puntos_ranking_referencia', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo', 32)->unique()->comment('campeon, subcampeon, semi, cuartos, octavos, 16avos, no_clasificados');
            $table->string('nombre', 64)->comment('Nombre para mostrar');
            $table->unsignedInteger('puntos')->comment('Puntos que suman');
            $table->unsignedTinyInteger('orden')->default(0)->comment('Orden para listar (1=mayor puntaje)');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('puntos_ranking_referencia');
    }
}
