<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovimientoStock extends Model
{
    protected $table = 'stock_movimientos_stock';

    public $timestamps = false;

    protected $fillable = [
        'stock_producto_id', 'tipo_movimiento', 'cantidad',
        'cantidad_anterior', 'cantidad_nueva', 'motivo', 'usuario_responsable', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(StockProducto::class, 'stock_producto_id');
    }
}
