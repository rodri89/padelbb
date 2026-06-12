<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarioInscripcion extends Model
{
    protected $table = 'calendario_inscripciones';

    protected $fillable = [
        'calendario_id',
        'jugador1_id',
        'jugador2_id',
        'jugador1_nombre',
        'jugador1_apellido',
        'jugador1_telefono',
        'jugador2_nombre',
        'jugador2_apellido',
        'jugador2_telefono',
        'disponibilidad_horaria',
    ];

    public function calendario(): BelongsTo
    {
        return $this->belongsTo(Calendario::class);
    }
}
