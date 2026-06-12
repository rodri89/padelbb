<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockVenta extends Model
{
    protected $table = 'stock_ventas';

    protected $fillable = [
        'nombre_cliente', 'nombre_turno', 'stock_cancha_id',
        'fecha_venta', 'hora_venta', 'precio_total',
        'metodo_pago', 'estado_pago', 'fecha_pago', 'referencia_pago', 'notas',
        'stock_venta_id_padre',
    ];

    protected $casts = [
        'fecha_venta' => 'date',
        'fecha_pago' => 'date',
        'precio_total' => 'decimal:2',
    ];

    public function cancha(): BelongsTo
    {
        return $this->belongsTo(StockCancha::class, 'stock_cancha_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(StockDetalleVenta::class, 'stock_venta_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(StockHistorialPago::class, 'stock_venta_id');
    }

    public function participantes(): HasMany
    {
        return $this->hasMany(StockVentaParticipante::class, 'stock_venta_id')->orderBy('slot');
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(StockVenta::class, 'stock_venta_id_padre');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(StockVenta::class, 'stock_venta_id_padre');
    }
}
