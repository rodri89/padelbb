<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Fecha extends Model
{
    protected $fillable = ['torneo_id','numero','partido_numero', 'jugador_id_1', 'jugador_id_2', 'jugador_id_3', 'jugador_id_4', 'es_torneo_individual', 'resultado_set_1', 'resultado_set_2','resultado_set_3', 'resultado_games_jugador_1', 'resultado_games_jugador_2', 'resultado_games_jugador_3', 'resultado_games_jugador_4','activo'];
}