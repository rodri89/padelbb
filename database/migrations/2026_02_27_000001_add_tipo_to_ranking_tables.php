<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddTipoToRankingTables extends Migration
{
    /**
     * Añade tipo (masculino, femenino, mixto) a ranking_puntos y ranking_totales
     * para poder filtrar el ranking por tipo de torneo.
     */
    public function up()
    {
        if (!Schema::hasColumn('ranking_puntos', 'tipo')) {
            Schema::table('ranking_puntos', function (Blueprint $table) {
                $table->string('tipo', 16)->default('masculino')->after('temporada')->comment('masculino, femenino, mixto');
            });
        }
        DB::table('ranking_puntos')->whereNull('tipo')->orWhere('tipo', '')->update(['tipo' => 'masculino']);

        if (!Schema::hasColumn('ranking_totales', 'tipo')) {
            Schema::table('ranking_totales', function (Blueprint $table) {
                $table->string('tipo', 16)->default('masculino')->after('temporada')->comment('masculino, femenino, mixto');
            });
        }
        DB::table('ranking_totales')->whereNull('tipo')->orWhere('tipo', '')->update(['tipo' => 'masculino']);

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql' && Schema::hasColumn('ranking_totales', 'tipo')) {
            $indexExists = DB::selectOne("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'ranking_totales' AND index_name = 'ranking_totales_jugador_cat_temp_unique'");
            $newUniqueExists = DB::selectOne("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'ranking_totales' AND index_name = 'ranking_totales_jugador_cat_temp_tipo_unique'");
            if ($indexExists && !$newUniqueExists) {
                Schema::table('ranking_totales', function (Blueprint $table) {
                    $table->dropForeign(['jugador_id']);
                });
                Schema::table('ranking_totales', function (Blueprint $table) {
                    $table->dropUnique('ranking_totales_jugador_cat_temp_unique');
                });
                Schema::table('ranking_totales', function (Blueprint $table) {
                    $table->unique(['jugador_id', 'categoria', 'temporada', 'tipo'], 'ranking_totales_jugador_cat_temp_tipo_unique');
                    $table->index(['categoria', 'temporada', 'tipo']);
                    $table->foreign('jugador_id')->references('id')->on('jugadores')->onDelete('cascade');
                });
            } elseif (!$newUniqueExists) {
                Schema::table('ranking_totales', function (Blueprint $table) {
                    $table->unique(['jugador_id', 'categoria', 'temporada', 'tipo'], 'ranking_totales_jugador_cat_temp_tipo_unique');
                    $table->index(['categoria', 'temporada', 'tipo']);
                });
            }
        }
        if (Schema::hasColumn('ranking_puntos', 'tipo')) {
            $idxName = 'ranking_puntos_categoria_temporada_tipo_index';
            if ($driver === 'mysql') {
                $idxExists = DB::selectOne("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'ranking_puntos' AND index_name = ?", [$idxName]);
                if (!$idxExists) {
                    Schema::table('ranking_puntos', function (Blueprint $table) {
                        $table->index(['categoria', 'temporada', 'tipo']);
                    });
                }
            } else {
                Schema::table('ranking_puntos', function (Blueprint $table) {
                    $table->index(['categoria', 'temporada', 'tipo']);
                });
            }
        }
    }

    public function down()
    {
        Schema::table('ranking_totales', function (Blueprint $table) {
            $table->dropUnique('ranking_totales_jugador_cat_temp_tipo_unique');
        });
        Schema::table('ranking_totales', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
        Schema::table('ranking_totales', function (Blueprint $table) {
            $table->unique(['jugador_id', 'categoria', 'temporada'], 'ranking_totales_jugador_cat_temp_unique');
            $table->index(['categoria', 'temporada']);
        });

        Schema::table('ranking_puntos', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
}
