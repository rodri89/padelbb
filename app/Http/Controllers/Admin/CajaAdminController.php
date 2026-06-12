<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StockVentaService;
use App\StockCajaApertura;
use App\StockCancha;
use App\StockDetalleVenta;
use App\StockHistorialPago;
use App\StockProducto;
use App\StockVenta;
use App\StockVentaParticipante;
use App\Jugadore;
use Illuminate\Http\Request;

class CajaAdminController extends Controller
{
    private function etiquetasCanchaCaja(): array
    {
        return ['Cancha 1', 'Cancha 2', 'Cancha 3', 'Particular'];
    }

    private function mapaCanchasCaja(): array
    {
        $map = [];
        foreach ($this->etiquetasCanchaCaja() as $nombre) {
            $c = StockCancha::query()->firstOrCreate(
                ['nombre' => $nombre],
                ['activa' => true, 'descripcion' => null]
            );
            $map[$nombre] = $c->id;
        }

        return $map;
    }

    /**
     * Día de caja normalizado (Y-m-d), máximo hoy.
     */
    private function normalizarFechaCaja(?string $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return now()->toDateString();
        }
        try {
            $d = \Carbon\Carbon::createFromFormat('Y-m-d', $fecha)->startOfDay();
        } catch (\Throwable $e) {
            return now()->toDateString();
        }
        $hoy = now()->startOfDay();
        if ($d->gt($hoy)) {
            return $hoy->toDateString();
        }

