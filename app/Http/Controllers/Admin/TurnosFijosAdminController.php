<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\StockCancha;
use App\StockTurnoFijo;
use Illuminate\Http\Request;

class TurnosFijosAdminController extends Controller
{
    private const HORARIOS = ['16:00', '17:30', '19:00', '20:30', '22:00'];
    private const DIAS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    public function index()
    {
        $canchas = StockCancha::whereIn('nombre', ['Cancha 1', 'Cancha 2', 'Cancha 3'])
            ->orderBy('nombre')
            ->get();

        $turnos = StockTurnoFijo::with('cancha')
            ->orderBy('dia_semana')
            ->orderBy('hora')
            ->orderBy('stock_cancha_id')
            ->get();

        return view('bahia_padel.admin.turnos_fijos.index', [
            'turnos' => $turnos,
            'canchas' => $canchas,
            'dias' => self::DIAS,
            'horarios' => self::HORARIOS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'stock_cancha_id' => 'required|exists:stock_canchas,id',
            'dia_semana' => 'required|integer|min:1|max:7',
            'hora' => 'required|in:' . implode(',', self::HORARIOS),
            'nombre_grupo' => 'required|string|max:100',
        ]);

        $exists = StockTurnoFijo::where('stock_cancha_id', $validated['stock_cancha_id'])
            ->where('dia_semana', $validated['dia_semana'])
            ->where('hora', $validated['hora'])
            ->exists();

        if ($exists) {
            return redirect()->route('adminturnosfijos')
                ->with('error', 'Ya existe un turno fijo para esa cancha, día y hora.');
        }

        StockTurnoFijo::create([
            'stock_cancha_id' => $validated['stock_cancha_id'],
            'dia_semana' => $validated['dia_semana'],
            'hora' => $validated['hora'],
            'nombre_grupo' => $validated['nombre_grupo'],
            'activo' => true,
        ]);

        return redirect()->route('adminturnosfijos')
            ->with('success', 'Turno fijo creado correctamente.');
    }

    public function update(Request $request, StockTurnoFijo $turno)
    {
        $validated = $request->validate([
            'stock_cancha_id' => 'required|exists:stock_canchas,id',
            'dia_semana' => 'required|integer|min:1|max:7',
            'hora' => 'required|in:' . implode(',', self::HORARIOS),
            'nombre_grupo' => 'required|string|max:100',
            'activo' => 'sometimes|boolean',
        ]);

        $exists = StockTurnoFijo::where('stock_cancha_id', $validated['stock_cancha_id'])
            ->where('dia_semana', $validated['dia_semana'])
            ->where('hora', $validated['hora'])
            ->where('id', '!=', $turno->id)
            ->exists();

        if ($exists) {
            return redirect()->route('adminturnosfijos')
                ->with('error', 'Ya existe otro turno fijo para esa cancha, día y hora.');
        }

        $turno->update([
            'stock_cancha_id' => $validated['stock_cancha_id'],
            'dia_semana' => $validated['dia_semana'],
            'hora' => $validated['hora'],
            'nombre_grupo' => $validated['nombre_grupo'],
            'activo' => $request->boolean('activo', $turno->activo),
        ]);

        return redirect()->route('adminturnosfijos')
            ->with('success', 'Turno fijo actualizado correctamente.');
    }

    public function destroy(StockTurnoFijo $turno)
    {
        $turno->delete();

        return redirect()->route('adminturnosfijos')
            ->with('success', 'Turno fijo eliminado correctamente.');
    }
}
