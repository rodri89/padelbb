@php
    $vid = $venta->id;
    $parts = $venta->participantes->sortBy('slot')->values();
@endphp
<div class="ticket-body-inner" data-venta-id="{{ $vid }}" data-modo-grupo="1" data-es-torneo="1">
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
        <span class="caja-label text-primary">Total del torneo:</span>
        <span class="h5 mb-0 text-primary ticket-total ml-2">{{ $fmtMoney($venta->precio_total) }}</span>
    </div>

    <div class="mb-3">
        <div class="form-row align-items-end">
            <div class="form-group col-md-6 mb-2">
                <label class="caja-label mb-1">Agregar jugador</label>
                <input type="text" class="form-control torneo-nuevo-participante" placeholder="Nombre del jugador" autocomplete="off">
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="caja-label mb-1 d-none d-md-block">&nbsp;</label>
                <button type="button" class="btn btn-primary btn-block btn-torneo-agregar-participante">Agregar</button>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="caja-label mb-1">Inscripción a todos</label>
                <select class="form-control torneo-producto-inscripcion">
                    <option value="">Elegir producto…</option>
                    @foreach($productosVenta as $prod)
                        <option value="{{ $prod->id }}">{{ $prod->nombre }} — {{ $fmtMoney($prod->precio_unitario) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="button" class="btn btn-outline-primary btn-torneo-inscripcion-todos">Cargar inscripción a todos</button>
    </div>

    <div class="table-responsive mb-2">
        <table class="table table-sm table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Jugador</th>
                    <th class="text-right">Consumido</th>
                    <th class="text-right">Pagado</th>
                    <th class="text-right">Saldo</th>
                    <th class="text-center" style="width:180px">Acciones</th>
                </tr>
            </thead>
            <tbody class="torneo-participantes-tbody">
                @foreach($parts as $p)
                    @php
                        $consumido = (float) $venta->detalles->where('stock_venta_participante_id', $p->id)->sum('subtotal');
                        $pagado = (float) $venta->detalles->where('stock_venta_participante_id', $p->id)->where('estado_pago', 'pagado')->sum('subtotal');
                        $saldo = $consumido - $pagado;
                    @endphp
                    <tr data-participante-id="{{ $p->id }}">
                        <td>{{ $p->slot }}</td>
                        <td>{{ $p->nombre }}</td>
                        <td class="text-right">{{ $fmtMoney($consumido) }}</td>
                        <td class="text-right">{{ $fmtMoney($pagado) }}</td>
                        <td class="text-right {{ $saldo > 0 ? 'text-danger font-weight-bold' : 'text-success' }}">{{ $fmtMoney($saldo) }}</td>
                        <td class="text-center">
                            @if($saldo > 0)
                                <button type="button" class="btn btn-sm btn-outline-primary btn-torneo-ver-detalle" data-participante-id="{{ $p->id }}" data-participante-nombre="{{ $p->nombre }}">Ver detalle</button>
                                <button type="button" class="btn btn-sm btn-success btn-participante-pago" data-participante-id="{{ $p->id }}" data-metodo="efectivo">Pagar todo</button>
                            @else
                                <span class="badge badge-success">Pagado</span>
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
                <input type="hidden" class="ticket-active-participante-id" value="{{ $parts->first()?->id }}">
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
    <small class="caja-texto-small d-block">Agregá jugadores con el campo de arriba. Cargá productos con <strong>+</strong> para el jugador activo. El ticket se cierra cuando todos los jugadores estén pagados.</small>
</div>

<div class="modal fade" id="modal-torneo-detalle-{{ $vid }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title torneo-detalle-titulo">Detalle del jugador</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body py-2">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-right">Subtotal</th><th class="text-center">Estado</th><th class="text-center" style="width:100px"></th></tr>
                        </thead>
                        <tbody class="torneo-detalle-lines-tbody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
