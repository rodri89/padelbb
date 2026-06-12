<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockProducto extends Model
{
    protected $table = 'stock_productos';

    protected $fillable = [
        'nombre', 'descripcion', 'stock_categoria_id',
        'precio_unitario', 'stock_actual', 'stock_minimo', 'activo',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(StockCategoriaProducto::class, 'stock_categoria_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(StockMovimientoStock::class, 'stock_producto_id');
    }

    public function nivelStock(): string
    {
        if ($this->stock_actual <= 0) {
            return 'CRITICO';
        }
        if ($this->stock_actual <= $this->stock_minimo) {
            return 'BAJO';
        }
        if ($this->stock_minimo > 0 && $this->stock_actual <= $this->stock_minimo * 2) {
            return 'MEDIO';
        }

        return 'BUENO';
    }
}
