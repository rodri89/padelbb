<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockCajaDiaria extends Model
{
    protected $table = 'stock_caja_aperturas';

    protected $fillable = [
        'fecha',
        'monto_efectivo_inicial',
        'fondo_final',
        'efectivo_real',
        'diferencia',
        'observaciones',
        'usuario_apertura',
        'usuario_cierre',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto_efectivo_inicial' => 'decimal:2',
        'fondo_final' => 'decimal:2',
        'efectivo_real' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    /**
     * Alias para compatibilidad con código que usa fondo_inicial
     */
    public function getFondoInicialAttribute(): ?float
    {
        return $this->attributes['monto_efectivo_inicial'] ?? null;
    }

    public function setFondoInicialAttribute($value): void
    {
        $this->attributes['monto_efectivo_inicial'] = $value;
    }
}
