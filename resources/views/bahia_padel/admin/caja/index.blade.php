@extends('bahia_padel/admin/plantilla')

@section('title_header','Caja')

@section('contenedor')
<style>
/* Accesibilidad / legibilidad caja */
.caja-stat-label { font-size: 0.95rem; letter-spacing: 0.03em; }
.caja-stat-valor { font-size: 1.25rem; font-weight: 700; }
.caja-stat-hint { font-size: 0.9rem; color: #555; }
.caja-label { font-size: 1rem; font-weight: 700; }
.caja-texto { font-size: 1rem; }
.caja-texto-small { font-size: 0.9rem; color: #555; }
.ticket-cat-btn { border-width: 2px; transition: transform .12s ease, box-shadow .12s ease; width: 44px; height: 44px; font-size: 0.85rem !important; }
.ticket-cat-btn:hover { transform: scale(1.06); box-shadow: 0 2px 6px rgba(78,115,223,.25); }
.ticket-cat-btn.active { border-color: #4e73df; }
.ticket-card-panel { display: none; }
.ticket-card-panel.is-open { display: block; }
.caja-stat-trigger { cursor: pointer; transition: transform .12s ease, box-shadow .12s ease; }
.caja-stat-trigger:hover { transform: translateY(-1px); box-shadow: 0 0.35rem 0.75rem rgba(0,0,0,.12) !important; }
.badge-caja-jugador { font-size: 0.95rem; padding: 0.35em 0.55em; min-width: 3.8em; display: inline-block; text-align: center; }
.ticket-grupo-tabs .btn { padding: 0.5rem 0.75rem; font-size: 1rem; }
.ticket-producto-dropdown .px-3:hover { background-color: #f8f9fa; }
.ticket-producto-autocomplete input[disabled] { background-color: #e9ecef; }
.ticket-producto-dropdown { background-color: #fff; }
.ticket-card .card-body { color: #212529; }
/* Dark mode complementos */
body.dark-mode .caja-texto-small { color: #b0b0b0; }
body.dark-mode .caja-stat-hint { color: #b0b0b0; }
body.dark-mode .ticket-producto-dropdown { background-color: #2d2d2d !important; color: #e0e0e0 !important; border-color: #3d3d3d !important; }
body.dark-mode .ticket-producto-dropdown .px-3:hover { background-color: #3d3d3d; }
body.dark-mode .ticket-card .card-body { color: #e0e0e0; }
body.dark-mode .ticket-card .text-muted { color: #b0b0b0 !important; }

/* Grilla de turnos */
.turno-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.turno-cancha-col { display: flex; flex-direction: column; gap: 0.75rem; }
.turno-cancha-title { font-weight: 700; font-size: 1.1rem; text-align: center; color: #4e73df; margin-bottom: 0.25rem; }
.turno-celda { border: 1px solid #dee2e6; border-radius: 0.5rem; padding: 0.75rem; display: flex; flex-direction: column; gap: 0.4rem; transition: box-shadow .15s ease; }
.turno-celda:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.turno-celda.libre { background: #f8f9fa; }
.turno-celda.fijo { background: #e8f0fe; border-color: #aecbfb; }
.turno-celda.vendido { background: #fff3cd; border-color: #ffdf7e; }
.turno-celda.pagado { background: #d4edda; border-color: #a3d9b1; }
.turno-hora { font-weight: 700; font-size: 1.05rem; color: #212529; }
.turno-nombre { font-size: 0.95rem; color: #495057; }
.turno-badge { align-self: flex-start; font-size: 0.8rem; }
.btn-turno-accion { align-self: stretch; font-size: 0.9rem; padding: 0.3rem 0.5rem; }
@media (max-width: 768px) {
  .turno-grid { grid-template-columns: 1fr; }
}

/* Tamaño base cómodo para toda la sección */
#form-caja-fecha, .ticket-card, #caja-resumen-detalle, #panel-nuevo-ticket { font-size: 1.05rem; }
.ticket-card .form-control, .ticket-card .input-group-text, .ticket-card select { font-size: 1rem; min-height: 38px; }
.ticket-card table th, .ticket-card table td { font-size: 1rem; padding: 0.5rem 0.6rem; }
.ticket-card .btn { font-size: 1rem; }
.ticket-card .btn-sm { font-size: 0.95rem; padding: 0.35rem 0.65rem; }
.ticket-card .h5, .ticket-card h5 { font-size: 1.35rem; }
.ticket-total { font-size: 1.4rem !important; }
</style>
@php
    $fmtMoney = fn ($n) => '$' . number_format((float) $n, 2, ',', '.');
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
@endif

<div class="container-fluid body_admin">
    <div class="row mb-3 align-items-end">
        <div class="col-lg-8 col-md-7 mb-2 mb-md-0">
            <form method="get" action="{{ route('admincaja') }}" class="form-inline flex-wrap align-items-center" id="form-caja-fecha">
                <label for="caja-fecha-consulta" class="mb-0 mr-2 font-weight-bold text-gray-800" style="font-size:1.05rem;">Caja del día</label>
                <input type="date"
                    name="fecha"
                    id="caja-fecha-consulta"
                    class="form-control"
                    value="{{ $fechaCaja }}"
                    max="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                    onchange="if (this.form) this.form.submit();">
                <button type="submit" class="btn btn-primary ml-2">Ver</button>
                @if(!$fechaCajaEsHoy)
                    <a href="{{ route('admincaja') }}" class="btn btn-outline-secondary ml-2">Volver a hoy</a>
                    <span class="caja-texto-small ml-2">Consulta: {{ $fechaCajaLabel }} (solo lectura: no podés abrir tickets nuevos).</span>
                @else
                    <span class="caja-texto-small ml-2 d-none d-md-inline">{{ $fechaCajaLabel }}</span>
                @endif
            </form>
        </div>
    </div>

    {{-- Estado de la caja --}}
    <div class="row mb-3">
        <div class="col-12">
            @if(!$cajaDelDia)
                @if($fechaCajaEsHoy)
                <div class="card shadow border-left-warning">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between py-3">
                        <div class="mb-2 mb-md-0">
                            <div class="font-weight-bold text-warning" style="font-size:1.1rem;">Caja no abierta</div>
                            <div class="small text-muted">Para empezar a operar, abrí la caja ingresando el fondo inicial.</div>
                        </div>
                        <form method="post" action="{{ route('admincaja.apertura') }}" class="form-inline">
                            @csrf
                            <input type="hidden" name="fecha" value="{{ $fechaCaja }}">
                            <div class="input-group mr-2">
                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                <input type="number" name="fondo_inicial" class="form-control" placeholder="Fondo inicial" min="0" step="0.01" required style="max-width:140px;">
                            </div>
                            <button type="submit" class="btn btn-warning font-weight-bold">Abrir caja</button>
                        </form>
                    </div>
                </div>
                @else
                <div class="alert alert-light border mb-0">
                    <span class="text-muted small">No hay registro de caja para el {{ $fechaCajaLabel }}.</span>
                </div>
                @endif
            @elseif($cajaDelDia->estado === 'abierta')
                <div class="card shadow border-left-success">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between py-3">
                        <div class="d-flex flex-wrap align-items-center mb-2 mb-md-0">
                            <div class="mr-4">
                                <div class="small text-muted">Fondo inicial</div>
                                <div class="font-weight-bold text-success" style="font-size:1.2rem;">{{ $fmtMoney($cajaDelDia->fondo_inicial) }}</div>
                            </div>
                            <div class="mr-4">
                                <div class="small text-muted">Efectivo esperado</div>
                                <div class="font-weight-bold text-primary" style="font-size:1.2rem;">{{ $fmtMoney($efectivoEsperadoCaja) }}</div>
                            </div>
                            <div>
                                <div class="small text-muted">Transferencia</div>
                                <div class="font-weight-bold text-info" style="font-size:1.2rem;">{{ $fmtMoney($statsHoy['transferencia']) }}</div>
                            </div>
                        </div>
                        @if($fechaCajaEsHoy)
                        <button type="button" class="btn btn-outline-danger font-weight-bold" data-toggle="modal" data-target="#modal-cierre-caja">Cerrar caja</button>
                        @endif
                    </div>
                </div>
            @else
                <div class="card shadow border-left-secondary">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between py-3">
                        <div class="d-flex flex-wrap align-items-center mb-2 mb-md-0">
                            <div class="mr-4">
                                <div class="small text-muted">Fondo inicial</div>
                                <div class="font-weight-bold text-success" style="font-size:1.2rem;">{{ $fmtMoney($cajaDelDia->fondo_inicial) }}</div>
                            </div>
                            <div class="mr-4">
                                <div class="small text-muted">Efectivo esperado</div>
                                <div class="font-weight-bold text-primary" style="font-size:1.2rem;">{{ $fmtMoney($efectivoEsperadoCaja) }}</div>
                            </div>
                            <div class="mr-4">
                                <div class="small text-muted">Efectivo real</div>
                                <div class="font-weight-bold" style="font-size:1.2rem;">{{ $fmtMoney($cajaDelDia->efectivo_real) }}</div>
                            </div>
                            <div>
                                <div class="small text-muted">Diferencia</div>
                                @if((float)$cajaDelDia->diferencia === 0.0)
                                    <div class="font-weight-bold text-success" style="font-size:1.2rem;">$0,00</div>
                                @elseif((float)$cajaDelDia->diferencia > 0)
                                    <div class="font-weight-bold text-success" style="font-size:1.2rem;">Sobrante {{ $fmtMoney($cajaDelDia->diferencia) }}</div>
                                @else
                                    <div class="font-weight-bold text-danger" style="font-size:1.2rem;">Faltante {{ $fmtMoney(abs((float)$cajaDelDia->diferencia)) }}</div>
                                @endif
                            </div>
                        </div>
                        <span class="badge badge-secondary">Caja cerrada</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-md-2 col-sm-6 mb-2">
            <div class="card border-left-primary shadow h-100 py-2 caja-stat-trigger" data-resumen="ventas-hoy" data-titulo="Ventas del {{ $fechaCajaLabel }} (detalle)">
                <div class="card-body">
                    <div class="caja-stat-label font-weight-bold text-primary text-uppercase mb-1">Ventas del día</div>
                    <div class="caja-stat-valor mb-0" id="caja-stat-transacciones">{{ $statsHoy['transacciones'] }} mov.</div>
                    <span class="caja-stat-hint">Tocá para listado</span>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <div class="card border-left-success shadow h-100 py-2 caja-stat-trigger" data-resumen="total-hoy" data-titulo="Total facturado el {{ $fechaCajaLabel }} (todas las ventas del día)">
                <div class="card-body">
                    <div class="caja-stat-label font-weight-bold text-success text-uppercase mb-1">Total del día</div>
                    <div class="caja-stat-valor mb-0" id="caja-stat-monto-total">{{ $fmtMoney($statsHoy['monto_total']) }}</div>
                    <span class="caja-stat-hint">Tocá para listado</span>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <div class="card border-left-info shadow h-100 py-2 caja-stat-trigger" data-resumen="efectivo-hoy" data-titulo="Ventas en efectivo ({{ $fechaCajaLabel }})">
                <div class="card-body">
                    <div class="caja-stat-label font-weight-bold text-info text-uppercase mb-1">Efectivo</div>
                    <div class="caja-stat-valor mb-0" id="caja-stat-efectivo">{{ $fmtMoney($statsHoy['efectivo']) }}</div>
                    <span class="caja-stat-hint">Tocá para listado</span>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <div class="card border-left-secondary shadow h-100 py-2 caja-stat-trigger" data-resumen="transfer-hoy" data-titulo="Ventas por transferencia ({{ $fechaCajaLabel }})">
                <div class="card-body">
                    <div class="caja-stat-label font-weight-bold text-secondary text-uppercase mb-1">Transfer.</div>
                    <div class="caja-stat-valor mb-0" id="caja-stat-transferencia">{{ $fmtMoney($statsHoy['transferencia']) }}</div>
                    <span class="caja-stat-hint">Tocá para listado</span>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <div class="card border-left-success shadow h-100 py-2 caja-stat-trigger" data-resumen="cobrado-hoy" data-titulo="Cobrado el {{ $fechaCajaLabel }} (ventas pagadas)">
                <div class="card-body">
                    <div class="caja-stat-label font-weight-bold text-success text-uppercase mb-1">Cobrado</div>
                    <div class="caja-stat-valor mb-0" id="caja-stat-pagado">{{ $fmtMoney($statsHoy['pagado']) }}</div>
                    <span class="caja-stat-hint">Tocá para listado</span>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <div class="card border-left-warning shadow h-100 py-2 caja-stat-trigger" data-resumen="pendientes-dia" data-titulo="Pendientes con fecha de venta {{ $fechaCajaLabel }}">
                <div class="card-body">
                    <div class="caja-stat-label font-weight-bold text-warning text-uppercase mb-1">Pendiente (día)</div>
                    <div class="caja-stat-valor mb-0" id="caja-stat-pendiente-dia">{{ $fmtMoney($statsHoy['pendiente']) }}</div>
                    <span class="caja-stat-hint">Tocá para listado</span>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-left-danger shadow h-100 py-2 caja-stat-trigger" data-resumen="pendientes-saldo" data-titulo="Ventas pendientes de cobro (con saldo, todas las fechas)">
                <div class="card-body py-2 d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <div class="caja-stat-label font-weight-bold text-danger text-uppercase mb-1">Pendientes de cobro (saldo)</div>
                        <div class="caja-texto-small mb-0">Incluye deudas de días anteriores. Tocá para ver el listado y Ver / Cobrar.</div>
                    </div>
                    <div class="h5 mb-0 text-danger font-weight-bold" id="caja-stat-pendientes-saldo">{{ $pendientes->count() }} venta(s)</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4 d-none" id="caja-resumen-detalle">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center py-2 bg-white border-bottom">
                    <span class="font-weight-bold text-primary m-0" id="caja-resumen-titulo"></span>
                    <button type="button" class="btn btn-outline-secondary" id="caja-resumen-cerrar">Cerrar</button>
                </div>
                <div class="card-body p-0">
                    <div class="resumen-tabla d-none" id="resumen-data-ventas-hoy">
                        @include('bahia_padel.admin.caja._tabla_listado_ventas', ['ventas' => $listaVentasHoy, 'fmtMoney' => $fmtMoney, 'mostrarAccionesVerCobrar' => false, 'mostrarVerModal' => true])
                    </div>
                    <div class="resumen-tabla d-none" id="resumen-data-total-hoy">
                        @include('bahia_padel.admin.caja._tabla_listado_ventas', ['ventas' => $listaVentasHoy, 'fmtMoney' => $fmtMoney, 'mostrarAccionesVerCobrar' => false, 'mostrarVerModal' => true])
                    </div>
                    <div class="resumen-tabla d-none" id="resumen-data-efectivo-hoy">
                        @include('bahia_padel.admin.caja._tabla_listado_ventas', ['ventas' => $listaEfectivoHoy, 'fmtMoney' => $fmtMoney, 'mostrarAccionesVerCobrar' => false, 'mostrarVerModal' => true])
                    </div>
                    <div class="resumen-tabla d-none" id="resumen-data-transfer-hoy">
                        @include('bahia_padel.admin.caja._tabla_listado_ventas', ['ventas' => $listaTransferHoy, 'fmtMoney' => $fmtMoney, 'mostrarAccionesVerCobrar' => false, 'mostrarVerModal' => true])
                    </div>
                    <div class="resumen-tabla d-none" id="resumen-data-cobrado-hoy">
                        @include('bahia_padel.admin.caja._tabla_listado_ventas', ['ventas' => $listaCobradoHoy, 'fmtMoney' => $fmtMoney, 'mostrarAccionesVerCobrar' => false, 'mostrarVerModal' => true])
                    </div>
                    <div class="resumen-tabla d-none" id="resumen-data-pendientes-dia">
                        @include('bahia_padel.admin.caja._tabla_listado_ventas', ['ventas' => $listaPendienteHoy, 'fmtMoney' => $fmtMoney, 'mostrarAccionesVerCobrar' => false, 'mostrarVerModal' => true])
                    </div>
                    <div class="resumen-tabla d-none" id="resumen-data-pendientes-saldo">
                        @include('bahia_padel.admin.caja._tabla_listado_ventas', ['ventas' => $pendientes, 'fmtMoney' => $fmtMoney, 'mostrarAccionesVerCobrar' => false, 'mostrarVerModal' => true])
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if($fechaCajaEsHoy)
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-center justify-content-md-start" style="gap:10px;">
                @foreach(['Cancha 1', 'Cancha 2', 'Cancha 3', 'Particular'] as $etiq)
                    <button type="button" class="btn btn-outline-primary btn-cancha-caja px-4 py-2" style="min-width:140px;" data-cancha-id="{{ $canchasCajaIds[$etiq] ?? '' }}">
                        {{ $etiq }}
                    </button>
                @endforeach
                <button type="button" class="btn btn-outline-dark btn-cancha-caja btn-torneo-caja px-4 py-2" style="min-width:140px;" data-es-torneo="1">
                    Torneo / Americano
                </button>
            </div>
        </div>
    </div>

    <div class="row mb-4 d-none" id="row-panel-nuevo-ticket">
        <div class="col-lg-10 col-xl-8">
            <div class="card shadow border-left-info" id="panel-nuevo-ticket">
                <div class="card-header py-2 font-weight-bold text-primary">Nueva venta (se guarda en el servidor al abrir el ticket)</div>
                <div class="card-body">
                    @if($productosVenta->isEmpty())
                        <p class="text-warning mb-0">No hay productos con stock. Cargá en <a href="{{ route('adminstock') }}">Stock</a> para poder vender.</p>
                    @else
                    <p class="caja-texto-small mb-2" id="nuevo-ticket-ayuda">Escribí el nombre del cliente (podés incluir horario u otra referencia en el mismo campo) y tocá <strong>Abrir ticket</strong>.</p>
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-6 mb-2">
                            <label class="caja-label mb-1" id="nuevo-ticket-label-nombre">Cliente</label>
                            <input type="text" id="nuevo-nombre-cliente" class="form-control" placeholder="Ej. Rodri · Cancha 1 17:00" autocomplete="off" disabled>
                        </div>
                        <div class="form-group col-md-6 mb-2">
                            <label class="caja-texto-small mb-1">Cancha elegida</label>
                            <input type="text" id="nuevo-cancha-label" class="form-control bg-light" readonly placeholder="Tocá Cancha 1–3 o Particular" value="">
                        </div>
                    </div>
                    <input type="hidden" id="nuevo-stock-cancha-id" value="">
                    <button type="button" class="btn btn-primary" id="btn-abrir-ticket" disabled>Abrir ticket</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(!empty($turnosDelDia) && $fechaCajaEsHoy)
    <div class="row mb-4" id="turnos-grid-container">
        <div class="col-12">
            <h5 class="font-weight-bold text-gray-800 mb-3">Turnos del día — {{ $fechaCajaLabel }}</h5>
            <div class="turno-grid">
                @foreach($turnosDelDia as $canchaNombre => $turnosCancha)
                <div class="turno-cancha-col">
                    <div class="turno-cancha-title">{{ $canchaNombre }}</div>
                    @foreach($horariosTurno as $hora)
                        @php
                            $t = $turnosCancha[$hora] ?? ['tipo'=>'libre','nombre'=>null,'venta_id'=>null,'cancha_id'=>null];
                        @endphp
                        <div class="turno-celda {{ $t['tipo'] }}"
                             data-cancha-id="{{ $t['cancha_id'] }}"
                             data-hora="{{ $hora }}"
                             data-tipo="{{ $t['tipo'] }}"
                             data-nombre="{{ $t['nombre'] ?? '' }}"
                             data-venta-id="{{ $t['venta_id'] ?? '' }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="turno-hora">{{ $hora }}</span>
                                @if($t['tipo'] === 'libre')
                                    <span class="badge badge-light turno-badge">Libre</span>
                                @elseif($t['tipo'] === 'fijo')
                                    <span class="badge badge-primary turno-badge">Fijo</span>
                                @elseif($t['tipo'] === 'vendido')
                                    <span class="badge badge-warning turno-badge">Pendiente</span>
                                @elseif($t['tipo'] === 'pagado')
                                    <span class="badge badge-success turno-badge">Pagado</span>
                                @endif
                            </div>
                            @if($t['nombre'])
                                <div class="turno-nombre">{{ $t['nombre'] }}</div>
                            @else
                                <div class="turno-nombre text-muted">—</div>
                            @endif
                            @if($fechaCajaEsHoy)
                                @if($t['tipo'] === 'libre')
                                    <button type="button" class="btn btn-outline-primary btn-turno-accion btn-turno-vender">Vender turno</button>
                                @elseif($t['tipo'] === 'fijo')
                                    <button type="button" class="btn btn-primary btn-turno-accion btn-turno-vender">Cobrar / Abrir</button>
                                @elseif(($t['tipo'] === 'vendido' || $t['tipo'] === 'pagado') && $t['venta_id'])
                                    <button type="button" class="btn btn-outline-secondary btn-turno-accion btn-turno-ver" data-venta-id="{{ $t['venta_id'] }}">Ver ticket</button>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if($torneosAbiertos->isNotEmpty())
    <div class="row mb-3">
        <div class="col-12">
            <h5 class="font-weight-bold text-gray-800 mb-2">Torneos / Americanos en curso</h5>
            @foreach($torneosAbiertos as $venta)
            <div class="col-12 mb-3 px-0">
                <div class="card shadow border-left-dark">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between py-3">
                        <div class="d-flex align-items-center mb-2 mb-md-0">
                            <i class="fas fa-trophy text-dark mr-3" style="font-size:1.5rem;"></i>
                            <div>
                                <div class="font-weight-bold text-dark" style="font-size:1.1rem;">{{ $venta->nombre_cliente }}</div>
                                <div class="small text-muted">Torneo / Americano — {{ $venta->participantes->count() }} jugador(es) — Creado {{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-primary mr-3" style="font-size:1rem;">{{ $fmtMoney($venta->precio_total) }}</span>
                            <a href="{{ route('admincaja.torneo.show', $venta) }}" class="btn btn-primary">Gestionar torneo</a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-wrap align-items-baseline justify-content-between mb-2">
                <h5 class="font-weight-bold text-gray-800 mb-0">Tickets en curso — {{ $fechaCajaLabel }}</h5>
                <button type="button" class="btn btn-link p-0 text-primary shadow-none" id="btn-toggle-lista-tickets" aria-expanded="false">Desplegar todos</button>
            </div>
            <div class="row" id="lista-tickets-abiertos">
                @foreach($ticketsAbiertos as $venta)
                @if($venta->es_torneo)
                {{-- Obsoleto: los torneos ahora van en su propia sección arriba --}}
                @else
                <div class="col-lg-4 col-md-6 mb-3 d-flex">
                <div class="card mb-0 ticket-card shadow flex-fill w-100" data-venta-id="{{ $venta->id }}">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center ticket-card-header-toggle" style="cursor:pointer">
                        <div>
                            <strong class="ticket-card-nombre">{{ $venta->nombre_cliente }}</strong>
                            <span class="text-muted small ml-2 ticket-card-cancha-meta">{{ $venta->cancha?->nombre }}</span>
                            @if($venta->padre)
                                <span class="badge badge-secondary ml-1">continuación #{{ $venta->padre->id }}</span>
                            @endif
                        </div>
                        <div>
                            <span class="badge badge-primary ticket-card-total">{{ $fmtMoney($venta->precio_total) }}</span>
                            <span class="caja-texto-small ml-1">#{{ $venta->id }}</span>
                        </div>
                    </div>
                    <div id="ticket-collapse-{{ $venta->id }}" class="ticket-card-panel">
                        <div class="card-body pt-3">
                            @include('bahia_padel.admin.caja._ticket_body', ['venta' => $venta, 'categoriasVenta' => $categoriasVenta, 'fmtMoney' => $fmtMoney, 'productosVenta' => $productosVenta])
                        </div>
                    </div>
                </div>
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

@if($cajaDelDia && $cajaDelDia->estado === 'abierta' && $fechaCajaEsHoy)
<div class="modal fade" id="modal-cierre-caja" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title">Cierre de caja — {{ $fechaCajaLabel }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="post" action="{{ route('admincaja.cierre') }}">
                @csrf
                <input type="hidden" name="fecha" value="{{ $fechaCaja }}">
                <div class="modal-body py-3">
                    <div class="alert alert-info small mb-3">
                        Efectivo esperado: <strong>{{ $fmtMoney($efectivoEsperadoCaja) }}</strong> (fondo inicial + pagos en efectivo del día)
                    </div>
                    <div class="form-group mb-2">
                        <label class="font-weight-bold">Efectivo real en caja ($)</label>
                        <input type="number" name="efectivo_real" class="form-control" min="0" step="0.01" required autofocus>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2" placeholder="Opcional…"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-danger font-weight-bold">Confirmar cierre</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="modal-buscar-jugador-caja" tabindex="-1" role="dialog" aria-labelledby="modal-buscar-jugador-caja-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="modal-buscar-jugador-caja-titulo">Buscar jugador del club</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body py-2">
                <input type="text" id="caja-jugador-buscar-input" class="form-control mb-2" placeholder="Filtrar por nombre o apellido…" autocomplete="off">
                <div id="caja-jugador-lista" class="list-group list-group-flush" style="max-height:50vh;overflow:auto;"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-caja-ver-ticket" tabindex="-1" role="dialog" aria-labelledby="modal-caja-ver-ticket-titulo" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title text-primary" id="modal-caja-ver-ticket-titulo">Ticket</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body py-3" id="modal-caja-ver-ticket-body">
                <p class="text-muted small mb-0">Cargando…</p>
            </div>
            <div class="modal-footer py-2">
                <a href="#" id="modal-caja-ver-ticket-link-completo" class="btn btn-outline-primary" target="_blank" rel="noopener">Abrir página completa</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-dividir-linea" tabindex="-1" role="dialog" aria-labelledby="modal-dividir-linea-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="modal-dividir-linea-titulo">Dividir producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body py-3">
                <p class="caja-texto-small">Seleccioná con quién querés dividir este producto. El costo se repartirá en partes iguales.</p>
                <div id="dividir-linea-opciones"></div>
                <input type="hidden" id="dividir-linea-detalle-id">
                <input type="hidden" id="dividir-linea-venta-id">
                <input type="hidden" id="dividir-linea-card-id">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-primary" id="btn-confirmar-dividir">Confirmar división</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@php
    $cajaCategoriasJson = $categoriasVenta->map(function ($c) {
        return [
            'id' => $c->id,
            'nombre' => $c->nombre,
            'abbr' => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($c->nombre, 0, 2)),
        ];
    })->values();
    $cajaProductosJson = $productosVenta->map(function ($p) use ($fmtMoney) {
        return [
            'id' => $p->id,
            'categoria_id' => $p->stock_categoria_id,
            'label' => $p->nombre.' (stock '.$p->stock_actual.') — '.$fmtMoney($p->precio_unitario),
        ];
    })->values();
@endphp
<script>
(function() {
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.getAttribute('content') : '';

    /** Rutas respecto al host actual (evita APP_URL distinto y rutas relativas mal resueltas). */
    function adminCajaBasePath() {
        var path = (window.location.pathname || '').replace(/\/$/, '');
        if (/\/admin_caja(\/|$)/.test(path)) {
            return path.replace(/\/admin_caja.*$/, '/admin_caja');
        }
        return path + '/admin_caja';
    }
    function borradorUrl() {
        return adminCajaBasePath() + '/venta/borrador';
    }
    function lineaUrl(ventaId) {
        return adminCajaBasePath() + '/venta/' + ventaId + '/linea';
    }
    function lineaDestroyUrl(ventaId, detalleId) {
        return adminCajaBasePath() + '/venta/' + ventaId + '/linea/' + detalleId;
    }
    function dividirLineaUrl(ventaId, detalleId) {
        return adminCajaBasePath() + '/venta/' + ventaId + '/linea/' + detalleId + '/dividir';
    }
    function updateUrl(ventaId) {
        return adminCajaBasePath() + '/venta/' + ventaId;
    }
    function pagoUrl(ventaId) {
        return adminCajaBasePath() + '/venta/' + ventaId + '/pago';
    }
    function lineaPagoUrl(ventaId, detalleId) {
        return adminCajaBasePath() + '/venta/' + ventaId + '/linea/' + detalleId + '/pago';
    }
    function participanteStoreUrl(ventaId) {
        return adminCajaBasePath() + '/venta/' + ventaId + '/participante';
    }
    function inscripcionTodosUrl(ventaId) {
        return adminCajaBasePath() + '/venta/' + ventaId + '/inscripcion-todos';
    }
    function ventaDestroyUrl(ventaId) {
        return adminCajaBasePath() + '/venta/' + ventaId;
    }
    function jugadoresCajaUrl() {
        return adminCajaBasePath() + '/jugadores';
    }
    function ventaTicketModalUrl(ventaId) {
        return adminCajaBasePath() + '/venta/' + ventaId + '/ticket-modal';
    }
    function participantePatchUrl(ventaId, participanteId) {
        return adminCajaBasePath() + '/venta/' + ventaId + '/participante/' + participanteId;
    }
    function participantePagoUrl(ventaId, participanteId) {
        return adminCajaBasePath() + '/venta/' + ventaId + '/participante/' + participanteId + '/pago';
    }
    function resumenCajaUrl() {
        return adminCajaBasePath() + '/resumen';
    }
    /** Refresca números y HTML de todas las tablas del panel (misma fecha que el datepicker). */
    function fetchCajaResumenAplicar() {
        var fechaEl = document.getElementById('caja-fecha-consulta');
        var q = '';
        if (fechaEl && fechaEl.value) q = '?fecha=' + encodeURIComponent(fechaEl.value);
        fetch(resumenCajaUrl() + q, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(parseFetchResponse).then(function(x) {
            if (!x.ok || !x.j) return;
            applyCajaResumen(x.j);
        }).catch(function() {});
    }

    function parseFetchResponse(r) {
        return r.text().then(function(text) {
            var j = null;
            if (text) {
                try {
                    j = JSON.parse(text);
                } catch (e) {
                    j = { message: 'Respuesta inválida (HTTP ' + r.status + '). ' + text.replace(/<[^>]+>/g, ' ').trim().slice(0, 200) };
                }
            } else {
                j = { message: 'Sin respuesta (HTTP ' + r.status + ')' };
            }
            return { ok: r.ok, status: r.status, j: j };
        });
    }

    window.CAJA_CATEGORIAS = @json($cajaCategoriasJson);
    window.CAJA_PRODUCTOS = @json($cajaProductosJson);

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function productosPorCategoria(categoriaId) {
        return (window.CAJA_PRODUCTOS || []).filter(function(p) {
            return String(p.categoria_id) === String(categoriaId);
        });
    }

    function renderProductoDropdown(dropdown, productos, onPick) {
        if (!dropdown) return;
        dropdown.innerHTML = '';
        if (!productos || !productos.length) {
            dropdown.innerHTML = '<div class="px-3 py-2 text-muted small">Sin productos</div>';
            dropdown.classList.remove('d-none');
            return;
        }
        productos.forEach(function(p) {
            var div = document.createElement('div');
            div.className = 'px-3 py-2 cursor-pointer caja-texto hover-bg-light';
            div.style.cursor = 'pointer';
            div.textContent = p.label;
            div.addEventListener('click', function() {
                onPick(p);
                dropdown.classList.add('d-none');
            });
            dropdown.appendChild(div);
        });
        dropdown.classList.remove('d-none');
    }

    function fillProductoAutocomplete(inner, categoriaId) {
        var search = inner.querySelector('.ticket-producto-search');
        var hidden = inner.querySelector('.ticket-producto-id');
        var dropdown = inner.querySelector('.ticket-producto-dropdown');
        if (!search || !hidden || !dropdown) return;
        search.value = '';
        hidden.value = '';
        search.disabled = !categoriaId;
        if (!categoriaId) {
            search.placeholder = 'Elegí una categoría…';
            dropdown.classList.add('d-none');
            return;
        }
        search.placeholder = 'Buscá producto…';
        search.dataset.categoriaId = categoriaId;
        var productos = productosPorCategoria(categoriaId);
        renderProductoDropdown(dropdown, productos, function(p) {
            search.value = p.label;
            hidden.value = p.id;
        });
        search.focus();
    }

    function categoriasPillsHtml() {
        var h = '';
        (window.CAJA_CATEGORIAS || []).forEach(function(c) {
            h += '<button type="button" class="btn btn-sm btn-outline-secondary ticket-cat-btn rounded-circle p-0 d-inline-flex align-items-center justify-content-center text-nowrap" '
                + 'style="width:44px;height:44px;font-size:0.85rem;font-weight:700;letter-spacing:-0.02em;" '
                + 'data-categoria-id="' + c.id + '" title="' + escapeHtml(c.nombre) + '">'
                + escapeHtml(c.abbr) + '</button>';
        });
        return h;
    }

    function wireProductPicker(inner) {
        if (!inner || inner._pickerWired) return;
        inner._pickerWired = true;
        var pills = inner.querySelectorAll('.ticket-cat-btn');
        var search = inner.querySelector('.ticket-producto-search');
        var hidden = inner.querySelector('.ticket-producto-id');
        var dropdown = inner.querySelector('.ticket-producto-dropdown');
        if (!pills.length || !search || !hidden || !dropdown) return;
        pills.forEach(function(btn) {
            btn.addEventListener('click', function() {
                pills.forEach(function(b) {
                    b.classList.remove('active', 'btn-primary');
                    b.classList.add('btn-outline-secondary');
                });
                btn.classList.add('active', 'btn-primary');
                btn.classList.remove('btn-outline-secondary');
                var cid = btn.getAttribute('data-categoria-id');
                fillProductoAutocomplete(inner, cid);
            });
        });
        if (search && !search._wiredSearch) {
            search._wiredSearch = true;
            search.addEventListener('input', function() {
                var cid = search.dataset.categoriaId;
                var q = (search.value || '').toLowerCase().trim();
                var productos = productosPorCategoria(cid).filter(function(p) {
                    return !q || p.label.toLowerCase().indexOf(q) >= 0;
                });
                renderProductoDropdown(dropdown, productos, function(p) {
                    search.value = p.label;
                    hidden.value = p.id;
                });
            });
            search.addEventListener('focus', function() {
                var cid = search.dataset.categoriaId;
                if (!cid) return;
                var q = (search.value || '').toLowerCase().trim();
                var productos = productosPorCategoria(cid).filter(function(p) {
                    return !q || p.label.toLowerCase().indexOf(q) >= 0;
                });
                renderProductoDropdown(dropdown, productos, function(p) {
                    search.value = p.label;
                    hidden.value = p.id;
                });
            });
            document.addEventListener('click', function(e) {
                if (!search.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('d-none');
                }
            });
        }
    }

    function jsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        };
    }

    /** Alinea el resumen de caja con la fecha consultada en pantalla (#caja-fecha-consulta). */
    function mergeCajaFecha(body) {
        var fechaEl = document.getElementById('caja-fecha-consulta');
        if (fechaEl && fechaEl.value) body.caja_fecha = fechaEl.value;
        return body;
    }

    function htmlLineaEstado(d) {
        if (d.estado_pago === 'pagado') {
            return '<span class="badge badge-success">✓ Pagado</span>';
        }
        return '<span class="badge badge-warning">Pendiente</span>';
    }

    function htmlLineaAccionesSimple(d) {
        if (d.estado_pago === 'pagado') {
            return '<span class="text-muted small">—</span>';
        }
        return '<button type="button" class="btn btn-sm btn-outline-danger btn-ticket-remove-linea px-2 py-0 font-weight-bold" data-detalle-id="' + d.id + '" title="Quitar línea">−</button>'
            + '<button type="button" class="btn btn-sm btn-outline-success btn-linea-pago px-2 py-0 font-weight-bold ml-1" data-detalle-id="' + d.id + '" data-metodo="efectivo" title="Pagar en efectivo">$E</button>'
            + '<button type="button" class="btn btn-sm btn-outline-primary btn-linea-pago px-2 py-0 font-weight-bold ml-1" data-detalle-id="' + d.id + '" data-metodo="transferencia" title="Pagar por transferencia">$T</button>';
    }

    function htmlLineaAccionesGrupo(d) {
        if (d.estado_pago === 'pagado') {
            return '<span class="text-muted small">—</span>';
        }
        return '<button type="button" class="btn btn-sm btn-outline-danger btn-ticket-remove-linea px-2 py-0 font-weight-bold" data-detalle-id="' + d.id + '" title="Quitar línea">−</button>'
            + '<button type="button" class="btn btn-sm btn-outline-info btn-ticket-dividir-linea px-2 py-0 font-weight-bold ml-1" data-detalle-id="' + d.id + '" data-participante-id="' + (d.stock_venta_participante_id || '') + '" title="Dividir con otros jugadores">÷</button>'
            + '<button type="button" class="btn btn-sm btn-outline-success btn-linea-pago px-2 py-0 font-weight-bold ml-1" data-detalle-id="' + d.id + '" data-metodo="efectivo" title="Pagar en efectivo">$E</button>'
            + '<button type="button" class="btn btn-sm btn-outline-primary btn-linea-pago px-2 py-0 font-weight-bold ml-1" data-detalle-id="' + d.id + '" data-metodo="transferencia" title="Pagar por transferencia">$T</button>';
    }

    function refreshLinesTbody(tbody, detalles) {
        tbody.innerHTML = '';
        detalles.forEach(function(d) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>' + escapeHtml(d.producto_nombre || '') + '</td>'
                + '<td class="text-center">' + d.cantidad + '</td>'
                + '<td class="text-right">' + escapeHtml(d.subtotal_fmt) + '</td>'
                + '<td class="text-center">' + htmlLineaEstado(d) + '</td>'
                + '<td class="text-center p-1 align-middle">' + htmlLineaAccionesSimple(d) + '</td>';
            tbody.appendChild(tr);
        });
    }

    function syncPayButtons(cardRoot, precioTotal) {
        var ok = precioTotal > 0;
        cardRoot.querySelectorAll('.btn-ticket-pay').forEach(function(b) {
            b.disabled = !ok;
        });
    }

    function grupoPickDefaultActiveId(venta) {
        var parts = venta.participantes || [];
        var id = null;
        for (var i = 0; i < parts.length; i++) {
            if (parts[i].estado_pago === 'pendiente') {
                id = parts[i].id;
                break;
            }
        }
        if (!id && parts.length) id = parts[0].id;
        return id;
    }

    function switchTicketGrupoTab(inner, participanteIdStr) {
        if (!inner) return;
        var hid = inner.querySelector('.ticket-active-participante-id');
        if (hid) hid.value = participanteIdStr;
        inner.querySelectorAll('.ticket-line-row').forEach(function(tr) {
            tr.style.display = (String(tr.getAttribute('data-participante-id')) === String(participanteIdStr)) ? '' : 'none';
        });
        inner.querySelectorAll('.ticket-tab-slot').forEach(function(btn) {
            var pid = btn.getAttribute('data-participante-id');
            var active = String(pid) === String(participanteIdStr);
            var est = btn.getAttribute('data-estado');
            btn.classList.remove('btn-primary', 'btn-outline-secondary');
            if (btn.disabled || est === 'pagado') {
                btn.classList.add('btn-outline-secondary');
                return;
            }
            btn.classList.add(active ? 'btn-primary' : 'btn-outline-secondary');
        });
        var tabSel = inner.querySelector('.ticket-tab-slot[data-participante-id="' + participanteIdStr + '"]');
        var estTab = tabSel ? tabSel.getAttribute('data-estado') : '';
        var addBtn = inner.querySelector('.btn-ticket-add-linea');
        if (addBtn) addBtn.disabled = (estTab === 'pagado');
        inner.querySelectorAll('.ticket-jugador-panel').forEach(function(panel) {
            panel.classList.toggle('d-none', String(panel.getAttribute('data-participante-id')) !== String(participanteIdStr));
        });
    }

    function htmlTicketGrupoPagoAcciones(p) {
        if (p.estado_pago === 'pagado') {
            return '<span class="badge badge-success">Pagado' + (p.metodo_pago ? ' (' + escapeHtml(p.metodo_pago) + ')' : '') + '</span>';
        }
        var h = '';
        var subPendiente = p.subtotal_pendiente !== undefined ? p.subtotal_pendiente : p.subtotal;
        if (subPendiente <= 0) {
            h += '<button type="button" class="btn btn-outline-secondary btn-participante-sin-consumo mr-1" data-participante-id="' + p.id + '">Sin consumo</button>';
        } else {
            h += '<button type="button" class="btn btn-success btn-participante-pago mr-1" data-participante-id="' + p.id + '" data-metodo="efectivo">Pagar todo (' + escapeHtml(p.subtotal_pendiente_fmt || p.subtotal_fmt) + ')</button>';
            h += '<button type="button" class="btn btn-info btn-participante-pago" data-participante-id="' + p.id + '" data-metodo="transferencia">Pagar todo (' + escapeHtml(p.subtotal_pendiente_fmt || p.subtotal_fmt) + ')</button>';
        }
        return h;
    }

    var cajaJugadoresCache = null;
    var cajaModalTarget = null;

    function ensureCajaJugadoresLoaded(cb) {
        if (cajaJugadoresCache) {
            cb(cajaJugadoresCache);
            return;
        }
        fetch(jugadoresCajaUrl(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(parseFetchResponse).then(function(x) {
            if (x.ok && x.j && x.j.jugadores) {
                cajaJugadoresCache = x.j.jugadores;
                cb(cajaJugadoresCache);
            } else {
                cb([]);
            }
        }).catch(function() { cb([]); });
    }

    function renderCajaJugadorLista(filter) {
        var wrap = document.getElementById('caja-jugador-lista');
        if (!wrap) return;
        var f = (filter || '').toLowerCase().trim();
        wrap.innerHTML = '';
        (cajaJugadoresCache || []).forEach(function(j) {
            var label = (j.nombre + ' ' + j.apellido).trim();
            if (f && label.toLowerCase().indexOf(f) < 0) return;
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'list-group-item list-group-item-action btn-elegir-jugador-caja text-left py-2';
            b.setAttribute('data-id', j.id);
            b.textContent = label;
            wrap.appendChild(b);
        });
    }

    function applyVentaJsonSimple(cardRoot, venta) {
        var hNombre = cardRoot.querySelector('.ticket-card-nombre');
        var hTot = cardRoot.querySelector('.ticket-card-total');
        var hMeta = cardRoot.querySelector('.ticket-card-cancha-meta');
        if (hNombre) hNombre.textContent = venta.nombre_cliente;
        if (hTot) hTot.textContent = venta.precio_total_fmt;
        if (hMeta) hMeta.textContent = venta.cancha_nombre ? String(venta.cancha_nombre) : '';
        var inner = cardRoot.querySelector('.ticket-body-inner[data-venta-id="' + venta.id + '"]');
        if (!inner) return;
        var inpNombre = inner.querySelector('.ticket-input-nombre');
        if (inpNombre) inpNombre.value = venta.nombre_cliente;
        var tTot = inner.querySelector('.ticket-total');
        if (tTot) tTot.textContent = venta.precio_total_fmt;
        var tbody = inner.querySelector('.ticket-lines-tbody');
        if (tbody) refreshLinesTbody(tbody, venta.detalles || []);
        syncPayButtons(inner, venta.precio_total);
    }

    function applyVentaJsonGrupo(cardRoot, venta) {
        var hNombre = cardRoot.querySelector('.ticket-card-nombre');
        var hTot = cardRoot.querySelector('.ticket-card-total');
        var hMeta = cardRoot.querySelector('.ticket-card-cancha-meta');
        if (hNombre) hNombre.textContent = venta.nombre_cliente;
        if (hTot) hTot.textContent = venta.precio_total_fmt;
        if (hMeta) hMeta.textContent = venta.cancha_nombre ? String(venta.cancha_nombre) : '';
        var inner = cardRoot.querySelector('.ticket-body-inner[data-venta-id="' + venta.id + '"]');
        if (!inner) return;
        var tTot = inner.querySelector('.ticket-total');
        if (tTot) tTot.textContent = venta.precio_total_fmt;

        var hid = inner.querySelector('.ticket-active-participante-id');
        var curActive = hid ? parseInt(hid.value, 10) : 0;
        var defaultId = grupoPickDefaultActiveId(venta);
        var parts = venta.participantes || [];
        var stillValid = parts.some(function(p) { return p.id === curActive && p.estado_pago === 'pendiente'; });
        var activePid = (curActive && stillValid) ? curActive : defaultId;
        if (hid) hid.value = activePid;

        inner.querySelectorAll('.ticket-tab-slot').forEach(function(btn) {
            var pid = parseInt(btn.getAttribute('data-participante-id'), 10);
            var p = parts.find(function(x) { return x.id === pid; });
            if (!p) return;
            btn.setAttribute('data-estado', p.estado_pago);
            btn.disabled = (p.estado_pago === 'pagado');
            var badge = btn.querySelector('.badge');
            if (badge) {
                badge.textContent = (p.estado_pago === 'pagado') ? 'OK' : (p.subtotal_fmt || '');
                badge.className = 'badge ml-1 badge-caja-jugador ' + ((p.estado_pago === 'pagado') ? 'badge-light' : 'badge-warning');
            }
            btn.classList.remove('btn-primary', 'btn-outline-secondary');
            if (p.estado_pago === 'pagado') {
                btn.classList.add('btn-outline-secondary');
            } else if (pid === activePid) {
                btn.classList.add('btn-primary');
            } else {
                btn.classList.add('btn-outline-secondary');
            }
        });

        parts.forEach(function(p) {
            var panel = inner.querySelector('.ticket-jugador-panel[data-participante-id="' + p.id + '"]');
            if (!panel) return;
            panel.classList.toggle('d-none', parseInt(p.id, 10) !== parseInt(activePid, 10));
            var inp = panel.querySelector('.ticket-input-nombre, .ticket-input-participante-nombre');
            if (inp) {
                inp.value = p.nombre;
                inp.readOnly = (p.estado_pago === 'pagado');
            }
            var buscar = panel.querySelector('.btn-buscar-jugador-caja');
            if (buscar) buscar.disabled = (p.estado_pago === 'pagado');
            panel.dataset.jugadorId = (p.jugador_id != null) ? String(p.jugador_id) : '';
            var acciones = panel.querySelector('.ticket-jugador-pago-acciones');
            if (acciones) acciones.innerHTML = htmlTicketGrupoPagoAcciones(p);
        });

        var tbody = inner.querySelector('.ticket-lines-tbody');
        if (tbody) {
            tbody.innerHTML = '';
            (venta.detalles || []).forEach(function(d) {
                var tr = document.createElement('tr');
                tr.className = 'ticket-line-row';
                tr.setAttribute('data-participante-id', d.stock_venta_participante_id);
                var show = parseInt(d.stock_venta_participante_id, 10) === parseInt(activePid, 10);
                tr.style.display = show ? '' : 'none';
                tr.innerHTML = '<td>' + escapeHtml(d.producto_nombre || '') + '</td>'
                    + '<td class="text-center">' + d.cantidad + '</td>'
                    + '<td class="text-right">' + escapeHtml(d.subtotal_fmt) + '</td>'
                    + '<td class="text-center">' + htmlLineaEstado(d) + '</td>'
                    + '<td class="text-center p-1 align-middle">' + htmlLineaAccionesGrupo(d) + '</td>';
                tbody.appendChild(tr);
            });
        }

        var addBtn = inner.querySelector('.btn-ticket-add-linea');
        if (addBtn) {
            var ap = parts.find(function(x) { return x.id === activePid; });
            addBtn.disabled = !ap || ap.estado_pago === 'pagado';
        }
    }

    function applyVentaJson(cardRoot, venta) {
        if (venta.modo_grupo) {
            applyVentaJsonGrupo(cardRoot, venta);
        } else {
            applyVentaJsonSimple(cardRoot, venta);
        }
    }

    function marcarTurnoGridPagado(ventaId) {
        if (!ventaId) return;
        var celda = document.querySelector('.turno-celda[data-venta-id="' + ventaId + '"]');
        if (!celda) return;
        celda.setAttribute('data-tipo', 'pagado');
        celda.classList.remove('vendido', 'fijo', 'libre');
        celda.classList.add('pagado');
        var badge = celda.querySelector('.turno-badge');
        if (badge) { badge.className = 'badge badge-success turno-badge'; badge.textContent = 'Pagado'; }
        var btn = celda.querySelector('.btn-turno-accion');
        if (btn) {
            btn.className = 'btn btn-outline-secondary btn-turno-accion btn-turno-ver';
            btn.textContent = 'Ver ticket';
            btn.disabled = false;
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var card = document.querySelector('.ticket-card[data-venta-id="' + ventaId + '"]');
                if (card) {
                    var panel = card.querySelector('.ticket-card-panel');
                    if (panel) {
                        panel.classList.add('is-open');
                        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        syncTicketsToggleBtn();
                    }
                }
            });
        }
    }

    function applyCajaResumen(res) {
        if (!res) return;
        var el;
        el = document.getElementById('caja-stat-transacciones');
        if (el) el.textContent = res.transacciones + ' mov.';
        el = document.getElementById('caja-stat-monto-total');
        if (el) el.textContent = res.monto_total_fmt;
        el = document.getElementById('caja-stat-efectivo');
        if (el) el.textContent = res.efectivo_fmt;
        el = document.getElementById('caja-stat-transferencia');
        if (el) el.textContent = res.transferencia_fmt;
        el = document.getElementById('caja-stat-pagado');
        if (el) el.textContent = res.pagado_fmt;
        el = document.getElementById('caja-stat-pendiente-dia');
        if (el) el.textContent = res.pendiente_dia_fmt;
        el = document.getElementById('caja-stat-pendientes-saldo');
        if (el) el.textContent = res.pendientes_saldo_count + ' venta(s)';
        var tablasResumen = [
            ['ventas-hoy', 'html_ventas_hoy'],
            ['total-hoy', 'html_total_hoy'],
            ['efectivo-hoy', 'html_efectivo_hoy'],
            ['transfer-hoy', 'html_transfer_hoy'],
            ['cobrado-hoy', 'html_cobrado_hoy'],
            ['pendientes-dia', 'html_pendientes_dia'],
            ['pendientes-saldo', 'html_pendientes_saldo'],
        ];
        tablasResumen.forEach(function(pair) {
            var wrap = document.getElementById('resumen-data-' + pair[0]);
            var k = pair[1];
            if (wrap && Object.prototype.hasOwnProperty.call(res, k) && typeof res[k] === 'string') {
                wrap.innerHTML = res[k];
            }
        });
    }

    function patchNombre(ventaId, nombre, statusEl, cb) {
        if (statusEl) statusEl.textContent = 'Guardando…';
        return fetch(updateUrl(ventaId), {
            method: 'PATCH',
            headers: jsonHeaders(),
            body: JSON.stringify({ _token: csrfToken, nombre_cliente: nombre })
        }).then(parseFetchResponse).then(function(x) {
            if (!x.ok) {
                var msg = (x.j && x.j.message) || (x.j && x.j.errors && (typeof x.j.errors === 'object') && JSON.stringify(x.j.errors));
                if (statusEl) statusEl.textContent = msg || 'Error';
                return Promise.reject(new Error(msg || 'Error'));
            }
            if (statusEl) statusEl.textContent = 'Guardado';
            if (cb) cb();
            setTimeout(function() { if (statusEl) statusEl.textContent = ''; }, 2000);
            return x;
        }).catch(function(e) {
            if (statusEl) statusEl.textContent = (e && e.message) ? e.message : 'Sin conexión';
            return Promise.reject(e);
        });
    }

    function patchParticipante(ventaId, participanteId, nombre, statusEl, cb, jugadorIdOptional) {
        if (statusEl) statusEl.textContent = 'Guardando…';
        var payload = { _token: csrfToken, nombre: nombre };
        if (jugadorIdOptional !== undefined) {
            payload.jugador_id = jugadorIdOptional;
        }
        return fetch(participantePatchUrl(ventaId, participanteId), {
            method: 'PATCH',
            headers: jsonHeaders(),
            body: JSON.stringify(payload)
        }).then(parseFetchResponse).then(function(x) {
            if (!x.ok) {
                var msg = (x.j && x.j.message) || (x.j && x.j.errors && (typeof x.j.errors === 'object') && JSON.stringify(x.j.errors));
                if (statusEl) statusEl.textContent = msg || 'Error';
                return Promise.reject(new Error(msg || 'Error'));
            }
            if (statusEl) statusEl.textContent = 'Guardado';
            if (cb) cb();
            setTimeout(function() { if (statusEl) statusEl.textContent = ''; }, 2000);
            return x;
        }).catch(function(e) {
            if (statusEl) statusEl.textContent = (e && e.message) ? e.message : 'Sin conexión';
            return Promise.reject(e);
        });
    }

    function wireTicketCard(card) {
        var inner = card.querySelector('.ticket-body-inner');
        if (!inner) return;
        var ventaId = inner.getAttribute('data-venta-id');
        var modoGrupo = inner.getAttribute('data-modo-grupo') === '1';
        var nombreInput = inner.querySelector('.ticket-input-nombre');
        var statusNombre = inner.querySelector('.ticket-nombre-status');
        var addBtn = inner.querySelector('.btn-ticket-add-linea');
        var guardarBtn = inner.querySelector('.btn-ticket-guardar');

        if (nombreInput && !nombreInput._wiredNombre) {
            nombreInput._wiredNombre = true;
            nombreInput.addEventListener('blur', function() {
                patchNombre(ventaId, nombreInput.value.trim() || '(Sin nombre)', statusNombre, function() {
                    var h = card.querySelector('.ticket-card-nombre');
                    if (h) h.textContent = nombreInput.value.trim() || '(Sin nombre)';
                });
            });
        }
        if (modoGrupo) {
            inner.querySelectorAll('.ticket-input-participante-nombre').forEach(function(inp) {
                if (inp._wiredNombre) return;
                inp._wiredNombre = true;
                inp.addEventListener('blur', function() {
                    var row = inp.closest('.ticket-fila-participante');
                    var pid = row && row.getAttribute('data-participante-id');
                    if (!pid) return;
                    patchParticipante(ventaId, pid, inp.value.trim() || '(Sin nombre)', statusNombre, function() {
                        var h = card.querySelector('.ticket-card-nombre');
                        var n1 = inner.querySelector('.ticket-input-nombre');
                        if (h && n1) h.textContent = n1.value.trim() || '(Sin nombre)';
                    });
                });
            });
        }
        if (guardarBtn && !guardarBtn._wired) {
            guardarBtn._wired = true;
            guardarBtn.addEventListener('click', function() {
                if (!modoGrupo) {
                    patchNombre(ventaId, nombreInput ? nombreInput.value.trim() || '(Sin nombre)' : '', statusNombre, function() {
                        var h = card.querySelector('.ticket-card-nombre');
                        if (h && nombreInput) h.textContent = nombreInput.value.trim() || '(Sin nombre)';
                    });
                    return;
                }
                var tasks = [];
                if (nombreInput) {
                    tasks.push(patchNombre(ventaId, nombreInput.value.trim() || '(Sin nombre)', statusNombre, function() {
                        var h = card.querySelector('.ticket-card-nombre');
                        if (h) h.textContent = nombreInput.value.trim() || '(Sin nombre)';
                    }));
                }
                inner.querySelectorAll('.ticket-input-participante-nombre').forEach(function(inp) {
                    var row = inp.closest('.ticket-fila-participante');
                    var pid = row && row.getAttribute('data-participante-id');
                    if (!pid) return;
                    tasks.push(patchParticipante(ventaId, pid, inp.value.trim() || '(Sin nombre)', statusNombre));
                });
                Promise.all(tasks).catch(function() {});
            });
        }
        if (addBtn && !addBtn._wired) {
            addBtn._wired = true;
            addBtn.addEventListener('click', function() {
                var pidEl = inner.querySelector('.ticket-producto-id');
                var pid = pidEl && pidEl.value;
                var qty = 1;
                if (!pid) { alert('Elegí una categoría y un producto.'); return; }
                var body = mergeCajaFecha({
                    _token: csrfToken,
                    stock_producto_id: parseInt(pid, 10),
                    cantidad: qty
                });
                if (modoGrupo) {
                    var hid = inner.querySelector('.ticket-active-participante-id');
                    var partId = hid && hid.value ? parseInt(hid.value, 10) : 0;
                    if (!partId) { alert('Elegí un jugador (pestaña J1–J4).'); return; }
                    body.stock_venta_participante_id = partId;
                }
                addBtn.disabled = true;
                fetch(lineaUrl(ventaId), {
                    method: 'POST',
                    headers: jsonHeaders(),
                    body: JSON.stringify(body)
                }).then(parseFetchResponse).then(function(x) {
                    addBtn.disabled = false;
                    if (!x.ok) {
                        var msg = (x.j && x.j.message) || (x.j && x.j.errors && (typeof x.j.errors === 'object') && Object.values(x.j.errors).flat().join(' '));
                        alert(msg || ('Error HTTP ' + x.status));
                        return;
                    }
                    if (!x.j.venta) {
                        alert('Respuesta sin datos de venta');
                        return;
                    }
                    if (x.j && x.j.resumen) applyCajaResumen(x.j.resumen);
                    applyVentaJson(card, x.j.venta);
                }).catch(function(e) {
                    addBtn.disabled = false;
                    alert((e && e.message) ? e.message : 'Error de red');
                });
            });
        }
        var cancelBtn = inner.querySelector('.btn-ticket-cancelar');
        if (cancelBtn && !cancelBtn._wired) {
            cancelBtn._wired = true;
            cancelBtn.addEventListener('click', function() {
                if (!confirm('¿Cancelar este ticket? Se eliminará la venta y se devolverá el stock de los productos agregados.')) return;
                cancelBtn.disabled = true;
                fetch(ventaDestroyUrl(ventaId), {
                    method: 'DELETE',
                    headers: jsonHeaders(),
                    body: JSON.stringify(mergeCajaFecha({ _token: csrfToken }))
                }).then(parseFetchResponse).then(function(x) {
                    cancelBtn.disabled = false;
                    if (!x.ok) {
                        var msg = (x.j && x.j.message) || (x.j && x.j.errors && Object.values(x.j.errors || {}).flat().join(' '));
                        alert(msg || ('Error HTTP ' + x.status));
                        return;
                    }
                    if (x.j && x.j.resumen) applyCajaResumen(x.j.resumen);
                    var col = card.closest('.col-lg-4');
                    if (col) col.remove();
                    syncTicketsToggleBtn();
                }).catch(function(e) {
                    cancelBtn.disabled = false;
                    alert((e && e.message) ? e.message : 'Error de red');
                });
            });
        }
        wireProductPicker(inner);
    }

    document.querySelectorAll('.ticket-card').forEach(wireTicketCard);

    var listaTicketsEl = document.getElementById('lista-tickets-abiertos');
    var btnToggleListaTickets = document.getElementById('btn-toggle-lista-tickets');
    function syncTicketsToggleBtn() {
        if (!btnToggleListaTickets || !listaTicketsEl) return;
        var algunAbierto = listaTicketsEl.querySelector('.ticket-card-panel.is-open');
        btnToggleListaTickets.textContent = algunAbierto ? 'Contraer todos' : 'Desplegar todos';
        btnToggleListaTickets.setAttribute('aria-expanded', algunAbierto ? 'true' : 'false');
    }
    if (btnToggleListaTickets && listaTicketsEl) {
        btnToggleListaTickets.addEventListener('click', function(e) {
            e.preventDefault();
            var panels = listaTicketsEl.querySelectorAll('.ticket-card-panel');
            var algunAbierto = listaTicketsEl.querySelector('.ticket-card-panel.is-open');
            panels.forEach(function(p) {
                if (algunAbierto) {
                    p.classList.remove('is-open');
                } else {
                    p.classList.add('is-open');
                }
            });
            syncTicketsToggleBtn();
        });
    }
    syncTicketsToggleBtn();
    if (listaTicketsEl) {
        listaTicketsEl.addEventListener('submit', function(e) {
            var form = e.target;
            if (!form.classList || !form.classList.contains('form-ticket-pago')) return;
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;
            var metodoInp = form.querySelector('input[name="metodo_pago"]');
            var metodo = (metodoInp && metodoInp.value) ? metodoInp.value : 'efectivo';
            fetch(form.action, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify(mergeCajaFecha({ _token: csrfToken, metodo_pago: metodo }))
            }).then(parseFetchResponse).then(function(x) {
                if (btn) btn.disabled = false;
                if (!x.ok) {
                    var msg = (x.j && x.j.message) || (x.j && x.j.errors && (typeof x.j.errors === 'object') && Object.values(x.j.errors).flat().join(' '));
                    alert(msg || ('Error HTTP ' + x.status));
                    return;
                }
                if (x.j && x.j.resumen) applyCajaResumen(x.j.resumen);
                var col = form.closest('.col-lg-4');
                var ventaIdForm = form.getAttribute('data-venta-id');
                if (col) col.remove();
                if (ventaIdForm) marcarTurnoGridPagado(ventaIdForm);
                syncTicketsToggleBtn();
            }).catch(function(err) {
                if (btn) btn.disabled = false;
                alert((err && err.message) ? err.message : 'Error de red');
            });
        });
    }
    if (listaTicketsEl) {
        listaTicketsEl.addEventListener('click', function(e) {
            var tabSlot = e.target.closest('.ticket-tab-slot');
            if (tabSlot && listaTicketsEl.contains(tabSlot) && !tabSlot.disabled) {
                e.preventDefault();
                e.stopPropagation();
                var cardtab = tabSlot.closest('.ticket-card');
                var innertab = cardtab && cardtab.querySelector('.ticket-body-inner[data-modo-grupo="1"]');
                if (!innertab) return;
                switchTicketGrupoTab(innertab, tabSlot.getAttribute('data-participante-id'));
                return;
            }
            var btnAgregarPart = e.target.closest('.btn-torneo-agregar-participante');
            if (btnAgregarPart && listaTicketsEl.contains(btnAgregarPart)) {
                e.preventDefault();
                e.stopPropagation();
                var cardAg = btnAgregarPart.closest('.ticket-card');
                var innerAg = cardAg && cardAg.querySelector('.ticket-body-inner');
                var vidAg = innerAg && innerAg.getAttribute('data-venta-id');
                var inputNombre = innerAg && innerAg.querySelector('.torneo-nuevo-participante');
                var nombre = inputNombre ? inputNombre.value.trim() : '';
                if (!vidAg || !nombre) return;
                btnAgregarPart.disabled = true;
                fetch(participanteStoreUrl(vidAg), {
                    method: 'POST',
                    headers: jsonHeaders(),
                    body: JSON.stringify(mergeCajaFecha({ _token: csrfToken, nombre: nombre }))
                }).then(parseFetchResponse).then(function(x) {
                    btnAgregarPart.disabled = false;
                    if (!x.ok) {
                        var msga = (x.j && x.j.message) || (x.j && x.j.errors && (typeof x.j.errors === 'object') && Object.values(x.j.errors).flat().join(' '));
                        alert(msga || ('Error HTTP ' + x.status));
                        return;
                    }
                    window.location.reload();
                }).catch(function(erra) {
                    btnAgregarPart.disabled = false;
                    alert((erra && erra.message) ? erra.message : 'Error de red');
                });
                return;
            }

            var btnInscTodos = e.target.closest('.btn-torneo-inscripcion-todos');
            if (btnInscTodos && listaTicketsEl.contains(btnInscTodos)) {
                e.preventDefault();
                e.stopPropagation();
                var cardIn = btnInscTodos.closest('.ticket-card');
                var innerIn = cardIn && cardIn.querySelector('.ticket-body-inner');
                var vidIn = innerIn && innerIn.getAttribute('data-venta-id');
                var selectProd = innerIn && innerIn.querySelector('.torneo-producto-inscripcion');
                var prodId = selectProd ? selectProd.value : '';
                if (!vidIn || !prodId) { alert('Elegí un producto de inscripción.'); return; }
                btnInscTodos.disabled = true;
                fetch(inscripcionTodosUrl(vidIn), {
                    method: 'POST',
                    headers: jsonHeaders(),
                    body: JSON.stringify(mergeCajaFecha({ _token: csrfToken, stock_producto_id: parseInt(prodId, 10) }))
                }).then(parseFetchResponse).then(function(x) {
                    btnInscTodos.disabled = false;
                    if (!x.ok) {
                        var msgi = (x.j && x.j.message) || (x.j && x.j.errors && (typeof x.j.errors === 'object') && Object.values(x.j.errors).flat().join(' '));
                        alert(msgi || ('Error HTTP ' + x.status));
                        return;
                    }
                    window.location.reload();
                }).catch(function(erri) {
                    btnInscTodos.disabled = false;
                    alert((erri && erri.message) ? erri.message : 'Error de red');
                });
                return;
            }

            var btnVerDetalle = e.target.closest('.btn-torneo-ver-detalle');
            if (btnVerDetalle && listaTicketsEl.contains(btnVerDetalle)) {
                e.preventDefault();
                e.stopPropagation();
                var cardVd = btnVerDetalle.closest('.ticket-card');
                var innerVd = cardVd && cardVd.querySelector('.ticket-body-inner');
                var vidVd = innerVd && innerVd.getAttribute('data-venta-id');
                var pidVd = btnVerDetalle.getAttribute('data-participante-id');
                var nombreVd = btnVerDetalle.getAttribute('data-participante-nombre');
                if (!vidVd || !pidVd) return;
                var modal = document.getElementById('modal-torneo-detalle-' + vidVd);
                var tbody = modal && modal.querySelector('.torneo-detalle-lines-tbody');
                var titulo = modal && modal.querySelector('.torneo-detalle-titulo');
                if (titulo) titulo.textContent = 'Detalle — ' + escapeHtml(nombreVd);
                if (tbody) {
                    tbody.innerHTML = '';
                    var detallesVd = [];
                    // Buscar detalles del participante en el DOM actual
                    var rows = innerVd.querySelectorAll('.ticket-line-row');
                    rows.forEach(function(row) {
                        if (String(row.getAttribute('data-participante-id')) === String(pidVd)) {
                            var cells = row.querySelectorAll('td');
                            if (cells.length >= 4) {
                                var estado = cells[3].innerHTML;
                                var acciones = cells[4].innerHTML;
                                var tr = document.createElement('tr');
                                tr.innerHTML = '<td>' + cells[0].innerHTML + '</td>'
                                    + '<td class="text-center">' + cells[1].innerHTML + '</td>'
                                    + '<td class="text-right">' + cells[2].innerHTML + '</td>'
                                    + '<td class="text-center">' + estado + '</td>'
                                    + '<td class="text-center p-1 align-middle">' + acciones + '</td>';
                                tbody.appendChild(tr);
                            }
                        }
                    });
                }
                if (window.jQuery) window.jQuery(modal).modal('show');
                return;
            }

            var payPar = e.target.closest('.btn-participante-pago');
            if (payPar && listaTicketsEl.contains(payPar)) {
                e.preventDefault();
                e.stopPropagation();
                var cardp = payPar.closest('.ticket-card');
                var innerp = cardp && cardp.querySelector('.ticket-body-inner');
                var vid = innerp && innerp.getAttribute('data-venta-id');
                var pidPay = payPar.getAttribute('data-participante-id');
                var metodo = payPar.getAttribute('data-metodo');
                if (!vid || !pidPay) return;
                payPar.disabled = true;
                fetch(participantePagoUrl(vid, pidPay), {
                    method: 'POST',
                    headers: jsonHeaders(),
                    body: JSON.stringify(mergeCajaFecha({ _token: csrfToken, metodo_pago: metodo }))
                }).then(parseFetchResponse).then(function(x) {
                    payPar.disabled = false;
                    if (!x.ok) {
                        var msgp = (x.j && x.j.message) || (x.j && x.j.errors && (typeof x.j.errors === 'object') && Object.values(x.j.errors).flat().join(' '));
                        alert(msgp || ('Error HTTP ' + x.status));
                        return;
                    }
                    if (x.j && x.j.resumen) applyCajaResumen(x.j.resumen);
                    if (x.j && x.j.ticket_cerrado) {
                        var colp = cardp.closest('.col-lg-4');
                        if (colp) colp.remove();
                        marcarTurnoGridPagado(vid);
                        syncTicketsToggleBtn();
                        return;
                    }
                    if (x.j && x.j.venta && cardp) applyVentaJson(cardp, x.j.venta);
                }).catch(function(errp) {
                    payPar.disabled = false;
                    alert((errp && errp.message) ? errp.message : 'Error de red');
                });
                return;
            }
            var lineaPay = e.target.closest('.btn-linea-pago');
            if (lineaPay && listaTicketsEl.contains(lineaPay)) {
                e.preventDefault();
                e.stopPropagation();
                var cardLinea = lineaPay.closest('.ticket-card');
                var innerLinea = cardLinea && cardLinea.querySelector('.ticket-body-inner');
                var vidLinea = innerLinea && innerLinea.getAttribute('data-venta-id');
                var didLinea = lineaPay.getAttribute('data-detalle-id');
                var metodoLinea = lineaPay.getAttribute('data-metodo');
                if (!vidLinea || !didLinea) return;
                lineaPay.disabled = true;
                fetch(lineaPagoUrl(vidLinea, didLinea), {
                    method: 'POST',
                    headers: jsonHeaders(),
                    body: JSON.stringify(mergeCajaFecha({ _token: csrfToken, metodo_pago: metodoLinea }))
                }).then(parseFetchResponse).then(function(x) {
                    lineaPay.disabled = false;
                    if (!x.ok) {
                        var msgl = (x.j && x.j.message) || (x.j && x.j.errors && (typeof x.j.errors === 'object') && Object.values(x.j.errors).flat().join(' '));
                        alert(msgl || ('Error HTTP ' + x.status));
                        return;
                    }
                    if (x.j && x.j.resumen) applyCajaResumen(x.j.resumen);
                    if (x.j && x.j.ticket_cerrado) {
                        var coll = cardLinea.closest('.col-lg-4');
                        if (coll) coll.remove();
                        marcarTurnoGridPagado(vidLinea);
                        syncTicketsToggleBtn();
                        return;
                    }
                    if (x.j && x.j.venta && cardLinea) applyVentaJson(cardLinea, x.j.venta);
                }).catch(function(errl) {
                    lineaPay.disabled = false;
                    alert((errl && errl.message) ? errl.message : 'Error de red');
                });
                return;
            }

            var sinPar = e.target.closest('.btn-participante-sin-consumo');
            if (sinPar && listaTicketsEl.contains(sinPar)) {
                e.preventDefault();
                e.stopPropagation();
                var cards = sinPar.closest('.ticket-card');
                var inners = cards && cards.querySelector('.ticket-body-inner');
                var vids = inners && inners.getAttribute('data-venta-id');
                var pidSin = sinPar.getAttribute('data-participante-id');
                if (!vids || !pidSin) return;
                sinPar.disabled = true;
                fetch(participantePagoUrl(vids, pidSin), {
                    method: 'POST',
                    headers: jsonHeaders(),
                    body: JSON.stringify(mergeCajaFecha({ _token: csrfToken }))
                }).then(parseFetchResponse).then(function(x) {
                    sinPar.disabled = false;
                    if (!x.ok) {
                        var msgs = (x.j && x.j.message) || (x.j && x.j.errors && Object.values(x.j.errors || {}).flat().join(' '));
                        alert(msgs || ('Error HTTP ' + x.status));
                        return;
                    }
                    if (x.j && x.j.resumen) applyCajaResumen(x.j.resumen);
                    if (x.j && x.j.ticket_cerrado) {
                        var cols = cards.closest('.col-lg-4');
                        if (cols) cols.remove();
                        marcarTurnoGridPagado(vids);
                        syncTicketsToggleBtn();
                        return;
                    }
                    if (x.j && x.j.venta && cards) applyVentaJson(cards, x.j.venta);
                }).catch(function(errs) {
                    sinPar.disabled = false;
                    alert((errs && errs.message) ? errs.message : 'Error de red');
                });
                return;
            }
            var busBtn = e.target.closest('.btn-buscar-jugador-caja');
            if (busBtn && listaTicketsEl.contains(busBtn)) {
                e.preventDefault();
                e.stopPropagation();
                var cardb = busBtn.closest('.ticket-card');
                var innerb = cardb && cardb.querySelector('.ticket-body-inner');
                var vidb = innerb && innerb.getAttribute('data-venta-id');
                var pidb = busBtn.getAttribute('data-participante-id');
                var solo = busBtn.classList.contains('btn-buscar-jugador-solo');
                cajaModalTarget = { ventaId: vidb, participanteId: solo ? null : pidb, solo: !!solo, card: cardb, inner: innerb };
                ensureCajaJugadoresLoaded(function() {
                    renderCajaJugadorLista('');
                    var sin = document.getElementById('caja-jugador-buscar-input');
                    if (sin) {
                        sin.value = '';
                        sin.focus();
                    }
                    if (window.jQuery) window.jQuery('#modal-buscar-jugador-caja').modal('show');
                });
                return;
            }
            var divBtn = e.target.closest('.btn-ticket-dividir-linea');
            if (divBtn && listaTicketsEl.contains(divBtn)) {
                e.preventDefault();
                e.stopPropagation();
                var cardDiv = divBtn.closest('.ticket-card');
                var innerDiv = cardDiv && cardDiv.querySelector('.ticket-body-inner');
                var vidDiv = innerDiv && innerDiv.getAttribute('data-venta-id');
                var didDiv = divBtn.getAttribute('data-detalle-id');
                var pidDiv = divBtn.getAttribute('data-participante-id');
                if (!vidDiv || !didDiv) return;
                var wrapOpc = document.getElementById('dividir-linea-opciones');
                var inpDet = document.getElementById('dividir-linea-detalle-id');
                var inpVen = document.getElementById('dividir-linea-venta-id');
                var inpCard = document.getElementById('dividir-linea-card-id');
                if (wrapOpc) {
                    wrapOpc.innerHTML = '';
                    wrapOpc.dataset.dueñoPid = pidDiv;
                    var partsDiv = innerDiv ? innerDiv.querySelectorAll('.ticket-jugador-panel') : [];
                    partsDiv.forEach(function(panel) {
                        var pId = panel.getAttribute('data-participante-id');
                        var slot = panel.getAttribute('data-slot');
                        var nombre = panel.querySelector('input[type="text"]');
                        var nombreTxt = nombre ? nombre.value : ('Jugador ' + slot);
                        if (String(pId) === String(pidDiv)) return; // no mostrar al dueño actual
                        var row = document.createElement('div');
                        row.className = 'form-check mb-1';
                        row.innerHTML = '<input class="form-check-input" type="checkbox" value="' + pId + '" id="chk-div-' + pId + '"><label class="form-check-label small" for="chk-div-' + pId + '">' + escapeHtml(nombreTxt) + ' (J' + slot + ')</label>';
                        wrapOpc.appendChild(row);
                    });
                }
                if (inpDet) inpDet.value = didDiv;
                if (inpVen) inpVen.value = vidDiv;
                if (inpCard) inpCard.value = cardDiv ? cardDiv.getAttribute('data-venta-id') : '';
                if (window.jQuery) window.jQuery('#modal-dividir-linea').modal('show');
                return;
            }
            var rm = e.target.closest('.btn-ticket-remove-linea');
            if (rm) {
                e.preventDefault();
                e.stopPropagation();
                var card = rm.closest('.ticket-card');
                var inner = card && card.querySelector('.ticket-body-inner');
                var ventaId = inner && inner.getAttribute('data-venta-id');
                var detalleId = rm.getAttribute('data-detalle-id');
                if (!ventaId || !detalleId) return;
                rm.disabled = true;
                fetch(lineaDestroyUrl(ventaId, detalleId), {
                    method: 'DELETE',
                    headers: jsonHeaders(),
                    body: JSON.stringify(mergeCajaFecha({ _token: csrfToken }))
                }).then(parseFetchResponse).then(function(x) {
                    rm.disabled = false;
                    if (!x.ok) {
                        var msg = (x.j && x.j.message) || (x.j && x.j.errors && Object.values(x.j.errors).flat().join(' '));
                        alert(msg || ('Error HTTP ' + x.status));
                        return;
                    }
                    if (!x.j.venta) {
                        alert('Respuesta sin datos de venta');
                        return;
                    }
                    if (x.j && x.j.resumen) applyCajaResumen(x.j.resumen);
                    applyVentaJson(card, x.j.venta);
                }).catch(function(err) {
                    rm.disabled = false;
                    alert((err && err.message) ? err.message : 'Error de red');
                });
                return;
            }
            var header = e.target.closest('.ticket-card-header-toggle');
            if (!header) return;
            var card = header.closest('.ticket-card');
            if (!card) return;
            var panel = card.querySelector('.ticket-card-panel');
            if (!panel) return;
            panel.classList.toggle('is-open');
            syncTicketsToggleBtn();
        });
    }

    (function initResumenCaja() {
        var wrap = document.getElementById('caja-resumen-detalle');
        var tituloEl = document.getElementById('caja-resumen-titulo');
        var cerrar = document.getElementById('caja-resumen-cerrar');
        if (!wrap || !tituloEl) return;
        document.querySelectorAll('.caja-stat-trigger').forEach(function(stat) {
            stat.addEventListener('click', function() {
                var key = stat.getAttribute('data-resumen');
                var titulo = stat.getAttribute('data-titulo') || '';
                if (!key) return;
                document.querySelectorAll('.resumen-tabla').forEach(function(el) { el.classList.add('d-none'); });
                var panel = document.getElementById('resumen-data-' + key);
                if (panel) panel.classList.remove('d-none');
                tituloEl.textContent = titulo;
                wrap.classList.remove('d-none');
                wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                fetchCajaResumenAplicar();
            });
        });
        if (cerrar) {
            cerrar.addEventListener('click', function() {
                wrap.classList.add('d-none');
                document.querySelectorAll('.resumen-tabla').forEach(function(el) { el.classList.add('d-none'); });
            });
        }
        wrap.addEventListener('click', function(e) {
            var btnV = e.target.closest('.btn-caja-ver-ticket-modal');
            if (!btnV) return;
            e.preventDefault();
            var vid = btnV.getAttribute('data-venta-id');
            if (!vid) return;
            var modalTicket = document.getElementById('modal-caja-ver-ticket');
            var bodyTicket = document.getElementById('modal-caja-ver-ticket-body');
            var tituloTicket = document.getElementById('modal-caja-ver-ticket-titulo');
            var linkTicket = document.getElementById('modal-caja-ver-ticket-link-completo');
            if (!bodyTicket || !modalTicket) return;
            bodyTicket.innerHTML = '<p class="text-muted small mb-0">Cargando…</p>';
            if (tituloTicket) tituloTicket.textContent = 'Ticket';
            if (linkTicket) linkTicket.setAttribute('href', '#');
            if (window.jQuery) window.jQuery(modalTicket).modal('show');
            fetch(ventaTicketModalUrl(vid), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(parseFetchResponse).then(function(x) {
                if (!x.ok || !x.j || !x.j.ok) {
                    var errTxt = (x.j && x.j.message) ? String(x.j.message) : ('Error HTTP ' + x.status);
                    bodyTicket.innerHTML = '<p class="text-danger small mb-0">' + escapeHtml(errTxt) + '</p>';
                    return;
                }
                if (tituloTicket && x.j.titulo) tituloTicket.textContent = x.j.titulo;
                bodyTicket.innerHTML = x.j.html || '';
                if (linkTicket && x.j.url_ver_completo) linkTicket.setAttribute('href', x.j.url_ver_completo);
            }).catch(function() {
                bodyTicket.innerHTML = '<p class="text-danger small mb-0">Error de red.</p>';
            });
        });
    })();

    function htmlBloquePadre(padre) {
        if (!padre) return '';
        var h = '<div class="mb-3 p-2 border rounded bg-light">'
            + '<div class="d-flex justify-content-between align-items-center mb-1">'
            + '<span class="caja-texto-small font-weight-bold">Ticket original #' + padre.id + '</span>'
            + '<span class="small text-success font-weight-bold">' + escapeHtml(padre.precio_total_fmt) + ' (pagado)</span></div>'
            + '<table class="table table-sm table-bordered mb-0"><thead class="thead-light"><tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-right">Subtotal</th></tr></thead><tbody>';
        (padre.detalles || []).forEach(function(d) {
            h += '<tr><td>' + escapeHtml(d.producto_nombre || '') + '</td>'
                + '<td class="text-center">' + d.cantidad + '</td>'
                + '<td class="text-right">' + escapeHtml(d.subtotal_fmt) + '</td></tr>';
        });
        h += '</tbody></table></div>';
        return h;
    }

    function buildTicketCardHtmlSimple(venta) {
        var id = venta.id;
        var collapseId = 'ticket-collapse-' + id;
        var body = ''
            + '<div class="ticket-body-inner" data-venta-id="' + id + '" data-modo-grupo="0">'
            + htmlBloquePadre(venta.padre)
            + '<div class="form-group">'
            + '<label class="caja-label mb-1">Cliente</label>'
            + '<div class="input-group">'
            + '<input type="text" class="form-control ticket-input-nombre" value="' + escapeHtml(venta.nombre_cliente) + '" autocomplete="off">'
            + '<div class="input-group-append">'
            + '<button type="button" class="btn btn-outline-secondary btn-buscar-jugador-caja btn-buscar-jugador-solo" title="Buscar jugador (solo rellena nombre)"><i class="fas fa-search"></i></button>'
            + '</div></div>'
            + '<small class="text-muted ticket-nombre-status"></small>'
            + '</div>'
            + '<div class="table-responsive mb-2">'
            + '<table class="table table-sm table-bordered mb-0">'
            + '<thead class="thead-light"><tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-right">Subtotal</th><th class="text-center p-1" style="width:44px"></th></tr></thead>'
            + '<tbody class="ticket-lines-tbody"></tbody></table></div>'
            + '<div class="mb-3 ticket-add-product-block">'
            + '<label class="small mb-1 d-block">Categoría</label>'
            + '<div class="d-flex flex-wrap ticket-cat-pills align-items-center" style="gap:6px;">'
            + categoriasPillsHtml()
            + '</div>'
            + '<div class="form-row align-items-end mt-2">'
            + '<div class="form-group col-md-10 mb-2 mb-md-0"><label class="small mb-1">Producto</label>'
            + '<div class="position-relative ticket-producto-autocomplete">'
            + '<input type="text" class="form-control ticket-producto-search" placeholder="Elegí una categoría…" autocomplete="off" disabled>'
            + '<input type="hidden" class="ticket-producto-id">'
            + '<div class="ticket-producto-dropdown d-none position-absolute w-100 border rounded shadow-sm" style="z-index:1050;max-height:220px;overflow:auto;"></div>'
            + '</div>'
            + '<input type="hidden" class="ticket-input-cantidad" value="1" aria-hidden="true"></div>'
            + '<div class="form-group col-md-2 mb-0"><label class="small mb-1 d-none d-md-block">&nbsp;</label>'
            + '<button type="button" class="btn btn-outline-primary btn-block btn-ticket-add-linea font-weight-bold" style="font-size:1.15rem;line-height:1.2;" title="Agregar 1 unidad">+</button></div>'
            + '</div></div>'
            + '<div class="d-flex flex-wrap align-items-center mb-2">'
            + '<span class="font-weight-bold mr-2">Total:</span>'
            + '<span class="h5 mb-0 text-primary ticket-total">' + escapeHtml(venta.precio_total_fmt) + '</span></div>'
            + '<div class="d-flex flex-wrap">'
            + '<form method="post" action="' + escapeHtml(pagoUrl(id)) + '" class="mr-2 mb-2 form-ticket-pago" data-venta-id="' + id + '">'
            + '<input type="hidden" name="_token" value="' + escapeHtml(csrfToken) + '">'
            + '<input type="hidden" name="metodo_pago" value="efectivo">'
            + '<button type="submit" class="btn btn-success btn-ticket-pay" disabled>Efectivo</button></form>'
            + '<form method="post" action="' + escapeHtml(pagoUrl(id)) + '" class="mr-2 mb-2 form-ticket-pago" data-venta-id="' + id + '">'
            + '<input type="hidden" name="_token" value="' + escapeHtml(csrfToken) + '">'
            + '<input type="hidden" name="metodo_pago" value="transferencia">'
            + '<button type="submit" class="btn btn-info btn-ticket-pay" disabled>Transferencia</button></form>'
            + '<button type="button" class="btn btn-secondary mb-2 btn-ticket-guardar">Guardar</button>'
            + '<button type="button" class="btn btn-outline-danger mb-2 ml-md-2 btn-ticket-cancelar" title="Anular ticket y devolver stock">Cancelar ticket</button></div>'
            + '<small class="text-muted d-block">Cada clic en <strong>+</strong> agrega 1 unidad. <strong>Cancelar ticket</strong> elimina la venta y devuelve el stock. Podés quitar una línea con <strong>−</strong>. El nombre con <strong>Guardar</strong> o al salir del campo cliente.</small>'
            + '</div>';

        return ''
            + '<div class="col-lg-4 col-md-6 mb-3 d-flex">'
            + '<div class="card mb-0 ticket-card shadow flex-fill w-100" data-venta-id="' + id + '">'
            + '<div class="card-header py-2 d-flex justify-content-between align-items-center ticket-card-header-toggle" style="cursor:pointer">'
            + '<div><strong class="ticket-card-nombre">' + escapeHtml(venta.nombre_cliente) + '</strong> '
            + '<span class="text-muted small ml-2 ticket-card-cancha-meta">' + escapeHtml(venta.cancha_nombre || '') + '</span>'
            + (venta.padre ? '<span class="badge badge-secondary ml-1">continuación #' + venta.padre.id + '</span>' : '')
            + '</div>'
            + '<div><span class="badge badge-primary ticket-card-total">' + escapeHtml(venta.precio_total_fmt) + '</span> '
            + '<span class="caja-texto-small ml-1">#' + id + '</span></div></div>'
            + '<div id="' + collapseId + '" class="ticket-card-panel is-open">'
            + '<div class="card-body pt-3">' + body + '</div></div></div></div>';
    }

    function buildTicketCardHtmlGrupo(venta) {
        var id = venta.id;
        var collapseId = 'ticket-collapse-' + id;
        var parts = venta.participantes || [];
        var activeId = grupoPickDefaultActiveId(venta);
        var detalles = venta.detalles || [];

        var tabs = '<div class="btn-group btn-group-sm mb-3 flex-wrap ticket-grupo-tabs" role="group">';
        parts.forEach(function(p) {
            var dis = (p.estado_pago === 'pagado') ? ' disabled title="Ya pagó"' : '';
            var cls = 'btn-outline-secondary';
            if (p.estado_pago !== 'pagado') cls = (p.id === activeId) ? 'btn-primary' : 'btn-outline-secondary';
            var badgeClass = 'badge-caja-jugador ' + ((p.estado_pago === 'pagado') ? 'badge-light' : 'badge-warning');
            var badgeTxt = (p.estado_pago === 'pagado') ? 'OK' : escapeHtml(p.subtotal_fmt);
            tabs += '<button type="button" class="btn ticket-tab-slot ' + cls + '" data-participante-id="' + p.id + '" data-slot="' + p.slot + '" data-estado="' + escapeHtml(p.estado_pago) + '"' + dis + '>J' + p.slot + ' <span class="badge ' + badgeClass + ' ml-1">' + badgeTxt + '</span></button>';
        });
        tabs += '</div>';

        var paneles = '<div class="ticket-grupo-paneles-jugador mb-3">';
        parts.forEach(function(p) {
            var visible = (parseInt(p.id, 10) === parseInt(activeId, 10)) ? '' : ' d-none';
            var lab = (p.slot === 1) ? 'Cliente / Jugador 1' : ('Jugador ' + p.slot);
            var readonly = (p.estado_pago === 'pagado') ? ' readonly' : '';
            var inpCls = (p.slot === 1) ? 'ticket-input-nombre' : 'ticket-input-participante-nombre';
            var busDis = (p.estado_pago === 'pagado') ? ' disabled' : '';
            var jid = (p.jugador_id != null) ? String(p.jugador_id) : '';
            paneles += '<div class="ticket-jugador-panel ticket-fila-participante border rounded p-2 mb-2' + visible + '" data-participante-id="' + p.id + '" data-slot="' + p.slot + '" data-jugador-id="' + jid + '">';
            paneles += '<label class="caja-label mb-1 d-block">' + escapeHtml(lab) + '</label>';
            paneles += '<div class="input-group input-group-sm">';
            paneles += '<input type="text" class="form-control ' + inpCls + '" value="' + escapeHtml(p.nombre) + '" autocomplete="off" data-participante-id="' + p.id + '"' + readonly + '>';
            paneles += '<div class="input-group-append"><button type="button" class="btn btn-outline-secondary btn-buscar-jugador-caja" data-participante-id="' + p.id + '"' + busDis + '><i class="fas fa-search"></i></button></div>';
            paneles += '</div>';
            paneles += '<div class="mt-2 pt-2 border-top">';
            paneles += '<div class="d-flex flex-wrap align-items-center justify-content-between mb-2">';
            paneles += '<span class="caja-texto-small">Consumo este jugador: <strong>' + escapeHtml(p.subtotal_fmt) + '</strong></span></div>';
            paneles += '<div class="ticket-jugador-pago-acciones d-flex flex-wrap align-items-center justify-content-end">';
            paneles += htmlTicketGrupoPagoAcciones(p);
            paneles += '</div></div></div>';
        });
        paneles += '<small class="text-muted ticket-nombre-status d-block"></small></div>';

        var lines = '';
        detalles.forEach(function(d) {
            var show = parseInt(d.stock_venta_participante_id, 10) === parseInt(activeId, 10);
            lines += '<tr class="ticket-line-row" data-participante-id="' + d.stock_venta_participante_id + '" style="' + (show ? '' : 'display:none;') + '">';
            lines += '<td>' + escapeHtml(d.producto_nombre || '') + '</td>';
            lines += '<td class="text-center">' + d.cantidad + '</td>';
            lines += '<td class="text-right">' + escapeHtml(d.subtotal_fmt) + '</td>';
            lines += '<td class="text-center p-1 align-middle">'
                + '<button type="button" class="btn btn-sm btn-outline-danger btn-ticket-remove-linea px-2 py-0 font-weight-bold" data-detalle-id="' + d.id + '" title="Quitar línea">−</button>'
                + '<button type="button" class="btn btn-sm btn-outline-info btn-ticket-dividir-linea px-2 py-0 font-weight-bold ml-1" data-detalle-id="' + d.id + '" data-participante-id="' + (d.stock_venta_participante_id || '') + '" title="Dividir con otros jugadores">÷</button>'
                + '</td></tr>';
        });

        var body = ''
            + '<div class="ticket-body-inner" data-venta-id="' + id + '" data-modo-grupo="1">'
            + htmlBloquePadre(venta.padre)
            + '<div class="mb-2"><span class="caja-label text-primary">Total del ticket:</span> '
            + '<span class="h5 mb-0 text-primary ticket-total ml-2">' + escapeHtml(venta.precio_total_fmt) + '</span></div>'
            + tabs
            + paneles
            + '<div class="table-responsive mb-2"><table class="table table-sm table-bordered mb-0">'
            + '<thead class="thead-light"><tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-right">Subtotal</th><th class="text-center p-1" style="width:44px"></th></tr></thead>'
            + '<tbody class="ticket-lines-tbody">' + lines + '</tbody></table></div>'
            + '<div class="mb-3 ticket-add-product-block">'
            + '<label class="small mb-1 d-block">Categoría — <span class="text-muted ticket-add-para-label">cargá para el jugador seleccionado arriba</span></label>'
            + '<div class="d-flex flex-wrap ticket-cat-pills align-items-center" style="gap:6px;">' + categoriasPillsHtml() + '</div>'
            + '<div class="form-row align-items-end mt-2">'
            + '<div class="form-group col-md-10 mb-2 mb-md-0"><label class="small mb-1">Producto</label>'
            + '<div class="position-relative ticket-producto-autocomplete">'
            + '<input type="text" class="form-control ticket-producto-search" placeholder="Elegí una categoría…" autocomplete="off" disabled>'
            + '<input type="hidden" class="ticket-producto-id">'
            + '<div class="ticket-producto-dropdown d-none position-absolute w-100 bg-white border rounded shadow-sm" style="z-index:1050;max-height:220px;overflow:auto;"></div>'
            + '</div>'
            + '<input type="hidden" class="ticket-input-cantidad" value="1" aria-hidden="true">'
            + '<input type="hidden" class="ticket-active-participante-id" value="' + activeId + '"></div>'
            + '<div class="form-group col-md-2 mb-0"><label class="small mb-1 d-none d-md-block">&nbsp;</label>'
            + '<button type="button" class="btn btn-outline-primary btn-block btn-ticket-add-linea font-weight-bold" style="font-size:1.15rem;line-height:1.2;" title="Agregar 1 unidad">+</button></div>'
            + '</div></div>'
            + '<div class="d-flex flex-wrap">'
            + '<button type="button" class="btn btn-secondary mb-2 btn-ticket-guardar">Guardar nombres</button>'
            + '<button type="button" class="btn btn-outline-danger mb-2 ml-md-2 btn-ticket-cancelar" title="Anular ticket y devolver stock">Cancelar ticket</button></div>'
            + '<small class="text-muted d-block">Tocá <strong>J1–J4</strong> para ver el nombre, la búsqueda y el cobro de ese jugador. Cargá productos con <strong>+</strong>. El ticket se cierra cuando los cuatro estén pagados (o “Sin consumo”).</small>'
            + '</div>';

        return ''
            + '<div class="col-lg-4 col-md-6 mb-3 d-flex">'
            + '<div class="card mb-0 ticket-card shadow flex-fill w-100" data-venta-id="' + id + '">'
            + '<div class="card-header py-2 d-flex justify-content-between align-items-center ticket-card-header-toggle" style="cursor:pointer">'
            + '<div><strong class="ticket-card-nombre">' + escapeHtml(venta.nombre_cliente) + '</strong> '
            + '<span class="text-muted small ml-2 ticket-card-cancha-meta">' + escapeHtml(venta.cancha_nombre || '') + '</span>'
            + (venta.padre ? '<span class="badge badge-secondary ml-1">continuación #' + venta.padre.id + '</span>' : '')
            + '</div>'
            + '<div><span class="badge badge-primary ticket-card-total">' + escapeHtml(venta.precio_total_fmt) + '</span> '
            + '<span class="caja-texto-small ml-1">#' + id + '</span></div></div>'
            + '<div id="' + collapseId + '" class="ticket-card-panel is-open">'
            + '<div class="card-body pt-3">' + body + '</div></div></div></div>';
    }

    function buildTicketCardHtml(venta) {
        if (venta.es_torneo) {
            window.location.reload();
            return '';
        }
        if (venta.modo_grupo) return buildTicketCardHtmlGrupo(venta);
        return buildTicketCardHtmlSimple(venta);
    }

    var selectedCanchaId = '';
    var selectedLabel = '';
    var esTorneoSeleccionado = false;
    var nuevoNombre = document.getElementById('nuevo-nombre-cliente');
    var nuevoCanchaLabel = document.getElementById('nuevo-cancha-label');
    var nuevoStockCanchaId = document.getElementById('nuevo-stock-cancha-id');
    var btnAbrir = document.getElementById('btn-abrir-ticket');

    function syncNuevoPanel() {
        var ok = (selectedCanchaId || esTorneoSeleccionado) && nuevoNombre && nuevoNombre.value.trim().length > 0;
        if (btnAbrir) btnAbrir.disabled = !ok;
        if (nuevoNombre) nuevoNombre.disabled = !(selectedCanchaId || esTorneoSeleccionado);
    }

    document.querySelectorAll('.btn-cancha-caja').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var rowPanel = document.getElementById('row-panel-nuevo-ticket');
            if (rowPanel) {
                rowPanel.classList.remove('d-none');
                rowPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            document.querySelectorAll('.btn-cancha-caja').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            esTorneoSeleccionado = btn.getAttribute('data-es-torneo') === '1';
            selectedCanchaId = esTorneoSeleccionado ? '' : (btn.getAttribute('data-cancha-id') || '');
            selectedLabel = btn.textContent.trim();
            if (nuevoStockCanchaId) nuevoStockCanchaId.value = selectedCanchaId;
            if (nuevoCanchaLabel) nuevoCanchaLabel.value = selectedLabel;
            syncNuevoPanel();
            // Cambiar label/placeholder según selección
            var labelNombre = document.getElementById('nuevo-ticket-label-nombre');
            var ayuda = document.getElementById('nuevo-ticket-ayuda');
            if (esTorneoSeleccionado) {
                if (labelNombre) labelNombre.textContent = 'Nombre del torneo / americano';
                if (nuevoNombre) nuevoNombre.placeholder = 'Ej. Americano Viernes 20:00';
                if (ayuda) ayuda.innerHTML = 'Escribí el nombre del torneo o americano y tocá <strong>Abrir ticket</strong>.';
            } else {
                if (labelNombre) labelNombre.textContent = 'Cliente';
                if (nuevoNombre) nuevoNombre.placeholder = 'Ej. Rodri · Cancha 1 17:00';
                if (ayuda) ayuda.innerHTML = 'Escribí el nombre del cliente (podés incluir horario u otra referencia en el mismo campo) y tocá <strong>Abrir ticket</strong>.';
            }
            // Ocultar/mostrar grilla de turnos
            var turnosGrid = document.getElementById('turnos-grid-container');
            if (turnosGrid) {
                if (esTorneoSeleccionado) {
                    turnosGrid.classList.add('d-none');
                } else {
                    turnosGrid.classList.remove('d-none');
                }
            }
            if (nuevoNombre) nuevoNombre.focus();
        });
    });
    if (nuevoNombre) {
        nuevoNombre.addEventListener('input', syncNuevoPanel);
    }
    syncNuevoPanel();

    if (btnAbrir) {
        btnAbrir.addEventListener('click', function() {
            if ((!selectedCanchaId && !esTorneoSeleccionado) || !nuevoNombre || !nuevoNombre.value.trim()) return;
            btnAbrir.disabled = true;
            var body = {
                _token: csrfToken,
                nombre_cliente: nuevoNombre.value.trim()
            };
            if (esTorneoSeleccionado) {
                body.es_torneo = true;
            } else {
                body.stock_cancha_id = parseInt(selectedCanchaId, 10);
            }
            fetch(borradorUrl(), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify(mergeCajaFecha(body))
            }).then(parseFetchResponse).then(function(x) {
                btnAbrir.disabled = false;
                if (!x.ok) {
                    var msg = (x.j && x.j.message) || (x.j && x.j.errors && Object.values(x.j.errors).flat().join(' '));
                    alert(msg || 'No se pudo abrir el ticket');
                    return;
                }
                if (!x.j.venta) {
                    alert('Respuesta inválida');
                    return;
                }
                var v = x.j.venta;
                if (v.es_torneo) {
                    window.location.href = adminCajaBasePath() + '/torneo/' + v.id;
                    return;
                }
                var list = document.getElementById('lista-tickets-abiertos');
                list.insertAdjacentHTML('afterbegin', buildTicketCardHtml(v));
                var newCard = list.querySelector('.ticket-card[data-venta-id="' + v.id + '"]');
                if (newCard) {
                    wireTicketCard(newCard);
                    applyVentaJson(newCard, v);
                }
                if (x.j && x.j.resumen) applyCajaResumen(x.j.resumen);
                nuevoNombre.value = '';
                document.querySelectorAll('.btn-cancha-caja').forEach(function(b) { b.classList.remove('active'); });
                selectedCanchaId = '';
                esTorneoSeleccionado = false;
                if (nuevoStockCanchaId) nuevoStockCanchaId.value = '';
                if (nuevoCanchaLabel) nuevoCanchaLabel.value = '';
                syncNuevoPanel();
                var rowPanelNuevo = document.getElementById('row-panel-nuevo-ticket');
                if (rowPanelNuevo) rowPanelNuevo.classList.add('d-none');
                syncTicketsToggleBtn();
            }).catch(function(e) {
                btnAbrir.disabled = false;
                alert((e && e.message) ? e.message : 'Error de red');
            });
        });
    }

    // Turnos grid handlers
    document.querySelectorAll('.btn-turno-vender').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var celda = btn.closest('.turno-celda');
            if (!celda) return;
            var canchaId = celda.getAttribute('data-cancha-id');
            var hora = celda.getAttribute('data-hora');
            var tipo = celda.getAttribute('data-tipo');
            var nombre = celda.getAttribute('data-nombre');
            var nombreCliente = tipo === 'fijo' && nombre ? nombre : ('Turno ' + hora);
            btn.disabled = true;
            fetch(borradorUrl(), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify(mergeCajaFecha({
                    _token: csrfToken,
                    nombre_cliente: nombreCliente,
                    stock_cancha_id: parseInt(canchaId, 10),
                    nombre_turno: hora
                }))
            }).then(parseFetchResponse).then(function(x) {
                btn.disabled = false;
                if (!x.ok) {
                    var msg = (x.j && x.j.message) || (x.j && x.j.errors && Object.values(x.j.errors).flat().join(' '));
                    alert(msg || 'No se pudo abrir el ticket');
                    return;
                }
                if (!x.j.venta) {
                    alert('Respuesta inválida');
                    return;
                }
                var v = x.j.venta;
                var list = document.getElementById('lista-tickets-abiertos');
                list.insertAdjacentHTML('afterbegin', buildTicketCardHtml(v));
                var newCard = list.querySelector('.ticket-card[data-venta-id="' + v.id + '"]');
                if (newCard) {
                    wireTicketCard(newCard);
                    applyVentaJson(newCard, v);
                }
                if (x.j && x.j.resumen) applyCajaResumen(x.j.resumen);
                // Actualizar celda a vendido
                celda.setAttribute('data-tipo', 'vendido');
                celda.setAttribute('data-nombre', v.nombre_cliente);
                celda.setAttribute('data-venta-id', v.id);
                celda.classList.remove('libre', 'fijo');
                celda.classList.add('vendido');
                var badge = celda.querySelector('.turno-badge');
                if (badge) { badge.className = 'badge badge-warning turno-badge'; badge.textContent = 'Pendiente'; }
                var nombreDiv = celda.querySelector('.turno-nombre');
                if (nombreDiv) { nombreDiv.textContent = v.nombre_cliente; nombreDiv.classList.remove('text-muted'); }
                btn.outerHTML = '<button type="button" class="btn btn-outline-warning btn-turno-accion btn-turno-ver" data-venta-id="' + v.id + '">Ver ticket</button>';
                // Re-wire nuevo botón
                var nuevoBtn = celda.querySelector('.btn-turno-ver');
                if (nuevoBtn) {
                    nuevoBtn.addEventListener('click', function(ev) {
                        ev.stopPropagation();
                        var vid = nuevoBtn.getAttribute('data-venta-id');
                        var card = document.querySelector('.ticket-card[data-venta-id="' + vid + '"]');
                        if (card) {
                            var panel = card.querySelector('.ticket-card-panel');
                            if (panel) {
                                panel.classList.add('is-open');
                                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                syncTicketsToggleBtn();
                            }
                        }
                    });
                }
                syncTicketsToggleBtn();
            }).catch(function(e) {
                btn.disabled = false;
                alert((e && e.message) ? e.message : 'Error de red');
            });
        });
    });

    document.querySelectorAll('.btn-turno-ver').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var vid = btn.getAttribute('data-venta-id');
            var card = document.querySelector('.ticket-card[data-venta-id="' + vid + '"]');
            if (card) {
                var panel = card.querySelector('.ticket-card-panel');
                if (panel) {
                    panel.classList.add('is-open');
                    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    syncTicketsToggleBtn();
                }
            }
        });
    });

    var listaJugModalEl = document.getElementById('caja-jugador-lista');
    if (listaJugModalEl) {
        listaJugModalEl.addEventListener('click', function(ev) {
            var eb = ev.target.closest('.btn-elegir-jugador-caja');
            if (!eb) return;
            var jid = parseInt(eb.getAttribute('data-id'), 10);
            var nombreCompleto = eb.textContent.trim();
            var tgt = cajaModalTarget;
            if (!tgt || !tgt.ventaId) return;
            var stModal = tgt.inner ? tgt.inner.querySelector('.ticket-nombre-status') : null;
            function cerrarModal() {
                if (window.jQuery) window.jQuery('#modal-buscar-jugador-caja').modal('hide');
            }
            if (tgt.solo) {
                patchNombre(tgt.ventaId, nombreCompleto, stModal, function() {
                    var inpS = tgt.inner && tgt.inner.querySelector('.ticket-input-nombre');
                    var hdrS = tgt.card && tgt.card.querySelector('.ticket-card-nombre');
                    if (inpS) inpS.value = nombreCompleto;
                    if (hdrS) hdrS.textContent = nombreCompleto;
                }).then(cerrarModal).catch(cerrarModal);
                return;
            }
            if (!tgt.participanteId) return;
            patchParticipante(tgt.ventaId, tgt.participanteId, nombreCompleto, stModal, function() {
                var rowM = tgt.inner && tgt.inner.querySelector('.ticket-fila-participante[data-participante-id="' + tgt.participanteId + '"]');
                if (rowM) {
                    rowM.dataset.jugadorId = String(jid);
                    var inpM = rowM.querySelector('.ticket-input-nombre, .ticket-input-participante-nombre');
                    if (inpM) inpM.value = nombreCompleto;
                }
                var hdrM = tgt.card && tgt.card.querySelector('.ticket-card-nombre');
                var n1m = tgt.inner && tgt.inner.querySelector('.ticket-input-nombre');
                if (hdrM && n1m) hdrM.textContent = n1m.value.trim() || nombreCompleto;
            }, jid).then(cerrarModal).catch(cerrarModal);
        });
    }
    var btnConfirmarDividir = document.getElementById('btn-confirmar-dividir');
    if (btnConfirmarDividir && !btnConfirmarDividir._wired) {
        btnConfirmarDividir._wired = true;
        btnConfirmarDividir.addEventListener('click', function() {
            var did = document.getElementById('dividir-linea-detalle-id').value;
            var vid = document.getElementById('dividir-linea-venta-id').value;
            var cardId = document.getElementById('dividir-linea-card-id').value;
            if (!did || !vid) return;
            var checks = document.querySelectorAll('#dividir-linea-opciones input[type="checkbox"]:checked');
            if (!checks.length) { alert('Seleccioná al menos un jugador para dividir.'); return; }
            var pids = [];
            checks.forEach(function(c) { pids.push(parseInt(c.value, 10)); });
            // Incluir siempre al jugador dueño original
            var wrapDiv = document.getElementById('dividir-linea-opciones');
            var dueñoPid = wrapDiv && wrapDiv.dataset.dueñoPid ? parseInt(wrapDiv.dataset.dueñoPid, 10) : null;
            if (dueñoPid && pids.indexOf(dueñoPid) < 0) pids.push(dueñoPid);
            btnConfirmarDividir.disabled = true;
            fetch(dividirLineaUrl(vid, did), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify(mergeCajaFecha({ _token: csrfToken, participantes_ids: pids }))
            }).then(parseFetchResponse).then(function(x) {
                btnConfirmarDividir.disabled = false;
                if (!x.ok) {
                    var msg = (x.j && x.j.message) || (x.j && x.j.errors && Object.values(x.j.errors).flat().join(' '));
                    alert(msg || ('Error HTTP ' + x.status));
                    return;
                }
                if (x.j && x.j.resumen) applyCajaResumen(x.j.resumen);
                var card = document.querySelector('.ticket-card[data-venta-id="' + cardId + '"]');
                if (x.j && x.j.venta && card) applyVentaJson(card, x.j.venta);
                if (window.jQuery) window.jQuery('#modal-dividir-linea').modal('hide');
            }).catch(function(err) {
                btnConfirmarDividir.disabled = false;
                alert((err && err.message) ? err.message : 'Error de red');
            });
        });
    }

    var jBuscarInp = document.getElementById('caja-jugador-buscar-input');
    if (jBuscarInp && !jBuscarInp._wiredModalSearch) {
        jBuscarInp._wiredModalSearch = true;
        jBuscarInp.addEventListener('keyup', function() {
            renderCajaJugadorLista(jBuscarInp.value);
        });
    }
})();
</script>

@include('bahia_padel.admin.caja._caja_apertura_control')
@include('bahia_padel.admin.caja._font_size_control')
@endsection
