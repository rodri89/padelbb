<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCamposToCalendarioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('calendario', function (Blueprint $table) {
            $table->date('fecha_desde')->nullable()->after('fecha');
            $table->date('fecha_hasta')->nullable()->after('fecha_desde');
            $table->date('fecha_abre_inscripcion')->nullable()->after('fecha_hasta');
            $table->date('fecha_cierra_inscripcion')->nullable()->after('fecha_abre_inscripcion');
            $table->decimal('premio_1', 12, 2)->nullable()->after('nombre');
            $table->decimal('premio_2', 12, 2)->nullable()->after('premio_1');
            $table->decimal('premio_3', 12, 2)->nullable()->after('premio_2');
            $table->decimal('premio_4', 12, 2)->nullable()->after('premio_3');
        });

        // Copiar fecha existente a rango (MySQL / MariaDB)
        if (Schema::hasColumn('calendario', 'fecha')) {
            DB::table('calendario')->whereNull('fecha_desde')->update([
                'fecha_desde' => DB::raw('fecha'),
                'fecha_hasta' => DB::raw('fecha'),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('calendario', function (Blueprint $table) {
            $table->dropColumn([
                'fecha_desde',
                'fecha_hasta',
                'fecha_abre_inscripcion',
                'fecha_cierra_inscripcion',
                'premio_1',
                'premio_2',
                'premio_3',
                'premio_4',
            ]);
        });
    }
}
