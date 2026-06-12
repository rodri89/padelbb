<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;

class MenuApiController extends Controller
{
    public function index()
    {
        $items = MenuItem::available()->sorted()->get()->groupBy('category');
        return response()->json($items);
    }

    public function show(MenuItem $menuItem)
    {
        return response()->json($menuItem);
    }
}