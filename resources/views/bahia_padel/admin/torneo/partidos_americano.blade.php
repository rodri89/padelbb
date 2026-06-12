@extends('bahia_padel/admin/plantilla')

@php
    $fechaFormateada = '';
    if (isset($torneo->fecha_inicio) && $torneo->fecha_inicio) {
        $fecha = new DateTime($torneo->fecha_inicio);
        $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $diaSemana = $dias[(int)$fecha->format('w')];
        $dia = $fecha->format('j');
        $fechaFormateada = $diaSemana . ' ' . $dia;
    }
@endphp

@section('title_header','Partidos Torneo Americano' . ($fechaFormateada ? ' - ' . $fechaFormateada : ''))

@section('contenedor')
<style>
    .tabla-container {
        display: flex;
        align-items: stretch;
    }
    .tabla-partidos-wrapper, .tabla-posiciones-wrapper {
        display: flex;
        flex-direction: column;
    }
    .tabla-partidos-wrapper .card, .tabla-posiciones-wrapper .card {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .tabla-partidos-wrapper .table-responsive, .tabla-posiciones-wrapper .table-responsive {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .tabla-partidos-wrapper table, .tabla-posiciones-wrapper table {
        flex: 1;
    }
    .body_admin {
        padding-top: 10px !important;
        padding-bottom: 10px !important;
        margin-top: -50px !important;
    }
</style>
<div class="container-fluid body_admin">
    <div class="row">
        <div class="col-12">
            <input type="hidden" id="torneo_id" value="{{ $torneo->id ?? 0 }}">

            <!-- Navegación de zonas -->
            <div class="card shadow bg-white p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                     <a href="{{ route('tvtorneoamericano') }}?torneo_id={{ $torneo->id }}" target="_blank" class="btn btn-primary">
                        <i class="fa fa-desktop"></i> Abrir Pantalla TV Zonas
                    </a>
                    <a href="{{ route('tvtorneosrotacion') }}?torneos={{ $torneo->id }}&intervalo=60" target="_blank" class="btn btn-info">
                        <i class="fa fa-tv"></i> TV Rotación
                    </a>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-secondary" id="btn-zona-anterior">
                        ← Anterior
                    </button>
                    <h4 id="zona-actual" class="mb-0">Zona A</h4>
                    <button type="button" class="btn btn-secondary" id="btn-zona-siguiente">
                        Siguiente →
                    </button>
                </div>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-success btn-lg" id="btn-cruces" style="display:none;">
                        Cruces
                    </button>
                </div>
            </div>
            

            <!-- Partidos por zona -->
            @php
                $zonas = array_keys($partidosPorZona ?? []);
                $zonaIndex = 0;
            @endphp
            
            @if(empty($partidosPorZona) || count($zonas) == 0)
                <div class="alert alert-warning">
                    <h5>No hay partidos creados</h5>
                    <p>Debe crear los partidos primero. Vuelva a la pantalla anterior y haga clic en "Comenzar Torneo".</p>
                </div>
            @else
            
            @php
                // Obtener todos los grupos para identificar el orden de las parejas en los partidos
                $gruposExistentes = collect(DB::table('grupos')
                    ->where('torneo_id', $torneo->id)
                    ->orderBy('partido_id')
                    ->orderBy('id')
                    ->get());
            @endphp
            
            @foreach($partidosPorZona as $zona => $partidos)
                <div class="zona-container" data-zona="{{ $zona }}" style="{{ $zonaIndex > 0 ? 'display:none;' : '' }}">
                    <div class="row tabla-container">
                        <!-- Columna izquierda: Tabla de partidos (más pequeña) -->
                        <div class="col-md-8 tabla-partidos-wrapper">
                            <div class="card shadow bg-white p-4">
                                <h4 class="mb-3">Grupo {{ $zona }}</h4>
                                
                                @if(count($partidos) == 0)
                                    <div class="alert alert-info">
                                        No hay partidos configurados para este grupo. Debe haber al menos 2 parejas.
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width:8%;" class="text-center">Partido</th>
                                            <th style="width:30%;">Pareja 1</th>
                                            <th style="width:15%;" class="text-center">Set 1</th>
                                            <th style="width:15%;" class="text-center">Set 1</th>
                                            <th style="width:30%;">Pareja 2</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($partidos as $partido)
                                            @php
                                                // Convertir jugadores a array y keyBy
                                                $jugadoresArray = is_array($jugadores) ? $jugadores : (is_object($jugadores) ? $jugadores->toArray() : []);
                                                $jugadoresKeyed = collect($jugadoresArray)->keyBy('id');
                                                
                                                $jugador1_1 = $jugadoresKeyed[$partido['pareja_1']['jugador_1']] ?? null;
                                                $jugador1_2 = $jugadoresKeyed[$partido['pareja_1']['jugador_2']] ?? null;
                                                $jugador2_1 = $jugadoresKeyed[$partido['pareja_2']['jugador_1']] ?? null;
                                                $jugador2_2 = $jugadoresKeyed[$partido['pareja_2']['jugador_2']] ?? null;
                                                
                                                // Obtener resultado del partido
                                                $partidoIdKey = isset($partido['partido_id']) ? $partido['partido_id'] : null;
                                                $resultado = null;
                                                if ($partidoIdKey && isset($partidosConResultados[$partidoIdKey])) {
                                                    $resultado = $partidosConResultados[$partidoIdKey];
                                                }
                                                
                                                
                                                // Determinar qué pareja corresponde a pareja_1 y pareja_2 en el partido
                                                // Buscar los grupos asociados a este partido para identificar el orden
                                                $gruposPartido = $gruposExistentes->where('partido_id', $partidoIdKey)->sortBy('id')->values();
                                                $valorPareja1 = 0;
                                                $valorPareja2 = 0;
                                                
                                                if ($resultado && $gruposPartido->count() >= 2) {
                                                    $grupo1 = $gruposPartido[0];
                                                    $grupo2 = $gruposPartido[1];
                                                    
                                                    // Verificar si la pareja_1 del partido coincide con el primer grupo
                                                    if ($grupo1->jugador_1 == $partido['pareja_1']['jugador_1'] && 
                                                        $grupo1->jugador_2 == $partido['pareja_1']['jugador_2']) {
                                                        // Pareja 1 es la primera en el partido
                                                        $valorPareja1 = $resultado->pareja_1_set_1 ?? 0;
                                                        $valorPareja2 = $resultado->pareja_2_set_1 ?? 0;
                                                    } else {
                                                        // Pareja 1 es la segunda en el partido (están invertidas)
                                                        $valorPareja1 = $resultado->pareja_2_set_1 ?? 0;
                                                        $valorPareja2 = $resultado->pareja_1_set_1 ?? 0;
                                                    }
                                                } else if ($resultado) {
                                                    // Si no hay grupos, usar el orden por defecto
                                                    $valorPareja1 = $resultado->pareja_1_set_1 ?? 0;
                                                    $valorPareja2 = $resultado->pareja_2_set_1 ?? 0;
                                                }
                                            @endphp
                                            <tr data-partido-id="{{ $partido['partido_id'] ?? '' }}" 
                                                data-torneo-id="{{ $torneo->id }}"
                                                data-zona="{{ $zona }}"
                                                data-pareja-1-jugador-1="{{ $partido['pareja_1']['jugador_1'] }}"
                                                data-pareja-1-jugador-2="{{ $partido['pareja_1']['jugador_2'] }}"
                                                data-pareja-2-jugador-1="{{ $partido['pareja_2']['jugador_1'] }}"
                                                data-pareja-2-jugador-2="{{ $partido['pareja_2']['jugador_2'] }}">
                                                <td class="text-center">
                                                    <strong style="font-size:1.1rem;">
                                                        Partido {{ $partido['numero_partido'] ?? $loop->iteration }} 
                                                        @if(isset($partido['partido_id']) && $partido['partido_id'] !== null && $partido['partido_id'] !== '' && $partido['partido_id'] !== 0)
                                                            ({{ $partido['partido_id'] }})
                                                        @endif
                                                    </strong>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($jugador1_1)
                                                            <div class="d-flex align-items-center mr-2">
                                                                <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" 
                                                                     class="rounded-circle" 
                                                                     style="width:40px; height:40px; object-fit:cover; border:2px solid #ddd;">
                                                                @if($jugador1_2)
                                                                    <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" 
                                                                         class="rounded-circle ml-1" 
                                                                         style="width:40px; height:40px; object-fit:cover; border:2px solid #ddd; margin-left:-10px;">
                                                                @endif
                                                            </div>
                                                            <div>
                                                                <div style="font-size:0.9rem;">{{ $jugador1_1->nombre }} {{ $jugador1_1->apellido }}</div>
                                                                @if($jugador1_2)
                                                                    <div style="font-size:0.9rem;">{{ $jugador1_2->nombre }} {{ $jugador1_2->apellido }}</div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <input type="number" 
                                                           class="form-control text-center resultado-set" 
                                                           data-partido-id="{{ $partido['partido_id'] }}"
                                                           data-pareja="1"
                                                           data-set="1"
                                                           value="{{ $valorPareja1 }}"
                                                           min="0"
                                                           max="99">
                                                </td>
                                                <td class="text-center">
                                                    <input type="number" 
                                                           class="form-control text-center resultado-set" 
                                                           data-partido-id="{{ $partido['partido_id'] }}"
                                                           data-pareja="2"
                                                           data-set="1"
                                                           value="{{ $valorPareja2 }}"
                                                           min="0"
                                                           max="99">
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($jugador2_1)
                                                            <div class="d-flex align-items-center mr-2">
                                                                <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" 
                                                                     class="rounded-circle" 
                                                                     style="width:40px; height:40px; object-fit:cover; border:2px solid #ddd;">
                                                                @if($jugador2_2)
                                                                    <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" 
                                                                         class="rounded-circle ml-1" 
                                                                         style="width:40px; height:40px; object-fit:cover; border:2px solid #ddd; margin-left:-10px;">
                                                                @endif
                                                            </div>
                                                            <div>
                                                                <div style="font-size:0.9rem;">{{ $jugador2_1->nombre }} {{ $jugador2_1->apellido }}</div>
                                                                @if($jugador2_2)
                                                                    <div style="font-size:0.9rem;">{{ $jugador2_2->nombre }} {{ $jugador2_2->apellido }}</div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                            </div>
                        </div>
                        
                        <!-- Columna derecha: Tabla de posiciones (pequeña, siempre visible) -->
                        <div class="col-md-4 tabla-posiciones-wrapper">
                            <div class="card shadow bg-white p-3">
                                <h5 class="mb-3 text-center">Posiciones - Grupo {{ $zona }}</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" style="font-size:0.85rem;">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width:15%;" class="text-center">Pos</th>
                                                <th style="width:45%;">Pareja</th>
                                                <th style="width:15%;" class="text-center">PG</th>
                                                <th style="width:25%;" class="text-center">Dif Games</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody-posiciones-{{ $zona }}">
                                            <tr>
                                                <td colspan="4" class="text-center text-muted" style="font-size:0.8rem;">
                                                    Ingrese resultados para ver posiciones
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @php $zonaIndex++; @endphp
            @endforeach
            @endif
        </div>
    </div>
</div>

<script type="text/javascript">
    let zonas = @json($zonas ?? []);
    let zonaIndex = 0;
    let partidosPorZona = @json($partidosPorZona ?? []);
    let partidosConResultados = @json($partidosConResultados ?? []);
    
    // Inicializar zona si hay zonas disponibles
    $(document).ready(function() {
        if (zonas.length > 0) {
            actualizarZona();
        } else {
            $('#zona-actual').text('No hay zonas disponibles');
            $('#btn-zona-anterior').prop('disabled', true);
            $('#btn-zona-siguiente').prop('disabled', true);
        }
    });
    
    function actualizarZona() {
        if (zonas.length === 0 || !zonas[zonaIndex]) {
            return;
        }
        
        $('.zona-container').hide();
        $('.zona-container[data-zona="' + zonas[zonaIndex] + '"]').show();
        $('#zona-actual').text('Zona ' + zonas[zonaIndex]);
        
        // Deshabilitar botones si es necesario
        $('#btn-zona-anterior').prop('disabled', zonaIndex === 0);
        $('#btn-zona-siguiente').prop('disabled', zonaIndex === zonas.length - 1);
        
        // Actualizar posiciones de la zona actual
        actualizarPosiciones();
        
        // Verificar si todos los partidos están completos para mostrar botón Cruces
        setTimeout(function() {
            verificarTodosPartidosCompletos();
        }, 100);
    }
    
    $('#btn-zona-anterior').on('click', function() {
        if (zonaIndex > 0) {
            zonaIndex--;
            actualizarZona();
        }
    });
    
    $('#btn-zona-siguiente').on('click', function() {
        if (zonaIndex < zonas.length - 1) {
            zonaIndex++;
            actualizarZona();
        }
    });
    
    // Función para guardar resultado (reutilizable)
    function guardarResultado(input) {
        let fila = input.closest('tr');
        // Obtener partido_id de forma más robusta
        let partidoId = fila.attr('data-partido-id') || fila.data('partido-id') || null;
        // Si es string vacío o 'null', convertirlo a null
        if (partidoId === '' || partidoId === 'null' || partidoId === null || partidoId === undefined) {
            partidoId = null;
        }
        let torneoId = fila.data('torneo-id') || fila.attr('data-torneo-id');
        let zona = fila.data('zona') || fila.attr('data-zona');
        let pareja1Jugador1 = fila.data('pareja-1-jugador-1') || fila.attr('data-pareja-1-jugador-1');
        let pareja1Jugador2 = fila.data('pareja-1-jugador-2') || fila.attr('data-pareja-1-jugador-2');
        let pareja2Jugador1 = fila.data('pareja-2-jugador-1') || fila.attr('data-pareja-2-jugador-1');
        let pareja2Jugador2 = fila.data('pareja-2-jugador-2') || fila.attr('data-pareja-2-jugador-2');
        let pareja1Set1 = fila.find('input[data-pareja="1"][data-set="1"]').val();
        let pareja2Set1 = fila.find('input[data-pareja="2"][data-set="1"]').val();
        
        // Guardar siempre que haya un valor o un partido existente
        if ((pareja1Set1 > 0 || pareja2Set1 > 0) || partidoId) {
            $.ajax({
                type: 'POST',
                dataType: 'JSON',
                url: '{{ route("guardarresultadoamericano") }}',
                data: {
                    partido_id: partidoId || null,
                    torneo_id: torneoId,
                    zona: zona,
                    pareja_1_jugador_1: pareja1Jugador1,
                    pareja_1_jugador_2: pareja1Jugador2,
                    pareja_2_jugador_1: pareja2Jugador1,
                    pareja_2_jugador_2: pareja2Jugador2,
                    pareja_1_set_1: pareja1Set1 || 0,
                    pareja_2_set_1: pareja2Set1 || 0,
                    _token: '{{csrf_token()}}'
                },
                success: function(response) {
                    if (response.success) {
                        // Actualizar el partido_id en la fila siempre (por si se encontró un partido existente)
                        if (response.partido_id) {
                            fila.attr('data-partido-id', response.partido_id);
                        }
                        
                        // Feedback visual sutil
                        input.css('background-color', '#d4edda');
                        setTimeout(function() {
                            input.css('background-color', '');
                        }, 500);
                        
                        // Actualizar el resultado en partidosConResultados
                        if (response.partido && response.partido_id) {
                            if (!partidosConResultados[response.partido_id]) {
                                partidosConResultados[response.partido_id] = {};
                            }
                            partidosConResultados[response.partido_id].pareja_1_set_1 = response.partido.pareja_1_set_1 || 0;
                            partidosConResultados[response.partido_id].pareja_2_set_1 = response.partido.pareja_2_set_1 || 0;
                        }
                        
                        // Actualizar posiciones automáticamente después de guardar
                        actualizarPosiciones();
                        
                        // Verificar si todos los partidos están completos para mostrar botón Cruces
                        setTimeout(function() {
                            verificarTodosPartidosCompletos();
                        }, 100);
                    } else {
                        alert('Error al guardar: ' + (response.message || 'Error desconocido'));
                    }
                },
                error: function() {
                    alert('Error al guardar el resultado');
                }
            });
        }
    }
    
    // Función para actualizar posiciones automáticamente
    function actualizarPosiciones() {
        let zonaActual = zonas[zonaIndex];
        let torneoId = $('#torneo_id').val();
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("calcularposicionesamericano") }}',
            data: {
                torneo_id: torneoId,
                zona: zonaActual,
                _token: '{{csrf_token()}}'
            },
            success: function(response) {
                if (response.success) {
                    mostrarPosiciones(response.posiciones, zonaActual);
                }
            },
            error: function() {
                // Silencioso, no mostrar error si falla
            }
        });
    }
    
    // Guardar resultado automáticamente mientras el usuario escribe (con debounce)
    let timeouts = {};
    $(document).on('input', '.resultado-set', function() {
        let input = $(this);
        let inputId = input.closest('tr').data('partido-id') || input.closest('tr').index();
        
        // Cancelar timeout anterior si existe
        if (timeouts[inputId]) {
            clearTimeout(timeouts[inputId]);
        }
        
        // Guardar después de 800ms de inactividad (debounce)
        timeouts[inputId] = setTimeout(function() {
            guardarResultado(input);
            delete timeouts[inputId];
        }, 800);
    });
    
    // También guardar al perder el foco (por si acaso)
    $(document).on('blur', '.resultado-set', function() {
        let input = $(this);
        let inputId = input.closest('tr').data('partido-id') || input.closest('tr').index();
        
        // Cancelar timeout si existe
        if (timeouts[inputId]) {
            clearTimeout(timeouts[inputId]);
            delete timeouts[inputId];
        }
        
        // Guardar inmediatamente al perder el foco
        guardarResultado(input);
    });
    
    // Función para verificar si todos los partidos están completos
    function verificarTodosPartidosCompletos() {
        let todasZonasCompletas = true;
        
        // Verificar cada zona
        for (let zona in partidosPorZona) {
            let partidos = partidosPorZona[zona];
            let partidosCompletos = 0;
            
            // Contar partidos con resultados
            for (let i = 0; i < partidos.length; i++) {
                let partido = partidos[i];
                let partidoId = partido.partido_id;
                
                // Verificar si el partido tiene resultado guardado
                if (partidoId && partidosConResultados[partidoId]) {
                    let resultado = partidosConResultados[partidoId];
                    if ((resultado.pareja_1_set_1 > 0 || resultado.pareja_2_set_1 > 0)) {
                        partidosCompletos++;
                    }
                } else {
                    // Verificar en el DOM si tiene valor ingresado
                    let fila = $('tr[data-partido-id="' + partidoId + '"]');
                    if (fila.length > 0) {
                        let valor1 = fila.find('input[data-pareja="1"][data-set="1"]').val();
                        let valor2 = fila.find('input[data-pareja="2"][data-set="1"]').val();
                        if ((valor1 && parseInt(valor1) > 0) || (valor2 && parseInt(valor2) > 0)) {
                            partidosCompletos++;
                        }
                    }
                }
            }
            
            // Si no todos los partidos de esta zona están completos, no mostrar el botón
            if (partidosCompletos < partidos.length) {
                todasZonasCompletas = false;
                break;
            }
        }
        
        // Mostrar u ocultar el botón Cruces
        if (todasZonasCompletas) {
            $('#btn-cruces').show();
        } else {
            $('#btn-cruces').hide();
        }
    }
    
    function mostrarPosiciones(posiciones, zona) {
        let tbody = $('#tbody-posiciones-' + zona);
        tbody.empty();
        
        if (posiciones.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="4" class="text-center text-muted" style="font-size:0.8rem;">
                        Ingrese resultados para ver posiciones
                    </td>
                </tr>
            `);
            return;
        }
        
        posiciones.forEach(function(pareja, index) {
            let jugador1 = obtenerJugadorPorId(pareja.jugador_1);
            let jugador2 = obtenerJugadorPorId(pareja.jugador_2);
            
            // Calcular diferencia de games (ganados - perdidos)
            const diferenciaGames = (pareja.puntos_ganados || 0) - (pareja.puntos_perdidos || 0);
            const diferenciaTexto = diferenciaGames >= 0 ? `+${diferenciaGames}` : `${diferenciaGames}`;
            const diferenciaClass = diferenciaGames >= 0 ? 'text-success' : 'text-danger';
            
            let fila = `
                <tr>
                    <td class="text-center"><strong style="font-size:0.9rem;">${index + 1}º</strong></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="d-flex align-items-center mr-1">
                                <img src="${jugador1 ? (jugador1.foto || '/images/jugador_img.png') : '/images/jugador_img.png'}" 
                                     class="rounded-circle" 
                                     style="width:30px; height:30px; object-fit:cover; border:2px solid #ddd;">
                                ${jugador2 ? '<img src="' + (jugador2.foto || '/images/jugador_img.png') + '" class="rounded-circle ml-1" style="width:30px; height:30px; object-fit:cover; border:2px solid #ddd; margin-left:-8px;">' : ''}
                            </div>
                            <div style="font-size:0.75rem; line-height:1.2;">
                                <div>${jugador1 ? (jugador1.nombre.split(' ')[0] + ' ' + jugador1.apellido.split(' ')[0]) : 'N/A'}</div>
                                ${jugador2 ? '<div>' + jugador2.nombre.split(' ')[0] + ' ' + jugador2.apellido.split(' ')[0] + '</div>' : ''}
                            </div>
                        </div>
                    </td>
                    <td class="text-center"><strong style="font-size:0.9rem;">${pareja.partidos_ganados}</strong></td>
                    <td class="text-center"><strong style="font-size:0.9rem;" class="${diferenciaClass}">${diferenciaTexto}</strong></td>
                </tr>
            `;
            tbody.append(fila);
        });
    }
    
    // Cache de jugadores convertido a array
    let jugadoresArray = [];
    function obtenerJugadorPorId(id) {
        if (jugadoresArray.length === 0) {
            let jugadores = @json($jugadores ?? []);
            // Convertir a array si es un objeto
            if (!Array.isArray(jugadores)) {
                jugadoresArray = Object.values(jugadores);
            } else {
                jugadoresArray = jugadores;
            }
        }
        return jugadoresArray.find(j => j.id == id);
    }
    
    // Botón cruces
    $('#btn-cruces').on('click', function() {
        let torneoId = $('#torneo_id').val();
        window.location.href = '{{ route("admintorneovalidarcruces") }}?torneo_id=' + torneoId;
    });
    
    // Inicializar
    $(document).ready(function() {
        actualizarZona();
        // Verificar al cargar la página si todos los partidos están completos
        setTimeout(function() {
            verificarTodosPartidosCompletos();
        }, 500);
    });
</script>
@endsection

