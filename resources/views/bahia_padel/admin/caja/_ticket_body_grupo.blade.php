@php
    $vid = $venta->id;
    $parts = $venta->participantes->sortBy('slot')->values();
@endphp
<div class="ticket-body-inner" data-venta-id="{{ $vid }}" data-modo-grupo="1">
    @if($venta->padre)
    <div class="mb-3 p-2 border rounded bg-light">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="caja-texto-small font-weight-bold">Ticket original #{{ $venta->padre->id }}</span>
            <span class="caja-texto-small text-success font-weight-bold">{{ $fmtMoney($venta->padre->precio_total) }} (pagado)</span>
        </div>
        <table class="table table-sm table-bordered mb-0">
            <thead class="thead-light"><tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-right">Subtotal</th></tr></thead>
            <tbody>
                @foreach($venta->padre->detalles as $d)
                <tr><td>{{ $d->producto?->nombre }}</td><td class="text-center">{{ $d->cantidad }}</td><td class="text-right">{{ $fmtMoney($d->subtotal) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    <div class="mb-2">
        <span class="caja-label text-primary">Total del ticket:</span>
        <span class="h5 mb-0 text-primary ticket-total ml-2">{{ $fmtMoney($venta->precio_total) }}</span>
    </div>

    <div class="btn-group mb-3 flex-wrap ticket-grupo-tabs" role="group">
        @php
            $firstPendienteTab = $parts->firstWhere('estado_pago', 'pendiente');
            $defaultActiveTabId = $firstPendienteTab ? $firstPendienteTab->id : $parts->first()->id;
        @endphp
        @foreach($parts as $p)
            @php
                $subPart = $venta->detalles->where('stock_venta_participante_id', $p->id)->where('estado_pago', 'pendiente')->sum('subtotal');
                $tabActive = (int) $p->id === (int) $defaultActiveTabId;
            @endphp
            <button type="button"
                class="btn ticket-tab-slot {{ $tabActive ? 'btn-primary' : 'btn-outline-secondary' }}" style="font-size:1rem;padding:0.5rem 0.75rem;"
                data-participante-id="{{ $p->id }}"
                data-slot="{{ $p->slot }}"
                data-estado="{{ $p->estado_pago }}"
                @if($p->estado_pago === 'pagado') disabled title="Ya pagó" @endif
            >
                J{{ $p->slot }}
                @if($p->estado_pago === 'pagado')
                    <span class="badge badge-caja-jugador badge-caja-jugador-ok ml-1">OK</span>
                @else
                    <span class="badge badge-warning badge-caja-jugador ml-1">{{ $fmtMoney($subPart) }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <div class="ticket-grupo-paneles-jugador mb-3">
        @foreach($parts as $p)
            @php
                $subPart = $venta->detalles->where('stock_venta_participante_id', $p->id)->where('estado_pago', 'pendiente')->sum('subtotal');
                $panelVisible = (int) $p->id === (int) $defaultActiveTabId;
            @endphp
            <div class="ticket-jugador-panel ticket-fila-participante border rounded p-2 mb-2 {{ $panelVisible ? '' : 'd-none' }}"
                data-participante-id="{{ $p->id }}"
                data-slot="{{ $p->slot }}"
                data-jugador-id="{{ $p->jugador_id ?? '' }}"
            >
                <label class="caja-label mb-1 d-block">
                    @if($p->slot === 1) Cliente / Jugador 1 @else Jugador {{ $p->slot }} @endif
                </label>
                <div class="input-group input-group-sm">
                    @if($p->slot === 1)
                        <input type="text" class="form-control ticket-input-nombre" value="{{ $p->nombre }}" autocomplete="off" data-participante-id="{{ $p->id }}" {{ $p->estado_pago === 'pagado' ? 'readonly' : '' }}>
                    @else
                        <input type="text" class="form-control ticket-input-participante-nombre" value="{{ $p->nombre }}" autocomplete="off" data-participante-id="{{ $p->id }}" {{ $p->estado_pago === 'pagado' ? 'readonly' : '' }}>
                    @endif
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary btn-buscar-jugador-caja" data-participante-id="{{ $p->id }}" title="Buscar jugador" {{ $p->estado_pago === 'pagado' ? 'disabled' : '' }}>
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <div class="mt-2 pt-2 border-top">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                        <span class="caja-texto-small">Saldo pendiente: <strong class="text-danger">{{ $fmtMoney($subPart) }}</strong></span>
                    </div>
                    <div class="ticket-jugador-pago-acciones d-flex flex-wrap align-items-center justify-content-end">
                        @if($p->estado_pago === 'pagado')
                            <span class="badge badge-success">Pagado @if($p->metodo_pago)({{ $p->metodo_pago }})@endif</span>
                        @else
                            @php
                                $subPendiente = $venta->detalles->where('stock_venta_participante_id', $p->id)->where('estado_pago', 'pendiente')->sum('subtotal');
                            @endphp
                            @if((float)$subPendiente <= 0)
                                <button type="button" class="btn btn-outline-secondary btn-participante-sin-consumo mr-1" data-participante-id="{{ $p->id }}">Sin consumo</button>
                            @else
                                <button type="button" class="btn btn-success btn-participante-pago mr-1" data-participante-id="{{ $p->id }}" data-metodo="efectivo">Pagar todo E ({{ $fmtMoney($subPendiente) }})</button>
                                <button type="button" class="btn btn-info btn-participante-pago" data-participante-id="{{ $p->id }}" data-metodo="transferencia">Pagar todo T ({{ $fmtMoney($subPendiente) }})</button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
        <small class="text-muted ticket-nombre-status d-block"></small>
    </div>

    <div class="table-responsive mb-2">
        <table class="table table-sm table-bordered mb-0">
            <thead class="thead-light"><tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-right">Subtotal</th><th class="text-center">Estado</th><th class="text-center p-1" style="min-width:120px">Acciones</th></tr></thead>
            <tbody class="ticket-lines-tbody">
                @foreach($venta->detalles as $d)
                    @php $lineaPagada = ($d->estado_pago ?? 'pendiente') === 'pagado'; @endphp
                    <tr class="ticket-line-row" data-participante-id="{{ $d->stock_venta_participante_id }}" data-detalle-id="{{ $d->id }}" data-estado-pago="{{ $d->estado_pago ?? 'pendiente' }}" style="{{ (int)$d->stock_venta_participante_id === (int)$defaultActiveTabId ? '' : 'display:none;' }}">
                        <td>
                            {{ $d->producto?->nombre }}
                            @if($lineaPagada)
                                <span class="badge badge-success ml-1">Pagado</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $d->cantidad }}</td>
                        <td class="text-right">{{ $fmtMoney($d->subtotal) }}</td>
                        <td class="text-center">
                            @if($d->estado_pago === 'pagado')
                                <span class="badge badge-success">✓ Pagado</span>
                            @else
                                <span class="badge badge-warning">Pendiente</span>
                            @endif
                        </td>
                        <td class="text-center p-1 align-middle text-nowrap">
                            @if($lineaPagada)
                                <span class="text-muted small">—</span>
                            @else
                                <button type="button" class="btn btn-sm btn-outline-danger btn-ticket-remove-linea px-2 py-0 font-weight-bold" data-detalle-id="{{ $d->id }}" title="Quitar línea">−</button>
                                <button type="button" class="btn btn-sm btn-outline-info btn-ticket-dividir-linea px-2 py-0 font-weight-bold ml-1" data-detalle-id="{{ $d->id }}" data-participante-id="{{ $d->stock_venta_participante_id }}" title="Dividir con otros jugadores">÷</button>
                                <button type="button" class="btn btn-sm btn-outline-success btn-linea-pago px-2 py-0 font-weight-bold ml-1" data-detalle-id="{{ $d->id }}" data-metodo="efectivo" title="Pagar en efectivo">$E</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-linea-pago px-2 py-0 font-weight-bold ml-1" data-detalle-id="{{ $d->id }}" data-metodo="transferencia" title="Pagar por transferencia">$T</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mb-3 ticket-add-product-block">
        <label class="caja-label mb-1 d-block">Categoría — <span class="text-muted ticket-add-para-label">cargá para el jugador seleccionado arriba</span></label>
        <div class="d-flex flex-wrap ticket-cat-pills align-items-center" style="gap:6px;">
            @foreach($categoriasVenta as $cat)
                @php
                    $abbr = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($cat->nombre, 0, 2));
                @endphp
                <button type="button"
                    class="btn btn-sm btn-outline-secondary ticket-cat-btn rounded-circle p-0 d-inline-flex align-items-center justify-content-center text-nowrap"
                    style="width:44px;height:44px;font-size:0.85rem;font-weight:700;letter-spacing:-0.02em;"
                    data-categoria-id="{{ $cat->id }}"
                    title="{{ $cat->nombre }}">{{ $abbr }}</button>
            @endforeach
        </div>
        <div class="form-row align-items-end mt-2">
            <div class="form-group col-md-10 mb-2 mb-md-0">
                <label class="caja-label mb-1">Producto</label>
                <div class="position-relative ticket-producto-autocomplete">
                    <input type="text" class="form-control ticket-producto-search" placeholder="Elegí una categoría…" autocomplete="off" disabled>
                    <input type="hidden" class="ticket-producto-id">
                    <div class="ticket-producto-dropdown d-none position-absolute w-100 border rounded shadow-sm" style="z-index:1050;max-height:220px;overflow:auto;"></div>
                </div>
                <input type="hidden" class="ticket-input-cantidad" value="1" aria-hidden="true">
                <input type="hidden" class="ticket-active-participante-id" value="{{ $defaultActiveTabId }}">
            </div>
            <div class="form-group col-md-2 mb-0">
                <label class="caja-label mb-1 d-none d-md-block">&nbsp;</label>
                <button type="button" class="btn btn-outline-primary btn-block btn-ticket-add-linea font-weight-bold" style="font-size:1.15rem;line-height:1.2;" title="Agregar 1 unidad">+</button>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap">
        <button type="button" class="btn btn-secondary mb-2 btn-ticket-guardar">Guardar nombres</button>
        <button type="button" class="btn btn-outline-danger mb-2 ml-md-2 btn-ticket-cancelar" title="Anular ticket y devolver stock">Cancelar ticket</button>
    </div>
    <small class="caja-texto-small d-block">Tocá <strong>J1–J4</strong> para ver los productos de cada jugador. Cobrá con <strong>$E</strong> / <strong>$T</strong> en cada línea o usá <strong>Pagar todo</strong> para el jugador activo. El ticket se cierra cuando los cuatro estén pagados (o "Sin consumo").</small>
</div>
