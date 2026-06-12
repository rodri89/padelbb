<?php

namespace App\Services;

use App\StockDetalleVenta;
use App\StockHistorialPago;
use App\StockMovimientoStock;
use App\StockProducto;
use App\StockCancha;
use App\StockVentaParticipante;
use App\StockVenta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockVentaService
{
    public static function responsable(): ?string
    {
        $u = Auth::user();

        return $u ? (string) ($u->name ?? $u->email ?? 'admin') : 'sistema';
    }

    /** Canchas que abren ticket con 4 jugadores independientes (caja). */
    public static function esCanchaMultiJugador(?StockCancha $cancha): bool
    {
        if ($cancha === null) {
            return false;
        }

        return in_array($cancha->nombre, ['Cancha 1', 'Cancha 2', 'Cancha 3'], true);
    }

    /**
     * Producto "Turno" en categoría Accesorio(s), para cargar 1 unidad por jugador al abrir ticket de cancha.
     */
    public static function resolverProductoTurnoCancha(): ?StockProducto
    {
        $porCat = StockProducto::query()
            ->where('activo', true)
            ->whereRaw('LOWER(TRIM(nombre)) = ?', ['turno'])
            ->whereHas('categoria', function ($q) {
                $q->where('activa', true)
                    ->whereRaw('LOWER(nombre) LIKE ?', ['%accesorio%']);
            })
            ->first();

        if ($porCat !== null) {
            return $porCat;
        }

        return StockProducto::query()
            ->where('activo', true)
            ->whereRaw('LOWER(TRIM(nombre)) = ?', ['turno'])
            ->first();
    }

    public static function ventaEsModoGrupo(StockVenta $venta): bool
    {
        if ($venta->relationLoaded('participantes')) {
            return $venta->participantes->isNotEmpty();
        }

        return StockVentaParticipante::query()->where('stock_venta_id', $venta->id)->exists();
    }

    public static function saldoPendienteVenta(StockVenta $venta): float
    {
        return round((float) StockDetalleVenta::query()
            ->where('stock_venta_id', $venta->id)
            ->where('estado_pago', 'pendiente')
            ->sum('subtotal'), 2);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function registrarPagoDetalleInterno(
        StockVenta $venta,
        StockDetalleVenta $detalle,
        string $metodo,
        \Carbon\Carbon $fechaPago,
        array $data
    ): void {
        $detalle->estado_pago = 'pagado';
        $detalle->save();

        StockHistorialPago::query()->create([
            'stock_venta_id' => $venta->id,
            'stock_venta_participante_id' => $detalle->stock_venta_participante_id,
            'stock_detalle_venta_id' => $detalle->id,
            'monto_pagado' => round((float) $detalle->subtotal, 2),
            'metodo_pago' => $metodo,
            'fecha_pago' => $fechaPago,
            'referencia_pago' => $data['referencia_pago'] ?? null,
            'usuario_responsable' => self::responsable(),
            'notas' => $data['notas'] ?? null,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function cerrarVentaSiSaldoCero(StockVenta $venta, array $data, string $metodo): void
    {
        $pendientes = (int) StockDetalleVenta::query()
            ->where('stock_venta_id', $venta->id)
            ->where('estado_pago', 'pendiente')
            ->count();

        if ($pendientes > 0) {
            return;
        }

        $venta->estado_pago = 'pagado';
        $venta->fecha_pago = isset($data['fecha_pago'])
            ? \Carbon\Carbon::parse($data['fecha_pago'])->toDateString()
            : now()->toDateString();
        $venta->metodo_pago = $metodo;
        if (! empty($data['referencia_pago'])) {
            $venta->referencia_pago = $data['referencia_pago'];
        }
        $venta->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function cerrarVentaSiParticipantesPagados(StockVenta $venta, array $data, string $metodo): void
    {
        $pendientes = (int) StockVentaParticipante::query()
            ->where('stock_venta_id', $venta->id)
            ->where('estado_pago', 'pendiente')
            ->count();

        if ($pendientes > 0) {
            return;
        }

        $venta->estado_pago = 'pagado';
        $venta->fecha_pago = isset($data['fecha_pago'])
            ? \Carbon\Carbon::parse($data['fecha_pago'])->toDateString()
            : now()->toDateString();
        $venta->metodo_pago = $metodo;
        if (! empty($data['referencia_pago'])) {
            $venta->referencia_pago = $data['referencia_pago'];
        }
        $venta->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function sincronizarEstadoGrupoTrasPagoLinea(StockVenta $venta, ?int $participanteId, array $data, string $metodo): void
    {
        if ($participanteId !== null) {
            /** @var StockVentaParticipante|null $participante */
            $participante = StockVentaParticipante::query()
                ->where('stock_venta_id', $venta->id)
                ->where('id', $participanteId)
                ->first();

            if ($participante !== null && $participante->estado_pago !== 'pagado') {
                $lineasPendientes = (int) StockDetalleVenta::query()
                    ->where('stock_venta_id', $venta->id)
                    ->where('stock_venta_participante_id', $participanteId)
                    ->where('estado_pago', 'pendiente')
                    ->count();

                if ($lineasPendientes === 0) {
                    $fechaPago = isset($data['fecha_pago']) ? \Carbon\Carbon::parse($data['fecha_pago']) : now();
                    $participante->estado_pago = 'pagado';
                    $participante->metodo_pago = $metodo;
                    $participante->fecha_pago = $fechaPago->toDateString();
                    $participante->save();
                }
            }
        }

        $this->cerrarVentaSiParticipantesPagados($venta, $data, $metodo);
    }

    /**
     * @param  array<string, mixed>  $ventaData
     * @param  array<int, array{stock_producto_id: int, cantidad: int}>  $lineas
     */
    public function crearVenta(array $ventaData, array $lineas): StockVenta
    {
        return DB::transaction(function () use ($ventaData, $lineas) {
            $venta = StockVenta::query()->create($ventaData);
            $total = 0.0;
            $user = self::responsable();

            foreach ($lineas as $line) {
                $pid = (int) $line['stock_producto_id'];
                $qty = (int) $line['cantidad'];
                if ($qty < 1) {
                    continue;
                }

                /** @var StockProducto $producto */
                $producto = StockProducto::query()->lockForUpdate()->findOrFail($pid);
                if ($producto->stock_actual < $qty) {
                    throw new \RuntimeException(
                        "Stock insuficiente para \"{$producto->nombre}\" (disponible: {$producto->stock_actual}, pedido: {$qty})."
                    );
                }
                if (! $producto->activo) {
                    throw new \RuntimeException("El producto \"{$producto->nombre}\" no está activo.");
                }

                $precio = (float) $producto->precio_unitario;
                $subtotal = round($precio * $qty, 2);

                StockDetalleVenta::query()->create([
                    'stock_venta_id' => $venta->id,
                    'stock_producto_id' => $producto->id,
                    'cantidad' => $qty,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotal,
                    'created_at' => now(),
                ]);

                $anterior = $producto->stock_actual;
                $nueva = $anterior - $qty;
                $producto->stock_actual = $nueva;
                $producto->save();

                StockMovimientoStock::query()->create([
                    'stock_producto_id' => $producto->id,
                    'tipo_movimiento' => 'salida',
                    'cantidad' => $qty,
                    'cantidad_anterior' => $anterior,
                    'cantidad_nueva' => $nueva,
                    'motivo' => 'Venta #'.$venta->id,
                    'usuario_responsable' => $user,
                    'created_at' => now(),
                ]);

                $total += $subtotal;
            }

            if ($total <= 0) {
                throw new \RuntimeException('La venta debe incluir al menos un producto con cantidad válida.');
            }

            $venta->precio_total = round($total, 2);
            $venta->save();

            if ($venta->estado_pago === 'pagado') {
                StockHistorialPago::query()->create([
                    'stock_venta_id' => $venta->id,
                    'monto_pagado' => $venta->precio_total,
                    'metodo_pago' => $venta->metodo_pago,
                    'fecha_pago' => now(),
                    'referencia_pago' => $venta->referencia_pago,
                    'usuario_responsable' => $user,
                    'notas' => 'Pago al registrar la venta',
                    'created_at' => now(),
                ]);
                if (! $venta->fecha_pago) {
                    $venta->fecha_pago = now()->toDateString();
                    $venta->save();
                }
            }

            return $venta->fresh(['detalles.producto', 'cancha']);
        });
    }

    /**
     * Borrador en caja: venta pendiente sin líneas, total 0. El stock se descuenta al agregar cada línea.
     *
     * @param  array<string, mixed>  $ventaData
     */
    public function crearVentaBorrador(array $ventaData): StockVenta
    {
        return DB::transaction(function () use ($ventaData) {
            $hora = $ventaData['hora_venta'] ?? now()->format('H:i:s');
            if (strlen((string) $hora) === 5) {
                $hora .= ':00';
            }

            $venta = StockVenta::query()->create([
                'nombre_cliente' => $ventaData['nombre_cliente'],
                'nombre_turno' => $ventaData['nombre_turno'] ?? null,
                'stock_cancha_id' => (int) $ventaData['stock_cancha_id'],
                'fecha_venta' => $ventaData['fecha_venta'] ?? now()->toDateString(),
                'hora_venta' => $hora,
                'precio_total' => 0,
                'metodo_pago' => 'efectivo',
                'estado_pago' => 'pendiente',
                'fecha_pago' => null,
                'referencia_pago' => null,
                'notas' => $ventaData['notas'] ?? null,
            ]);

            $cancha = StockCancha::query()->find((int) $ventaData['stock_cancha_id']);
            if (self::esCanchaMultiJugador($cancha)) {
                $nombreCliente = (string) $ventaData['nombre_cliente'];
                $now = now();
                for ($slot = 1; $slot <= 4; $slot++) {
                    StockVentaParticipante::query()->create([
                        'stock_venta_id' => $venta->id,
                        'slot' => $slot,
                        'nombre' => $slot === 1 ? $nombreCliente : 'Jugador '.$slot,
                        'jugador_id' => null,
                        'estado_pago' => 'pendiente',
                        'metodo_pago' => null,
                        'fecha_pago' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $productoTurno = self::resolverProductoTurnoCancha();
                if ($productoTurno === null) {
                    throw new \RuntimeException(
                        'No se encontró el producto activo "Turno" (categoría Accesorios). Crealo en Stock para usar tickets de cancha.'
                    );
                }

                $venta = $venta->fresh(['participantes']);
                foreach ($venta->participantes->sortBy('slot')->values() as $participante) {
                    $venta = $this->agregarLineaVenta(
                        $venta,
                        (int) $productoTurno->id,
                        1,
                        (int) $participante->id
                    );
                }
            }

            return $venta->fresh(['detalles.producto', 'cancha', 'participantes']);
        });
    }

    public function crearContinuacionBorrador(StockVenta $ventaPadre): StockVenta
    {
        return DB::transaction(function () use ($ventaPadre) {
            $ventaPadre = StockVenta::query()->lockForUpdate()->findOrFail($ventaPadre->id);
            if ($ventaPadre->estado_pago !== 'pagado') {
                throw new \RuntimeException('Solo se pueden continuar ventas cerradas.');
            }

            $nueva = StockVenta::query()->create([
                'stock_venta_id_padre' => $ventaPadre->id,
                'nombre_cliente' => $ventaPadre->nombre_cliente,
                'nombre_turno' => $ventaPadre->nombre_turno,
                'stock_cancha_id' => $ventaPadre->stock_cancha_id,
                'fecha_venta' => now()->toDateString(),
                'hora_venta' => now()->format('H:i:s'),
                'precio_total' => 0,
                'metodo_pago' => 'efectivo',
                'estado_pago' => 'pendiente',
                'fecha_pago' => null,
                'referencia_pago' => null,
                'notas' => null,
            ]);

            $cancha = StockCancha::query()->find((int) $ventaPadre->stock_cancha_id);
            if (self::esCanchaMultiJugador($cancha)) {
                $now = now();
                $participantesPadre = $ventaPadre->participantes->sortBy('slot')->values();
                foreach ($participantesPadre as $pPadre) {
                    StockVentaParticipante::query()->create([
                        'stock_venta_id' => $nueva->id,
                        'slot' => $pPadre->slot,
                        'nombre' => $pPadre->nombre,
                        'jugador_id' => $pPadre->jugador_id,
                        'estado_pago' => 'pendiente',
                        'metodo_pago' => null,
                        'fecha_pago' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            return $nueva->fresh(['detalles.producto', 'cancha', 'participantes']);
        });
    }

    public function actualizarNombreBorrador(StockVenta $venta, string $nombreCliente): void
    {
        DB::transaction(function () use ($venta, $nombreCliente) {
            $venta = StockVenta::query()->lockForUpdate()->findOrFail($venta->id);
            if ($venta->estado_pago !== 'pendiente') {
                throw new \RuntimeException('No se puede editar una venta ya cobrada.');
            }
            $venta->nombre_cliente = $nombreCliente;
            $venta->save();

            $p1 = StockVentaParticipante::query()
                ->where('stock_venta_id', $venta->id)
                ->where('slot', 1)
                ->first();
            if ($p1 !== null && $p1->estado_pago === 'pendiente') {
                $p1->nombre = $nombreCliente;
                $p1->save();
            }
        });
    }

    public function actualizarParticipante(StockVenta $venta, int $participanteId, string $nombre, ?int $jugadorId, bool $actualizarJugadorId = false): void
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new \RuntimeException('El nombre no puede estar vacío.');
        }

        DB::transaction(function () use ($venta, $participanteId, $nombre, $jugadorId, $actualizarJugadorId) {
            $venta = StockVenta::query()->lockForUpdate()->findOrFail($venta->id);
            if ($venta->estado_pago !== 'pendiente') {
                throw new \RuntimeException('No se puede editar una venta ya cobrada.');
            }

            /** @var StockVentaParticipante $p */
            $p = StockVentaParticipante::query()
                ->where('stock_venta_id', $venta->id)
                ->where('id', $participanteId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($p->estado_pago !== 'pendiente') {
                throw new \RuntimeException('Este jugador ya pagó; no se puede editar.');
            }

            $p->nombre = mb_substr($nombre, 0, 100);
            if ($actualizarJugadorId) {
                $p->jugador_id = $jugadorId;
            }
            $p->save();

            if ($p->slot === 1) {
                $venta->nombre_cliente = $p->nombre;
                $venta->save();
            }
        });
    }

    public function agregarLineaVenta(StockVenta $venta, int $productoId, int $cantidad, ?int $stockVentaParticipanteId = null): StockVenta
    {
        if ($cantidad < 1) {
            throw new \RuntimeException('La cantidad debe ser al menos 1.');
        }

        return DB::transaction(function () use ($venta, $productoId, $cantidad, $stockVentaParticipanteId) {
            $venta = StockVenta::query()->lockForUpdate()->findOrFail($venta->id);
            if ($venta->estado_pago !== 'pendiente') {
                throw new \RuntimeException('La venta ya no admite productos.');
            }

            $esGrupo = StockVentaParticipante::query()->where('stock_venta_id', $venta->id)->exists();
            $participanteFk = null;

            if ($esGrupo) {
                if ($stockVentaParticipanteId === null || $stockVentaParticipanteId < 1) {
                    throw new \RuntimeException('Elegí el jugador para cargar el producto.');
                }

                /** @var StockVentaParticipante $participante */
                $participante = StockVentaParticipante::query()
                    ->where('stock_venta_id', $venta->id)
                    ->where('id', $stockVentaParticipanteId)
                    ->lockForUpdate()
                    ->first();

                if ($participante === null) {
                    throw new \RuntimeException('Participante inválido.');
                }
                if ($participante->estado_pago !== 'pendiente') {
                    throw new \RuntimeException('Ese jugador ya pagó; no se pueden agregar productos.');
                }
                $participanteFk = $participante->id;
            } elseif ($stockVentaParticipanteId !== null) {
                throw new \RuntimeException('Este ticket no usa reparto por jugador.');
            }

            /** @var StockProducto $producto */
            $producto = StockProducto::query()->lockForUpdate()->findOrFail($productoId);
            if ($producto->stock_actual < $cantidad) {
                throw new \RuntimeException(
                    "Stock insuficiente para \"{$producto->nombre}\" (disponible: {$producto->stock_actual}, pedido: {$cantidad})."
                );
            }
            if (! $producto->activo) {
                throw new \RuntimeException("El producto \"{$producto->nombre}\" no está activo.");
            }

            $user = self::responsable();
            $precio = (float) $producto->precio_unitario;
            $subtotal = round($precio * $cantidad, 2);

            StockDetalleVenta::query()->create([
                'stock_venta_id' => $venta->id,
                'stock_venta_participante_id' => $participanteFk,
                'stock_producto_id' => $producto->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'subtotal' => $subtotal,
                'created_at' => now(),
            ]);

            $anterior = $producto->stock_actual;
            $nueva = $anterior - $cantidad;
            $producto->stock_actual = $nueva;
            $producto->save();

            StockMovimientoStock::query()->create([
                'stock_producto_id' => $producto->id,
                'tipo_movimiento' => 'salida',
                'cantidad' => $cantidad,
                'cantidad_anterior' => $anterior,
                'cantidad_nueva' => $nueva,
                'motivo' => 'Venta #'.$venta->id,
                'usuario_responsable' => $user,
                'created_at' => now(),
            ]);

            $venta->precio_total = round((float) $venta->precio_total + $subtotal, 2);
            $venta->save();

            return $venta->fresh(['detalles.producto', 'cancha', 'participantes']);
        });
    }

    public function dividirLineaVenta(StockVenta $venta, int $detalleId, array $participantesIds): StockVenta
    {
        if (empty($participantesIds)) {
            throw new \RuntimeException('Elegí al menos un jugador para dividir.');
        }

        return DB::transaction(function () use ($venta, $detalleId, $participantesIds) {
            $venta = StockVenta::query()->lockForUpdate()->findOrFail($venta->id);
            if ($venta->estado_pago !== 'pendiente') {
                throw new \RuntimeException('La venta ya no admite cambios.');
            }

            /** @var StockDetalleVenta|null $detalle */
            $detalle = StockDetalleVenta::query()
                ->where('stock_venta_id', $venta->id)
                ->where('id', $detalleId)
                ->lockForUpdate()
                ->first();

            if (! $detalle) {
                throw new \RuntimeException('Línea no encontrada.');
            }

            if ($detalle->estado_pago === 'pagado') {
                throw new \RuntimeException('No se puede dividir un producto que ya fue cobrado.');
            }

            $cantidadParticipantes = count($participantesIds);

            $participantes = StockVentaParticipante::query()
                ->where('stock_venta_id', $venta->id)
                ->whereIn('id', $participantesIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($participantes->count() !== $cantidadParticipantes) {
                throw new \RuntimeException('Uno o más participantes no pertenecen a esta venta.');
            }

            foreach ($participantes as $part) {
                if ($part->estado_pago === 'pagado') {
                    throw new \RuntimeException('No se puede dividir con un jugador que ya pagó.');
                }
            }

            $subtotalOriginal = (float) $detalle->subtotal;
            $precioUnitario = (float) $detalle->precio_unitario;
            $cantidadOriginal = (int) $detalle->cantidad;
            $productoId = (int) $detalle->stock_producto_id;

            // Eliminar detalle original sin devolver stock (ya salió físicamente)
            $detalle->delete();

            $baseSubtotal = round($subtotalOriginal / $cantidadParticipantes, 2);
            $sumaSubtotales = 0.0;
            $now = now();

            foreach ($participantesIds as $idx => $participanteId) {
                $esUltimo = ($idx === $cantidadParticipantes - 1);
                $sub = $esUltimo
                    ? round($subtotalOriginal - $sumaSubtotales, 2)
                    : $baseSubtotal;
                $sumaSubtotales += $sub;

                StockDetalleVenta::query()->create([
                    'stock_venta_id' => $venta->id,
                    'stock_venta_participante_id' => (int) $participanteId,
                    'stock_producto_id' => $productoId,
                    'cantidad' => $cantidadOriginal, // cada uno ve la cantidad original (representa la porción)
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $sub,
                    'es_division' => true,
                    'created_at' => $now,
                ]);
            }

            // Asegurar que el total de la venta siga siendo consistente
            $nuevoTotal = (float) StockDetalleVenta::query()
                ->where('stock_venta_id', $venta->id)
                ->sum('subtotal');
            $venta->precio_total = round($nuevoTotal, 2);
            $venta->save();

            return $venta->fresh(['detalles.producto', 'cancha', 'participantes']);
        });
    }

    public function eliminarLineaVenta(StockVenta $venta, int $detalleId): StockVenta
    {
        return DB::transaction(function () use ($venta, $detalleId) {
            $venta = StockVenta::query()->lockForUpdate()->findOrFail($venta->id);
            if ($venta->estado_pago !== 'pendiente') {
                throw new \RuntimeException('La venta ya no admite cambios.');
            }

            /** @var StockDetalleVenta|null $detalle */
            $detalle = StockDetalleVenta::query()
                ->where('stock_venta_id', $venta->id)
                ->where('id', $detalleId)
                ->lockForUpdate()
                ->first();

            if (! $detalle) {
                throw new \RuntimeException('Línea no encontrada.');
            }

            if ($detalle->estado_pago === 'pagado') {
                throw new \RuntimeException('No se puede quitar una línea que ya fue cobrada.');
            }

            if ($detalle->stock_venta_participante_id !== null) {
                /** @var StockVentaParticipante|null $part */
                $part = StockVentaParticipante::query()
                    ->where('id', $detalle->stock_venta_participante_id)
                    ->lockForUpdate()
                    ->first();
                if ($part !== null && $part->estado_pago === 'pagado') {
                    throw new \RuntimeException('No se puede quitar una línea de un jugador que ya pagó.');
                }
            }

            $subtotal = (float) $detalle->subtotal;

            // Solo devolver stock si NO es una línea de división (el stock ya se manejó al dividir)
            if (! $detalle->es_division) {
                $user = self::responsable();
                /** @var StockProducto $producto */
                $producto = StockProducto::query()->lockForUpdate()->findOrFail($detalle->stock_producto_id);
                $qty = (int) $detalle->cantidad;

                $anterior = $producto->stock_actual;
                $nueva = $anterior + $qty;
                $producto->stock_actual = $nueva;
                $producto->save();

                StockMovimientoStock::query()->create([
                    'stock_producto_id' => $producto->id,
                    'tipo_movimiento' => 'entrada',
                    'cantidad' => $qty,
                    'cantidad_anterior' => $anterior,
                    'cantidad_nueva' => $nueva,
                    'motivo' => 'Anula línea venta #'.$venta->id,
                    'usuario_responsable' => $user,
                    'created_at' => now(),
                ]);
            }

            $detalle->delete();
            $venta->precio_total = max(0, round((float) $venta->precio_total - $subtotal, 2));
            $venta->save();

            return $venta->fresh(['detalles.producto', 'cancha', 'participantes']);
        });
    }

    /**
     * Cancela un ticket de caja (borrador): solo ventas pendientes de cobro.
     * Devuelve el stock de todas las líneas y elimina la venta.
     */
    public function cancelarVentaBorrador(StockVenta $venta): void
    {
        DB::transaction(function () use ($venta) {
            $venta = StockVenta::query()->lockForUpdate()->findOrFail($venta->id);
            if ($venta->estado_pago !== 'pendiente') {
                throw new \RuntimeException('Solo se pueden cancelar ventas pendientes de cobro.');
            }

            if (StockVentaParticipante::query()->where('stock_venta_id', $venta->id)->where('estado_pago', 'pagado')->exists()) {
                throw new \RuntimeException('No se puede cancelar: ya hay jugadores que pagaron.');
            }

            if (StockDetalleVenta::query()->where('stock_venta_id', $venta->id)->where('estado_pago', 'pagado')->exists()) {
                throw new \RuntimeException('No se puede cancelar: ya hay productos cobrados en este ticket.');
            }

            $user = self::responsable();
            $detalles = StockDetalleVenta::query()
                ->where('stock_venta_id', $venta->id)
                ->lockForUpdate()
                ->get();

            foreach ($detalles as $detalle) {
                // Solo devolver stock si NO es línea de división
                if (! $detalle->es_division) {
                    /** @var StockProducto $producto */
                    $producto = StockProducto::query()->lockForUpdate()->findOrFail($detalle->stock_producto_id);
                    $qty = (int) $detalle->cantidad;

                    $anterior = $producto->stock_actual;
                    $nueva = $anterior + $qty;
                    $producto->stock_actual = $nueva;
                    $producto->save();

                    StockMovimientoStock::query()->create([
                        'stock_producto_id' => $producto->id,
                        'tipo_movimiento' => 'entrada',
                        'cantidad' => $qty,
                        'cantidad_anterior' => $anterior,
                        'cantidad_nueva' => $nueva,
                        'motivo' => 'Cancelación venta #'.$venta->id,
                        'usuario_responsable' => $user,
                        'created_at' => now(),
                    ]);
                }

                $detalle->delete();
            }

            $venta->delete();
        });
    }

    public function registrarPagoParticipante(StockVenta $venta, int $participanteId, array $data): StockVenta
    {
        return DB::transaction(function () use ($venta, $participanteId, $data) {
            $venta = StockVenta::query()->lockForUpdate()->findOrFail($venta->id);
            if ($venta->estado_pago === 'pagado') {
                throw new \RuntimeException('La venta ya está cerrada.');
            }

            /** @var StockVentaParticipante $participante */
            $participante = StockVentaParticipante::query()
                ->where('stock_venta_id', $venta->id)
                ->where('id', $participanteId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($participante->estado_pago === 'pagado') {
                throw new \RuntimeException('Este jugador ya figura como pagado.');
            }

            $metodo = $data['metodo_pago'] ?? $venta->metodo_pago;
            if (! in_array($metodo, ['efectivo', 'transferencia'], true)) {
                $metodo = 'efectivo';
            }

            $fechaPago = isset($data['fecha_pago']) ? \Carbon\Carbon::parse($data['fecha_pago']) : now();

            $detallesPendientes = StockDetalleVenta::query()
                ->where('stock_venta_id', $venta->id)
                ->where('stock_venta_participante_id', $participante->id)
                ->where('estado_pago', 'pendiente')
                ->lockForUpdate()
                ->get();

            foreach ($detallesPendientes as $detalle) {
                $this->registrarPagoDetalleInterno($venta, $detalle, $metodo, $fechaPago, $data);
            }

            $participante->estado_pago = 'pagado';
            $participante->metodo_pago = $metodo;
            $participante->fecha_pago = $fechaPago->toDateString();
            $participante->save();

            $pendientes = (int) StockVentaParticipante::query()
                ->where('stock_venta_id', $venta->id)
                ->where('estado_pago', 'pendiente')
                ->count();

            if ($pendientes === 0) {
                $venta->estado_pago = 'pagado';
                $venta->fecha_pago = now()->toDateString();
                $venta->metodo_pago = $metodo;
                if (! empty($data['referencia_pago'])) {
                    $venta->referencia_pago = $data['referencia_pago'];
                }
                $venta->save();
            }

            return $venta->fresh(['detalles.producto', 'cancha', 'participantes']);
        });
    }

    public function registrarPagoLinea(StockVenta $venta, int $detalleId, array $data): StockVenta
    {
        return DB::transaction(function () use ($venta, $detalleId, $data) {
            $venta = StockVenta::query()->lockForUpdate()->findOrFail($venta->id);
            if ($venta->estado_pago === 'pagado') {
                throw new \RuntimeException('La venta ya está cerrada.');
            }

            /** @var StockDetalleVenta|null $detalle */
            $detalle = StockDetalleVenta::query()
                ->where('stock_venta_id', $venta->id)
                ->where('id', $detalleId)
                ->lockForUpdate()
                ->first();

            if (! $detalle) {
                throw new \RuntimeException('Línea no encontrada.');
            }
            if ($detalle->estado_pago === 'pagado') {
                throw new \RuntimeException('Este producto ya fue cobrado.');
            }

            if ($detalle->stock_venta_participante_id !== null) {
                /** @var StockVentaParticipante|null $partLinea */
                $partLinea = StockVentaParticipante::query()
                    ->where('id', $detalle->stock_venta_participante_id)
                    ->lockForUpdate()
                    ->first();
                if ($partLinea !== null && $partLinea->estado_pago === 'pagado') {
                    throw new \RuntimeException('Este jugador ya figura como pagado.');
                }
            }

            $metodo = $data['metodo_pago'] ?? $venta->metodo_pago;
            if (! in_array($metodo, ['efectivo', 'transferencia'], true)) {
                $metodo = 'efectivo';
            }

            $fechaPago = isset($data['fecha_pago']) ? \Carbon\Carbon::parse($data['fecha_pago']) : now();

            $this->registrarPagoDetalleInterno($venta, $detalle, $metodo, $fechaPago, $data);

            $esGrupo = StockVentaParticipante::query()->where('stock_venta_id', $venta->id)->exists();
            if ($esGrupo) {
                $this->sincronizarEstadoGrupoTrasPagoLinea(
                    $venta,
                    $detalle->stock_venta_participante_id !== null ? (int) $detalle->stock_venta_participante_id : null,
                    $data,
                    $metodo
                );
            } else {
                $this->cerrarVentaSiSaldoCero($venta, $data, $metodo);
            }

            return $venta->fresh(['detalles.producto', 'cancha', 'participantes']);
        });
    }

    public function registrarPago(StockVenta $venta, array $data): void
    {
        DB::transaction(function () use ($venta, $data) {
            $venta = StockVenta::query()->lockForUpdate()->findOrFail($venta->id);
            if ($venta->estado_pago === 'pagado') {
                throw new \RuntimeException('La venta ya está marcada como pagada.');
            }
            if (StockVentaParticipante::query()->where('stock_venta_id', $venta->id)->exists()) {
                throw new \RuntimeException('Esta venta se cobra por jugador (efectivo / transferencia en cada uno).');
            }

            $saldo = self::saldoPendienteVenta($venta);
            if ($saldo <= 0) {
                throw new \RuntimeException('No hay saldo pendiente para cobrar.');
            }

            $metodo = $data['metodo_pago'] ?? $venta->metodo_pago;
            if (! in_array($metodo, ['efectivo', 'transferencia'], true)) {
                $metodo = 'efectivo';
            }

            $fechaPago = isset($data['fecha_pago']) ? \Carbon\Carbon::parse($data['fecha_pago']) : now();

            $detallesPendientes = StockDetalleVenta::query()
                ->where('stock_venta_id', $venta->id)
                ->where('estado_pago', 'pendiente')
                ->lockForUpdate()
                ->get();

            foreach ($detallesPendientes as $detalle) {
                $this->registrarPagoDetalleInterno($venta, $detalle, $metodo, $fechaPago, $data);
            }

            $venta->estado_pago = 'pagado';
            $venta->fecha_pago = $fechaPago->toDateString();
            $venta->metodo_pago = $metodo;
            if (! empty($data['referencia_pago'])) {
                $venta->referencia_pago = $data['referencia_pago'];
            }
            $venta->save();
        });
    }
}
