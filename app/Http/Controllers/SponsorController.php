<?php

namespace App\Http\Controllers;

use App\Sponsor;
use Illuminate\Http\Request;

class SponsorController extends Controller
{
    public function index()
    {
        $sponsors = Sponsor::orderBy('orden')->get();
        return view('bahia_padel.admin.sponsors.index', compact('sponsors'));
    }

    public function create()
    {
        return view('bahia_padel.admin.sponsors.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'imagen' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $sponsor = new Sponsor();
        $sponsor->nombre = $request->nombre;
        $sponsor->active = $request->has('active') ? 1 : 0;
        $sponsor->orden = $request->orden ?? 0;

        if ($request->hasFile('imagen')) {
            $image = $request->file('imagen');
            $name = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images/ads'), $name);
            $sponsor->imagen = $name;
        }

        $sponsor->save();

        return redirect()->route('sponsors.index')->with('success', 'Sponsor creado correctamente.');
    }

    public function edit($id)
    {
        $sponsor = Sponsor::findOrFail($id);
        return view('bahia_padel.admin.sponsors.edit', compact('sponsor'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required',
            'imagen' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $sponsor = Sponsor::findOrFail($id);
        $sponsor->nombre = $request->nombre;
        $sponsor->active = $request->has('active') ? 1 : 0;
        $sponsor->orden = $request->orden ?? 0;

        if ($request->hasFile('imagen')) {
            // Borrar imagen anterior si existe
            if ($sponsor->imagen && file_exists(public_path('images/ads/' . $sponsor->imagen)) && !in_array($sponsor->imagen, ['garage.png', 'iph.png'])) {
                unlink(public_path('images/ads/' . $sponsor->imagen));
            }

            $image = $request->file('imagen');
            $name = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images/ads'), $name);
            $sponsor->imagen = $name;
        }

        $sponsor->save();

        return redirect()->route('sponsors.index')->with('success', 'Sponsor actualizado correctamente.');
    }

    public function destroy($id)
    {
        $sponsor = Sponsor::findOrFail($id);
        
        if ($sponsor->imagen && file_exists(public_path('images/ads/' . $sponsor->imagen)) && !in_array($sponsor->imagen, ['garage.png', 'iph.png'])) {
            unlink(public_path('images/ads/' . $sponsor->imagen));
        }

        $sponsor->delete();

        return redirect()->route('sponsors.index')->with('success', 'Sponsor eliminado correctamente.');
    }
}
