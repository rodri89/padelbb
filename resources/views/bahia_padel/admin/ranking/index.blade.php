@extends('bahia_padel/admin/plantilla')

@section('title_header','Ranking por categoría')

@section('contenedor')

<style>
    .ranking-table th, .ranking-table td { color: #000 !important; }
    .ranking-table thead th { font-weight: 600; border-bottom: 2px solid #4e73df; }
    .ranking-foto { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; }
    #modalReferenciasPuntuacion .table th,
    #modalReferenciasPuntuacion .table td,
    #modalReferenciasPuntuacion .form-control { color: #000 !important; background-color: #fff !important; }
    .col-entrada { background: #f0f7ff; }
    .badge-entrada { font-size: 0.7rem; background: #3a7bd5; color: #fff; padding: 1px 6px; border-radius: 8px; vertical-align: middle; }
    /* Modal jugadores */
    #modalGestionJugadores .modal-dialog { max-width: 720px; }
    #modalGestionJugadores .table th,
    #modalGestionJugadores .table td { color: #000 !important; }
    .jugador-row-rank { background: #fff; }
    .jugador-row-rank:hover { background: #f8f9fa; }
    #tablaJugadoresEntrada select, #tablaJugadoresEntrada input { color: #000 !important; background: #fff !important; }
    .btn-quitar-jugador { color: #dc3545; background: none; border: none; cursor: pointer; padding: 0 4px; }
    .btn-quitar-jugador:hover { color: #a71d2a; }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="m-0 font-weight-bold text-primary">Ranking por categoría</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-success btn-sm" id="btn-nueva-entrada" title="Crear nueva entrada manual de ranking">
                            <i class="fas fa-plus"></i> Nuevo ranking manual
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-referencias-puntuacion" title="Ver y editar puntos por posición">
                            <i class="fas fa-list-ol"></i> Referencias de puntuación
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="get" action="{{ route('adminranking') }}" class="form-inline mb-4 flex-wrap gap-2">
                        <label class="mr-2 mb-1 mb-md-0" for="tipo">Tipo:</label>
                        <select name="tipo" id="tipo" class="form-control form-control-sm mr-3 mb-1 mb-md-0" style="min-width: 120px;">
                            @foreach($tipos as $valor => $etiqueta)
                                <option value="{{ $valor }}" {{ $valor === $tipo_seleccionado ? 'selected' : '' }}>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @if(!$categorias->isEmpty())
                        <label class="mr-2 mb-1 mb-md-0" for="categoria">Categoría:</label>
                        <select name="categoria" id="categoria" class="form-control form-control-sm mr-3 mb-1 mb-md-0" style="min-width: 120px;">
                            @foreach($categorias as $cat)
                                <option value="{{ $cat }}" {{ (int)$cat === (int)$categoria_seleccionada ? 'selected' : '' }}>{{ $cat }}º Categoría</option>
                            @endforeach
                        </select>
                        @endif
                        @if(!$temporadas->isEmpty())
                        <label class="mr-2 mb-1 mb-md-0" for="temporada">Temporada:</label>
                        <select name="temporada" id="temporada" class="form-control form-control-sm mr-2 mb-1 mb-md-0" style="min-width: 100px;">
                            @foreach($temporadas as $temp)
                                <option value="{{ $temp }}" {{ (int)$temp === (int)$temporada_seleccionada ? 'selected' : '' }}>{{ $temp }}</option>
                            @endforeach
                        </select>
                        @endif
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Ver</button>
                    </form>

                    {{-- Entradas manuales creadas para este filtro --}}
                    @if(!$entradas_manuales->isEmpty())
                    <div class="mb-3">
                        <h6 class="font-weight-bold text-secondary mb-2"><i class="fas fa-edit"></i> Entradas manuales — {{ $categoria_seleccionada }}ª cat · {{ $temporada_seleccionada }} · {{ $tipos[$tipo_seleccionado] ?? $tipo_seleccionado }}</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($entradas_manuales as $em)
                            <div class="border rounded px-3 py-2 bg-light d-flex align-items-center gap-2" style="min-width:220px;">
                                <div class="flex-grow-1">
                                    <strong>{{ $em->nombre }}</strong>
                                    <small class="text-muted d-block">
                                        @php $mesesNombres = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre']; @endphp
                                        {{ $mesesNombres[(int)$em->mes] ?? $em->mes }} {{ $em->temporada }}
                                    </small>
                                </div>
                                <div class="d-flex flex-column gap-1">
                                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 btn-gestionar-entrada"
                                        data-id="{{ $em->id }}"
                                        data-nombre="{{ $em->nombre }}"
                                        title="Gestionar jugadores y puntos">
                                        <i class="fas fa-users"></i> Jugadores
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-xs py-0 px-2 btn-eliminar-entrada"
                                        data-id="{{ $em->id }}"
                                        data-nombre="{{ $em->nombre }}"
                                        title="Eliminar entrada">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($categorias->isEmpty() && $entradas_manuales->isEmpty())
                        <p class="text-muted mb-0">No hay datos de ranking para el tipo {{ $tipos[$tipo_seleccionado] ?? $tipo_seleccionado }}. Los puntos se cargan al asignar puntos en los torneos puntuables o mediante entradas manuales.</p>
                    @endif

                    @if(!$ranking->isEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm table-hover ranking-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">Pos.</th>
                                        <th style="min-width: 180px;">Jugador</th>
                                        @foreach($torneos_ranking as $t)
                                            <th class="text-center {{ $t->tipo_columna === 'entrada' ? 'col-entrada' : '' }}"
                                                style="min-width: 90px;"
                                                title="{{ $t->nombre ?? '' }}">
                                                {{ $t->mes_label ?? '—' }}
                                                @if($t->tipo_columna === 'entrada')
                                                    <span class="badge-entrada">M</span>
                                                @endif
                                            </th>
                                        @endforeach
                                        <th class="text-right font-weight-bold" style="width: 90px;">Total</th>
                                        <th class="text-right" style="width: 120px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ranking as $pos => $fila)
                                    <tr>
                                        <td><strong>{{ $pos + 1 }}</strong></td>
                                        <td>
                                            <img src="{{ asset($fila->foto ?? 'images/jugador_img.png') }}" alt="" class="ranking-foto mr-2" onerror="this.src='{{ asset('images/jugador_img.png') }}';">
                                            {{ $fila->nombre ?? '' }} {{ $fila->apellido ?? '' }}
                                        </td>
                                        @foreach($torneos_ranking as $t)
                                            <td class="text-center {{ $t->tipo_columna === 'entrada' ? 'col-entrada' : '' }}">
                                                @isset($desglose_puntos[$fila->jugador_id][$t->col_key])
                                                    {{ number_format($desglose_puntos[$fila->jugador_id][$t->col_key], 0, ',', '.') }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endisset
                                            </td>
                                        @endforeach
                                        <td class="text-right font-weight-bold">{{ number_format($fila->puntos_totales, 0, ',', '.') }}</td>
                                        <td class="text-right text-nowrap">
                                            <button type="button"
                                                    class="btn btn-outline-success btn-sm"
                                                    title="Subir de categoría (divide puntos por 2)"
                                                    onclick="moverCategoriaRanking({{ (int) $fila->jugador_id }}, 'up')">
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-outline-warning btn-sm"
                                                    title="Bajar de categoría (divide puntos por 2)"
                                                    onclick="moverCategoriaRanking({{ (int) $fila->jugador_id }}, 'down')">
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif(!$categorias->isEmpty() || !$entradas_manuales->isEmpty())
                        <p class="text-muted mb-0">No hay datos de ranking para {{ $categoria_seleccionada }}º categoría en la temporada {{ $temporada_seleccionada }}.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     Modal: Crear nueva entrada manual de ranking
============================================================ --}}
<div class="modal fade" id="modalNuevaEntrada" tabindex="-1" role="dialog" aria-labelledby="modalNuevaEntradaLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaEntradaLabel"><i class="fas fa-plus-circle text-success mr-1"></i> Nuevo ranking manual</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Creá un período de ranking manual (ej. un torneo externo, un mes sin torneo registrado). Después podrás agregar los jugadores y sus puntos.</p>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label col-form-label-sm" for="ne-nombre">Nombre <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control form-control-sm" id="ne-nombre" placeholder="Ej: Copa Verano Enero 2026" maxlength="128">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label col-form-label-sm" for="ne-tipo">Tipo</label>
                    <div class="col-sm-9">
                        <select class="form-control form-control-sm" id="ne-tipo">
                            <option value="masculino">Masculino</option>
                            <option value="femenino">Femenino</option>
                            <option value="mixto">Mixto</option>
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label col-form-label-sm" for="ne-categoria">Categoría <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <select class="form-control form-control-sm" id="ne-categoria">
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ $i == $categoria_seleccionada ? 'selected' : '' }}>{{ $i }}ª Categoría</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label col-form-label-sm" for="ne-temporada">Temporada</label>
                    <div class="col-sm-9">
                        <select class="form-control form-control-sm" id="ne-temporada">
                            @for($y = date('Y') + 1; $y >= 2024; $y--)
                                <option value="{{ $y }}" {{ $y == $temporada_seleccionada ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label col-form-label-sm" for="ne-mes">Mes</label>
                    <div class="col-sm-9">
                        <select class="form-control form-control-sm" id="ne-mes">
                            <option value="1">Enero</option>
                            <option value="2">Febrero</option>
                            <option value="3">Marzo</option>
                            <option value="4">Abril</option>
                            <option value="5">Mayo</option>
                            <option value="6">Junio</option>
                            <option value="7">Julio</option>
                            <option value="8">Agosto</option>
                            <option value="9">Septiembre</option>
                            <option value="10">Octubre</option>
                            <option value="11">Noviembre</option>
                            <option value="12">Diciembre</option>
                        </select>
                    </div>
                </div>
                <div class="form-group row mb-0">
                    <label class="col-sm-3 col-form-label col-form-label-sm" for="ne-descripcion">Descripción</label>
                    <div class="col-sm-9">
                        <textarea class="form-control form-control-sm" id="ne-descripcion" rows="2" placeholder="Opcional..." maxlength="500"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success btn-sm" id="btn-guardar-nueva-entrada">
                    <i class="fas fa-save"></i> Crear entrada
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     Modal: Gestionar jugadores de una entrada manual
============================================================ --}}
<div class="modal fade" id="modalGestionJugadores" tabindex="-1" role="dialog" aria-labelledby="modalGestionJugadoresLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGestionJugadoresLabel"><i class="fas fa-users text-primary mr-1"></i> Jugadores — <span id="gj-nombre-entrada"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                {{-- Agregar jugador --}}
                <div class="d-flex gap-2 align-items-end mb-3 flex-wrap">
                    <div class="flex-grow-1">
                        <label class="small font-weight-bold mb-1">Agregar jugador</label>
                        <select class="form-control form-control-sm" id="gj-select-jugador" style="min-width: 220px;">
                            <option value="">— Seleccioná un jugador —</option>
                        </select>
                    </div>
                    <div style="min-width:120px;">
                        <label class="small font-weight-bold mb-1">Posición</label>
                        <select class="form-control form-control-sm" id="gj-select-referencia">
                            {{-- se llena con JS --}}
                        </select>
                    </div>
                    <div>
                        <label class="small font-weight-bold mb-1">Puntos</label>
                        <input type="number" min="0" class="form-control form-control-sm" id="gj-input-puntos" style="width:90px;" placeholder="0">
                    </div>
                    <div class="pt-1">
                        <button type="button" class="btn btn-primary btn-sm" id="btn-agregar-jugador-entrada">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    </div>
                </div>

                <div id="gj-loading" class="text-center py-3 d-none"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>

                <div class="table-responsive" id="gj-tabla-wrap">
                    <table class="table table-sm table-bordered mb-0" id="tablaJugadoresEntrada">
                        <thead class="thead-light">
                            <tr>
                                <th>Jugador</th>
                                <th style="width: 160px;">Posición</th>
                                <th style="width: 100px;">Puntos</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tbody-jugadores-entrada">
                            <tr id="tr-sin-jugadores"><td colspan="4" class="text-muted text-center py-3">No hay jugadores en esta entrada.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary btn-sm" id="btn-guardar-jugadores-entrada">
                    <i class="fas fa-save"></i> Guardar puntos
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     Modal: Referencias de puntuación
============================================================ --}}
<div class="modal fade" id="modalReferenciasPuntuacion" tabindex="-1" role="dialog" aria-labelledby="modalReferenciasPuntuacionLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalReferenciasPuntuacionLabel">Referencias de puntuación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Puntos que se asignan por defecto según la posición en el torneo.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">Orden</th>
                                <th>Nombre</th>
                                <th style="width: 100px;">Puntos</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-referencias-puntuacion">
                            @forelse($referencias_puntuacion as $ref)
                            <tr data-id="{{ $ref->id }}">
                                <td class="align-middle">{{ $ref->orden }}</td>
                                <td><input type="text" class="form-control form-control-sm ref-nombre" value="{{ $ref->nombre }}" data-id="{{ $ref->id }}"></td>
                                <td><input type="number" min="0" class="form-control form-control-sm ref-puntos" value="{{ $ref->puntos }}" data-id="{{ $ref->id }}"></td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted text-center">No hay referencias cargadas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-referencias">
                    <i class="fa fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var CSRF_TOKEN = '{{ csrf_token() }}';
var URL_CREAR_ENTRADA    = '{{ route("adminrankingentradacrear") }}';
var URL_ELIMINAR_ENTRADA = '{{ route("adminrankingentradaeliminar") }}';
var URL_JUGADORES_ENTRADA= '{{ route("adminrankingentradajugadores") }}';
var URL_GUARDAR_JUGADORES= '{{ route("adminrankingentradajugadoresguardar") }}';
var URL_LISTA_JUGADORES  = '{{ route("adminrankingjugadoreslista") }}';
var URL_GUARDAR_REF      = '{{ route("guardarreferenciaspuntuacion") }}';
var URL_MOVER            = '{{ route("adminrankingmover") }}';

// ── Estado del modal de gestión ──────────────────────────────────────────────
var gjEntradaId = null;
var gjReferencias = [];      // [{codigo, nombre, puntos}]
var gjJugadoresLista = [];   // todos los jugadores activos
var gjFilasActuales = [];    // filas actualmente en la tabla del modal

$(function() {

    // ── Referencias de puntuación ─────────────────────────────────────────────
    $('#btn-referencias-puntuacion').on('click', function() {
        $('#modalReferenciasPuntuacion').modal('show');
    });

    $('#btn-guardar-referencias').on('click', function() {
        var items = [];
        $('#tbody-referencias-puntuacion tr').each(function() {
            var id     = $(this).data('id');
            var nombre = $(this).find('.ref-nombre').val();
            var puntos = parseInt($(this).find('.ref-puntos').val(), 10);
            if (isNaN(puntos)) puntos = 0;
            items.push({ id: id, nombre: nombre, puntos: puntos });
        });
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
        $.post(URL_GUARDAR_REF, { items: items, _token: CSRF_TOKEN }, function(res) {
            btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
            if (res.success) {
                snack(res.message || 'Guardado.');
                $('#modalReferenciasPuntuacion').modal('hide');
            } else {
                alert(res.message || 'Error al guardar.');
            }
        }, 'json').fail(function(xhr) {
            btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
            alert((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error al guardar.');
        });
    });

    // ── Nueva entrada ─────────────────────────────────────────────────────────
    $('#btn-nueva-entrada').on('click', function() {
        // Pre-seleccionar el mes actual
        var mesActual = {{ (int) date('n') }};
        $('#ne-mes').val(mesActual);
        $('#ne-nombre').val('');
        $('#ne-descripcion').val('');
        $('#ne-tipo').val('{{ $tipo_seleccionado }}');
        $('#modalNuevaEntrada').modal('show');
    });

    $('#btn-guardar-nueva-entrada').on('click', function() {
        var nombre    = $('#ne-nombre').val().trim();
        var tipo      = $('#ne-tipo').val();
        var categoria = $('#ne-categoria').val();
        var temporada = $('#ne-temporada').val();
        var mes       = $('#ne-mes').val();
        var descripcion = $('#ne-descripcion').val().trim();

        if (!nombre) { alert('El nombre es obligatorio.'); return; }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando...');

        $.post(URL_CREAR_ENTRADA, {
            _token: CSRF_TOKEN,
            nombre: nombre, tipo: tipo, categoria: categoria,
            temporada: temporada, mes: mes, descripcion: descripcion
        }, function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Crear entrada');
            if (res.success) {
                snack(res.message || 'Entrada creada.');
                $('#modalNuevaEntrada').modal('hide');
                // Abrimos el modal de jugadores para la nueva entrada
                setTimeout(function() {
                    abrirModalGestionJugadores(res.entrada.id, res.entrada.nombre);
                }, 400);
            } else {
                alert(res.message || 'Error al crear.');
            }
        }, 'json').fail(function(xhr) {
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Crear entrada');
            alert((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error.');
        });
    });

    // ── Gestionar jugadores (desde tarjeta de entrada) ────────────────────────
    $(document).on('click', '.btn-gestionar-entrada', function() {
        var id     = $(this).data('id');
        var nombre = $(this).data('nombre');
        abrirModalGestionJugadores(id, nombre);
    });

    // ── Eliminar entrada ──────────────────────────────────────────────────────
    $(document).on('click', '.btn-eliminar-entrada', function() {
        var id     = $(this).data('id');
        var nombre = $(this).data('nombre');
        if (!confirm('¿Eliminar la entrada "' + nombre + '"?\nSe eliminarán los puntos asignados y se recalculará el ranking.')) return;

        $.post(URL_ELIMINAR_ENTRADA, { _token: CSRF_TOKEN, entrada_id: id }, function(res) {
            if (res.success) {
                snack(res.message || 'Eliminada.');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                alert(res.message || 'Error al eliminar.');
            }
        }, 'json').fail(function(xhr) {
            alert((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error al eliminar.');
        });
    });

    // ── Referencia auto-rellena puntos ────────────────────────────────────────
    $('#gj-select-referencia').on('change', function() {
        var codigo = $(this).val();
        var ref = gjReferencias.find(function(r) { return r.codigo === codigo; });
        if (ref) $('#gj-input-puntos').val(ref.puntos);
    });

    // ── Agregar jugador a la tabla del modal ──────────────────────────────────
    $('#btn-agregar-jugador-entrada').on('click', function() {
        var jugadorId = parseInt($('#gj-select-jugador').val(), 10);
        if (!jugadorId) { alert('Seleccioná un jugador.'); return; }

        // Verificar duplicado
        var existe = gjFilasActuales.find(function(f) { return f.jugador_id === jugadorId; });
        if (existe) { alert('Este jugador ya está en la lista.'); return; }

        var jugador = gjJugadoresLista.find(function(j) { return j.id === jugadorId; });
        var referenciaCodigo = $('#gj-select-referencia').val();
        var puntos = parseInt($('#gj-input-puntos').val(), 10);
        if (isNaN(puntos)) puntos = 0;

        var fila = {
            jugador_id: jugadorId,
            nombre: (jugador ? jugador.apellido + ' ' + jugador.nombre : 'ID ' + jugadorId),
            puntos: puntos,
            referencia_codigo: referenciaCodigo
        };
        gjFilasActuales.push(fila);
        renderizarTablaJugadores();
        $('#gj-select-jugador').val('');
    });

    // ── Guardar puntos ────────────────────────────────────────────────────────
    $('#btn-guardar-jugadores-entrada').on('click', function() {
        if (!gjEntradaId) return;

        // Leer valores actuales de la tabla
        var items = [];
        $('#tbody-jugadores-entrada tr[data-jugador-id]').each(function() {
            var jugadorId = parseInt($(this).data('jugador-id'), 10);
            var puntos    = parseInt($(this).find('.inp-puntos').val(), 10);
            var ref       = $(this).find('.sel-referencia').val();
            if (isNaN(puntos)) puntos = 0;
            items.push({ jugador_id: jugadorId, puntos: puntos, referencia_codigo: ref });
        });

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.post(URL_GUARDAR_JUGADORES, {
            _token: CSRF_TOKEN,
            entrada_id: gjEntradaId,
            items: items
        }, function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar puntos');
            if (res.success) {
                snack(res.message || 'Guardado.');
                $('#modalGestionJugadores').modal('hide');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                alert(res.message || 'Error al guardar.');
            }
        }, 'json').fail(function(xhr) {
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar puntos');
            alert((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error al guardar.');
        });
    });

});

// ── Funciones auxiliares ──────────────────────────────────────────────────────

function abrirModalGestionJugadores(entradaId, nombreEntrada) {
    gjEntradaId = entradaId;
    gjFilasActuales = [];
    $('#gj-nombre-entrada').text(nombreEntrada);
    $('#tbody-jugadores-entrada').html('<tr><td colspan="4" class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');
    $('#gj-select-jugador').html('<option value="">— Seleccioná un jugador —</option>');
    $('#gj-select-referencia').html('');

    // Cargar jugadores activos y datos de la entrada en paralelo
    $.when(
        $.getJSON(URL_LISTA_JUGADORES),
        $.getJSON(URL_JUGADORES_ENTRADA + '?entrada_id=' + entradaId)
    ).done(function(resJugadores, resEntrada) {
        var dataJugadores = resJugadores[0];
        var dataEntrada   = resEntrada[0];

        if (!dataJugadores.success || !dataEntrada.success) {
            $('#tbody-jugadores-entrada').html('<tr><td colspan="4" class="text-danger text-center">Error al cargar datos.</td></tr>');
            return;
        }

        gjJugadoresLista = dataJugadores.jugadores || [];
        gjReferencias    = dataEntrada.referencias  || [];

        // Poblar select de referencias
        var refOpts = '';
        gjReferencias.forEach(function(r) {
            refOpts += '<option value="' + r.codigo + '">' + r.nombre + ' (' + r.puntos + ' pts)</option>';
        });
        $('#gj-select-referencia').html(refOpts);

        // Poblar select de jugadores
        var jOpts = '<option value="">— Seleccioná un jugador —</option>';
        gjJugadoresLista.forEach(function(j) {
            jOpts += '<option value="' + j.id + '">' + j.apellido + ' ' + j.nombre + '</option>';
        });
        $('#gj-select-jugador').html(jOpts);

        // Filas existentes
        gjFilasActuales = (dataEntrada.jugadores || []).map(function(j) {
            return {
                jugador_id: j.jugador_id,
                nombre: j.apellido + ' ' + j.nombre,
                puntos: j.puntos,
                referencia_codigo: j.referencia_codigo
            };
        });
        renderizarTablaJugadores();

    }).fail(function() {
        $('#tbody-jugadores-entrada').html('<tr><td colspan="4" class="text-danger text-center">Error al cargar datos.</td></tr>');
    });

    $('#modalGestionJugadores').modal('show');
}

function renderizarTablaJugadores() {
    var tbody = $('#tbody-jugadores-entrada');
    if (gjFilasActuales.length === 0) {
        tbody.html('<tr id="tr-sin-jugadores"><td colspan="4" class="text-muted text-center py-3">No hay jugadores en esta entrada.</td></tr>');
        return;
    }

    var refOpts = '';
    gjReferencias.forEach(function(r) {
        refOpts += '<option value="' + r.codigo + '">' + r.nombre + ' (' + r.puntos + ' pts)</option>';
    });

    var html = '';
    gjFilasActuales.forEach(function(f, idx) {
        html += '<tr class="jugador-row-rank" data-jugador-id="' + f.jugador_id + '">';
        html += '<td class="align-middle">' + escHtml(f.nombre) + '</td>';
        html += '<td><select class="form-control form-control-sm sel-referencia">';
        gjReferencias.forEach(function(r) {
            html += '<option value="' + r.codigo + '"' + (r.codigo === f.referencia_codigo ? ' selected' : '') + '>' + escHtml(r.nombre) + ' (' + r.puntos + ' pts)</option>';
        });
        html += '</select></td>';
        html += '<td><input type="number" min="0" class="form-control form-control-sm inp-puntos" value="' + f.puntos + '"></td>';
        html += '<td class="text-center align-middle"><button type="button" class="btn-quitar-jugador" data-idx="' + idx + '" title="Quitar"><i class="fas fa-times"></i></button></td>';
        html += '</tr>';
    });
    tbody.html(html);

    // Evento quitar
    tbody.find('.btn-quitar-jugador').on('click', function() {
        var idx = parseInt($(this).data('idx'), 10);
        gjFilasActuales.splice(idx, 1);
        renderizarTablaJugadores();
    });

    // Evento sel-referencia auto-rellena puntos
    tbody.find('.sel-referencia').on('change', function() {
        var codigo = $(this).val();
        var ref = gjReferencias.find(function(r) { return r.codigo === codigo; });
        if (ref) $(this).closest('tr').find('.inp-puntos').val(ref.puntos);
    });
}

function moverCategoriaRanking(jugadorId, direccion) {
    if (!jugadorId) return;
    var tipo      = $('#tipo').val();
    var categoria = $('#categoria').val();
    var temporada = $('#temporada').val();
    var texto     = (direccion === 'up') ? 'subir' : 'bajar';
    if (!confirm('¿Seguro que querés ' + texto + ' de categoría? (los puntos se dividen por 2)')) return;

    $.post(URL_MOVER, {
        jugador_id: jugadorId, direccion: direccion,
        tipo: tipo, categoria: categoria, temporada: temporada,
        _token: CSRF_TOKEN
    }, function(res) {
        if (res && res.success) {
            location.reload();
        } else {
            alert((res && res.message) ? res.message : 'Error');
        }
    }, 'json').fail(function(xhr) {
        alert((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error');
    });
}

function snack(msg) {
    if (typeof mostrarSnackbar === 'function') mostrarSnackbar(msg);
    else {
        var s = $('<div class="alert alert-success alert-dismissible" role="alert" style="position:fixed;bottom:20px;right:20px;z-index:9999;min-width:260px;">' + escHtml(msg) + '</div>');
        $('body').append(s);
        setTimeout(function() { s.fadeOut(400, function() { s.remove(); }); }, 3000);
    }
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

@endsection
