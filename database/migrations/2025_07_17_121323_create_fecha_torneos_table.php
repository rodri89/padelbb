<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFechaTorneosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fecha_torneos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('torneo_id');            
            $table->date('fecha');
            $table->string('horario');
            $table->string('zona');
            $table->integer('pareja_1_jugador_1');
            $table->integer('pareja_1_jugador_2');
            $table->integer('pareja_2_jugador_1');
            $table->integer('pareja_2_jugador_2');            
            $table->integer('pareja_1_set_1');
            $table->integer('pareja_1_set_1_tie_break');
            $table->integer('pareja_2_set_1');
            $table->integer('pareja_2_set_1_tie_break');
            $table->integer('pareja_1_set_2');
            $table->integer('pareja_1_set_2_tie_break');
            $table->integer('pareja_2_set_2');
            $table->integer('pareja_2_set_2_tie_break');
            $table->integer('pareja_1_set_3');
            $table->integer('pareja_1_set_3_tie_break');
            $table->integer('pareja_2_set_3');
            $table->integer('pareja_2_set_3_tie_break');
            $table->integer('pareja_1_set_super_tie_break');            
            $table->integer('pareja_2_set_super_tie_break');                  
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
        Schema::dropIfExists('fecha_torneos');
    }
}
