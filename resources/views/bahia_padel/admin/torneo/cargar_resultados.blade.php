@extends('bahia_padel/admin/plantilla')

@section('title_header','Cargar Resultados')

@section('contenedor')
<style>
    /* Mobile-first: pantalla optimizada para cargar resultados */
    @media (max-width: 768px) {
        .cargar-resultados-container { padding: 0.5rem; }
        .partido-card-cargar { min-width: 280px !important; }
    }
    .partidos-scroll-horizontal {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        padding: 1rem 0;
        margin: 0 -0.5rem;
    }
    .partidos-scroll-horizontal::-webkit-scrollbar { height: 8px; }
    .partidos-scroll-horizontal::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .partidos-scroll-horizontal::-webkit-scrollbar-thumb { background: #4e73df; border-radius: 10px; }
    .partidos-row {
        display: flex;
        flex-wrap: nowrap;
        gap: 1rem;
        min-width: max-content;
        padding: 0 0.5rem;
    }
    .partido-card-cargar {
        min-width: 300px;
        flex-shrink: 0;
        transition: opacity 0.3s ease;
        background: #fff !important;
    }
    .partido-card-cargar.guardado {
        opacity: 0;
        pointer-events: none;
        transform: scale(0.95);
    }
    /* Colores por categoría (1-7) */
    .banner-cat-1 { background: #4e73df; color: #fff !important; }
    .banner-cat-2 { background: #1cc88a; color: #fff !important; }
    .banner-cat-3 { background: #f6c23e; color: #000 !important; }
    .banner-cat-4 { background: #e74a3b; color: #fff !important; }
    .banner-cat-5 { background: #36b9cc; color: #fff !important; }
    .banner-cat-6 { background: #6f42c1; color: #fff !important; }
    .banner-cat-7 { background: #fd7e14; color: #fff !important; }
    .banner-categoria {
        display: inline-block;
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 700;
        margin-left: 0.5rem;
    }
    /* Texto negro en las cards */
    .partido-card-cargar,
    .partido-card-cargar .card-body,
    .partido-card-cargar label,
    .partido-card-cargar .card-title,
    .partido-card-cargar div,
    .partido-card-cargar span { color: #000 !important; }
    .partido-card-cargar .form-control { color: #000 !important; background: #fff !important; }
    .partido-card-cargar .btn-primary { color: #fff !important; }
</style>

<div class="container-fluid cargar-resultados-container">
    @if(!$torneo)
        {{-- Selector de torneo --}}
        <div class="row justify-content-center">
            <div class="col-12">
                <h4 class="mb-4 text-center" style="color:#4e73df;">Seleccionar torneo en progreso</h4>
                @if($torneos->isEmpty())
                    <div class="alert alert-info text-center">No hay torneos en progreso.</div>
                @else
                    <div class="list-group">
                        @foreach($torneos as $t)
                        <a href="{{ route('admincargarresultados') }}?torneo_id={{ $t->id }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><strong>{{ $t->categoria }}º</strong> {{ $t->nombre ?? 'Torneo' }} - {{ $t->tipo ?? '' }}</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        @endforeach
                    </div>
                @endif
                <div class="mt-3 text-center">
                    <a href="{{ route('admintorneos') }}" class="btn btn-outline-secondary">← Volver a Torneos</a>
                </div>
            </div>
        </div>
    @else
        {{-- Partidos --}}
        @php $modoEditar = $modoEditar ?? false; @endphp
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <div>
                    <a href="{{ route('admincargarresultados') }}" class="btn btn-sm btn-outline-secondary mb-2">
                        <i class="fas fa-arrow-left"></i> Cambiar torneo
                    </a>
                    <h5 class="mb-0" style="color:#4e73df;">{{ $torneo->categoria }}º {{ $torneo->nombre ?? 'Torneo' }}</h5>
                    <small class="text-muted">{{ $modoEditar ? 'Todos los partidos (editar)' : 'Partidos pendientes de resultado' }}</small>
                </div>
                <div>
                    @if($modoEditar)
                        <a href="{{ route('admincargarresultados') }}?torneo_id={{ $torneo->id }}" class="btn btn-sm btn-outline-secondary">
                            Ver pendientes
                        </a>
                    @else
                        <a href="{{ route('admincargarresultados') }}?torneo_id={{ $torneo->id }}&editar=1" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @php
            $jugadoresMap = [];
            foreach($jugadores as $j) { $jugadoresMap[$j->id] = $j; }
        @endphp

        @if($partidos->isEmpty())
            <div class="alert alert-success text-center">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <p class="mb-0">No hay partidos pendientes. Todos los resultados están cargados.</p>
                <a href="{{ route('admincargarresultados') }}" class="btn btn-primary mt-3">Ver otros torneos</a>
            </div>
        @else
            <div class="partidos-scroll-horizontal">
                <div class="partidos-row" id="partidos-row">
                    @foreach($partidos as $partidoData)
                    @php
                        $partidoId = $partidoData['partido_id'];
                        $pareja1 = $partidoData['pareja_1'] ?? null;
                        $pareja2 = $partidoData['pareja_2'] ?? null;
                        $resultados = $partidoData['resultados'];
                        $fecha = $partidoData['fecha'] ?? null;
                        $horario = $partidoData['horario'] ?? null;
                        $tienePareja1 = $pareja1 && $pareja1['jugador_1'] && $pareja1['jugador_2'];
                        $tienePareja2 = $pareja2 && $pareja2['jugador_1'] && $pareja2['jugador_2'];
                        $esGanadorPerdedor = ($pareja1 && ($pareja1['jugador_1']==0 || $pareja1['jugador_2']==0)) || ($pareja2 && ($pareja2['jugador_1']==0 || $pareja2['jugador_2']==0));
                        $jugador1 = $tienePareja1 ? ($jugadoresMap[$pareja1['jugador_1']] ?? null) : null;
                        $jugador2 = $tienePareja1 ? ($jugadoresMap[$pareja1['jugador_2']] ?? null) : null;
                        $jugador3 = $tienePareja2 ? ($jugadoresMap[$pareja2['jugador_1']] ?? null) : null;
                        $jugador4 = $tienePareja2 ? ($jugadoresMap[$pareja2['jugador_2']] ?? null) : null;
                        $fechaFormateada = '';
                        if ($fecha && $fecha != '2000-01-01') {
                            $diasSemana = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
                            $fechaFormateada = ucfirst($diasSemana[date('w', strtotime($fecha))]) . ' ' . date('d', strtotime($fecha));
                        }
                        $tipoPartido = $partidoData['tipo'] ?? 'normal';
                        $zonaPartido = $partidoData['zona'] ?? '';
                        $tituloPartido = $tipoPartido === 'cruce'
                            ? (match(true) {
                                str_starts_with($zonaPartido, '16avos') => '16avos',
                                str_starts_with($zonaPartido, 'dieciseisavos') => '16avos',
                                str_starts_with($zonaPartido, 'octavos') => 'Octavos',
                                str_starts_with($zonaPartido, 'cuartos') => 'Cuartos',
                                $zonaPartido === 'semifinal' => 'Semifinal',
                                $zonaPartido === 'final' => 'Final',
                                default => $zonaPartido
                            })
                            : ($tipoPartido === 'ganador' ? 'Ganador' : ($tipoPartido === 'perdedor' ? 'Perdedor' : 'Zona ' . $zonaPartido));
                        $categoria = (int) ($torneo->categoria ?? 1);
                        $categoriaClase = 'banner-cat-' . (($categoria % 7) ?: 7);
                    @endphp
                    <div class="partido-card-cargar card border shadow-sm" data-partido-id="{{ $partidoId }}" id="card-partido-{{ $partidoId }}">
                        <div class="card-body p-3">
                            <h6 class="card-title text-center mb-2">
                                {{ $tituloPartido }}
                                <span class="banner-categoria {{ $categoriaClase }}">Torneo {{ $categoria }}ª</span>
                            </h6>
                            @if($fechaFormateada || ($horario && $horario != '00:00'))
                            <div class="text-center mb-2" style="font-size:0.8rem; color:#333;">
                                @if($fechaFormateada)<div>{{ $fechaFormateada }}</div>@endif
                                @if($horario && $horario != '00:00')<div>{{ $horario }}</div>@endif
                            </div>
                            @endif
                            <div class="d-flex justify-content-around align-items-center mb-2">
                                <div class="text-center" style="max-width:80px;">
                                    @if($tienePareja1 && $jugador1)
                                    <img src="{{ asset($jugador1->foto ?? 'images/jugador_img.png') }}" class="rounded-circle" style="width:50px;height:50px;object-fit:cover;border:2px solid #4e73df;">
                                    <div style="font-size:0.65rem;font-weight:600;color:#000;">{{ $jugador1->nombre ?? '' }} {{ $jugador1->apellido ?? '' }}</div>
                                    @endif
                                    @if($tienePareja1 && $jugador2)
                                    <img src="{{ asset($jugador2->foto ?? 'images/jugador_img.png') }}" class="rounded-circle" style="width:50px;height:50px;object-fit:cover;border:2px solid #4e73df;">
                                    <div style="font-size:0.65rem;font-weight:600;color:#000;">{{ $jugador2->nombre ?? '' }} {{ $jugador2->apellido ?? '' }}</div>
                                    @endif
                                    @if($esGanadorPerdedor && !$tienePareja1)
                                    <div style="width:50px;height:50px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;margin:0 auto;"><span style="font-size:0.7rem;color:#333;">?</span></div>
                                    <div style="font-size:0.65rem;color:#333;">Esperando</div>
                                    @endif
                                </div>
                                <div class="mx-2"><strong style="color:#dc3545;">VS</strong></div>
                                <div class="text-center" style="max-width:80px;">
                                    @if($tienePareja2 && $jugador3)
                                    <img src="{{ asset($jugador3->foto ?? 'images/jugador_img.png') }}" class="rounded-circle" style="width:50px;height:50px;object-fit:cover;border:2px solid #1a8917;">
                                    <div style="font-size:0.65rem;font-weight:600;color:#000;">{{ $jugador3->nombre ?? '' }} {{ $jugador3->apellido ?? '' }}</div>
                                    @endif
                                    @if($tienePareja2 && $jugador4)
                                    <img src="{{ asset($jugador4->foto ?? 'images/jugador_img.png') }}" class="rounded-circle" style="width:50px;height:50px;object-fit:cover;border:2px solid #1a8917;">
                                    <div style="font-size:0.65rem;font-weight:600;color:#000;">{{ $jugador4->nombre ?? '' }} {{ $jugador4->apellido ?? '' }}</div>
                                    @endif
                                    @if($esGanadorPerdedor && !$tienePareja2)
                                    <div style="width:50px;height:50px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;margin:0 auto;"><span style="font-size:0.7rem;color:#333;">?</span></div>
                                    <div style="font-size:0.65rem;color:#333;">Esperando</div>
                                    @endif
                                </div>
                            </div>
                            @php
                                $r = $resultados ?? null;
                                $v1_1 = $r ? ($r->pareja_1_set_1 ?? 0) : 0;
                                $v2_1 = $r ? ($r->pareja_2_set_1 ?? 0) : 0;
                                $v1_2 = $r ? ($r->pareja_1_set_2 ?? 0) : 0;
                                $v2_2 = $r ? ($r->pareja_2_set_2 ?? 0) : 0;
                                $v1_3 = $r ? ($r->pareja_1_set_3 ?? 0) : 0;
                                $v2_3 = $r ? ($r->pareja_2_set_3 ?? 0) : 0;
                            @endphp
                            <div class="resultado-partido" data-partido-id="{{ $partidoId }}">
                                <div class="mb-2">
                                    <label style="font-size:0.75rem;color:#000;">Set 1</label>
                                    <div class="d-flex justify-content-center align-items-center">
                                        <input type="number" min="0" max="99" class="form-control form-control-sm" style="width:50px;" name="pareja_1_set_1" value="{{ $v1_1 }}" data-partido-id="{{ $partidoId }}">
                                        <span class="mx-1" style="color:#000;">-</span>
                                        <input type="number" min="0" max="99" class="form-control form-control-sm" style="width:50px;" name="pareja_2_set_1" value="{{ $v2_1 }}" data-partido-id="{{ $partidoId }}">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label style="font-size:0.75rem;color:#000;">Set 2</label>
                                    <div class="d-flex justify-content-center align-items-center">
                                        <input type="number" min="0" max="99" class="form-control form-control-sm" style="width:50px;" name="pareja_1_set_2" value="{{ $v1_2 }}" data-partido-id="{{ $partidoId }}">
                                        <span class="mx-1" style="color:#000;">-</span>
                                        <input type="number" min="0" max="99" class="form-control form-control-sm" style="width:50px;" name="pareja_2_set_2" value="{{ $v2_2 }}" data-partido-id="{{ $partidoId }}">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label style="font-size:0.75rem;color:#000;">Set 3</label>
                                    <div class="d-flex justify-content-center align-items-center">
                                        <input type="number" min="0" max="99" class="form-control form-control-sm" style="width:50px;" name="pareja_1_set_3" value="{{ $v1_3 }}" data-partido-id="{{ $partidoId }}">
                                        <span class="mx-1" style="color:#000;">-</span>
                                        <input type="number" min="0" max="99" class="form-control form-control-sm" style="width:50px;" name="pareja_2_set_3" value="{{ $v2_3 }}" data-partido-id="{{ $partidoId }}">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm btn-block guardar-resultado-cargar" data-partido-id="{{ $partidoId }}">
                                    Guardar
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>

@if($torneo && $partidos->isNotEmpty())
<script>
$(function() {
    var modoEditar = {{ ($modoEditar ?? false) ? 'true' : 'false' }};
    
    $(document).on('click', '.guardar-resultado-cargar', function() {
        var partidoId = $(this).data('partido-id');
        var card = $('#card-partido-' + partidoId);
        var resultadoPartido = card.find('.resultado-partido');
        var btn = $(this);
        
        var datos = {
            partido_id: partidoId,
            pareja_1_set_1: resultadoPartido.find('input[name="pareja_1_set_1"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_1: resultadoPartido.find('input[name="pareja_2_set_1"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_2: resultadoPartido.find('input[name="pareja_1_set_2"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_2: resultadoPartido.find('input[name="pareja_2_set_2"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_3: resultadoPartido.find('input[name="pareja_1_set_3"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_3: resultadoPartido.find('input[name="pareja_2_set_3"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_1_tie_break: 0,
            pareja_2_set_1_tie_break: 0,
            pareja_1_set_2_tie_break: 0,
            pareja_2_set_2_tie_break: 0,
            pareja_1_set_3_tie_break: 0,
            pareja_2_set_3_tie_break: 0,
            pareja_1_set_super_tie_break: 0,
            pareja_2_set_super_tie_break: 0,
            _token: '{{ csrf_token() }}'
        };
        
        btn.prop('disabled', true).text('Guardando...');
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("guardarresultadopartido") }}',
            data: datos,
            success: function(data) {
                if (data.success) {
                    if (modoEditar) {
                        btn.prop('disabled', false).text('Guardar');
                        btn.closest('.card-body').prepend('<div class="alert alert-success py-1 px-2 mb-2" style="font-size:0.75rem;">Guardado</div>');
                        setTimeout(function() {
                            btn.closest('.card-body').find('.alert-success').remove();
                        }, 2000);
                    } else {
                        card.addClass('guardado');
                        setTimeout(function() {
                            card.remove();
                            if ($('#partidos-row').children().length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                } else {
                    alert('Error: ' + (data.message || 'Error desconocido'));
                    btn.prop('disabled', false).text('Guardar');
                }
            },
            error: function(xhr) {
                var msg = 'Error al guardar';
                try {
                    var r = JSON.parse(xhr.responseText || '{}');
                    if (r.message) msg = r.message;
                } catch(e) {}
                alert(msg);
                btn.prop('disabled', false).text('Guardar');
            }
        });
    });
});
</script>
@endif
@endsection
