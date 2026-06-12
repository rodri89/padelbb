<?php

namespace Database\Seeders;

use App\StockCancha;
use App\StockCategoriaProducto;
use Illuminate\Database\Seeder;

class StockTiendaSeeder extends Seeder
{
    public function run(): void
    {
        if (StockCancha::query()->count() === 0) {
            StockCancha::query()->insert([
                ['nombre' => 'Cancha 1', 'descripcion' => null, 'activa' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Cancha 2', 'descripcion' => null, 'activa' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Cancha 3', 'descripcion' => null, 'activa' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Particular', 'descripcion' => null, 'activa' => 1, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (StockCategoriaProducto::query()->count() === 0) {
            foreach (['Bebidas', 'Snacks', 'Equipamiento', 'Accesorios'] as $nombre) {
                StockCategoriaProducto::query()->create([
                    'nombre' => $nombre,
                    'descripcion' => null,
                    'activa' => true,
                ]);
            }
        }
    }
}
