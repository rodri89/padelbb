<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Semifinal: C1–C4 en JSON/config y en grupos.referencia_config pasan a CU1–CU4
 * para no confundir con la zona C del grupo (referencia C1 = primer clasificado de zona C).
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('configuracion_cruces_puntuables')
            ->whereNotNull('llave_semifinal')
            ->get(['id', 'llave_semifinal']);

        foreach ($rows as $row) {
            $j = (string) ($row->llave_semifinal ?? '');
            if ($j === '') {
                continue;
            }
            $orig = $j;
            foreach ([4, 3, 2, 1] as $n) {
                $j = str_replace('"C' . $n . '"', '"CU' . $n . '"', $j);
            }
            if ($j !== $orig) {
                DB::table('configuracion_cruces_puntuables')->where('id', $row->id)->update(['llave_semifinal' => $j]);
            }
        }

        foreach ([4, 3, 2, 1] as $n) {
            DB::table('grupos')
                ->where('zona', 'semifinal')
                ->where('referencia_config', 'C' . $n)
                ->update(['referencia_config' => 'CU' . $n]);
        }
    }

    public function down(): void
    {
        foreach ([1, 2, 3, 4] as $n) {
            DB::table('grupos')
                ->where('zona', 'semifinal')
                ->where('referencia_config', 'CU' . $n)
                ->update(['referencia_config' => 'C' . $n]);
        }

        $rows = DB::table('configuracion_cruces_puntuables')
            ->whereNotNull('llave_semifinal')
            ->get(['id', 'llave_semifinal']);

        foreach ($rows as $row) {
            $j = (string) ($row->llave_semifinal ?? '');
            if ($j === '') {
                continue;
            }
            $orig = $j;
            foreach ([1, 2, 3, 4] as $n) {
                $j = str_replace('"CU' . $n . '"', '"C' . $n . '"', $j);
            }
            if ($j !== $orig) {
                DB::table('configuracion_cruces_puntuables')->where('id', $row->id)->update(['llave_semifinal' => $j]);
            }
        }
    }
};
