<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $fillable = ['torneo_id','zona','fecha','horario', 'jugador_1', 'jugador_2', 'partido_id', 'referencia_config'];
}
