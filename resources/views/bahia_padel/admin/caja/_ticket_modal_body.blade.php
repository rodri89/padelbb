@php
    $modoGrupo = $venta->participantes && $venta->participantes->isNotEmpty();
    $partsSorted = $modoGrupo ? $venta->participantes->sortBy('slot')->values() : collect();
@endphp
<div class="text-dark small">
    <div class="row mb-3">
        <div class="col-md-6">
            <p class="mb-1"><strong>Cliente:</strong> {{ $venta->nombre_cliente }}</p>
            <p class="mb-1"><strong>Turno:</strong> {{ $venta->nombre_turno ?: '—' }}</p>
            <p class="mb-1"><strong>Cancha:</strong> {{ $venta->cancha?->nombre ?? '—' }}</p>
            <p class="mb-0"><strong>Fecha:</strong> {{ $venta->fecha_venta?->format('d/m/Y') }} · <strong>Hora:</strong> {{ is_string($venta->hora_venta) ? substr($venta->hora_venta, 0, 5) : $venta->hora_venta }}</p>
        </div>
        <div class="col-md-6">
            <p class="mb-1"><strong>Total:</strong> {{ $fmtMoney($venta->precio_total) }}</p>
            <p class="mb-1"><strong>Método:</strong> {{ $venta->metodo_pago ?: '—' }}</p>
            <p class="mb-1"><strong>Estado:</strong>
                <span class="badge badge-{{ $venta->estado_pago === 'pagado' ? 'success' : 'warning' }}">{{ $venta->estado_pago }}</span>
            </p>
            @if($venta->fecha_pago)
                <p class="mb-0"><strong>Fecha pago:</strong> {{ $venta->fecha_pago->format('d/m/Y') }}</p>
            @endif
        </div>
    </div>

    @if($modoGrupo)
        <h6 class="font-weight-bold border-bottom pb-1 mb-2">Jugadores</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered mb-0">
                <thead class="thead-light"><tr><th>Slot</th><th>Nombre</th><th>Consumido</th><th>Estado</th></tr></thead>
                <tbody>
                    @foreach($partsSorted as $p)
                        @php $subPart = $venta->detalles->where('stock_venta_participante_id', $p->id)->sum('subtotal'); @endphp
                        <tr>
                            <td>J{{ $p->slot }}</td>
                            <td>{{ $p->nombre }}</td>
                            <td>{{ $fmtMoney($subPart) }}</td>
                            <td>
                                @if($p->estado_pago === 'pagado')
                                    <span class="badge badge-success">Pagado{{ $p->metodo_pago ? ' ('.$p->metodo_pago.')' : '' }}</span>
                                @else
                                    <span class="badge badge-warning">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h6 class="font-weight-bold border-bottom pb-1 mb-2">Productos</h6>
    <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    @if($modoGrupo)<th>Jug.</th>@endif
                    <th>Producto</th><th>Cant.</th><th>P. unit.</th><th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($venta->detalles as $d)
                    <tr>
                        @if($modoGrupo)
                            <td>{{ $d->participante ? 'J'.$d->participante->slot : '—' }}</td>
                        @endif
                        <td>{{ $d->producto?->nombre }}</td>
                        <td>{{ $d->cantidad }}</td>
                        <td>{{ $fmtMoney($d->precio_unitario) }}</td>
                        <td>{{ $fmtMoney($d->subtotal) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $modoGrupo ? 5 : 4 }}" class="text-center text-muted">Sin líneas</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($venta->pagos->isNotEmpty())
        <h6 class="font-weight-bold border-bottom pb-1 mb-2">Historial de pagos</h6>
        <div class="table-responsive mb-0">
            <table class="table table-sm table-bordered mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Fecha</th>
                        @if($modoGrupo)<th>Jug.</th>@endif
                        <th>Monto</th><th>Método</th><th>Ref.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->pagos as $pg)
                        <tr>
                            <td>{{ $pg->fecha_pago?->format('d/m/Y H:i') }}</td>
                            @if($modoGrupo)
                                <td>{{ $pg->participante ? 'J'.$pg->participante->slot : '—' }}</td>
                            @endif
                            <td>{{ $fmtMoney($pg->monto_pagado) }}</td>
                            <td>{{ $pg->metodo_pago }}</td>
                            <td>{{ $pg->referencia_pago ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
