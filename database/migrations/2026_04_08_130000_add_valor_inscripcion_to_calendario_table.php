<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddValorInscripcionToCalendarioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('calendario', function (Blueprint $table) {
            $table->decimal('valor_inscripcion', 12, 2)->nullable()->after('premio_4');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('calendario', function (Blueprint $table) {
            $table->dropColumn('valor_inscripcion');
        });
    }
}
