<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockDetalleVenta extends Model
{
    protected $table = 'stock_detalles_venta';

    public $timestamps = false;

    protected $fillable = [
        'stock_venta_id', 'stock_producto_id', 'cantidad',
        'precio_unitario', 'subtotal', 'created_at',
        'stock_venta_participante_id', 'es_division', 'estado_pago',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(StockVenta::class, 'stock_venta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(StockProducto::class, 'stock_producto_id');
    }

    public function participante(): BelongsTo
    {
        return $this->belongsTo(StockVentaParticipante::class, 'stock_venta_participante_id');
    }
}
