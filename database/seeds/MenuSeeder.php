<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Café con leche', 'price' => 2500, 'category' => 'Bebidas', 'available' => true, 'sort_order' => 1],
            ['name' => 'Agua mineral', 'price' => 1800, 'category' => 'Bebidas', 'available' => true, 'sort_order' => 2],
            ['name' => 'Hamburguesa completa', 'price' => 6800, 'category' => 'Platos Principales', 'available' => true, 'sort_order' => 1, 'description' => 'Carne, lechuga, tomate, queso y huevo con papas'],
            ['name' => 'Sándwich de milanesa XL', 'price' => 6200, 'category' => 'Platos Principales', 'available' => true, 'sort_order' => 2, 'description' => 'Milanesa de ternera con lechuga y tomate'],
            ['name' => 'Nachos con cheddar', 'price' => 5200, 'category' => 'Aperitivos', 'available' => true, 'sort_order' => 1],
            ['name' => 'Mix de frutos secos', 'price' => 3200, 'category' => 'Aperitivos', 'available' => false, 'sort_order' => 2],
        ];

        foreach ($items as $item) {
            MenuItem::create($item);
        }
    }
}