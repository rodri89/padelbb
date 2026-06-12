@extends('bahia_padel/admin/plantilla')

@section('title_header','Stock')

@section('contenedor')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<div class="container-fluid body_admin">
    @if($alertas->isNotEmpty())
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-left-warning shadow">
                <div class="card-body py-3">
                    <h6 class="m-0 font-weight-bold text-warning">Alertas de stock bajo o mínimo</h6>
                    <ul class="mb-0 mt-2 small">
                        @foreach($alertas as $p)
                            <li><strong>{{ $p->nombre }}</strong>: {{ $p->stock_actual }} u. (mín. {{ $p->stock_minimo }}) — {{ $p->nivelStock() }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row mb-3">
        <div class="col-12 d-flex align-items-center flex-wrap">
            <button type="button"
                class="btn btn-primary font-weight-bold"
                data-toggle="collapse"
                data-target="#seccionCategorias"
                aria-expanded="false"
                aria-controls="seccionCategorias"
                id="btnToggleCategoriasStock">
                <i class="fas fa-tags mr-1"></i> Categorías
            </button>
            <button type="button"
                class="btn btn-success font-weight-bold ml-2"
                data-toggle="collapse"
                data-target="#seccionActualizarStock"
                aria-expanded="false"
                aria-controls="seccionActualizarStock"
                id="btnToggleActualizarStock">
                <i class="fas fa-sync-alt mr-1"></i> Actualizar stock
            </button>
            <button type="button"
                class="btn btn-info font-weight-bold text-white ml-2"
                data-toggle="collapse"
                data-target="#seccionProductos"
                aria-expanded="false"
                aria-controls="seccionProductos"
                id="btnToggleProductosStock">
                <i class="fas fa-box-open mr-1"></i> Productos
            </button>
            <button type="button"
                class="btn btn-secondary font-weight-bold ml-2"
                data-toggle="collapse"
                data-target="#seccionMovimientos"
                aria-expanded="false"
                aria-controls="seccionMovimientos"
                id="btnToggleMovimientosStock">
                <i class="fas fa-history mr-1"></i> Ver movimientos
            </button>
        </div>
    </div>

    <div class="collapse" id="seccionActualizarStock">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow border-success">
                    <div class="card-header py-3 bg-light">
                        <h6 class="m-0 font-weight-bold text-success">Actualizar stock</h6>
                        <p class="mb-0 small text-muted mt-1">Ingresá unidades a sumar al stock actual por producto. El precio se edita en el panel <strong>Productos</strong>.</p>
                    </div>
                    <div class="card-body p-0 text-dark">
                        @if($productos->isEmpty())
                            <p class="p-3 mb-0 text-muted">No hay productos cargados. Creá uno desde el panel <strong>Productos</strong>.</p>
                        @else
                        <form method="post" action="{{ route('adminstock.actualizar.store') }}" id="formActualizarStockMasivo">
                            @csrf
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-0 small">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Producto</th>
                                            <th style="min-width:110px">Cantidad a ingresar</th>
                                            <th style="min-width:90px">Stock actual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($productos as $p)
                                            <tr>
                                                <td class="align-middle">
                                                    <strong>{{ $p->nombre }}</strong>
                                                    @if($p->categoria)
                                                        <br><span class="text-muted">{{ $p->categoria->nombre }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <input type="number"
                                                        name="lineas[{{ $p->id }}][cantidad_ingresar]"
                                                        class="form-control form-control-sm"
                                                        min="0" value="" placeholder="0" autocomplete="off">
                                                </td>
                                                <td class="align-middle text-center font-weight-bold">{{ $p->stock_actual }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-3 bg-light border-top">
                                <button type="submit" class="btn btn-success font-weight-bold px-4">
                                    <i class="fas fa-save mr-1"></i> Guardar
                                </button>
                            </div>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="collapse" id="seccionCategorias">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Categorías</h6>
                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalNuevaCategoria">+ Nueva</button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Nombre</th><th>Activa</th><th></th></tr></thead>
                                <tbody>
                                    @foreach($categorias as $c)
                                        <tr>
                                            <td>{{ $c->nombre }}</td>
                                            <td>{{ $c->activa ? 'Sí' : 'No' }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    data-toggle="modal" data-target="#modalEditarCategoria{{ $c->id }}">Editar</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach($categorias as $c)
    <div class="modal fade" id="modalEditarCategoria{{ $c->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="{{ route('adminstock.categoria.update', $c) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Editar categoría</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body text-dark">
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" class="form-control" value="{{ $c->nombre }}" required>
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <input type="text" name="descripcion" class="form-control" value="{{ $c->descripcion }}">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="activa" value="1" class="form-check-input" id="act{{ $c->id }}" {{ $c->activa ? 'checked' : '' }}>
                            <label class="form-check-label" for="act{{ $c->id }}">Activa</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    <div class="collapse" id="seccionProductos">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h6 class="m-0 font-weight-bold text-primary">Productos</h6>
                        <p class="mb-0 small text-muted mt-1">Editá los datos en la tabla y presioná <strong>Guardar productos</strong>. <strong>+ Nuevo producto</strong> agrega una fila vacía para completar y guardar junto al resto. Para sumar stock sin tocar el resto usá <strong>Actualizar stock</strong>.</p>
                    </div>
                    @if($categorias->isNotEmpty())
                    <button type="button" class="btn btn-sm btn-primary mt-2 mt-md-0" id="btnStockNuevoProductoFila">+ Nuevo producto</button>
                    @endif
                </div>
                @if($categorias->isEmpty())
                <div class="card-body">
                    <p class="text-muted mb-0">Creá al menos una <strong>categoría</strong> para poder cargar productos.</p>
                </div>
                @else
                <form method="post" action="{{ route('adminstock.productos.tabla.store') }}" id="formProductosTabla">
                    @csrf
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 small">
                            <thead class="thead-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th style="min-width:7rem">Precio unitario</th>
                                    <th style="min-width:5.5rem">Stock</th>
                                    <th style="min-width:5.5rem">Mín.</th>
                                    <th>Nivel</th>
                                    <th class="text-center" style="min-width:4rem">Activo</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyProductosStock">
                                @foreach($productos as $p)
                                    <tr>
                                        <td>
                                            <input type="text"
                                                name="productos[{{ $p->id }}][nombre]"
                                                class="form-control form-control-sm"
                                                value="{{ $p->nombre }}"
                                                maxlength="100"
                                                required
                                                autocomplete="off">
                                        </td>
                                        <td>
                                            <select name="productos[{{ $p->id }}][stock_categoria_id]" class="form-control form-control-sm" required>
                                                @foreach($categorias as $c)
                                                    <option value="{{ $c->id }}" @selected($c->id == $p->stock_categoria_id)>{{ $c->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                                <input type="number"
                                                    step="0.01"
                                                    name="productos[{{ $p->id }}][precio_unitario]"
                                                    class="form-control"
                                                    value="{{ number_format((float) $p->precio_unitario, 2, '.', '') }}"
                                                    min="0"
                                                    inputmode="decimal"
                                                    required
                                                    autocomplete="off">
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number"
                                                name="productos[{{ $p->id }}][stock_actual]"
                                                class="form-control form-control-sm text-center"
                                                value="{{ $p->stock_actual }}"
                                                min="0"
                                                required
                                                autocomplete="off">
                                        </td>
                                        <td>
                                            <input type="number"
                                                name="productos[{{ $p->id }}][stock_minimo]"
                                                class="form-control form-control-sm text-center"
                                                value="{{ $p->stock_minimo }}"
                                                min="0"
                                                required
                                                autocomplete="off">
                                        </td>
                                        <td class="align-middle">
                                            @php $n = $p->nivelStock(); @endphp
                                            <span class="badge badge-{{ $n === 'BUENO' ? 'success' : ($n === 'MEDIO' ? 'info' : ($n === 'BAJO' ? 'warning' : 'danger')) }}">{{ $n }}</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <input type="checkbox"
                                                class="form-check-input m-0 align-middle"
                                                style="position: relative;"
                                                name="productos[{{ $p->id }}][activo]"
                                                value="1"
                                                id="prodActivo{{ $p->id }}"
                                                {{ $p->activo ? 'checked' : '' }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light py-3">
                    <button type="submit" class="btn btn-primary font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Guardar productos
                    </button>
                </div>
                </form>
                @endif
            </div>
        </div>
    </div>
    </div>

    <div class="collapse" id="seccionMovimientos">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Movimientos de stock</h6>
                        <p class="mb-0 small text-muted mt-2">Buscá por fecha (ej. <code>2026-05</code>, <code>07/05</code>), producto, tipo de movimiento, cantidad o usuario. Podés ordenar clickeando los encabezados.</p>
                    </div>
                    <div class="card-body p-0 text-dark">
                        <div class="table-responsive p-2">
                        <table class="table table-sm table-striped mb-0 w-100" id="tablaMovimientosStock" style="width:100%">
                            <thead class="thead-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th>Cant.</th>
                                    <th>Ant. → Nuevo</th>
                                    <th>Motivo</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        </div>
                    </div>
            </div>
        </div>
    </div>
    </div>
</div>

{{-- Modal nueva categoría --}}
<div class="modal fade" id="modalNuevaCategoria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('adminstock.categoria.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nueva categoría</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body text-dark">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <input type="text" name="descripcion" class="form-control">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="activa" value="1" class="form-check-input" id="nuevaCatAct" checked>
                        <label class="form-check-label" for="nuevaCatAct">Activa</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@php
    $stockCategoriasJson = $categorias->map(function ($c) {
        return ['id' => $c->id, 'nombre' => $c->nombre];
    })->values();
@endphp
@push('scripts')
<script>
window.STOCK_CATEGORIAS_FILAS = @json($stockCategoriasJson);
(function () {
  var movimientosUrl = @json(route('adminstock.movimientos.data'));
  var movimientosDtInited = false;
  var movimientosDt = null;
  var langDt = {
    decimal: '',
    emptyTable: 'No hay movimientos',
    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
    infoFiltered: '(filtrado de _MAX_ registros totales)',
    lengthMenu: 'Mostrar _MENU_ registros',
    loadingRecords: 'Cargando...',
    processing: 'Procesando...',
    search: 'Buscar:',
    zeroRecords: 'Sin resultados',
    paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
  };

  $('#seccionMovimientos').on('shown.bs.collapse', function () {
    if (movimientosDtInited && movimientosDt) {
      movimientosDt.columns.adjust();
      return;
    }
    movimientosDtInited = true;
    movimientosDt = $('#tablaMovimientosStock').DataTable({
      processing: true,
      serverSide: true,
      ajax: movimientosUrl,
      pageLength: 25,
      lengthMenu: [[10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, 'Todos']],
      order: [[0, 'desc']],
      language: langDt,
      columns: [
        { data: 'created_at', name: 'stock_movimientos_stock.created_at' },
        { data: 'producto_nombre', name: 'producto_nombre', defaultContent: '—' },
        { data: 'tipo_movimiento', name: 'stock_movimientos_stock.tipo_movimiento' },
        { data: 'cantidad', name: 'stock_movimientos_stock.cantidad' },
        { data: 'stock_cambio', name: 'stock_cambio', orderable: false, searchable: false },
        { data: 'motivo', name: 'stock_movimientos_stock.motivo' },
        { data: 'usuario_responsable', name: 'stock_movimientos_stock.usuario_responsable' },
      ],
    });
  });
})();

(function () {
  function escText(s) {
    if (!s) return '';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
  function stockSelectOptionsHtml() {
    var html = '';
    (window.STOCK_CATEGORIAS_FILAS || []).forEach(function (c) {
      html += '<option value="' + String(c.id) + '">' + escText(c.nombre) + '</option>';
    });
    return html;
  }
  function nuevaFilaProductoStock() {
    var cat = window.STOCK_CATEGORIAS_FILAS || [];
    if (!cat.length) return;
    var tbody = document.getElementById('tbodyProductosStock');
    if (!tbody) return;
    var k = 'new_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);
    var precioDef = '0.00';
    var opts = stockSelectOptionsHtml();
    var row = document.createElement('tr');
    row.className = 'stock-fila-producto-nuevo';
    row.setAttribute('data-stock-new-key', k);
    row.innerHTML =
      '<td><input type="text" name="productos[' + k + '][nombre]" class="form-control form-control-sm" value="" maxlength="100" required autocomplete="off" placeholder="Nombre del producto"></td>' +
      '<td><select name="productos[' + k + '][stock_categoria_id]" class="form-control form-control-sm" required>' + opts + '</select></td>' +
      '<td><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text">$</span></div>' +
      '<input type="number" step="0.01" name="productos[' + k + '][precio_unitario]" class="form-control" value="' + precioDef + '" min="0" inputmode="decimal" required autocomplete="off"></div></td>' +
      '<td><input type="number" name="productos[' + k + '][stock_actual]" class="form-control form-control-sm text-center" value="0" min="0" required autocomplete="off"></td>' +
      '<td><input type="number" name="productos[' + k + '][stock_minimo]" class="form-control form-control-sm text-center" value="0" min="0" required autocomplete="off"></td>' +
      '<td class="align-middle"><span class="badge badge-secondary">Nuevo</span></td>' +
      '<td class="align-middle text-center">' +
      '<input type="checkbox" class="form-check-input m-0 align-middle" style="position:relative;" name="productos[' + k + '][activo]" value="1" checked>' +
      '<button type="button" class="btn btn-sm btn-outline-danger ml-2 align-middle btn-quitar-fila-producto-nuevo" title="Quitar fila">&times;</button></td>';
    tbody.appendChild(row);
    var inp = row.querySelector('input[name*="[nombre]"]');
    if (inp) inp.focus();
  }
  document.addEventListener('click', function (e) {
    if (e.target.closest('#btnStockNuevoProductoFila')) {
      e.preventDefault();
      nuevaFilaProductoStock();
      return;
    }
    var q = e.target.closest('.btn-quitar-fila-producto-nuevo');
    if (q) {
      e.preventDefault();
      var tr = q.closest('tr');
      if (tr) tr.remove();
    }
  });
})();
</script>
@endpush
