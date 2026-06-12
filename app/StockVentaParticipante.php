<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockVentaParticipante extends Model
{
    protected $table = 'stock_venta_participantes';

    protected $fillable = [
        'stock_venta_id', 'slot', 'nombre', 'jugador_id',
        'estado_pago', 'metodo_pago', 'fecha_pago',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(StockVenta::class, 'stock_venta_id');
    }

    public function jugador(): BelongsTo
    {
        return $this->belongsTo(Jugadore::class, 'jugador_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(StockDetalleVenta::class, 'stock_venta_participante_id');
    }
}
