<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGruposTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('torneo_id');            
            $table->date('fecha');
            $table->string('horario');
            $table->string('zona');
            $table->integer('pareja_1_jugador_1');
            $table->integer('pareja_1_jugador_2');
            $table->integer('pareja_2_jugador_1');
            $table->integer('pareja_2_jugador_2');            
            $table->integer('partido_id');            
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
        Schema::dropIfExists('grupos');
    }
}
