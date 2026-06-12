<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddJugadorIdsToCalendarioInscripcionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('calendario_inscripciones', function (Blueprint $table) {
            $table->unsignedInteger('jugador1_id')->nullable()->after('calendario_id');
            $table->unsignedInteger('jugador2_id')->nullable()->after('jugador1_id');

            $table->index('jugador1_id');
            $table->index('jugador2_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('calendario_inscripciones', function (Blueprint $table) {
            $table->dropIndex(['jugador1_id']);
            $table->dropIndex(['jugador2_id']);
            $table->dropColumn(['jugador1_id', 'jugador2_id']);
        });
    }
}

