@extends('bahia_padel/admin/plantilla')

@section('title_header', 'Torneo / Americano — ' . $venta->nombre_cliente)

@section('contenedor')
<style>
.torneo-total { font-size: 1.5rem; font-weight: 700; }
.torneo-jugador-table th, .torneo-jugador-table td { font-size: 1rem; vertical-align: middle; }
.torneo-acciones .btn { font-size: 0.9rem; padding: 0.3rem 0.6rem; }
.torneo-cat-btn { border-width: 2px; transition: transform .12s ease, box-shadow .12s ease; width: 44px; height: 44px; font-size: 0.85rem !important; }
.torneo-cat-btn:hover { transform: scale(1.06); box-shadow: 0 2px 6px rgba(78,115,223,.25); }
.torneo-cat-btn.active { border-color: #4e73df; }
.torneo-producto-dropdown { background-color: #fff; }
.torneo-producto-dropdown .px-3:hover { background-color: #f8f9fa; }
body.dark-mode .torneo-producto-dropdown { background-color: #2d2d2d !important; color: #e0e0e0 !important; border-color: #3d3d3d !important; }
body.dark-mode .torneo-producto-dropdown .px-3:hover { background-color: #3d3d3d; }
</style>

@php
    $vid = $venta->id;
    $parts = $venta->participantes->sortBy('slot')->values();
@endphp

<div class="container-fluid body_admin">
    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div>
            <h4 class="font-weight-bold text-primary mb-1">
                <i class="fas fa-trophy mr-2"></i>Torneo / Americano #{{ $vid }}
            </h4>
            <div class="d-flex align-items-center">
                <input type="text" id="torneo-nombre" class="form-control form-control-sm mr-2"
                    value="{{ $venta->nombre_cliente }}" style="max-width:320px;"
                    data-original="{{ $venta->nombre_cliente }}">
                <small id="torneo-nombre-status" class="text-muted"></small>
            </div>
        </div>
        <div class="text-right">
            <div class="torneo-total text-primary">{{ $fmtMoney($venta->precio_total) }}</div>
            <div class="small text-muted">Total del torneo</div>
            <a href="{{ route('admincaja', ['fecha' => $venta->fecha_venta]) }}" class="btn btn-outline-secondary btn-sm mt-2">
                <i class="fas fa-arrow-left mr-1"></i>Volver a caja
            </a>
        </div>
    </div>

    @if($venta->padre)
    <div class="mb-3 p-2 border rounded bg-light">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="small font-weight-bold">Ticket original #{{ $venta->padre->id }}</span>
            <span class="small text-success font-weight-bold">{{ $fmtMoney($venta->padre->precio_total) }} (pagado)</span>
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

    {{-- Agregar jugador + Inscripción masiva --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="form-row align-items-end">
                <div class="form-group col-md-4 mb-2">
                    <label class="font-weight-bold mb-1">Agregar jugador</label>
                    <input type="text" id="torneo-nuevo-participante" class="form-control" placeholder="Nombre del jugador" autocomplete="off">
                </div>
                <div class="form-group col-md-2 mb-2">
                    <button type="button" class="btn btn-primary btn-block" id="btn-torneo-agregar-participante">Agregar</button>
                </div>
                <div class="form-group col-md-4 mb-2">
                    <label class="font-weight-bold mb-1">Inscripción a todos los pendientes</label>
                    <select class="form-control" id="torneo-producto-inscripcion">
                        <option value="">Elegir producto…</option>
                        @foreach($productosVenta as $prod)
                            <option value="{{ $prod->id }}">{{ $prod->nombre }} — {{ $fmtMoney($prod->precio_unitario) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-2">
                    <button type="button" class="btn btn-outline-primary btn-block" id="btn-torneo-inscripcion-todos">Cargar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de jugadores --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 font-weight-bold text-primary">Jugadores</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 torneo-jugador-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width:50px">#</th>
                            <th>Jugador</th>
                            <th class="text-right" style="width:120px">Consumido</th>
                            <th class="text-right" style="width:120px">Pagado</th>
                            <th class="text-right" style="width:120px">Saldo</th>
                            <th class="text-center" style="width:220px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="torneo-participantes-tbody">
                        @foreach($parts as $p)
                            @php
                                $consumido = (float) $venta->detalles->where('stock_venta_participante_id', $p->id)->sum('subtotal');
                                $pagado = (float) $venta->detalles->where('stock_venta_participante_id', $p->id)->where('estado_pago', 'pagado')->sum('subtotal');
                                $saldo = $consumido - $pagado;
                            @endphp
                            <tr data-participante-id="{{ $p->id }}">
                                <td class="text-center">{{ $p->slot }}</td>
                                <td>
                                    <input type="text" class="form-control form-control-sm torneo-participante-nombre"
                                        value="{{ $p->nombre }}" data-participante-id="{{ $p->id }}"
                                        @if($saldo <= 0 && $consumido > 0) readonly @endif>
                                </td>
                                <td class="text-right">{{ $fmtMoney($consumido) }}</td>
                                <td class="text-right">{{ $fmtMoney($pagado) }}</td>
                                <td class="text-right {{ $saldo > 0 ? 'text-danger font-weight-bold' : 'text-success' }}">
                                    {{ $fmtMoney($saldo) }}
                                </td>
                                <td class="text-center torneo-acciones">
                                    @if($saldo > 0)
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-torneo-ver-detalle"
                                            data-participante-id="{{ $p->id }}" data-participante-nombre="{{ $p->nombre }}">
                                            <i class="fas fa-edit mr-1"></i>Gestionar
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success btn-torneo-abrir-pago"
                                            data-participante-id="{{ $p->id }}" data-participante-nombre="{{ $p->nombre }}" data-saldo="{{ $saldo }}">
                                            <i class="fas fa-cash-register mr-1"></i>Pagar
                                        </button>
                                    @else
                                        <span class="badge badge-success">Pagado</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Cargar producto a jugador --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 font-weight-bold text-primary">Cargar producto</div>
        <div class="card-body">
            <div class="form-row align-items-end mb-3">
                <div class="form-group col-md-4 mb-2">
                    <label class="font-weight-bold mb-1">Jugador</label>
                    <select class="form-control" id="torneo-jugador-activo">
                        <option value="">Elegir jugador…</option>
                        @foreach($parts->where('estado_pago', 'pendiente') as $p)
                            <option value="{{ $p->id }}">{{ $p->slot }} — {{ $p->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <label class="font-weight-bold mb-1 d-block">Categoría</label>
            <div class="d-flex flex-wrap align-items-center mb-3" style="gap:6px;">
                @foreach($categoriasVenta as $cat)
                    @php
                        $abbr = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($cat->nombre, 0, 2));
                    @endphp
                    <button type="button"
                        class="btn btn-sm btn-outline-secondary torneo-cat-btn rounded-circle p-0 d-inline-flex align-items-center justify-content-center text-nowrap"
                        style="width:44px;height:44px;font-size:0.85rem;font-weight:700;letter-spacing:-0.02em;"
                        data-categoria-id="{{ $cat->id }}"
                        title="{{ $cat->nombre }}">{{ $abbr }}</button>
                @endforeach
            </div>
            <div class="form-row align-items-end">
                <div class="form-group col-md-8 mb-2">
                    <label class="font-weight-bold mb-1">Producto</label>
                    <div class="position-relative">
                        <input type="text" class="form-control" id="torneo-producto-search" placeholder="Elegí una categoría…" autocomplete="off" disabled>
                        <input type="hidden" id="torneo-producto-id">
                        <div class="torneo-producto-dropdown d-none position-absolute w-100 border rounded shadow-sm" style="z-index:1050;max-height:220px;overflow:auto;"></div>
                    </div>
                </div>
                <div class="form-group col-md-2 mb-2">
                    <label class="font-weight-bold mb-1">Cantidad</label>
                    <input type="number" class="form-control" id="torneo-cantidad" value="1" min="1">
                </div>
                <div class="form-group col-md-2 mb-2">
                    <button type="button" class="btn btn-outline-primary btn-block font-weight-bold" id="btn-torneo-add-linea" style="font-size:1.15rem;" disabled>+</button>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-light border mb-4">
        <p class="mb-1"><strong>¿Cómo funciona?</strong></p>
        <ul class="mb-0 small">
            <li><strong>Agregar jugador:</strong> escribí el nombre y clickeá <strong>Agregar</strong>.</li>
            <li><strong>Inscripción masiva:</strong> elegí un producto y clickeá <strong>Cargar</strong> para asignárselo a todos los jugadores pendientes.</li>
            <li><strong>Cargar producto:</strong> elegí el jugador, la categoría, buscá el producto y clickeá <strong>+</strong>.</li>
            <li><strong>Gestionar consumo:</strong> en cada jugador con saldo, clickeá <strong>Gestionar</strong> para ver sus líneas, quitar las que no corresponden o pagar una por una.</li>
            <li><strong>Pago mixto:</strong> desde <strong>Gestionar</strong> podés pagar algunas líneas en <strong>efectivo</strong> y otras por <strong>transferencia</strong>.</li>
            <li><strong>Pagar todo:</strong> desde la tabla principal, clickeá <strong>Efectivo</strong> o <strong>Transf.</strong> para saldar todo el saldo del jugador de una vez.</li>
        </ul>
    </div>

    {{-- Acciones finales --}}
    <div class="d-flex flex-wrap align-items-center mb-4">
        <button type="button" class="btn btn-secondary mr-2" id="btn-torneo-guardar-nombres">Guardar nombres</button>
        <button type="button" class="btn btn-outline-danger" id="btn-torneo-cancelar">Cancelar ticket</button>
    </div>
</div>

{{-- Modal detalle jugador --}}
<div class="modal fade" id="modal-torneo-detalle" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="modal-torneo-detalle-titulo">Gestionar consumo</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body py-2">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center" style="width:60px">Cant.</th>
                                <th class="text-right" style="width:100px">Subtotal</th>
                                <th class="text-center" style="width:100px">Estado</th>
                                <th class="text-center" style="width:180px">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="torneo-detalle-tbody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2 d-flex justify-content-between align-items-center">
                <div id="torneo-detalle-total" class="font-weight-bold"></div>
                <div>
                    <button type="button" class="btn btn-success btn-torneo-pagar-todo-modal" data-metodo="efectivo">Pagar todo efectivo</button>
                    <button type="button" class="btn btn-info btn-torneo-pagar-todo-modal" data-metodo="transferencia">Pagar todo transferencia</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal dividir línea --}}
<div class="modal fade" id="modal-torneo-dividir" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="modal-torneo-dividir-titulo">Dividir producto</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body py-3">
                <p class="small text-muted">Seleccioná con quién querés dividir este producto. El costo se repartirá en partes iguales.</p>
                <div id="torneo-dividir-opciones"></div>
                <input type="hidden" id="torneo-dividir-detalle-id">
                <input type="hidden" id="torneo-dividir-participante-id">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-primary" id="btn-torneo-confirmar-dividir">Confirmar división</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal pago mixto --}}
<div class="modal fade" id="modal-torneo-pago" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="modal-torneo-pago-titulo">Registrar pago</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body py-3">
                <div class="alert alert-info small mb-3">
                    Ingresá cuánto pagó en <strong>efectivo</strong> y cuánto por <strong>transferencia</strong>. La suma debe ser igual al saldo pendiente.
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6 mb-2">
                        <label class="font-weight-bold">Efectivo ($)</label>
                        <input type="number" class="form-control" id="torneo-pago-efectivo" min="0" step="0.01" value="0">
                    </div>
                    <div class="form-group col-md-6 mb-2">
                        <label class="font-weight-bold">Transferencia ($)</label>
                        <input type="number" class="form-control" id="torneo-pago-transferencia" min="0" step="0.01" value="0">
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top pt-2">
                    <div>
                        <span class="text-muted">Saldo:</span>
                        <span class="font-weight-bold" id="torneo-pago-saldo"></span>
                    </div>
                    <div>
                        <span class="text-muted">Total ingresado:</span>
                        <span class="font-weight-bold" id="torneo-pago-total-ingresado">$0,00</span>
                    </div>
                </div>
                <div id="torneo-pago-error" class="text-danger small mt-2 d-none"></div>
                <input type="hidden" id="torneo-pago-participante-id">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-success" id="btn-torneo-confirmar-pago">Confirmar pago</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@php
    $cajaCategoriasJson = $categoriasVenta->map(function ($c) {
        return ['id' => $c->id, 'nombre' => $c->nombre, 'abbr' => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($c->nombre, 0, 2))];
    })->values();
    $cajaProductosJson = $productosVenta->map(function ($p) use ($fmtMoney) {
        return ['id' => $p->id, 'categoria_id' => $p->stock_categoria_id, 'label' => $p->nombre.' (stock '.$p->stock_actual.') — '.$fmtMoney($p->precio_unitario)];
    })->values();
    $ventaDetallesJson = $venta->detalles->map(function ($d) use ($fmtMoney) {
        return [
            'id' => $d->id,
            'participante_id' => $d->stock_venta_participante_id,
            'producto_nombre' => $d->producto?->nombre,
            'cantidad' => $d->cantidad,
            'subtotal' => (float) $d->subtotal,
            'subtotal_fmt' => $fmtMoney($d->subtotal),
            'estado_pago' => $d->estado_pago,
        ];
    })->values();
@endphp

<script>
(function() {
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.getAttribute('content') : '';
    var ventaId = {{ $vid }};

    var CAJA_CATEGORIAS = @json($cajaCategoriasJson);
    var CAJA_PRODUCTOS = @json($cajaProductosJson);

    function adminCajaBasePath() {
        var path = (window.location.pathname || '').replace(/\/$/, '');
        if (/\/admin_caja(\/|$)/.test(path)) {
            return path.replace(/\/admin_caja.*$/, '/admin_caja');
        }
        return path + '/admin_caja';
    }
    function lineaUrl(vid) { return adminCajaBasePath() + '/venta/' + vid + '/linea'; }
    function lineaDestroyUrl(vid, did) { return adminCajaBasePath() + '/venta/' + vid + '/linea/' + did; }
    function lineaPagoUrl(vid, did) { return adminCajaBasePath() + '/venta/' + vid + '/linea/' + did + '/pago'; }
    function dividirLineaUrl(vid, did) { return adminCajaBasePath() + '/venta/' + vid + '/linea/' + did + '/dividir'; }
    function participanteStoreUrl(vid) { return adminCajaBasePath() + '/venta/' + vid + '/participante'; }
    function participantePatchUrl(vid, pid) { return adminCajaBasePath() + '/venta/' + vid + '/participante/' + pid; }
    function participantePagoUrl(vid, pid) { return adminCajaBasePath() + '/venta/' + vid + '/participante/' + pid + '/pago'; }
    function participantePagoMixtoUrl(vid, pid) { return adminCajaBasePath() + '/venta/' + vid + '/participante/' + pid + '/pago-mixto'; }
    function inscripcionTodosUrl(vid) { return adminCajaBasePath() + '/venta/' + vid + '/inscripcion-todos'; }
    function ventaDestroyUrl(vid) { return adminCajaBasePath() + '/venta/' + vid; }
    function updateUrl(vid) { return adminCajaBasePath() + '/venta/' + vid; }

    function jsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        };
    }

    function parseFetchResponse(r) {
        return r.text().then(function(text) {
            var j = null;
            if (text) {
                try { j = JSON.parse(text); } catch (e) { j = { message: 'Respuesta inválida (HTTP ' + r.status + ').' }; }
            } else { j = { message: 'Sin respuesta (HTTP ' + r.status + ')' }; }
            return { ok: r.ok, status: r.status, j: j };
        });
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function fmtMoney(n) {
        return '$' + Number(n).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ── Product picker ──
    var selectedCategoriaId = null;
    var productoSearch = document.getElementById('torneo-producto-search');
    var productoIdInput = document.getElementById('torneo-producto-id');
    var productoDropdown = document.querySelector('.torneo-producto-dropdown');

    function productosPorCategoria(cid) {
        return (CAJA_PRODUCTOS || []).filter(function(p) { return String(p.categoria_id) === String(cid); });
    }

    function renderDropdown(productos) {
        if (!productoDropdown) return;
        productoDropdown.innerHTML = '';
        if (!productos || !productos.length) {
            productoDropdown.innerHTML = '<div class="px-3 py-2 text-muted small">Sin productos</div>';
            productoDropdown.classList.remove('d-none');
            return;
        }
        productos.forEach(function(p) {
            var div = document.createElement('div');
            div.className = 'px-3 py-2 cursor-pointer hover-bg-light';
            div.style.cursor = 'pointer';
            div.textContent = p.label;
            div.addEventListener('click', function() {
                productoSearch.value = p.label;
                productoIdInput.value = p.id;
                productoDropdown.classList.add('d-none');
                syncAddLineaBtn();
            });
            productoDropdown.appendChild(div);
        });
        productoDropdown.classList.remove('d-none');
    }

    document.querySelectorAll('.torneo-cat-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.torneo-cat-btn').forEach(function(b) {
                b.classList.remove('active', 'btn-primary');
                b.classList.add('btn-outline-secondary');
            });
            btn.classList.add('active', 'btn-primary');
            btn.classList.remove('btn-outline-secondary');
            selectedCategoriaId = btn.getAttribute('data-categoria-id');
            productoSearch.disabled = false;
            productoSearch.placeholder = 'Buscá producto…';
            productoSearch.value = '';
            productoIdInput.value = '';
            renderDropdown(productosPorCategoria(selectedCategoriaId));
            productoSearch.focus();
            syncAddLineaBtn();
        });
    });

    if (productoSearch) {
        productoSearch.addEventListener('input', function() {
            var q = (productoSearch.value || '').toLowerCase().trim();
            var productos = productosPorCategoria(selectedCategoriaId).filter(function(p) {
                return !q || p.label.toLowerCase().indexOf(q) >= 0;
            });
            renderDropdown(productos);
        });
        productoSearch.addEventListener('focus', function() {
            var q = (productoSearch.value || '').toLowerCase().trim();
            renderDropdown(productosPorCategoria(selectedCategoriaId).filter(function(p) {
                return !q || p.label.toLowerCase().indexOf(q) >= 0;
            }));
        });
        document.addEventListener('click', function(e) {
            if (productoDropdown && !productoSearch.contains(e.target) && !productoDropdown.contains(e.target)) {
                productoDropdown.classList.add('d-none');
            }
        });
    }

    var jugadorActivoSelect = document.getElementById('torneo-jugador-activo');
    var cantidadInput = document.getElementById('torneo-cantidad');
    var addLineaBtn = document.getElementById('btn-torneo-add-linea');

    function syncAddLineaBtn() {
        if (!addLineaBtn) return;
        var ok = jugadorActivoSelect && jugadorActivoSelect.value && productoIdInput && productoIdInput.value;
        addLineaBtn.disabled = !ok;
    }
    if (jugadorActivoSelect) jugadorActivoSelect.addEventListener('change', syncAddLineaBtn);

    // ── Agregar línea ──
    if (addLineaBtn) {
        addLineaBtn.addEventListener('click', function() {
            var pid = productoIdInput ? productoIdInput.value : '';
            var qty = cantidadInput ? parseInt(cantidadInput.value, 10) : 1;
            var partId = jugadorActivoSelect ? jugadorActivoSelect.value : '';
            if (!pid) { alert('Elegí un producto.'); return; }
            if (!partId) { alert('Elegí un jugador.'); return; }
            if (!qty || qty < 1) qty = 1;
            addLineaBtn.disabled = true;
            fetch(lineaUrl(ventaId), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ _token: csrfToken, stock_producto_id: parseInt(pid, 10), cantidad: qty, stock_venta_participante_id: parseInt(partId, 10) })
            }).then(parseFetchResponse).then(function(x) {
                addLineaBtn.disabled = false;
                if (!x.ok) { alert((x.j && x.j.message) || 'Error'); return; }
                window.location.reload();
            }).catch(function(e) { addLineaBtn.disabled = false; alert('Error de red'); });
        });
    }

    // ── Agregar participante ──
    var btnAgregarPart = document.getElementById('btn-torneo-agregar-participante');
    var inputNuevoPart = document.getElementById('torneo-nuevo-participante');
    if (btnAgregarPart) {
        btnAgregarPart.addEventListener('click', function() {
            var nombre = inputNuevoPart ? inputNuevoPart.value.trim() : '';
            if (!nombre) { alert('Escribí un nombre.'); return; }
            btnAgregarPart.disabled = true;
            fetch(participanteStoreUrl(ventaId), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ _token: csrfToken, nombre: nombre })
            }).then(parseFetchResponse).then(function(x) {
                btnAgregarPart.disabled = false;
                if (!x.ok) { alert((x.j && x.j.message) || 'Error'); return; }
                window.location.reload();
            }).catch(function(e) { btnAgregarPart.disabled = false; alert('Error de red'); });
        });
    }

    // ── Inscripción masiva ──
    var btnInscTodos = document.getElementById('btn-torneo-inscripcion-todos');
    var selectInsc = document.getElementById('torneo-producto-inscripcion');
    if (btnInscTodos) {
        btnInscTodos.addEventListener('click', function() {
            var prodId = selectInsc ? selectInsc.value : '';
            if (!prodId) { alert('Elegí un producto de inscripción.'); return; }
            btnInscTodos.disabled = true;
            fetch(inscripcionTodosUrl(ventaId), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ _token: csrfToken, stock_producto_id: parseInt(prodId, 10) })
            }).then(parseFetchResponse).then(function(x) {
                btnInscTodos.disabled = false;
                if (!x.ok) { alert((x.j && x.j.message) || 'Error'); return; }
                window.location.reload();
            }).catch(function(e) { btnInscTodos.disabled = false; alert('Error de red'); });
        });
    }

    // ── Ver detalle modal ──
    var modalDetalle = document.getElementById('modal-torneo-detalle');
    var detalleTbody = document.getElementById('torneo-detalle-tbody');
    var detalleTitulo = document.getElementById('modal-torneo-detalle-titulo');
    var detalleTotal = document.getElementById('torneo-detalle-total');
    var modalParticipanteId = null;

    // Datos precargados del servidor
    var VENTA_DETALLES = @json($ventaDetallesJson);
    var TORNEO_PARTICIPANTES = @json($parts->map(function($p) { return ['id' => $p->id, 'nombre' => $p->nombre, 'slot' => $p->slot]; })->values());

    function abrirDetalleParticipante(participanteId, nombre) {
        modalParticipanteId = participanteId;
        if (detalleTitulo) detalleTitulo.textContent = 'Gestionar consumo de ' + escapeHtml(nombre);
        if (detalleTbody) {
            detalleTbody.innerHTML = '';
            var total = 0;
            var tienePendientes = false;
            VENTA_DETALLES.forEach(function(d) {
                if (String(d.participante_id) !== String(participanteId)) return;
                total += d.subtotal;
                var tr = document.createElement('tr');
                var estadoHtml = d.estado_pago === 'pagado'
                    ? '<span class="badge badge-success">Pagado</span>'
                    : '<span class="badge badge-warning">Pendiente</span>';
                var accionesHtml = '';
                if (d.estado_pago === 'pendiente') {
                    tienePendientes = true;
                    accionesHtml = '<button type="button" class="btn btn-sm btn-outline-danger btn-torneo-quitar-linea px-2 py-0 font-weight-bold" data-detalle-id="' + d.id + '" title="Quitar"><i class="fas fa-trash-alt"></i></button>'
                        + '<button type="button" class="btn btn-sm btn-outline-secondary btn-torneo-dividir-linea px-2 py-0 font-weight-bold ml-1" data-detalle-id="' + d.id + '" data-participante-id="' + d.participante_id + '" title="Dividir"><i class="fas fa-divide"></i></button>'
                        + '<button type="button" class="btn btn-sm btn-outline-success btn-torneo-linea-pago px-2 py-0 font-weight-bold ml-1" data-detalle-id="' + d.id + '" data-metodo="efectivo" title="Pagar efectivo"><i class="fas fa-dollar-sign"></i></button>'
                        + '<button type="button" class="btn btn-sm btn-outline-info btn-torneo-linea-pago px-2 py-0 font-weight-bold ml-1" data-detalle-id="' + d.id + '" data-metodo="transferencia" title="Pagar transferencia"><i class="fas fa-university"></i></button>';
                } else {
                    accionesHtml = '<span class="text-muted small">—</span>';
                }
                tr.innerHTML = '<td>' + escapeHtml(d.producto_nombre || '') + '</td>'
                    + '<td class="text-center">' + d.cantidad + '</td>'
                    + '<td class="text-right">' + escapeHtml(d.subtotal_fmt) + '</td>'
                    + '<td class="text-center">' + estadoHtml + '</td>'
                    + '<td class="text-center p-1 align-middle">' + accionesHtml + '</td>';
                detalleTbody.appendChild(tr);
            });
            if (detalleTotal) detalleTotal.textContent = 'Total: ' + fmtMoney(total);
        }
        // Mostrar/ocultar botones pagar todo según tenga pendientes
        document.querySelectorAll('.btn-torneo-pagar-todo-modal').forEach(function(b) {
            b.style.display = tienePendientes ? '' : 'none';
        });
        if (window.jQuery) window.jQuery(modalDetalle).modal('show');
    }

    document.addEventListener('click', function(e) {
        var btnDet = e.target.closest('.btn-torneo-ver-detalle');
        if (btnDet) {
            var pid = btnDet.getAttribute('data-participante-id');
            var nombre = btnDet.getAttribute('data-participante-nombre');
            abrirDetalleParticipante(pid, nombre);
            return;
        }
    });

    // ── Pagar línea individual (desde modal) ──
    if (detalleTbody) {
        detalleTbody.addEventListener('click', function(e) {
            var btnPay = e.target.closest('.btn-torneo-linea-pago');
            if (btnPay) {
                var did = btnPay.getAttribute('data-detalle-id');
                var metodo = btnPay.getAttribute('data-metodo');
                if (!did) return;
                btnPay.disabled = true;
                fetch(lineaPagoUrl(ventaId, did), {
                    method: 'POST',
                    headers: jsonHeaders(),
                    body: JSON.stringify({ _token: csrfToken, metodo_pago: metodo })
                }).then(parseFetchResponse).then(function(x) {
                    btnPay.disabled = false;
                    if (!x.ok) { alert((x.j && x.j.message) || 'Error'); return; }
                    window.location.reload();
                }).catch(function(e) { btnPay.disabled = false; alert('Error de red'); });
                return;
            }
            var btnRemove = e.target.closest('.btn-torneo-quitar-linea');
            if (btnRemove) {
                var didRem = btnRemove.getAttribute('data-detalle-id');
                if (!didRem) return;
                if (!confirm('¿Quitar esta línea?')) return;
                btnRemove.disabled = true;
                fetch(lineaDestroyUrl(ventaId, didRem), {
                    method: 'DELETE',
                    headers: jsonHeaders(),
                    body: JSON.stringify({ _token: csrfToken })
                }).then(parseFetchResponse).then(function(x) {
                    btnRemove.disabled = false;
                    if (!x.ok) { alert((x.j && x.j.message) || 'Error'); return; }
                    window.location.reload();
                }).catch(function(e) { btnRemove.disabled = false; alert('Error de red'); });
            }
        });
    }

    // ── Dividir línea ──
    var modalDividir = document.getElementById('modal-torneo-dividir');
    var dividirOpciones = document.getElementById('torneo-dividir-opciones');
    var dividirDetalleIdInput = document.getElementById('torneo-dividir-detalle-id');
    var dividirParticipanteIdInput = document.getElementById('torneo-dividir-participante-id');
    var btnConfirmarDividir = document.getElementById('btn-torneo-confirmar-dividir');

    function abrirModalDividir(detalleId, dueñoParticipanteId) {
        if (!dividirDetalleIdInput || !dividirParticipanteIdInput || !dividirOpciones) return;
        dividirDetalleIdInput.value = detalleId;
        dividirParticipanteIdInput.value = dueñoParticipanteId;
        dividirOpciones.innerHTML = '';
        if (TORNEO_PARTICIPANTES && TORNEO_PARTICIPANTES.length) {
            TORNEO_PARTICIPANTES.forEach(function(p) {
                var checked = String(p.id) === String(dueñoParticipanteId) ? 'checked disabled' : '';
                var div = document.createElement('div');
                div.className = 'form-check';
                div.innerHTML = '<input class="form-check-input" type="checkbox" value="' + p.id + '" id="dividir-chk-' + p.id + '" ' + checked + '>'
                    + '<label class="form-check-label" for="dividir-chk-' + p.id + '">' + escapeHtml(p.slot + ' — ' + p.nombre) + '</label>';
                dividirOpciones.appendChild(div);
            });
        }
        if (window.jQuery) window.jQuery(modalDividir).modal('show');
    }

    if (detalleTbody) {
        detalleTbody.addEventListener('click', function(e) {
            var btnDividir = e.target.closest('.btn-torneo-dividir-linea');
            if (btnDividir) {
                var did = btnDividir.getAttribute('data-detalle-id');
                var pid = btnDividir.getAttribute('data-participante-id');
                if (did && pid) abrirModalDividir(did, pid);
            }
        });
    }

    if (btnConfirmarDividir) {
        btnConfirmarDividir.addEventListener('click', function() {
            var did = dividirDetalleIdInput ? dividirDetalleIdInput.value : '';
            var dueñoPid = dividirParticipanteIdInput ? dividirParticipanteIdInput.value : '';
            if (!did) return;
            var checks = document.querySelectorAll('#torneo-dividir-opciones input[type="checkbox"]:checked');
            if (!checks.length) { alert('Seleccioná al menos un jugador para dividir.'); return; }
            var pids = [];
            checks.forEach(function(c) { pids.push(parseInt(c.value, 10)); });
            // Asegurar que el dueño esté incluido
            if (dueñoPid) {
                var dueñoId = parseInt(dueñoPid, 10);
                if (pids.indexOf(dueñoId) < 0) pids.push(dueñoId);
            }
            btnConfirmarDividir.disabled = true;
            fetch(dividirLineaUrl(ventaId, did), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ _token: csrfToken, participantes_ids: pids })
            }).then(parseFetchResponse).then(function(x) {
                btnConfirmarDividir.disabled = false;
                if (!x.ok) { alert((x.j && x.j.message) || 'Error'); return; }
                window.location.reload();
            }).catch(function(e) { btnConfirmarDividir.disabled = false; alert('Error de red'); });
        });
    }

    // ── Pagar todo participante ──
    document.addEventListener('click', function(e) {
        var btnPayTodo = e.target.closest('.btn-torneo-pagar-todo');
        if (btnPayTodo) {
            var pid = btnPayTodo.getAttribute('data-participante-id');
            var metodo = btnPayTodo.getAttribute('data-metodo');
            if (!pid) return;
            btnPayTodo.disabled = true;
            fetch(participantePagoUrl(ventaId, pid), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ _token: csrfToken, metodo_pago: metodo })
            }).then(parseFetchResponse).then(function(x) {
                btnPayTodo.disabled = false;
                if (!x.ok) { alert((x.j && x.j.message) || 'Error'); return; }
                if (x.j && x.j.ticket_cerrado) {
                    alert('Ticket cerrado: todos los jugadores pagaron.');
                    window.location.href = '{{ route('admincaja', ['fecha' => $venta->fecha_venta]) }}';
                    return;
                }
                window.location.reload();
            }).catch(function(e) { btnPayTodo.disabled = false; alert('Error de red'); });
            return;
        }

        // Pagar todo desde modal
        var btnPayModal = e.target.closest('.btn-torneo-pagar-todo-modal');
        if (btnPayModal && modalParticipanteId) {
            var metodoModal = btnPayModal.getAttribute('data-metodo');
            btnPayModal.disabled = true;
            fetch(participantePagoUrl(ventaId, modalParticipanteId), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ _token: csrfToken, metodo_pago: metodoModal })
            }).then(parseFetchResponse).then(function(x) {
                btnPayModal.disabled = false;
                if (!x.ok) { alert((x.j && x.j.message) || 'Error'); return; }
                if (x.j && x.j.ticket_cerrado) {
                    alert('Ticket cerrado: todos los jugadores pagaron.');
                    window.location.href = '{{ route('admincaja', ['fecha' => $venta->fecha_venta]) }}';
                    return;
                }
                window.location.reload();
            }).catch(function(e) { btnPayModal.disabled = false; alert('Error de red'); });
        }
    });

    // ── Modal de pago mixto ──
    var modalPago = document.getElementById('modal-torneo-pago');
    var pagoEfectivoInput = document.getElementById('torneo-pago-efectivo');
    var pagoTransferenciaInput = document.getElementById('torneo-pago-transferencia');
    var pagoSaldoEl = document.getElementById('torneo-pago-saldo');
    var pagoTotalIngresadoEl = document.getElementById('torneo-pago-total-ingresado');
    var pagoErrorEl = document.getElementById('torneo-pago-error');
    var pagoParticipanteIdInput = document.getElementById('torneo-pago-participante-id');
    var btnConfirmarPago = document.getElementById('btn-torneo-confirmar-pago');

    function validarPagoMixto() {
        var saldo = parseFloat(pagoSaldoEl.getAttribute('data-saldo')) || 0;
        var efectivo = parseFloat(pagoEfectivoInput.value) || 0;
        var transferencia = parseFloat(pagoTransferenciaInput.value) || 0;
        var total = parseFloat((efectivo + transferencia).toFixed(2));
        pagoTotalIngresadoEl.textContent = fmtMoney(total);
        var ok = Math.abs(total - saldo) < 0.01 && total > 0;
        if (btnConfirmarPago) btnConfirmarPago.disabled = !ok;
        if (pagoErrorEl) {
            if (!ok && total > 0) {
                pagoErrorEl.textContent = 'La suma debe ser exactamente ' + fmtMoney(saldo);
                pagoErrorEl.classList.remove('d-none');
            } else {
                pagoErrorEl.classList.add('d-none');
            }
        }
        return ok;
    }

    if (pagoEfectivoInput) {
        pagoEfectivoInput.addEventListener('input', validarPagoMixto);
    }
    if (pagoTransferenciaInput) {
        pagoTransferenciaInput.addEventListener('input', validarPagoMixto);
    }

    document.addEventListener('click', function(e) {
        var btnAbrir = e.target.closest('.btn-torneo-abrir-pago');
        if (btnAbrir) {
            var pid = btnAbrir.getAttribute('data-participante-id');
            var nombre = btnAbrir.getAttribute('data-participante-nombre');
            var saldo = parseFloat(btnAbrir.getAttribute('data-saldo')) || 0;
            var titulo = document.getElementById('modal-torneo-pago-titulo');
            if (titulo) titulo.textContent = 'Registrar pago — ' + escapeHtml(nombre);
            if (pagoSaldoEl) {
                pagoSaldoEl.textContent = fmtMoney(saldo);
                pagoSaldoEl.setAttribute('data-saldo', saldo);
            }
            if (pagoEfectivoInput) { pagoEfectivoInput.value = saldo > 0 ? saldo.toFixed(2) : '0'; }
            if (pagoTransferenciaInput) { pagoTransferenciaInput.value = '0'; }
            if (pagoParticipanteIdInput) { pagoParticipanteIdInput.value = pid; }
            validarPagoMixto();
            if (window.jQuery) window.jQuery(modalPago).modal('show');
        }
    });

    if (btnConfirmarPago) {
        btnConfirmarPago.addEventListener('click', function() {
            if (!validarPagoMixto()) return;
            var pid = pagoParticipanteIdInput ? pagoParticipanteIdInput.value : '';
            var efectivo = parseFloat(pagoEfectivoInput.value) || 0;
            var transferencia = parseFloat(pagoTransferenciaInput.value) || 0;
            if (!pid) return;
            btnConfirmarPago.disabled = true;
            fetch(participantePagoMixtoUrl(ventaId, pid), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ _token: csrfToken, monto_efectivo: efectivo, monto_transferencia: transferencia })
            }).then(parseFetchResponse).then(function(x) {
                btnConfirmarPago.disabled = false;
                if (!x.ok) { alert((x.j && x.j.message) || 'Error'); return; }
                if (x.j && x.j.ticket_cerrado) {
                    alert('Ticket cerrado: todos los jugadores pagaron.');
                    window.location.href = '{{ route('admincaja', ['fecha' => $venta->fecha_venta]) }}';
                    return;
                }
                window.location.reload();
            }).catch(function(e) { btnConfirmarPago.disabled = false; alert('Error de red'); });
        });
    }

    // ── Guardar nombres ──
    var btnGuardarNombres = document.getElementById('btn-torneo-guardar-nombres');
    if (btnGuardarNombres) {
        btnGuardarNombres.addEventListener('click', function() {
            var tasks = [];
            // Guardar nombre del torneo
            var nombreTorneo = document.getElementById('torneo-nombre');
            var statusEl = document.getElementById('torneo-nombre-status');
            if (nombreTorneo && nombreTorneo.value.trim()) {
                tasks.push(fetch(updateUrl(ventaId), {
                    method: 'PATCH',
                    headers: jsonHeaders(),
                    body: JSON.stringify({ _token: csrfToken, nombre_cliente: nombreTorneo.value.trim() })
                }).then(parseFetchResponse).then(function(x) {
                    if (!x.ok) throw new Error(x.j && x.j.message);
                }));
            }
            // Guardar nombres de participantes
            document.querySelectorAll('.torneo-participante-nombre').forEach(function(inp) {
                if (inp.readOnly) return;
                var pid = inp.getAttribute('data-participante-id');
                var nombre = inp.value.trim();
                if (!pid || !nombre) return;
                tasks.push(fetch(participantePatchUrl(ventaId, pid), {
                    method: 'PATCH',
                    headers: jsonHeaders(),
                    body: JSON.stringify({ _token: csrfToken, nombre: nombre })
                }).then(parseFetchResponse).then(function(x) {
                    if (!x.ok) throw new Error(x.j && x.j.message);
                }));
            });
            if (statusEl) statusEl.textContent = 'Guardando…';
            Promise.all(tasks).then(function() {
                if (statusEl) { statusEl.textContent = 'Guardado'; setTimeout(function() { statusEl.textContent = ''; }, 2000); }
                window.location.reload();
            }).catch(function(e) {
                if (statusEl) statusEl.textContent = (e && e.message) ? e.message : 'Error';
            });
        });
    }

    // ── Cancelar ticket ──
    var btnCancelar = document.getElementById('btn-torneo-cancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function() {
            if (!confirm('¿Cancelar este ticket? Se eliminará la venta y se devolverá el stock.')) return;
            btnCancelar.disabled = true;
            fetch(ventaDestroyUrl(ventaId), {
                method: 'DELETE',
                headers: jsonHeaders(),
                body: JSON.stringify({ _token: csrfToken })
            }).then(parseFetchResponse).then(function(x) {
                btnCancelar.disabled = false;
                if (!x.ok) { alert((x.j && x.j.message) || 'Error'); return; }
                window.location.href = '{{ route('admincaja', ['fecha' => $venta->fecha_venta]) }}';
            }).catch(function(e) { btnCancelar.disabled = false; alert('Error de red'); });
        });
    }
})();
</script>
@endsection
