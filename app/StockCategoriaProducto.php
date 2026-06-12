<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCategoriaProducto extends Model
{
    protected $table = 'stock_categorias_productos';

    protected $fillable = ['nombre', 'descripcion', 'activa'];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(StockProducto::class, 'stock_categoria_id');
    }
}
