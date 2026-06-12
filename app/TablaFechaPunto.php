<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TablaFechaPunto extends Model
{
    protected $fillable = ['jugador_id','torneo_id', 'fecha_numero', 'puntos'];
}
