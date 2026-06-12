<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCalendarioInscripcionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calendario_inscripciones', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('calendario_id');
            $table->string('jugador1_nombre', 120);
            $table->string('jugador1_apellido', 120);
            $table->string('jugador1_telefono', 40);
            $table->string('jugador2_nombre', 120);
            $table->string('jugador2_apellido', 120);
            $table->string('jugador2_telefono', 40)->nullable();
            $table->text('disponibilidad_horaria');
            $table->timestamps();

            $table->foreign('calendario_id')
                ->references('id')
                ->on('calendario')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('calendario_inscripciones');
    }
}
