<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedPuntosRankingReferencia extends Migration
{
    /**
     * Valores por defecto: Campeón 100, Sub 75, 3ro/4to 50, Cuartos 25, Octavos 15, 16avos 10, No clasificados 5.
     */
    public function up()
    {
        $referencias = [
            ['codigo' => 'campeon',           'nombre' => 'Campeón',           'puntos' => 100, 'orden' => 1],
            ['codigo' => 'subcampeon',       'nombre' => 'Subcampeón',        'puntos' => 75,  'orden' => 2],
            ['codigo' => 'tercero_cuarto',   'nombre' => '3º y 4º puesto',    'puntos' => 50,  'orden' => 3],
            ['codigo' => 'cuartos',          'nombre' => 'Cuartos de final',  'puntos' => 25,  'orden' => 4],
            ['codigo' => 'octavos',          'nombre' => 'Octavos de final',  'puntos' => 15,  'orden' => 5],
            ['codigo' => '16avos',           'nombre' => '16avos de final',   'puntos' => 10,  'orden' => 6],
            ['codigo' => 'no_clasificados',  'nombre' => 'No clasificados',    'puntos' => 5,   'orden' => 7],
        ];

        $now = now();
        foreach ($referencias as $r) {
            $r['created_at'] = $now;
            $r['updated_at'] = $now;
        }
        DB::table('puntos_ranking_referencia')->insert($referencias);
    }

    public function down()
    {
        DB::table('puntos_ranking_referencia')->truncate();
    }
}
