<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;

class CartaController extends Controller
{
    public function index()
    {
        $items = MenuItem::available()->sorted()->get()->groupBy('category');
        return view('carta', compact('items'));
    }
}