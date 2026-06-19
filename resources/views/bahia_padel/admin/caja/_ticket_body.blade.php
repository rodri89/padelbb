@php
    use App\Services\StockVentaService;
    $vid = $venta->id;
    $saldoPendiente = StockVentaService::saldoPendienteVenta($venta);
@endphp
@if($venta->es_torneo)
    @include('bahia_padel.admin.caja._ticket_body_torneo', ['venta' => $venta, 'fmtMoney' => $fmtMoney, 'categoriasVenta' => $categoriasVenta, 'productosVenta' => $productosVenta])
@elseif($venta->participantes && $venta->participantes->isNotEmpty())
    @include('bahia_padel.admin.caja._ticket_body_grupo', ['venta' => $venta, 'fmtMoney' => $fmtMoney, 'categoriasVenta' => $categoriasVenta])
@else
<div class="ticket-body-inner" data-venta-id="{{ $vid }}" data-modo-grupo="0">
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
    <div class="form-group">
        <label class="caja-label mb-1">Cliente</label>
        <div class="input-group">
            <input type="text" class="form-control ticket-input-nombre" value="{{ $venta->nombre_cliente }}" autocomplete="off">
            <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary btn-buscar-jugador-caja btn-buscar-jugador-solo" title="Buscar jugador (solo rellena nombre)">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <small class="caja-texto-small ticket-nombre-status"></small>
    </div>
    <div class="table-responsive mb-2">
        <table class="table table-sm table-bordered mb-0">
            <thead class="thead-light"><tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-right">Subtotal</th><th class="text-center">Estado</th><th class="text-center p-1" style="min-width:100px">Acciones</th></tr></thead>
            <tbody class="ticket-lines-tbody">
                @foreach($venta->detalles as $d)
                    @php $lineaPagada = ($d->estado_pago ?? 'pendiente') === 'pagado'; @endphp
                    <tr data-detalle-id="{{ $d->id }}" data-estado-pago="{{ $d->estado_pago ?? 'pendiente' }}">
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
        <label class="caja-label mb-1 d-block">Categoría</label>
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
            </div>
            <div class="form-group col-md-2 mb-0">
                <label class="caja-label mb-1 d-none d-md-block">&nbsp;</label>
                <button type="button" class="btn btn-outline-primary btn-block btn-ticket-add-linea font-weight-bold" style="font-size:1.15rem;line-height:1.2;" title="Agregar 1 unidad">+</button>
            </div>
        </div>
    </div>
    <div class="d-flex flex-wrap align-items-center mb-2">
        <span class="caja-label mr-2">Total:</span>
        <span class="h5 mb-0 text-primary ticket-total">{{ $fmtMoney($venta->precio_total) }}</span>
    </div>
    <div class="d-flex flex-wrap align-items-center mb-2">
        <span class="font-weight-bold mr-2">Saldo pendiente:</span>
        <span class="h5 mb-0 text-danger ticket-saldo-pendiente">{{ $fmtMoney($saldoPendiente) }}</span>
    </div>
    <div class="d-flex flex-wrap">
        <form method="post" action="{{ route('admincaja.venta.pago', $venta) }}" class="mr-2 mb-2 form-ticket-pago" data-venta-id="{{ $vid }}">
            @csrf
            <input type="hidden" name="metodo_pago" value="efectivo">
            <button type="submit" class="btn btn-success btn-ticket-pay" @if((float)$saldoPendiente <= 0) disabled @endif>Cobrar resto (Efectivo)</button>
        </form>
        <form method="post" action="{{ route('admincaja.venta.pago', $venta) }}" class="mr-2 mb-2 form-ticket-pago" data-venta-id="{{ $vid }}">
            @csrf
            <input type="hidden" name="metodo_pago" value="transferencia">
            <button type="submit" class="btn btn-info btn-ticket-pay" @if((float)$saldoPendiente <= 0) disabled @endif>Cobrar resto (Transferencia)</button>
        </form>
        <button type="button" class="btn btn-secondary mb-2 btn-ticket-guardar">Guardar</button>
        <button type="button" class="btn btn-outline-danger mb-2 ml-md-2 btn-ticket-cancelar" title="Anular ticket y devolver stock">Cancelar ticket</button>
    </div>
    <small class="caja-texto-small d-block">Cada clic en <strong>+</strong> agrega 1 unidad. Cobrá producto por producto con <strong>$E</strong> / <strong>$T</strong> o el saldo restante con los botones de abajo. <strong>Cancelar ticket</strong> solo si no hay productos cobrados. El nombre se guarda con <strong>Guardar</strong> o al salir del campo cliente.</small>
</div>
@endif
