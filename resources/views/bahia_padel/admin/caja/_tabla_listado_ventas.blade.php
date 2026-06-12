<div class="table-responsive">
    @php
        $mostrarAccionesVerCobrar = $mostrarAccionesVerCobrar ?? true;
        $mostrarVerModal = $mostrarVerModal ?? false;
        $colAcciones = ($mostrarAccionesVerCobrar || $mostrarVerModal);
    @endphp
    <table class="table table-sm mb-0">
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
                    <td class="text-right">{{ $fmtMoney($v->precio_total) }}</td>
                    <td>{{ $v->metodo_pago }}</td>
                    <td>
                        <span class="badge badge-{{ $v->estado_pago === 'pagado' ? 'success' : 'warning' }}">{{ $v->estado_pago }}</span>
                    </td>
                    @if($mostrarAccionesVerCobrar)
                    <td class="text-center text-nowrap">
                        <a href="{{ route('admincaja.venta.show', $v) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                        @if($v->estado_pago === 'pendiente' && (float) $v->precio_total > 0)
                            <a href="{{ route('admincaja.venta.show', $v) }}" class="btn btn-sm btn-primary ml-1">Cobrar</a>
                        @endif
                    </td>
                    @elseif($mostrarVerModal)
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-caja-ver-ticket-modal" data-venta-id="{{ $v->id }}">Ver</button>
                    </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $colAcciones ? 8 : 7 }}" class="text-center text-muted small py-4">No hay registros para este listado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
