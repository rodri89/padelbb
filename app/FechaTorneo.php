<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FechaTorneo extends Model
{
    protected $fillable = ['torneo_id','fecha','horario', 'pareja_1_jugador_1', 'pareja_1_jugador_2', 'pareja_2_jugador_1', 
    'pareja_2_jugador_2', 'pareja_1_set_1', 'pareja_1_set_1_tie_break', 'pareja_2_set_1','pareja_2_set_1_tie_break', 'pareja_1_set_2', 
    'pareja_1_set_2_tie_break', 'pareja_2_set_2', 'pareja_2_set_2_tie_break','pareja_1_set_3',
    'pareja_1_set_3_tie_break', 'pareja_2_set_3', 'pareja_2_set_3_tie_break', 'pareja_1_set_super_tie_break',
    'pareja_2_set_super_tie_break'];
}
