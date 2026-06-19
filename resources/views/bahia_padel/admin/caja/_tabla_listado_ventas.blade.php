<div class="table-responsive">
    @php
        $mostrarAccionesVerCobrar = $mostrarAccionesVerCobrar ?? true;
        $mostrarVerModal = $mostrarVerModal ?? false;
        $colAcciones = ($mostrarAccionesVerCobrar || $mostrarVerModal);
    @endphp
    <table class="table table-sm mb-0 caja-tabla-listado">
        <thead class="thead-light">
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Cancha</th>
                <th>Fecha</th>
                <th class="text-right">Total</th>
                <th>Método</th>
                <th>Estado</th>
                @if($colAcciones)
                <th class="text-center" style="width:1%">Acciones</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($ventas as $v)
                @php
                    $saldoPendiente = $v->relationLoaded('detalles')
                        ? (float) $v->detalles->where('estado_pago', 'pendiente')->sum('subtotal')
                        : (float) $v->precio_total;
                @endphp
                <tr>
                    <td>#{{ $v->id }}</td>
                    <td>{{ $v->nombre_cliente }}</td>
                    <td>{{ $v->cancha ? $v->cancha->nombre : '—' }}</td>
                    <td>
                        @if($v->fecha_venta)
                            {{ \Illuminate\Support\Carbon::parse($v->fecha_venta)->format('d/m/Y') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right">
                        @if($v->estado_pago === 'pendiente' && $saldoPendiente > 0 && $saldoPendiente < (float) $v->precio_total)
                            <span class="text-muted small">{{ $fmtMoney($v->precio_total) }}</span>
                            <span class="text-danger font-weight-bold">{{ $fmtMoney($saldoPendiente) }}</span>
                        @else
                            {{ $fmtMoney($v->precio_total) }}
                        @endif
                    </td>
                    <td>{{ $v->metodo_pago }}</td>
                    <td>
                        <span class="badge badge-{{ $v->estado_pago === 'pagado' ? 'success' : 'warning' }}">{{ $v->estado_pago }}</span>
                    </td>
                    @if($mostrarAccionesVerCobrar)
                    <td class="text-center text-nowrap">
                        <a href="{{ route('admincaja.venta.show', $v) }}" class="btn btn-outline-primary">Ver</a>
                        @if($v->estado_pago === 'pendiente' && $saldoPendiente > 0)
                            <a href="{{ route('admincaja.venta.show', $v) }}" class="btn btn-primary ml-1">Cobrar</a>
                        @endif
                    </td>
                    @elseif($mostrarVerModal)
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-outline-primary btn-caja-ver-ticket-modal" data-venta-id="{{ $v->id }}">Ver</button>
                    </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $colAcciones ? 8 : 7 }}" class="text-center caja-texto-small py-4">No hay registros para este listado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
