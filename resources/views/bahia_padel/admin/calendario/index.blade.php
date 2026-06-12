@extends('bahia_padel/admin/plantilla')

@section('title_header','Calendario')

@section('contenedor')
@php
    $fmtFecha = function ($d) {
        return $d ? $d->format('d/m/Y') : '—';
    };
    $fmtPremio = function ($v) {
        if ($v === null || $v === '') return '—';
        return '$' . number_format((float) $v, 0, ',', '.');
    };
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Eventos del calendario</h6>
                    <button type="button" class="btn btn-success btn-sm" id="btn-nueva" onclick="mostrarFormNuevo()">
                        <i class="fas fa-plus"></i> Nuevo
                    </button>
                </div>
                <div class="card-body">
                    @if($eventos->isEmpty())
                        <p class="text-muted mb-0">No hay eventos. Agregá uno con el botón «Nuevo».</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Desde</th>
                                        <th>Hasta</th>
                                        <th>Abre inscr.</th>
                                        <th>Cierra inscr.</th>
                                        <th>Cat.</th>
                                        <th>Tipo</th>
                                        <th>Nombre</th>
                                        <th>P.1</th>
                                        <th>P.2</th>
                                        <th>P.3</th>
                                        <th>P.4</th>
                                        <th>Inscr.</th>
                                        <th class="text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($eventos as $e)
                                    <tr>
                                        <td>{{ $fmtFecha($e->fecha_desde ?? $e->fecha) }}</td>
                                        <td>{{ $fmtFecha($e->fecha_hasta ?? $e->fecha_desde ?? $e->fecha) }}</td>
                                        <td>{{ $fmtFecha($e->fecha_abre_inscripcion) }}</td>
                                        <td>{{ $fmtFecha($e->fecha_cierra_inscripcion) }}</td>
                                        <td>{{ $e->categoria }}ª</td>
                                        <td>{{ $e->tipo_label }}</td>
                                        <td>{{ $e->nombre ?? '—' }}</td>
                                        <td>{{ $fmtPremio($e->premio_1) }}</td>
                                        <td>{{ $fmtPremio($e->premio_2) }}</td>
                                        <td>{{ $fmtPremio($e->premio_3) }}</td>
                                        <td>{{ $fmtPremio($e->premio_4) }}</td>
                                        <td>{{ $fmtPremio($e->valor_inscripcion) }}</td>
                                        <td class="text-right text-nowrap">
                                            <button type="button" class="btn btn-outline-info btn-sm" onclick="verInscripcionesCalendario({{ $e->id }})">Ver <span class="badge badge-light text-dark border">{{ (int) ($e->inscripciones_count ?? 0) }}</span></button>
                                            <a href="{{ route('admincalendario') }}?editar={{ $e->id }}" class="btn btn-outline-primary btn-sm">Editar</a>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminar({{ $e->id }})">Eliminar</button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow mb-4" id="card-form" style="{{ (isset($item) && $item) ? '' : 'display:none;' }}">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        {{ $item ? 'Editar evento' : 'Nuevo evento' }}
                    </h6>
                </div>
                <div class="card-body">
                    <form id="form-calendario">
                        @csrf
                        <input type="hidden" name="id" id="calendario_id" value="{{ $item ? $item->id : '' }}">
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Fecha desde <span class="text-danger">*</span></label>
                            <div class="col-sm-4">
                                <input type="date" class="form-control" name="fecha_desde" id="calendario_fecha_desde" value="{{ $item && ($item->fecha_desde ?? $item->fecha) ? ($item->fecha_desde ?? $item->fecha)->format('Y-m-d') : '' }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Fecha hasta</label>
                            <div class="col-sm-4">
                                <input type="date" class="form-control" name="fecha_hasta" id="calendario_fecha_hasta" value="{{ $item && $item->fecha_hasta ? $item->fecha_hasta->format('Y-m-d') : '' }}">
                                <small class="text-muted">Si queda vacío, se usa la misma que «desde».</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Abre inscripción</label>
                            <div class="col-sm-4">
                                <input type="date" class="form-control" name="fecha_abre_inscripcion" id="calendario_fecha_abre_inscripcion" value="{{ $item && $item->fecha_abre_inscripcion ? $item->fecha_abre_inscripcion->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Cierra inscripción</label>
                            <div class="col-sm-4">
                                <input type="date" class="form-control" name="fecha_cierra_inscripcion" id="calendario_fecha_cierra_inscripcion" value="{{ $item && $item->fecha_cierra_inscripcion ? $item->fecha_cierra_inscripcion->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Categoría</label>
                            <div class="col-sm-4">
                                <select class="form-control" name="categoria" id="calendario_categoria" required>
                                    @for($i=1; $i<=7; $i++)
                                    <option value="{{ $i }}" {{ ($item && $item->categoria == $i) ? 'selected' : '' }}>{{ $i }}ª</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Tipo</label>
                            <div class="col-sm-4">
                                <select class="form-control" name="tipo" id="calendario_tipo">
                                    <option value="mixto" {{ ($item && $item->tipo == 'mixto') ? 'selected' : '' }}>Mixto</option>
                                    <option value="femenino" {{ ($item && $item->tipo == 'femenino') ? 'selected' : '' }}>Damas</option>
                                    <option value="masculino" {{ ($item && $item->tipo == 'masculino') ? 'selected' : '' }}>Libre</option>
                                </select>
                                <small class="text-muted">Se guarda como femenino/masculino, en la home se muestra Damas/Libre</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Nombre (opcional)</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control" name="nombre" id="calendario_nombre" value="{{ $item ? $item->nombre : '' }}" placeholder="Ej: Torneo marzo">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Premios ($)</label>
                            <div class="col-sm-10">
                                <div class="form-row">
                                    <div class="col-md-3 mb-2">
                                        <label class="small text-muted">1º premio</label>
                                        <input type="number" class="form-control" name="premio_1" id="calendario_premio_1" value="{{ $item && $item->premio_1 !== null ? $item->premio_1 : '' }}" step="0.01" min="0" placeholder="0">
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="small text-muted">2º premio</label>
                                        <input type="number" class="form-control" name="premio_2" id="calendario_premio_2" value="{{ $item && $item->premio_2 !== null ? $item->premio_2 : '' }}" step="0.01" min="0" placeholder="0">
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="small text-muted">3º premio</label>
                                        <input type="number" class="form-control" name="premio_3" id="calendario_premio_3" value="{{ $item && $item->premio_3 !== null ? $item->premio_3 : '' }}" step="0.01" min="0" placeholder="0">
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="small text-muted">4º premio</label>
                                        <input type="number" class="form-control" name="premio_4" id="calendario_premio_4" value="{{ $item && $item->premio_4 !== null ? $item->premio_4 : '' }}" step="0.01" min="0" placeholder="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Valor inscripción ($)</label>
                            <div class="col-sm-4">
                                <input type="number" class="form-control" name="valor_inscripcion" id="calendario_valor_inscripcion" value="{{ $item && $item->valor_inscripcion !== null ? $item->valor_inscripcion : '' }}" step="0.01" min="0" placeholder="Opcional">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-10">
                                <button type="submit" class="btn btn-primary">Guardar</button>
                                <a href="{{ route('admincalendario') }}" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInscripcionesCalendario" tabindex="-1" role="dialog" aria-labelledby="modalInscCalTitulo" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="modalInscCalTitulo">Inscripciones</h5>
                    <div id="modalInscCalSub" class="small text-muted mt-1"></div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalInscCalBody">
                <p class="text-muted mb-0">Cargando…</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function escHtml(s) {
    if (s === null || s === undefined) return '';
    return $('<div/>').text(String(s)).html();
}

