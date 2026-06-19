@extends('bahia_padel/admin/plantilla')

@section('title_header','Venta #' . $venta->id)

@section('contenedor')
@php
    use App\Services\StockVentaService;
    $fmtMoney = fn ($n) => '$' . number_format((float) $n, 2, ',', '.');
    $modoGrupo = $venta->participantes && $venta->participantes->isNotEmpty();
    $partsSorted = $modoGrupo ? $venta->participantes->sortBy('slot')->values() : collect();
    $saldoPendiente = StockVentaService::saldoPendienteVenta($venta);
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="container-fluid body_admin">
    <p><a href="{{ route('admincaja') }}" class="btn btn-secondary">&larr; Volver a Caja</a></p>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h5 class="m-0 font-weight-bold text-primary">Datos de la venta</h5></div>
        <div class="card-body row">
            <div class="col-md-6">
                <p><strong>Cliente:</strong> {{ $venta->nombre_cliente }}</p>
                <p><strong>Turno:</strong> {{ $venta->nombre_turno ?: '—' }}</p>
                <p><strong>Cancha:</strong> {{ $venta->cancha?->nombre }}</p>
                <p><strong>Fecha:</strong> {{ $venta->fecha_venta?->format('d/m/Y') }} <strong>Hora:</strong> {{ is_string($venta->hora_venta) ? substr($venta->hora_venta, 0, 5) : $venta->hora_venta }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Total ticket:</strong> {{ $fmtMoney($venta->precio_total) }}</p>
                @if(!$modoGrupo && $venta->estado_pago === 'pendiente')
                    <p><strong>Saldo pendiente:</strong> <span class="text-danger font-weight-bold">{{ $fmtMoney($saldoPendiente) }}</span></p>
                @endif
                <p><strong>Método:</strong> {{ $venta->metodo_pago ?: '—' }}</p>
                <p><strong>Estado:</strong>
                    <span class="badge badge-{{ $venta->estado_pago === 'pagado' ? 'success' : 'warning' }}">{{ $venta->estado_pago }}</span>
                </p>
                @if($venta->fecha_pago)
                    <p><strong>Fecha pago:</strong> {{ $venta->fecha_pago->format('d/m/Y') }}</p>
                @endif
                @if($venta->referencia_pago)
                    <p><strong>Referencia:</strong> {{ $venta->referencia_pago }}</p>
                @endif
                @if($venta->notas)
                    <p><strong>Notas:</strong> {{ $venta->notas }}</p>
                @endif
                @if($modoGrupo)
                    <p class="caja-texto-small mb-0">Ticket <strong>multi-jugador</strong>: el cobro es por jugador; la venta queda pendiente hasta que los cuatro estén pagados (o “sin consumo”).</p>
                @endif
            </div>
            <div class="col-md-12 mt-2">
                @if($venta->estado_pago === 'pagado')
                    <form method="post" action="{{ route('admincaja.venta.continuar', $venta) }}" class="d-inline" onsubmit="return confirm('Se creará un nuevo ticket vinculado a este. ¿Continuar?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary">Agregar más productos</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if($modoGrupo)
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h5 class="m-0 font-weight-bold text-primary">Jugadores</h5></div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead><tr><th>Slot</th><th>Nombre</th><th>Consumido</th><th>Estado</th></tr></thead>
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
    </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h5 class="m-0 font-weight-bold text-primary">Productos</h5></div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        @if($modoGrupo)<th>Jug.</th>@endif
                        <th>Producto</th><th>Cant.</th><th>P. unit.</th><th>Subtotal</th>
                        @if(!$modoGrupo && $venta->estado_pago === 'pendiente')<th class="text-center">Cobro</th>@endif
                        @if(!$modoGrupo)<th>Estado</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalles as $d)
                        @php $lineaPagada = ($d->estado_pago ?? 'pendiente') === 'pagado'; @endphp
                        <tr>
                            @if($modoGrupo)
                                <td>{{ $d->participante ? 'J'.$d->participante->slot : '—' }}</td>
                            @endif
                            <td>{{ $d->producto?->nombre }}</td>
                            <td>{{ $d->cantidad }}</td>
                            <td>{{ $fmtMoney($d->precio_unitario) }}</td>
                            <td>{{ $fmtMoney($d->subtotal) }}</td>
                            @if(!$modoGrupo && $venta->estado_pago === 'pendiente')
                                <td class="text-center text-nowrap">
                                    @if($lineaPagada)
                                        —
                                    @else
                                        <form method="post" action="{{ route('admincaja.venta.linea.pago', [$venta, $d]) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="metodo_pago" value="efectivo">
                                            <button type="submit" class="btn btn-sm btn-success px-2 py-0" title="Cobrar efectivo">E</button>
                                        </form>
                                        <form method="post" action="{{ route('admincaja.venta.linea.pago', [$venta, $d]) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="metodo_pago" value="transferencia">
                                            <button type="submit" class="btn btn-sm btn-info px-2 py-0" title="Cobrar transferencia">T</button>
                                        </form>
                                    @endif
                                </td>
                            @endif
                            @if(!$modoGrupo)
                                <td>
                                    @if($lineaPagada)
                                        <span class="badge badge-success">Pagado</span>
                                    @else
                                        <span class="badge badge-warning">Pendiente</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($venta->pagos->isNotEmpty())
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h5 class="m-0 font-weight-bold text-primary">Historial de pagos</h5></div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        @if($modoGrupo)<th>Jug.</th>@endif
                        @if(!$modoGrupo)<th>Producto</th>@endif
                        <th>Monto</th><th>Método</th><th>Ref.</th><th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->pagos as $p)
                        <tr>
                            <td>{{ $p->fecha_pago?->format('d/m/Y H:i') }}</td>
                            @if($modoGrupo)
                                <td>{{ $p->participante ? 'J'.$p->participante->slot : '—' }}</td>
                            @endif
                            @if(!$modoGrupo)
                                <td>{{ $p->detalle?->producto?->nombre ?? '—' }}</td>
                            @endif
                            <td>{{ $fmtMoney($p->monto_pagado) }}</td>
                            <td>{{ $p->metodo_pago }}</td>
                            <td>{{ $p->referencia_pago }}</td>
                            <td>{{ $p->usuario_responsable }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($venta->estado_pago === 'pendiente')
    <div class="card shadow border-success">
        <div class="card-header bg-light font-weight-bold">Registrar cobro</div>
        <div class="card-body">
            @if($modoGrupo)
                @foreach($partsSorted as $p)
                    @php $subPart = $venta->detalles->where('stock_venta_participante_id', $p->id)->sum('subtotal'); @endphp
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                            <div>
                                <strong>J{{ $p->slot }}</strong> {{ $p->nombre }}
                                <span class="caja-texto-small ml-2">{{ $fmtMoney($subPart) }}</span>
                            </div>
                            @if($p->estado_pago === 'pagado')
                                <span class="badge badge-success">Ya cobrado{{ $p->metodo_pago ? ' ('.$p->metodo_pago.')' : '' }}</span>
                            @endif
                        </div>
                        @if($p->estado_pago !== 'pagado')
                            @if((float) $subPart <= 0)
                                <form method="post" action="{{ route('admincaja.venta.participante.pago', [$venta, $p]) }}" class="d-inline" onsubmit="return confirm('¿Marcar a este jugador como sin consumo?');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary">Sin consumo</button>
                                </form>
                            @else
                                <form method="post" action="{{ route('admincaja.venta.participante.pago', [$venta, $p]) }}" class="mb-0">
                                    @csrf
                                    <div class="form-row align-items-end">
                                        <div class="form-group col-md-4 mb-2">
                                            <label class="caja-label mb-0">Método</label>
                                            <select name="metodo_pago" class="form-control" required>
                                                <option value="efectivo">Efectivo</option>
                                                <option value="transferencia">Transferencia</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4 mb-2">
                                            <label class="caja-label mb-0">Fecha de pago</label>
                                            <input type="date" name="fecha_pago" class="form-control" value="{{ now()->toDateString() }}">
                                        </div>
                                        <div class="form-group col-md-4 mb-2">
                                            <label class="caja-label mb-0">Referencia</label>
                                            <input type="text" name="referencia_pago" class="form-control" placeholder="Opcional">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success">Cobrar {{ $fmtMoney($subPart) }}</button>
                                </form>
                            @endif
                        @endif
                    </div>
                @endforeach
            @else
                @if((float) $saldoPendiente > 0)
                <form method="post" action="{{ route('admincaja.venta.pago', $venta) }}">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Método de pago</label>
                            <select name="metodo_pago" class="form-control">
                                <option value="efectivo" @selected($venta->metodo_pago === 'efectivo')>Efectivo</option>
                                <option value="transferencia" @selected($venta->metodo_pago === 'transferencia')>Transferencia</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Fecha de pago</label>
                            <input type="date" name="fecha_pago" class="form-control" value="{{ now()->toDateString() }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Referencia / comprobante</label>
                            <input type="text" name="referencia_pago" class="form-control" value="{{ $venta->referencia_pago }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notas</label>
                        <input type="text" name="notas" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-success">Cobrar resto ({{ $fmtMoney($saldoPendiente) }})</button>
                </form>
                @else
                    <p class="text-muted mb-0">No hay saldo pendiente.</p>
                @endif
            @endif
            <hr class="my-3">
            <p class="caja-texto-small mb-2">Si no se va a cobrar esta venta, podés cancelarla: se borra el ticket y el stock vuelve a los productos (solo si no hay productos cobrados).</p>
            <form method="post" action="{{ route('admincaja.venta.destroy', $venta) }}" class="d-inline" onsubmit="return confirm('¿Cancelar este ticket? Se eliminará la venta y se devolverá el stock de todos los productos.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Cancelar ticket</button>
            </form>
        </div>
    </div>
    @endif
</div>

@include('bahia_padel.admin.caja._font_size_control')
@endsection
