<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockCajaApertura extends Model
{
    protected $table = 'stock_caja_aperturas';

    protected $fillable = [
        'fecha',
        'monto_efectivo_inicial',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto_efectivo_inicial' => 'decimal:2',
    ];
}
