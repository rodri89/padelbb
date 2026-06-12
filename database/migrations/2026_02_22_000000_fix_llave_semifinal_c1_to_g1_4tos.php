<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrige referencias C1,C2,C3,C4 -> G1-4tos,G2-4tos,G3-4tos,G4-4tos en llave_semifinal.
     * C1-C4 en semifinales significan "Ganador Cuartos 1-4", no zona C.
     */
    public function up(): void
    {
        $configs = DB::table('configuracion_cruces_puntuables')
            ->whereNotNull('llave_semifinal')
            ->get();

        foreach ($configs as $row) {
            $llave = $row->llave_semifinal;
            $llave = str_replace('"C1"', '"G1-4tos"', $llave);
            $llave = str_replace('"C2"', '"G2-4tos"', $llave);
            $llave = str_replace('"C3"', '"G3-4tos"', $llave);
            $llave = str_replace('"C4"', '"G4-4tos"', $llave);
            if ($llave !== $row->llave_semifinal) {
                DB::table('configuracion_cruces_puntuables')
                    ->where('id', $row->id)
                    ->update(['llave_semifinal' => $llave]);
            }
        }
    }

    public function down(): void
    {
        $configs = DB::table('configuracion_cruces_puntuables')
            ->whereNotNull('llave_semifinal')
            ->get();

        foreach ($configs as $row) {
            $llave = $row->llave_semifinal;
            $llave = str_replace('"G1-4tos"', '"C1"', $llave);
            $llave = str_replace('"G2-4tos"', '"C2"', $llave);
            $llave = str_replace('"G3-4tos"', '"C3"', $llave);
            $llave = str_replace('"G4-4tos"', '"C4"', $llave);
            if ($llave !== $row->llave_semifinal) {
                DB::table('configuracion_cruces_puntuables')
                    ->where('id', $row->id)
                    ->update(['llave_semifinal' => $llave]);
            }
        }
    }
};
