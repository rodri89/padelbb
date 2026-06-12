<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfiguracionCrucesAmericanosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configuracion_cruces_americanos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre')->nullable(); // Nombre descriptivo de la configuración
            $table->integer('cantidad_parejas');
            $table->integer('cantidad_zonas');
            $table->integer('parejas_por_zona')->default(4); // Típicamente 4
            
            // Cuántos clasifican de cada posición
            $table->integer('clasifican_primeros')->default(0); // Todos los primeros
            $table->integer('clasifican_segundos')->default(0); // Todos los segundos
            $table->integer('clasifican_terceros')->default(0); // Mejores X terceros
            $table->integer('clasifican_cuartos')->default(0); // Mejores X cuartos
            
            // Rondas eliminatorias
            $table->boolean('tiene_16avos_final')->default(false);
            $table->boolean('tiene_8vos_final')->default(false);
            $table->boolean('tiene_4tos_final')->default(false);
            
            // Criterio de desempate para "mejores terceros/cuartos"
            // Opciones: 'PG' (partidos ganados), 'DIF_GAMES' (diferencia de games), 
            // 'GF' (games a favor), 'ENFRENTAMIENTO' (enfrentamiento directo)
            $table->string('criterio_desempate_orden')->default('PG,DIF_GAMES,GF');
            
            // Games por partido en cada fase
            $table->integer('games_fase_grupos')->default(5); // Primer equipo en llegar a X games
            $table->integer('games_cruces')->default(5);
            $table->integer('games_semifinal')->default(5);
            $table->integer('games_final')->default(7);
            
            // Configuración de llaves (JSON)
            $table->text('llave_16avos')->nullable();
            $table->text('llave_8vos')->nullable();
            $table->text('llave_4tos')->nullable();
            $table->text('llave_semifinal')->nullable();
            $table->text('llave_final')->nullable();
            
            // Notas/observaciones del administrador
            $table->text('notas')->nullable();
            
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
        Schema::dropIfExists('configuracion_cruces_americanos');
    }
}
