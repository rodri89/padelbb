<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TablaGeneral extends Model
{
    protected $fillable = ['jugador_id','torneo_id', 'puntos'];
}
