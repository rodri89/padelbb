<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFechasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fechas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('torneo_id');
            $table->integer('numero');
            $table->integer('partido_numero');            
            $table->integer('jugador_id_1');
            $table->integer('jugador_id_2');
            $table->integer('jugador_id_3');
            $table->integer('jugador_id_4');            
            $table->integer('es_torneo_individual');
            $table->integer('resultado_set_1');
            $table->integer('resultado_set_2');
            $table->integer('resultado_set_3'); 
            $table->integer('resultado_games_jugador_1');
            $table->integer('resultado_games_jugador_2');
            $table->integer('resultado_games_jugador_3');
            $table->integer('resultado_games_jugador_4');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fechas');
    }
}
