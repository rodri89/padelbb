<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function index()
    {
        $items = MenuItem::orderBy('category')->orderBy('sort_order')->get();
        return view('admin.menu.index', compact('items'));
    }

    public function create()
    {
        return view('admin.menu.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'available' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $data['available'] = $request->boolean('available', false);
        MenuItem::create($data);

        return redirect()->route('admin.menu.index')->with('success', 'Producto creado');
    }

    public function edit(MenuItem $menuItem)
    {
        return view('admin.menu.edit', compact('menuItem'));
    }

    public function update(Request $request, MenuItem $menuItem)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'available' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $data['available'] = $request->boolean('available', false);
        $menuItem->update($data);

        return redirect()->route('admin.menu.index')->with('success', 'Producto actualizado');
    }

    public function destroy(MenuItem $menuItem)
    {
        $menuItem->delete();
        return redirect()->route('admin.menu.index')->with('success', 'Producto eliminado');
    }
}