<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfiguracionCrucesPuntuablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configuracion_cruces_puntuables', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('torneo_id')->nullable(); // Si es null, es configuración global
            $table->integer('cantidad_parejas');
            $table->boolean('tiene_16avos_final')->default(false);
            $table->boolean('tiene_8vos_final')->default(false);
            $table->boolean('tiene_4tos_final')->default(false);
            $table->integer('clasifican_zona_3')->default(1); // Cuántos clasifican de zona de 3 parejas
            $table->integer('clasifican_zona_4')->default(2); // Cuántos clasifican de zona de 4 parejas
            $table->text('llave_16avos')->nullable(); // JSON con la configuración de llaves
            $table->text('llave_8vos')->nullable(); // JSON con la configuración de llaves
            $table->text('llave_4tos')->nullable(); // JSON con la configuración de llaves
            $table->text('llave_semifinal')->nullable(); // JSON con la configuración de llaves
            $table->text('llave_final')->nullable(); // JSON con la configuración de llaves
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
        Schema::dropIfExists('configuracion_cruces_puntuables');
    }
}

