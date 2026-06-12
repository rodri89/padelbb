<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCancha extends Model
{
    protected $table = 'stock_canchas';

    protected $fillable = ['nombre', 'descripcion', 'activa'];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function ventas(): HasMany
    {
        return $this->hasMany(StockVenta::class, 'stock_cancha_id');
    }
}
