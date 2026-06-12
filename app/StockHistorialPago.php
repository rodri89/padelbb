<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockHistorialPago extends Model
{
    protected $table = 'stock_historial_pagos';

    public $timestamps = false;

    protected $fillable = [
        'stock_venta_id', 'stock_venta_participante_id', 'stock_detalle_venta_id',
        'monto_pagado', 'metodo_pago', 'fecha_pago',
        'referencia_pago', 'usuario_responsable', 'notas', 'created_at',
    ];

    protected $casts = [
        'monto_pagado' => 'decimal:2',
        'fecha_pago' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(StockVenta::class, 'stock_venta_id');
    }

    public function participante(): BelongsTo
    {
        return $this->belongsTo(StockVentaParticipante::class, 'stock_venta_participante_id');
    }

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(StockDetalleVenta::class, 'stock_detalle_venta_id');
    }
}
