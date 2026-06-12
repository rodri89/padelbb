<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Torneo extends Model
{
    protected $fillable = ['nombre', 'tipo', 'es_torneo_individual', 'fecha_inicio', 'fecha_fin', 'categoria', 'premio_1', 'premio_2', 'descripcion', 'imagen', 'activo', 'estado', 'tipo_torneo_formato', 'version', 'config_cruces_puntuable_id'];
    
    /**
     * Incrementa la versión del torneo para notificar a las vistas TV
     * que deben refrescar sus datos.
     */
    public static function incrementarVersion($torneoId)
    {
        if (!$torneoId) return;
        
        DB::table('torneos')
            ->where('id', $torneoId)
            ->increment('version');
    }
    
    /**
     * Obtiene la versión actual del torneo
     */
    public static function getVersion($torneoId)
    {
        if (!$torneoId) return 0;
        
        $torneo = DB::table('torneos')->where('id', $torneoId)->first();
        return $torneo ? ($torneo->version ?? 0) : 0;
    }
}
