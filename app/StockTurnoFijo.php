<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockTurnoFijo extends Model
{
    protected $table = 'stock_turnos_fijos';

    protected $fillable = [
        'stock_cancha_id',
        'dia_semana',
        'hora',
        'nombre_grupo',
        'activo',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'activo' => 'boolean',
    ];

    public function cancha()
    {
        return $this->belongsTo(StockCancha::class, 'stock_cancha_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