function verInscripcionesCalendario(calendarioId) {
    var $body = $('#modalInscCalBody');
    var $title = $('#modalInscCalTitulo');
    var $sub = $('#modalInscCalSub');
    $body.html('<p class="text-muted mb-0">Cargando…</p>');
    $title.text('Inscripciones');
    $sub.empty();
    $('#modalInscripcionesCalendario').modal('show');
    var urlTpl = '{{ route('admincalendarioinscripcionesjson', ['calendario' => '__ID__']) }}';
    var url = urlTpl.replace('__ID__', String(calendarioId));
    $.getJSON(url).done(function(data) {
        $title.text(data.titulo || 'Inscripciones');
        var subLines = [];
        if (data.fechas) subLines.push('Fecha: ' + data.fechas);
        if (data.inscripciones) subLines.push('Total: ' + data.inscripciones.length);
        $sub.html(subLines.map(function(line) { return '<div>' + escHtml(line) + '</div>'; }).join(''));

        if (!data.inscripciones || data.inscripciones.length === 0) {
            $body.html('<p class="text-muted mb-0">Todavía no hay inscripciones para este evento.</p>');
            return;
        }
        var html = '<div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead class="thead-light"><tr>';
        html += '<th>Fecha registro</th><th>Jugador 1</th><th>Tel. J1</th><th>Jugador 2</th><th>Tel. J2</th><th>Disponibilidad</th>';
        html += '</tr></thead><tbody>';
        data.inscripciones.forEach(function(row) {
            html += '<tr>';
            html += '<td class="text-nowrap">' + escHtml(row.registrado) + '</td>';
            html += '<td>' + escHtml(row.jugador1) + '</td>';
            html += '<td>' + escHtml(row.tel1) + '</td>';
            html += '<td>' + escHtml(row.jugador2) + '</td>';
            html += '<td>' + escHtml(row.tel2 || '—') + '</td>';
            html += '<td style="max-width:220px;white-space:pre-wrap;">' + escHtml(row.disponibilidad) + '</td>';
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        $body.html(html);
    }).fail(function(xhr) {
        var status = xhr && xhr.status ? (' (' + xhr.status + ')') : '';
        $body.html(
            '<p class="text-danger mb-2">No se pudo cargar el listado' + status + '.</p>' +
            '<p class="text-muted small mb-0">' + escHtml(url) + '</p>'
        );
    });
}

function mostrarFormNuevo() {
    document.getElementById('card-form').style.display = 'block';
    document.getElementById('calendario_id').value = '';
    document.getElementById('calendario_fecha_desde').value = '';
    document.getElementById('calendario_fecha_hasta').value = '';
    document.getElementById('calendario_fecha_abre_inscripcion').value = '';
    document.getElementById('calendario_fecha_cierra_inscripcion').value = '';
    document.getElementById('calendario_categoria').value = '1';
    document.getElementById('calendario_tipo').value = 'mixto';
    document.getElementById('calendario_nombre').value = '';
    document.getElementById('calendario_premio_1').value = '';
    document.getElementById('calendario_premio_2').value = '';
    document.getElementById('calendario_premio_3').value = '';
    document.getElementById('calendario_premio_4').value = '';
    document.getElementById('calendario_valor_inscripcion').value = '';
}

function eliminar(id) {
    if (!confirm('¿Eliminar este evento?')) return;
    $.post('{{ route("admincalendarioeliminar") }}', { id: id, _token: '{{ csrf_token() }}' }, function(r) {
        if (r.success) location.reload();
        else alert(r.message || 'Error');
    }, 'json').fail(function() { alert('Error al eliminar'); });
}

$('#form-calendario').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('[type="submit"]');
    $btn.prop('disabled', true);
    $.post('{{ route("admincalendarioguardar") }}', {
        id: $('#calendario_id').val(),
        fecha_desde: $('#calendario_fecha_desde').val(),
        fecha_hasta: $('#calendario_fecha_hasta').val(),
        fecha_abre_inscripcion: $('#calendario_fecha_abre_inscripcion').val(),
        fecha_cierra_inscripcion: $('#calendario_fecha_cierra_inscripcion').val(),
        categoria: $('#calendario_categoria').val(),
        tipo: $('#calendario_tipo').val(),
        nombre: $('#calendario_nombre').val(),
        premio_1: $('#calendario_premio_1').val(),
        premio_2: $('#calendario_premio_2').val(),
        premio_3: $('#calendario_premio_3').val(),
        premio_4: $('#calendario_premio_4').val(),
        valor_inscripcion: $('#calendario_valor_inscripcion').val(),
        _token: '{{ csrf_token() }}'
    }, function(r) {
        $btn.prop('disabled', false);
        if (r.success) location.reload();
        else alert(r.message || 'Error');
    }, 'json').fail(function() {
        $btn.prop('disabled', false);
        alert('Error al guardar');
    });
});

@if($item)
document.getElementById('card-form').style.display = 'block';
@endif
</script>
@endsection