        return $d->toDateString();
    }

    /**
     * Filtro por día de caja. `fecha_venta` es columna DATE: usar igualdad evita
     * diferencias de driver/timezone con whereDate(DATE(col)) en algunos hosts.
     */
    private function aplicarFiltroFechaVentas(\Illuminate\Database\Eloquent\Builder $q, string $fechaYmd): \Illuminate\Database\Eloquent\Builder
    {
        return $q->where('fecha_venta', $fechaYmd);
    }

    /**
     * HTML de la tabla de listado (sin columnas Ver/Cobrar), para actualizar paneles vía AJAX.
     *
     * @param  \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator  $ventas
     */
    private function htmlTablaListadoVentas($ventas, callable $fmtMoney): string
    {
        return view('bahia_padel.admin.caja._tabla_listado_ventas', [
            'ventas' => $ventas,
            'fmtMoney' => $fmtMoney,
            'mostrarAccionesVerCobrar' => false,
            'mostrarVerModal' => true,
        ])->render();
    }

    /**
     * Datos del panel superior + HTML de todas las tablas de detalle (tras cobros / líneas / abrir ticket).
     *
     * @return array<string, mixed>
     */
    private function sumSaldoPendienteVentasDelDia(string $dia): float
    {
        return (float) StockDetalleVenta::query()
            ->where('estado_pago', 'pendiente')
            ->whereHas('venta', function ($q) use ($dia) {
                $q->where('fecha_venta', $dia)->where('estado_pago', 'pendiente');
            })
            ->sum('subtotal');
    }

    /**
     * @return array<string, float|int>
     */
    private function statsCajaDelDia(string $dia): array
    {
        $ventaIdsDelDia = $this->aplicarFiltroFechaVentas(StockVenta::query(), $dia)->pluck('id');

        return [
            'transacciones' => $ventaIdsDelDia->count(),
            'monto_total' => (float) StockVenta::query()->whereIn('id', $ventaIdsDelDia)->sum('precio_total'),
            'efectivo' => (float) StockHistorialPago::query()
                ->whereIn('stock_venta_id', $ventaIdsDelDia)
                ->where('metodo_pago', 'efectivo')
                ->sum('monto_pagado'),
            'transferencia' => (float) StockHistorialPago::query()
                ->whereIn('stock_venta_id', $ventaIdsDelDia)
                ->where('metodo_pago', 'transferencia')
                ->sum('monto_pagado'),
            'pagado' => (float) $this->aplicarFiltroFechaVentas(StockVenta::query(), $dia)
                ->where('estado_pago', 'pagado')
                ->sum('precio_total'),
            'pendiente' => $this->sumSaldoPendienteVentasDelDia($dia),
        ];
    }

    private function cajaResumenAjaxPayload(?string $fechaCaja = null): array
    {
        $dia = $this->normalizarFechaCaja($fechaCaja);
        $fmtMoney = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');

        $statsHoy = $this->statsCajaDelDia($dia);

        $pendientes = StockVenta::query()
            ->with('cancha')
            ->where('estado_pago', 'pendiente')
            ->where('precio_total', '>', 0)
            ->orderBy('fecha_venta')
            ->get();

        $baseListasQuery = fn () => StockVenta::query()->with('cancha')->where('fecha_venta', $dia);

        $listaVentasHoy = $baseListasQuery()->orderByDesc('id')->get();
        $listaEfectivoHoy = $baseListasQuery()->where('metodo_pago', 'efectivo')->orderByDesc('id')->get();
        $listaTransferHoy = $baseListasQuery()->where('metodo_pago', 'transferencia')->orderByDesc('id')->get();
        $listaCobradoHoy = $baseListasQuery()->where('estado_pago', 'pagado')->orderByDesc('id')->get();
        $listaPendienteHoy = $baseListasQuery()->where('estado_pago', 'pendiente')->orderByDesc('id')->get();

        $htmlVentas = $this->htmlTablaListadoVentas($listaVentasHoy, $fmtMoney);

        return [
            'transacciones' => (int) $statsHoy['transacciones'],
            'monto_total_fmt' => $fmtMoney($statsHoy['monto_total']),
            'efectivo_fmt' => $fmtMoney($statsHoy['efectivo']),
            'transferencia_fmt' => $fmtMoney($statsHoy['transferencia']),
            'pagado_fmt' => $fmtMoney($statsHoy['pagado']),
            'pendiente_dia_fmt' => $fmtMoney($statsHoy['pendiente']),
            'pendientes_saldo_count' => $pendientes->count(),
            'html_ventas_hoy' => $htmlVentas,
            'html_total_hoy' => $htmlVentas,
            'html_efectivo_hoy' => $this->htmlTablaListadoVentas($listaEfectivoHoy, $fmtMoney),
            'html_transfer_hoy' => $this->htmlTablaListadoVentas($listaTransferHoy, $fmtMoney),
            'html_cobrado_hoy' => $this->htmlTablaListadoVentas($listaCobradoHoy, $fmtMoney),
            'html_pendientes_dia' => $this->htmlTablaListadoVentas($listaPendienteHoy, $fmtMoney),
            'html_pendientes_saldo' => $this->htmlTablaListadoVentas($pendientes, $fmtMoney),
        ];
    }

    private function cajaFechaParaResumenDesdeRequest(Request $request): string
    {
        return $this->normalizarFechaCaja($request->input('caja_fecha'));
    }

    /**
     * Respuesta JSON con montos y HTML de todas las tablas del panel resumen.
     * Usado al tocar una tarjeta (efectivo, pendientes, etc.) para evitar datos viejos sin F5.
     */
    public function resumenJson(Request $request)
    {
        if ($request->filled('fecha')) {
            $request->validate([
                'fecha' => 'required|date_format:Y-m-d|before_or_equal:today',
            ]);
        }

        return response()->json(
            $this->cajaResumenAjaxPayload($this->normalizarFechaCaja($request->query('fecha')))
        );
    }

    private function ventaToArray(StockVenta $venta, callable $fmtMoney): array
    {
        $venta->load(['detalles.producto', 'detalles.participante', 'cancha', 'participantes']);

        $modoGrupo = $venta->participantes->isNotEmpty();

        $detallesPayload = $venta->detalles->map(function ($d) use ($fmtMoney) {
            return [
                'id' => $d->id,
                'stock_venta_participante_id' => $d->stock_venta_participante_id !== null ? (int) $d->stock_venta_participante_id : null,
                'slot' => $d->participante ? (int) $d->participante->slot : null,
                'producto_nombre' => $d->producto ? $d->producto->nombre : null,
                'cantidad' => (int) $d->cantidad,
                'subtotal' => (float) $d->subtotal,
                'subtotal_fmt' => $fmtMoney($d->subtotal),
                'estado_pago' => $d->estado_pago ?? 'pendiente',
                'es_division' => (bool) $d->es_division,
            ];
        })->values()->all();

        $participantesPayload = [];
        if ($modoGrupo) {
            foreach ($venta->participantes->sortBy('slot')->values() as $p) {
                $sub = (float) $venta->detalles
                    ->where('stock_venta_participante_id', $p->id)
                    ->where('estado_pago', 'pendiente')
                    ->sum('subtotal');
                $participantesPayload[] = [
                    'id' => $p->id,
                    'slot' => (int) $p->slot,
                    'nombre' => $p->nombre,
                    'jugador_id' => $p->jugador_id !== null ? (int) $p->jugador_id : null,
                    'estado_pago' => $p->estado_pago,
                    'metodo_pago' => $p->metodo_pago,
                    'subtotal' => $sub,
                    'subtotal_fmt' => $fmtMoney($sub),
                ];
            }
        }

        $padrePayload = null;
        if ($venta->padre) {
            $padrePayload = [
                'id' => $venta->padre->id,
                'precio_total_fmt' => $fmtMoney($venta->padre->precio_total),
                'detalles' => $venta->padre->detalles->map(function ($d) use ($fmtMoney) {
                    return [
                        'id' => $d->id,
                        'producto_nombre' => $d->producto ? $d->producto->nombre : null,
                        'cantidad' => (int) $d->cantidad,
                        'subtotal_fmt' => $fmtMoney($d->subtotal),
                        'slot' => $d->participante ? (int) $d->participante->slot : null,
                    ];
                })->values()->all(),
            ];
        }

        $saldoPendiente = StockVentaService::saldoPendienteVenta($venta);

        return [
            'id' => $venta->id,
            'nombre_cliente' => $venta->nombre_cliente,
            'precio_total' => (float) $venta->precio_total,
            'precio_total_fmt' => $fmtMoney($venta->precio_total),
            'saldo_pendiente' => $saldoPendiente,
            'saldo_pendiente_fmt' => $fmtMoney($saldoPendiente),
            'estado_pago' => $venta->estado_pago,
            'cancha_nombre' => $venta->cancha ? $venta->cancha->nombre : null,
            'modo_grupo' => $modoGrupo,
            'participantes' => $participantesPayload,
            'detalles' => $detallesPayload,
            'padre' => $padrePayload,
        ];
    }

    public function jugadoresCajaJson()
    {
        $jugadores = Jugadore::query()
            ->where('activo', 1)
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'apellido']);

        return response()->json(['success' => true, 'jugadores' => $jugadores]);
    }

    public function updateParticipante(Request $request, StockVenta $venta, StockVentaParticipante $participante, StockVentaService $ventaService)
    {
        if ((int) $participante->stock_venta_id !== (int) $venta->id) {
            abort(404);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'jugador_id' => 'sometimes|nullable|integer|exists:jugadores,id',
        ]);

        $actualizarJugadorId = array_key_exists('jugador_id', $validated);
        $jugadorId = $actualizarJugadorId ? ($validated['jugador_id'] ?? null) : null;

        try {
            $ventaService->actualizarParticipante(
                $venta,
                (int) $participante->id,
                $validated['nombre'],
                $jugadorId,
                $actualizarJugadorId
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }

    public function pagoParticipante(Request $request, StockVenta $venta, StockVentaParticipante $participante, StockVentaService $ventaService)
    {
        if ((int) $participante->stock_venta_id !== (int) $venta->id) {
            abort(404);
        }

        $data = $request->validate([
            'metodo_pago' => 'nullable|in:efectivo,transferencia',
            'referencia_pago' => 'nullable|string|max:100',
            'fecha_pago' => 'nullable|date',
            'notas' => 'nullable|string|max:255',
            'caja_fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);

        $fmtMoney = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');
        $resumenFecha = $this->normalizarFechaCaja($data['caja_fecha'] ?? null);

        try {
            $venta = $ventaService->registrarPagoParticipante(
                $venta,
                (int) $participante->id,
                \Illuminate\Support\Arr::only($data, ['metodo_pago', 'referencia_pago', 'fecha_pago', 'notas'])
            );
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        $msg = $venta->estado_pago === 'pagado'
            ? 'Ticket cerrado: todos los jugadores pagaron.'
            : 'Pago del jugador registrado.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $msg,
                'venta' => $this->ventaToArray($venta, $fmtMoney),
                'ticket_cerrado' => $venta->estado_pago === 'pagado',
                'resumen' => $this->cajaResumenAjaxPayload($resumenFecha),
            ]);
        }

        return redirect()->route('admincaja.venta.show', $venta)->with('success', $msg);
    }

    public function index(Request $request)
    {
        if ($request->filled('fecha')) {
            $request->validate([
                'fecha' => 'required|date_format:Y-m-d|before_or_equal:today',
            ]);
        }
        $fechaCaja = $this->normalizarFechaCaja($request->query('fecha'));
        $fechaCajaEsHoy = $fechaCaja === now()->toDateString();
        $fechaCajaLabel = \Carbon\Carbon::parse($fechaCaja)->format('d/m/Y');

        $canchasCajaIds = $this->mapaCanchasCaja();

        $productosVenta = StockProducto::query()
            ->where('activo', true)
            ->where('stock_actual', '>', 0)
            ->with('categoria')
            ->orderBy('nombre')
            ->get();

        $categoriasVenta = $productosVenta
            ->map(function ($p) {
                return $p->categoria;
            })
            ->filter()
            ->unique('id')
            ->sortBy('nombre')
            ->values();

        $pendientes = StockVenta::query()
            ->with('cancha')
            ->where('estado_pago', 'pendiente')
            ->where('precio_total', '>', 0)
            ->orderBy('fecha_venta')
            ->get();

        $statsHoy = $this->statsCajaDelDia($fechaCaja);

        $baseListasQuery = fn () => StockVenta::query()->with('cancha')->where('fecha_venta', $fechaCaja);

        $listaVentasHoy = $baseListasQuery()->orderByDesc('id')->get();
        $listaEfectivoHoy = $baseListasQuery()->where('metodo_pago', 'efectivo')->orderByDesc('id')->get();
        $listaTransferHoy = $baseListasQuery()->where('metodo_pago', 'transferencia')->orderByDesc('id')->get();
        $listaCobradoHoy = $baseListasQuery()->where('estado_pago', 'pagado')->orderByDesc('id')->get();
        $listaPendienteHoy = $baseListasQuery()->where('estado_pago', 'pendiente')->orderByDesc('id')->get();

        $ticketsAbiertos = StockVenta::query()
            ->with(['cancha', 'detalles.producto', 'detalles.participante', 'participantes', 'padre.detalles.producto', 'padre.participantes'])
            ->where('estado_pago', 'pendiente')
            ->where('fecha_venta', $fechaCaja)
            ->orderByDesc('updated_at')
            ->get();

        $cajaApertura = StockCajaApertura::query()->where('fecha', $fechaCaja)->first();
        $puedeEditarAperturaCaja = $this->puedeEditarAperturaCaja();

        return view('bahia_padel.admin.caja.index', compact(
            'pendientes',
            'statsHoy',
            'listaVentasHoy',
            'listaEfectivoHoy',
            'listaTransferHoy',
            'listaCobradoHoy',
            'listaPendienteHoy',
            'productosVenta',
            'categoriasVenta',
            'canchasCajaIds',
            'ticketsAbiertos',
            'fechaCaja',
            'fechaCajaEsHoy',
            'fechaCajaLabel',
            'cajaApertura',
            'puedeEditarAperturaCaja'
        ));
    }

    private function puedeEditarAperturaCaja(): bool
    {
        $user = auth()->user();

        return $user && (int) $user->perfil === 2;
    }

    public function storeApertura(Request $request)
    {
        $data = $request->validate([
            'fecha' => 'required|date_format:Y-m-d|before_or_equal:today',
            'monto_efectivo_inicial' => 'required|numeric|min:0',
        ]);

        $fecha = $this->normalizarFechaCaja($data['fecha']);
        $existente = StockCajaApertura::query()->where('fecha', $fecha)->first();

        if ($existente && ! $this->puedeEditarAperturaCaja()) {
            return response()->json([
                'message' => 'Solo usuarios con perfil 2 pueden modificar el efectivo inicial de caja.',
            ], 403);
        }

        $apertura = StockCajaApertura::query()->updateOrCreate(
            ['fecha' => $fecha],
            ['monto_efectivo_inicial' => round((float) $data['monto_efectivo_inicial'], 2)]
        );

        $fmtMoney = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');

        return response()->json([
            'fecha' => $fecha,
            'monto_efectivo_inicial' => (float) $apertura->monto_efectivo_inicial,
            'monto_fmt' => $fmtMoney($apertura->monto_efectivo_inicial),
        ]);
    }

    public function storeBorrador(Request $request, StockVentaService $ventaService)
    {
        $request->validate([
            'nombre_cliente' => 'required|string|max:100',
            'stock_cancha_id' => 'required|exists:stock_canchas,id',
            'caja_fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);

        $fmtMoney = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');

        try {
            // Misma fecha que muestra "Caja del día" (input + caja_fecha en JSON). Evita desfasaje UTC vs Argentina.
            $fechaVenta = $this->normalizarFechaCaja($request->input('caja_fecha'));

            $venta = $ventaService->crearVentaBorrador([
                'nombre_cliente' => $request->nombre_cliente,
                'stock_cancha_id' => (int) $request->stock_cancha_id,
                'fecha_venta' => $fechaVenta,
                'hora_venta' => now()->format('H:i:s'),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'venta' => $this->ventaToArray($venta, $fmtMoney),
            'resumen' => $this->cajaResumenAjaxPayload($this->cajaFechaParaResumenDesdeRequest($request)),
        ]);
    }

    public function continuarVenta(Request $request, StockVenta $venta, StockVentaService $ventaService)
    {
        if ($venta->estado_pago !== 'pagado') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Solo se pueden continuar ventas cerradas.'], 422);
            }
            return redirect()->back()->with('error', 'Solo se pueden continuar ventas cerradas.');
        }

        $request->validate([
            'caja_fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);

        try {
            $nueva = $ventaService->crearContinuacionBorrador($venta);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            $fmtMoney = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');
            return response()->json([
                'venta' => $this->ventaToArray($nueva, $fmtMoney),
                'resumen' => $this->cajaResumenAjaxPayload($this->cajaFechaParaResumenDesdeRequest($request)),
            ]);
        }

        return redirect()->route('admincaja')->with('success', 'Ticket de continuación #'.$nueva->id.' creado.');
    }

    public function storeLinea(Request $request, StockVenta $venta, StockVentaService $ventaService)
    {
        $request->validate([
            'stock_producto_id' => 'required|exists:stock_productos,id',
            'cantidad' => 'required|integer|min:1',
            'stock_venta_participante_id' => 'nullable|integer',
            'caja_fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);

        $fmtMoney = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');

        $venta->load('participantes');
        $participanteLineaId = $request->input('stock_venta_participante_id');
        if ($venta->participantes->isNotEmpty()) {
            $request->validate([
                'stock_venta_participante_id' => 'required|integer',
            ]);
            $participanteLineaId = (int) $participanteLineaId;
        } else {
            $participanteLineaId = null;
        }

        try {
            $venta = $ventaService->agregarLineaVenta(
                $venta,
                (int) $request->stock_producto_id,
                (int) $request->cantidad,
                $participanteLineaId
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'venta' => $this->ventaToArray($venta, $fmtMoney),
            'resumen' => $this->cajaResumenAjaxPayload($this->cajaFechaParaResumenDesdeRequest($request)),
        ]);
    }

    public function dividirLinea(Request $request, StockVenta $venta, StockDetalleVenta $detalle, StockVentaService $ventaService)
    {
        if ((int) $detalle->stock_venta_id !== (int) $venta->id) {
            abort(404);
        }

        $data = $request->validate([
            'participantes_ids' => 'required|array|min:1',
            'participantes_ids.*' => 'required|integer',
            'caja_fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);

        $fmtMoney = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');

        try {
            $venta = $ventaService->dividirLineaVenta(
                $venta,
                (int) $detalle->id,
                array_map('intval', $data['participantes_ids'])
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'venta' => $this->ventaToArray($venta, $fmtMoney),
            'resumen' => $this->cajaResumenAjaxPayload($this->cajaFechaParaResumenDesdeRequest($request)),
        ]);
    }

    public function destroyLinea(Request $request, StockVenta $venta, StockDetalleVenta $detalle, StockVentaService $ventaService)
    {
        if ((int) $detalle->stock_venta_id !== (int) $venta->id) {
            abort(404);
        }

        $request->validate([
            'caja_fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);

        $fmtMoney = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');

        try {
            $venta = $ventaService->eliminarLineaVenta($venta, (int) $detalle->id);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'venta' => $this->ventaToArray($venta, $fmtMoney),
            'resumen' => $this->cajaResumenAjaxPayload($this->cajaFechaParaResumenDesdeRequest($request)),
        ]);
    }

    public function destroyVenta(Request $request, StockVenta $venta, StockVentaService $ventaService)
    {
        $request->validate([
            'caja_fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);

        $fechaRedirect = $venta->fecha_venta
            ? \Carbon\Carbon::parse($venta->fecha_venta)->toDateString()
            : now()->toDateString();

        try {
            $ventaService->cancelarVentaBorrador($venta);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Ticket cancelado.',
                'resumen' => $this->cajaResumenAjaxPayload($this->cajaFechaParaResumenDesdeRequest($request)),
            ]);
        }

        return redirect()->route('admincaja', ['fecha' => $fechaRedirect])->with('success', 'Ticket cancelado.');
    }

    public function updateBorrador(Request $request, StockVenta $venta, StockVentaService $ventaService)
    {
        $request->validate([
            'nombre_cliente' => 'required|string|max:100',
        ]);

        try {
            $ventaService->actualizarNombreBorrador($venta, $request->nombre_cliente);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }

    public function storeVenta(Request $request, StockVentaService $ventaService)
    {
        $request->validate([
            'nombre_cliente' => 'required|string|max:100',
            'nombre_turno' => 'nullable|string|max:50',
            'stock_cancha_id' => 'required|exists:stock_canchas,id',
            'fecha_venta' => 'required|date',
            'hora_venta' => 'required|date_format:H:i',
            'metodo_pago' => 'required|in:efectivo,transferencia',
            'estado_pago' => 'required|in:pagado,pendiente',
            'referencia_pago' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:255',
            'lineas' => 'required|array|min:1',
            'lineas.*.stock_producto_id' => 'required|exists:stock_productos,id',
            'lineas.*.cantidad' => 'required|integer|min:1',
        ]);

        $hora = $request->hora_venta;
        if (strlen((string) $hora) === 5) {
            $hora .= ':00';
        }

        $ventaData = [
            'nombre_cliente' => $request->nombre_cliente,
            'nombre_turno' => $request->nombre_turno,
            'stock_cancha_id' => (int) $request->stock_cancha_id,
            'fecha_venta' => $request->fecha_venta,
            'hora_venta' => $hora,
            'precio_total' => 0,
            'metodo_pago' => $request->metodo_pago,
            'estado_pago' => $request->estado_pago,
            'fecha_pago' => $request->estado_pago === 'pagado' ? $request->fecha_venta : null,
            'referencia_pago' => $request->referencia_pago,
            'notas' => $request->notas,
        ];

        try {
            $ventaService->crearVenta($ventaData, $request->lineas);
        } catch (\RuntimeException $e) {
            return redirect()->route('admincaja')->with('error', $e->getMessage());
        }

        return redirect()->route('admincaja')->with('success', 'Venta registrada correctamente.');
    }

    public function showVenta(StockVenta $venta)
    {
        $venta->load(['cancha', 'detalles.producto.categoria', 'detalles.participante', 'participantes', 'pagos.participante', 'pagos.detalle.producto']);

        return view('bahia_padel.admin.caja.show', compact('venta'));
    }

    public function ventaTicketModal(StockVenta $venta)
    {
        $venta->load(['cancha', 'detalles.producto.categoria', 'detalles.participante', 'participantes', 'pagos.participante']);

        $fmtMoney = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');

        return response()->json([
            'ok' => true,
            'titulo' => 'Venta #'.$venta->id,
            'html' => view('bahia_padel.admin.caja._ticket_modal_body', compact('venta', 'fmtMoney'))->render(),
            'url_ver_completo' => route('admincaja.venta.show', $venta),
        ]);
    }

    public function pagoLinea(Request $request, StockVenta $venta, StockDetalleVenta $detalle, StockVentaService $ventaService)
    {
        if ((int) $detalle->stock_venta_id !== (int) $venta->id) {
            abort(404);
        }

        $data = $request->validate([
            'metodo_pago' => 'nullable|in:efectivo,transferencia',
            'referencia_pago' => 'nullable|string|max:100',
            'fecha_pago' => 'nullable|date',
            'notas' => 'nullable|string|max:255',
            'caja_fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);

        $fmtMoney = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');
        $resumenFecha = $this->normalizarFechaCaja($data['caja_fecha'] ?? null);

        try {
            $venta = $ventaService->registrarPagoLinea(
                $venta,
                (int) $detalle->id,
                \Illuminate\Support\Arr::only($data, ['metodo_pago', 'referencia_pago', 'fecha_pago', 'notas'])
            );
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        $msg = $venta->estado_pago === 'pagado'
            ? 'Ticket cerrado: todos los productos fueron cobrados.'
            : 'Pago del producto registrado.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $msg,
                'venta' => $this->ventaToArray($venta, $fmtMoney),
                'ticket_cerrado' => $venta->estado_pago === 'pagado',
                'resumen' => $this->cajaResumenAjaxPayload($resumenFecha),
            ]);
        }

        return redirect()->route('admincaja.venta.show', $venta)->with('success', $msg);
    }

    public function registrarPago(Request $request, StockVenta $venta, StockVentaService $ventaService)
    {
        $data = $request->validate([
            'metodo_pago' => 'nullable|in:efectivo,transferencia',
            'referencia_pago' => 'nullable|string|max:100',
            'fecha_pago' => 'nullable|date',
            'notas' => 'nullable|string|max:255',
            'caja_fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);

        $resumenFecha = $this->normalizarFechaCaja($data['caja_fecha'] ?? null);

        try {
            $pagoData = \Illuminate\Support\Arr::only($data, ['metodo_pago', 'referencia_pago', 'fecha_pago', 'notas']);
            $ventaService->registrarPago($venta, $pagoData);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Pago registrado.',
                'resumen' => $this->cajaResumenAjaxPayload($resumenFecha),
            ]);
        }

        return redirect()->route('admincaja')->with('success', 'Pago registrado.');
    }
}
