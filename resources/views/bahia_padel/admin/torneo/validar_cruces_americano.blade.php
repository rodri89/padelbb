@extends('bahia_padel/admin/plantilla')

@section('title_header','Validar Cruces - Torneo Americano')

@section('contenedor')
<link rel="stylesheet" href="{{ asset('css/bracket.css') }}">
<link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}">

<style>
    .posiciones-container-scroll {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 2rem;
        padding-bottom: 0.5rem;
        white-space: nowrap;
    }
    
    .posiciones-container-scroll::-webkit-scrollbar {
        height: 8px;
    }
    
    .posiciones-container-scroll::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .posiciones-container-scroll::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    
    .zona-posiciones {
        display: inline-block;
        vertical-align: top;
        min-width: 280px;
        margin-right: 1.5rem;
        flex-shrink: 0;
    }
    
    .posicion-item {
        padding: 0.5rem;
        margin-bottom: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: #f9f9f9;
    }
    
    .posicion-item.primero {
        background: #d4edda;
        border-color: #28a745;
    }
    
    .posicion-item.segundo {
        background: #d1ecf1;
        border-color: #17a2b8;
    }
    
    .posicion-item.tercero {
        /* Sin estilo por defecto, solo se aplicará amarillo a los mejores */
    }
    
    /* Estilos para las tarjetas de cruces */
    .cruces-cards-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        max-height: 450px;
        overflow-y: auto;
        padding: 5px;
    }
    
    .cruce-card {
        background: #fff;
        border: 2px solid #e3e6f0;
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.2s ease;
    }
    
    .cruce-card:hover {
        border-color: #4e73df;
        box-shadow: 0 2px 8px rgba(78, 115, 223, 0.2);
    }
    
    .cruce-card-header {
        background: linear-gradient(135deg, #4e73df, #224abe);
        color: #fff;
        padding: 6px 10px;
        text-align: center;
    }
    
    .cruce-label {
        font-weight: 700;
        font-size: 1rem;
    }
    
    .cruce-card-body {
        padding: 8px;
    }
    
    .cruce-pareja {
        background: #f8f9fc;
        border: 2px dashed #d1d3e2;
        border-radius: 6px;
        padding: 8px;
        cursor: pointer;
        min-height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .cruce-pareja:hover {
        background: #eaecf4;
        border-color: #4e73df;
    }
    
    .cruce-pareja.filled {
        background: #d4edda;
        border: 2px solid #28a745;
        border-style: solid;
    }
    
    .cruce-pareja .pareja-placeholder {
        color: #858796;
        font-size: 0.75rem;
        font-style: italic;
    }
    
    .cruce-pareja .pareja-info {
        text-align: center;
        font-size: 0.7rem;
        line-height: 1.2;
    }
    
    .cruce-pareja .pareja-info .pareja-badge {
        display: inline-block;
        background: #4e73df;
        color: #fff;
        padding: 1px 6px;
        border-radius: 10px;
        font-size: 0.65rem;
        font-weight: 600;
        margin-bottom: 2px;
    }
    
    .cruce-pareja .pareja-info .pareja-nombres {
        color: #2e2e2e;
        font-weight: 500;
    }
    
    .cruce-vs {
        text-align: center;
        font-weight: 700;
        color: #858796;
        font-size: 0.75rem;
        padding: 3px 0;
    }
</style>

<div class="bracket-container">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button type="button" class="btn btn-secondary" id="btn-volver-resultados">
                        ← Volver a Resultados
                    </button>
                    
                    <h2 class="text-center flex-grow-1 mb-0" style="color: #000;">Validar Cruces - {{ $torneo->nombre ?? 'Torneo' }}</h2>
                    
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-success ml-2" id="btn-confirmar-cruces">
                            <i class="fa fa-check"></i> Confirmar Cruces
                        </button>
                    </div>
                </div>
                <input type="hidden" id="torneo_id" value="{{ $torneo->id ?? 0 }}">
                
                {{-- Selector de configuración de cruces para torneos puntuables --}}
                @if($tipoTorneo === 'puntuable' && isset($configsCrucesPuntuables) && count($configsCrucesPuntuables) > 0)
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="config_cruces_puntuable"><strong>Configuración de Cruces:</strong></label>
                        <select class="form-control" id="config_cruces_puntuable" name="config_cruces_puntuable">
                            <option value="">-- Seleccionar configuración --</option>
                            @foreach($configsCrucesPuntuables as $config)
                                <option value="{{ $config->id }}" 
                                    data-tiene16avos="{{ $config->tiene_16avos_final }}"
                                    @if(($torneo->config_cruces_puntuable_id ?? null) == $config->id) selected @endif>
                                    {{ $config->cantidad_parejas }} parejas {{ $config->tiene_16avos_final ? '(con 16avos)' : '(sin 16avos)' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Selecciona la configuración que define si hay 16avos o no.</small>
                    </div>
                </div>
                @endif
                
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> 
                    @if($necesitaOctavos ?? false)
                        Revisa los cruces de octavos de final generados. Puedes editar cualquier pareja haciendo clic en los jugadores.
                    @else
                        Revisa los cruces de cuartos de final generados. Puedes editar cualquier pareja haciendo clic en los jugadores.
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Sección de Posiciones por Zona y Tabla de Selección -->
        <div class="row mb-4">
            <!-- Posiciones por Zona (izquierda) -->
            <div class="col-lg-8">
                <h3 class="mb-3">Posiciones por Zona</h3>
                <div class="posiciones-container-scroll">
                    @php
                        $jugadoresMap = [];
                        foreach($jugadores as $j) {
                            $jugadoresMap[$j->id] = $j;
                        }
                    @endphp
                    @foreach($posicionesPorZona ?? [] as $zona => $posiciones)
                        <div class="zona-posiciones">
                            <div class="card">
                                <div class="card-header bg-primary text-white text-center">
                                    <h5 class="mb-0">Zona {{ $zona }}</h5>
                                </div>
                                <div class="card-body">
                                    @foreach($posiciones as $index => $posicion)
                                        @php
                                            $jugador1 = $jugadoresMap[$posicion['jugador_1']] ?? null;
                                            $jugador2 = $jugadoresMap[$posicion['jugador_2']] ?? null;
                                            $clasePosicion = '';
                                            $esMejorTercero = false;
                                            
                                            // Verificar si es uno de los dos mejores terceros
                                            if ($index == 2) {
                                                $terceroId = $zona . '_' . $posicion['jugador_1'] . '_' . $posicion['jugador_2'];
                                                $mejoresTercerosIdsArray = $mejoresTercerosIds ?? [];
                                                $esMejorTercero = in_array($terceroId, $mejoresTercerosIdsArray, true); // strict comparison
                                            }
                                            
                                            if ($index == 0) $clasePosicion = 'primero';
                                            else if ($index == 1) $clasePosicion = 'segundo';
                                            else if ($index == 2) $clasePosicion = 'tercero';
                                            
                                            // Calcular diferencia de games
                                            $diferenciaGames = ($posicion['puntos_ganados'] ?? 0) - ($posicion['puntos_perdidos'] ?? 0);
                                            $diferenciaTexto = $diferenciaGames >= 0 ? '+' . $diferenciaGames : (string)$diferenciaGames;
                                            $diferenciaClass = $diferenciaGames >= 0 ? 'text-success' : 'text-danger';
                                        @endphp
                                        <div class="posicion-item {{ $clasePosicion }}" style="{{ $esMejorTercero ? 'background-color: #fff3cd !important; border-color: #ffc107 !important;' : '' }}">
                                            <div class="d-flex align-items-center">
                                                <span class="badge badge-secondary mr-2" style="font-size: 1rem;">{{ $index + 1 }}º</span>
                                                <div class="flex-grow-1" style="color: #000;">
                                                    @if($jugador1)
                                                        <div><strong style="color: #000;">{{ $jugador1->nombre }} {{ $jugador1->apellido }}</strong></div>
                                                    @endif
                                                    @if($jugador2)
                                                        <div><strong style="color: #000;">{{ $jugador2->nombre }} {{ $jugador2->apellido }}</strong></div>
                                                    @endif
                                                </div>
                                                <div class="text-right">
                                                    <small class="text-muted">
                                                        PG: {{ $posicion['partidos_ganados'] }}<br>
                                                        Pts: {{ $posicion['puntos_ganados'] }}<br>
                                                        <strong class="{{ $diferenciaClass }}">Dif: {{ $diferenciaTexto }}</strong>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Tarjetas de cruces precargados (oculta en puntuables) -->
            @if(($tipoTorneo ?? '') !== 'puntuable')
            <div class="col-lg-4">
                <div class="card shadow bg-white px-4 py-3" style="border-radius: 12px; border: 1px solid #e3e6f0;">
                    <h3 class="text-center mb-3" style="color:#4e73df; font-weight:700;">
                        @if($necesitaOctavos ?? false)
                            Octavos de Final
                        @else
                            Cuartos de Final
                        @endif
                    </h3>
                    
                    @if(isset($configuracionAmericano) && $configuracionAmericano)
                    <div class="alert alert-success py-2 px-3 mb-3" style="font-size: 0.85rem;">
                        <i class="fa fa-check-circle"></i> Config: <strong>{{ $configuracionAmericano->nombre ?? 'Sin nombre' }}</strong>
                    </div>
                    @endif
                    
                    <!-- Tarjetas de cruces -->
                    <div id="cruces-cards-container" class="cruces-cards-grid">
                        @php
                            $numCruces = ($necesitaOctavos ?? false) ? 8 : 4;
                            $prefijo = ($necesitaOctavos ?? false) ? 'O' : 'C';
                        @endphp
                        @for($i = 1; $i <= $numCruces; $i++)
                        <div class="cruce-card" data-cruce-num="{{ $i }}">
                            <div class="cruce-card-header">
                                <span class="cruce-label">{{ $prefijo }}{{ $i }}</span>
                            </div>
                            <div class="cruce-card-body">
                                <div class="cruce-pareja cruce-pareja-1" data-num="{{ $i }}" data-pos="1">
                                    <span class="pareja-placeholder">Pareja 1</span>
                                </div>
                                <div class="cruce-vs">vs</div>
                                <div class="cruce-pareja cruce-pareja-2" data-num="{{ $i }}" data-pos="2">
                                    <span class="pareja-placeholder">Pareja 2</span>
                                </div>
                            </div>
                        </div>
                        @endfor
                    </div>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted d-block mb-2">
                            <i class="fa fa-info-circle"></i> Clic en una pareja para cambiarla
                        </small>
                    </div>
                </div>
            </div>
            @endif
        </div>
        
        <div class="row">
            @if(($necesitaOctavos ?? false) || ($tieneCrucesOctavos ?? false))
            <!-- Octavos de Final -->
            <div class="col-12 mb-4">
                <div class="bracket-round">
                    <div class="bracket-round-title">OCTAVOS DE FINAL</div>
                    <div id="cruces-octavos" class="d-flex flex-wrap justify-content-center">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Cuartos de Final (se muestra si hay cruces de cuartos o si no necesita octavos) -->
            <div class="col-12" @if(($necesitaOctavos ?? false) && !($tieneCrucesCuartos ?? false)) style="display: none;" @endif>
                <div class="bracket-round">
                    <div class="bracket-round-title">CUARTOS DE FINAL</div>
                    <div id="cruces-cuartos" class="d-flex flex-wrap justify-content-center">
                        @foreach($cruces as $index => $cruce)
                            @if($cruce['ronda'] == 'cuartos')
                                @php
                                    $jugador1_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                                    $jugador1_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                                    $jugador2_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                                    $jugador2_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                                @endphp
                                <div class="match-card cruce-editable" data-cruce-index="{{ $index }}" data-ronda="cuartos">
                                    <!-- Pareja 1 -->
                                    <div class="player-pair pareja-editable" 
                                         data-pareja="1"
                                         data-cruce-index="{{ $index }}"
                                         style="cursor: pointer;">
                                        <div class="player-pair-content">
                                            <div class="player-images">
                                                <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" 
                                                     alt="{{ $jugador1_1->nombre ?? '' }}"
                                                     style="pointer-events: none;">
                                                <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" 
                                                     alt="{{ $jugador1_2->nombre ?? '' }}"
                                                     style="pointer-events: none;">
                                            </div>
                                            <div class="player-names" style="color: #000;">
                                                <div class="player-name" style="color: #000;">{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}</div>
                                                <div class="player-name" style="color: #000;">{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}</div>
                                            </div>
                                            @if(isset($cruce['pareja_1']['zona']) && isset($cruce['pareja_1']['posicion']))
                                                <span class="badge badge-info">{{ $cruce['pareja_1']['zona'] }}{{ $cruce['pareja_1']['posicion'] }}º</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="text-center my-2">
                                        <span style="font-size: 1.5rem; font-weight: bold;">VS</span>
                                    </div>
                                    
                                    <!-- Pareja 2 -->
                                    <div class="player-pair pareja-editable" 
                                         data-pareja="2"
                                         data-cruce-index="{{ $index }}"
                                         style="cursor: pointer;">
                                        <div class="player-pair-content">
                                            <div class="player-images">
                                                <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" 
                                                     alt="{{ $jugador2_1->nombre ?? '' }}"
                                                     style="pointer-events: none;">
                                                <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" 
                                                     alt="{{ $jugador2_2->nombre ?? '' }}"
                                                     style="pointer-events: none;">
                                            </div>
                                            <div class="player-names" style="color: #000;">
                                                <div class="player-name" style="color: #000;">{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}</div>
                                                <div class="player-name" style="color: #000;">{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}</div>
                                            </div>
                                            @if(isset($cruce['pareja_2']['zona']) && isset($cruce['pareja_2']['posicion']))
                                                <span class="badge badge-info">{{ $cruce['pareja_2']['zona'] }}{{ $cruce['pareja_2']['posicion'] }}º</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Seleccionar Pareja -->
<div class="modal fade body_admin" id="modalSeleccionarPareja" tabindex="-1" role="dialog" aria-labelledby="modalSeleccionarParejaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSeleccionarParejaLabel">Seleccionar Pareja</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Buscador -->
        <input type="text" class="form-control mb-3" id="buscador-pareja" placeholder="Buscar pareja por nombre o zona...">
        
        <div class="list-group" id="lista-parejas" style="max-height: 500px; overflow-y: auto;">
          <!-- Las parejas se cargarán dinámicamente -->
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    var cruces = @json($cruces);
    var necesitaOctavos = {{ ($necesitaOctavos ?? false) ? 'true' : 'false' }};
    
    // Asegurar que los cruces existentes tengan la ronda correcta
    if (necesitaOctavos) {
        cruces.forEach(function(cruce, index) {
            if (!cruce.ronda || cruce.ronda === 'cuartos') {
                if (cruces.length > 4 && index < 8) {
                    cruce.ronda = 'octavos';
                }
            }
        });
    }
    
    var jugadores = @json($jugadores);
    var posicionesPorZona = @json($posicionesPorZona);
    var torneoId = $('#torneo_id').val();
    var tarjetaSeleccionada = null; // { num: número de cruce, pos: 1 o 2 }
    
    // Preparar datos de posiciones para JavaScript (formato: posiciones[zona][posicion])
    var posicionesJS = {};
    if (posicionesPorZona) {
        Object.keys(posicionesPorZona).forEach(function(zona) {
            posicionesJS[zona] = {};
            if (posicionesPorZona[zona] && Array.isArray(posicionesPorZona[zona])) {
                posicionesPorZona[zona].forEach(function(pareja, index) {
                    posicionesJS[zona][index + 1] = {
                        jugador_1: pareja.jugador_1,
                        jugador_2: pareja.jugador_2,
                        zona: zona,
                        posicion: index + 1
                    };
                });
            }
        });
    }
    
    // Crear mapa de jugadores
    var jugadoresMap = {};
    if (jugadores && Array.isArray(jugadores)) {
        jugadores.forEach(function(j) {
            jugadoresMap[j.id] = j;
        });
    }
    
    // Array para guardar los cruces de las tarjetas
    var crucesCards = [];
    var numCruces = necesitaOctavos ? 8 : 4;
    for (var i = 0; i < numCruces; i++) {
        crucesCards.push({
            pareja_1: null,
            pareja_2: null
        });
    }
    
    // === CONFIGURACIÓN Y MAPEO DE ZONAS ===
    var llavesPreconfiguradas = @json($llavesPreconfiguradas ?? []);
    var configuracionAmericano = @json($configuracionAmericano ?? null);
    
    // Mapear las zonas del torneo a letras (A, B, C, D...)
    // Las zonas vienen ordenadas, asignarlas a letras correspondientes
    var zonasOrdenadas = Object.keys(posicionesJS).sort(function(a, b) {
        // Ordenar numéricamente si son números, sino alfabéticamente
        var numA = parseInt(a);
        var numB = parseInt(b);
        if (!isNaN(numA) && !isNaN(numB)) {
            return numA - numB;
        }
        return a.localeCompare(b);
    });
    
    var letrasZonas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
    var letraAZona = {};
    var zonaALetra = {};
    
    console.log('Zonas ordenadas:', zonasOrdenadas);
    
    zonasOrdenadas.forEach(function(zona, index) {
        if (index < letrasZonas.length) {
            var letra = letrasZonas[index];
            letraAZona[letra] = zona;
            zonaALetra[zona] = letra;
        }
    });
    
    console.log('Mapeo letraAZona:', letraAZona);
    console.log('Mapeo zonaALetra:', zonaALetra);
    console.log('Llaves preconfiguradas recibidas:', llavesPreconfiguradas);
    
    // Función para obtener pareja desde referencia (ej: "A1")
    function obtenerParejaDesdeReferencia(referencia) {
        if (!referencia) {
            console.log('Referencia vacía');
            return null;
        }
        
        console.log('Procesando referencia:', referencia);
        
        var match = referencia.match(/^([A-P])(\d+)$/);
        if (match) {
            var letra = match[1];
            var posicion = parseInt(match[2]);
            var zona = letraAZona[letra];
            
            console.log('Letra:', letra, '- Posición:', posicion, '- Zona encontrada:', zona);
            
            if (zona && posicionesJS[zona]) {
                console.log('Posiciones en zona', zona, ':', Object.keys(posicionesJS[zona]));
                
                if (posicionesJS[zona][posicion]) {
                    var pareja = posicionesJS[zona][posicion];
                    console.log('Pareja encontrada:', pareja);
                    return {
                        jugador_1: pareja.jugador_1,
                        jugador_2: pareja.jugador_2,
                        zona: zona,
                        posicion: posicion,
                        referencia: referencia
                    };
                } else {
                    console.log('Posición', posicion, 'no encontrada en zona', zona);
                }
            } else {
                console.log('Zona no encontrada para letra', letra);
            }
        } else {
            console.log('Referencia no coincide con patrón A1-P9:', referencia);
        }
        return null;
    }
    
    // Función para actualizar la visualización de una tarjeta
    function actualizarTarjetaVisual(num, pos, datosPare) {
        var selector = '.cruce-pareja[data-num="' + num + '"][data-pos="' + pos + '"]';
        var $tarjeta = $(selector);
        
        console.log('Actualizando tarjeta num=' + num + ', pos=' + pos, 'Selector:', selector, 'Elemento encontrado:', $tarjeta.length > 0);
        
        if (!datosPare) {
            console.log('datosPare es null para tarjeta', num, pos);
            $tarjeta.removeClass('filled');
            $tarjeta.html('<span class="pareja-placeholder">Pareja ' + pos + '</span>');
            return;
        }
        
        var jugador1 = jugadoresMap[datosPare.jugador_1];
        var jugador2 = jugadoresMap[datosPare.jugador_2];
        
        console.log('Jugador1:', jugador1, 'Jugador2:', jugador2);
        
        if (!jugador1 || !jugador2) {
            console.log('Jugadores no encontrados para tarjeta', num, pos);
            $tarjeta.removeClass('filled');
            $tarjeta.html('<span class="pareja-placeholder">Pareja ' + pos + '</span>');
            return;
        }
        
        var letraZona = zonaALetra[datosPare.zona] || datosPare.zona;
        var badge = letraZona + datosPare.posicion;
        var nombre1 = (jugador1.nombre || '').split(' ')[0]; // Solo primer nombre
        var apellido1 = jugador1.apellido || '';
        var nombre2 = (jugador2.nombre || '').split(' ')[0];
        var apellido2 = jugador2.apellido || '';
        
        var html = '<div class="pareja-info">' +
            '<span class="pareja-badge">' + badge + '</span>' +
            '<div class="pareja-nombres">' + nombre1 + ' ' + apellido1 + '</div>' +
            '<div class="pareja-nombres">' + nombre2 + ' ' + apellido2 + '</div>' +
            '</div>';
        
        console.log('HTML generado para tarjeta', num, pos, ':', html);
        $tarjeta.addClass('filled').html(html);
    }
    
    // === AUTO-CARGAR CRUCES DESDE CONFIGURACIÓN ===
    console.log('=== CONFIGURACIÓN COMPLETA ===');
    console.log('configuracionAmericano:', configuracionAmericano);
    console.log('llavesPreconfiguradas:', JSON.stringify(llavesPreconfiguradas, null, 2));
    
    if (llavesPreconfiguradas && llavesPreconfiguradas.llaves && llavesPreconfiguradas.llaves.length > 0) {
        console.log('Auto-cargando cruces desde configuración:', llavesPreconfiguradas);
        console.log('Llaves a procesar:');
        llavesPreconfiguradas.llaves.forEach(function(llave, idx) {
            console.log('  Llave ' + (idx+1) + ': pareja_1=' + llave.pareja_1 + ', pareja_2=' + llave.pareja_2);
        });
        
        llavesPreconfiguradas.llaves.forEach(function(llave, index) {
            if (index >= numCruces) return;
            
            var pareja1 = obtenerParejaDesdeReferencia(llave.pareja_1);
            var pareja2 = obtenerParejaDesdeReferencia(llave.pareja_2);
            
            crucesCards[index] = {
                pareja_1: pareja1,
                pareja_2: pareja2
            };
            
            // Actualizar visual
            actualizarTarjetaVisual(index + 1, 1, pareja1);
            actualizarTarjetaVisual(index + 1, 2, pareja2);
        });
        
        // También generar los cruces grandes abajo
        generarCrucesDesdeCards();
    }
    
    // === CLICK EN TARJETAS PARA EDITAR ===
    $(document).on('click', '.cruce-pareja', function(e) {
        e.stopPropagation();
        var num = $(this).data('num');
        var pos = $(this).data('pos');
        
        tarjetaSeleccionada = { num: num, pos: pos };
        
        // Mostrar modal
        $('#modalSeleccionarParejaLabel').text('Seleccionar Pareja ' + pos + ' para Cruce ' + num);
        $('#modalSeleccionarPareja').modal('show');
    });
    
    // Función para generar cruces desde las tarjetas
    function generarCrucesDesdeCards() {
        var crucesTemp = [];
        var ronda = necesitaOctavos ? 'octavos' : 'cuartos';
        
        crucesCards.forEach(function(cruce, index) {
            if (cruce.pareja_1 && cruce.pareja_2) {
                crucesTemp.push({
                    id: 'cruce_card_' + (index + 1),
                    ronda: ronda,
                    pareja_1: cruce.pareja_1,
                    pareja_2: cruce.pareja_2
                });
            }
        });
        
        // Actualizar el array global y renderizar
        cruces = crucesTemp;
        renderizarCrucesEnCuartos(crucesTemp, necesitaOctavos);
        
        return crucesTemp;
    }
    
    // Función para renderizar cruces en el contenedor de octavos o cuartos
    function renderizarCrucesEnCuartos(crucesData, esOctavos) {
        esOctavos = esOctavos || false;
        var container = esOctavos ? $('#cruces-octavos') : $('#cruces-cuartos');
        var ronda = esOctavos ? 'octavos' : 'cuartos';
        container.empty();
        
        if (!crucesData || crucesData.length === 0) {
            container.html('<p class="text-center text-muted">No hay cruces para mostrar. Selecciona parejas en las tarjetas de arriba.</p>');
            return;
        }
        
        crucesData.forEach(function(cruce, index) {
            var jugador1_1 = jugadoresMap[cruce.pareja_1.jugador_1] || null;
            var jugador1_2 = jugadoresMap[cruce.pareja_1.jugador_2] || null;
            var jugador2_1 = jugadoresMap[cruce.pareja_2.jugador_1] || null;
            var jugador2_2 = jugadoresMap[cruce.pareja_2.jugador_2] || null;
            
            var letraZona1 = zonaALetra[cruce.pareja_1.zona] || cruce.pareja_1.zona;
            var letraZona2 = zonaALetra[cruce.pareja_2.zona] || cruce.pareja_2.zona;
            
            var cruceHTML = `
                <div class="match-card cruce-editable" data-cruce-index="${index}" data-ronda="${ronda}">
                    <!-- Pareja 1 -->
                    <div class="player-pair pareja-editable-grande" 
                         data-pareja="1"
                         data-cruce-index="${index}"
                         style="cursor: pointer;">
                        <div class="player-pair-content">
                            <div class="player-images">
                                ${jugador1_1 ? '<img src="{{ asset("") }}' + (jugador1_1.foto || 'images/jugador_img.png') + '" alt="' + jugador1_1.nombre + ' ' + jugador1_1.apellido + '" style="pointer-events: none;">' : ''}
                                ${jugador1_2 ? '<img src="{{ asset("") }}' + (jugador1_2.foto || 'images/jugador_img.png') + '" alt="' + jugador1_2.nombre + ' ' + jugador1_2.apellido + '" style="pointer-events: none;">' : ''}
                            </div>
                            <div class="player-names" style="color: #000;">
                                ${jugador1_1 ? '<div class="player-name" style="color: #000;">' + jugador1_1.nombre + ' ' + jugador1_1.apellido + '</div>' : ''}
                                ${jugador1_2 ? '<div class="player-name" style="color: #000;">' + jugador1_2.nombre + ' ' + jugador1_2.apellido + '</div>' : ''}
                            </div>
                            ${cruce.pareja_1 && cruce.pareja_1.zona && cruce.pareja_1.posicion ? '<span class="badge badge-info">' + letraZona1 + cruce.pareja_1.posicion + 'º</span>' : ''}
                        </div>
                    </div>
                    
                    <div class="text-center my-2">
                        <span style="font-size: 1.5rem; font-weight: bold;">VS</span>
                    </div>
                    
                    <!-- Pareja 2 -->
                    <div class="player-pair pareja-editable-grande" 
                         data-pareja="2"
                         data-cruce-index="${index}"
                         style="cursor: pointer;">
                        <div class="player-pair-content">
                            <div class="player-images">
                                ${jugador2_1 ? '<img src="{{ asset("") }}' + (jugador2_1.foto || 'images/jugador_img.png') + '" alt="' + jugador2_1.nombre + ' ' + jugador2_1.apellido + '" style="pointer-events: none;">' : ''}
                                ${jugador2_2 ? '<img src="{{ asset("") }}' + (jugador2_2.foto || 'images/jugador_img.png') + '" alt="' + jugador2_2.nombre + ' ' + jugador2_2.apellido + '" style="pointer-events: none;">' : ''}
                            </div>
                            <div class="player-names" style="color: #000;">
                                ${jugador2_1 ? '<div class="player-name" style="color: #000;">' + jugador2_1.nombre + ' ' + jugador2_1.apellido + '</div>' : ''}
                                ${jugador2_2 ? '<div class="player-name" style="color: #000;">' + jugador2_2.nombre + ' ' + jugador2_2.apellido + '</div>' : ''}
                            </div>
                            ${cruce.pareja_2 && cruce.pareja_2.zona && cruce.pareja_2.posicion ? '<span class="badge badge-info">' + letraZona2 + cruce.pareja_2.posicion + 'º</span>' : ''}
                        </div>
                    </div>
                </div>
            `;
            
            container.append(cruceHTML);
        });
        
        // Mostrar/ocultar secciones según corresponda
        if (esOctavos) {
            $('#cruces-octavos').closest('.col-12').show();
        } else {
            $('#cruces-cuartos').closest('.col-12').show();
        }
    }
    
    // También permitir editar haciendo clic en los cruces grandes de abajo
    $(document).on('click', '.pareja-editable-grande', function(e) {
        e.stopPropagation();
        var pareja = $(this).data('pareja');
        var cruceIndex = $(this).data('cruce-index');
        
        // Traducir a tarjeta: cruceIndex es el número de cruce (0-based)
        tarjetaSeleccionada = { num: cruceIndex + 1, pos: pareja };
        
        $('#modalSeleccionarParejaLabel').text('Seleccionar Pareja ' + pareja + ' para Cruce ' + (cruceIndex + 1));
        $('#modalSeleccionarPareja').modal('show');
    });
    
    // Construir lista de todas las parejas disponibles
    function construirListaParejas() {
        var todasLasParejas = [];
        
        // Recorrer todas las zonas y posiciones
        Object.keys(posicionesPorZona).forEach(function(zona) {
            posicionesPorZona[zona].forEach(function(posicion, index) {
                var jugador1 = jugadoresMap[posicion.jugador_1];
                var jugador2 = jugadoresMap[posicion.jugador_2];
                
                if (jugador1 && jugador2) {
                    todasLasParejas.push({
                        zona: zona,
                        posicion: index + 1,
                        jugador_1: posicion.jugador_1,
                        jugador_2: posicion.jugador_2,
                        jugador1_nombre: jugador1.nombre + ' ' + jugador1.apellido,
                        jugador2_nombre: jugador2.nombre + ' ' + jugador2.apellido,
                        jugador1_foto: jugador1.foto || 'images/jugador_img.png',
                        jugador2_foto: jugador2.foto || 'images/jugador_img.png',
                        partidos_ganados: posicion.partidos_ganados || 0,
                        puntos_ganados: posicion.puntos_ganados || 0
                    });
                }
            });
        });
        
        return todasLasParejas;
    }
    
    var todasLasParejas = construirListaParejas();
    
    // Función para renderizar la lista de parejas
    function renderizarListaParejas(parejas) {
        var lista = $('#lista-parejas');
        lista.empty();
        
        if (parejas.length === 0) {
            lista.html('<div class="list-group-item text-center text-muted">No se encontraron parejas</div>');
            return;
        }
        
        parejas.forEach(function(pareja) {
            var item = $('<button>')
                .attr('type', 'button')
                .addClass('list-group-item list-group-item-action pareja-option')
                .css({
                    'display': 'flex',
                    'align-items': 'center',
                    'padding': '1rem',
                    'border': '1px solid #ddd',
                    'margin-bottom': '0.5rem',
                    'border-radius': '5px',
                    'cursor': 'pointer'
                })
                .data('pareja', pareja);
            
            var contenido = $('<div>').css({
                'display': 'flex',
                'align-items': 'center',
                'width': '100%'
            });
            
            // Imágenes de los jugadores
            var imagenes = $('<div>').css({
                'display': 'flex',
                'margin-right': '1rem'
            });
            
            var img1Src = pareja.jugador1_foto && pareja.jugador1_foto !== 'images/jugador_img.png' 
                ? '{{ asset("") }}' + pareja.jugador1_foto 
                : '{{ asset("images/jugador_img.png") }}';
            var img2Src = pareja.jugador2_foto && pareja.jugador2_foto !== 'images/jugador_img.png' 
                ? '{{ asset("") }}' + pareja.jugador2_foto 
                : '{{ asset("images/jugador_img.png") }}';
            
            var img1 = $('<img>')
                .attr('src', img1Src)
                .addClass('rounded-circle')
                .css({
                    'width': '50px',
                    'height': '50px',
                    'object-fit': 'cover',
                    'margin-right': '5px'
                });
            
            var img2 = $('<img>')
                .attr('src', img2Src)
                .addClass('rounded-circle')
                .css({
                    'width': '50px',
                    'height': '50px',
                    'object-fit': 'cover'
                });
            
            imagenes.append(img1).append(img2);
            
            // Información de la pareja
            var info = $('<div>').css({
                'flex-grow': '1'
            });
            
            var nombres = $('<div>').css({
                'font-weight': 'bold',
                'color': '#000',
                'margin-bottom': '0.25rem'
            }).text(pareja.jugador1_nombre + ' / ' + pareja.jugador2_nombre);
            
            var letraZona = zonaALetra[pareja.zona] || pareja.zona;
            var badge = $('<span>')
                .addClass('badge badge-info')
                .text(letraZona + pareja.posicion + 'º');
            
            var stats = $('<small>')
                .addClass('text-muted d-block mt-1')
                .text('PG: ' + pareja.partidos_ganados + ' | Pts: ' + pareja.puntos_ganados);
            
            info.append(nombres).append(badge).append(stats);
            
            contenido.append(imagenes).append(info);
            item.append(contenido);
            lista.append(item);
        });
    }
    
    // Inicializar lista
    renderizarListaParejas(todasLasParejas);
    
    // Buscador de parejas
    $('#buscador-pareja').on('keyup', function() {
        var filtro = $(this).val().toLowerCase();
        var parejasFiltradas = todasLasParejas.filter(function(pareja) {
            var texto = pareja.jugador1_nombre + ' ' + pareja.jugador2_nombre + ' ' + pareja.zona;
            return texto.toLowerCase().includes(filtro);
        });
        renderizarListaParejas(parejasFiltradas);
    });
    
    // Al seleccionar una pareja del modal (para tarjetas pequeñas y cruces grandes)
    $(document).on('click', '.pareja-option', function() {
        if (!tarjetaSeleccionada) return;
        
        var parejaData = $(this).data('pareja');
        var num = tarjetaSeleccionada.num;
        var pos = tarjetaSeleccionada.pos;
        
        // Guardar en el array de crucesCards
        var nuevaPareja = {
            jugador_1: parejaData.jugador_1,
            jugador_2: parejaData.jugador_2,
            zona: parejaData.zona,
            posicion: parejaData.posicion
        };
        
        crucesCards[num - 1]['pareja_' + pos] = nuevaPareja;
        
        // Actualizar visual de la tarjeta pequeña
        actualizarTarjetaVisual(num, pos, nuevaPareja);
        
        // Regenerar cruces grandes
        generarCrucesDesdeCards();
        
        $('#modalSeleccionarPareja').modal('hide');
        $('#buscador-pareja').val('');
        renderizarListaParejas(todasLasParejas);
        tarjetaSeleccionada = null;
    });
    
    // Limpiar buscador cuando se cierra el modal
    $('#modalSeleccionarPareja').on('hidden.bs.modal', function() {
        $('#buscador-pareja').val('');
        renderizarListaParejas(todasLasParejas);
        tarjetaSeleccionada = null;
    });
    
    // Botón volver a resultados
    $('#btn-volver-resultados').on('click', function() {
        window.location.href = '{{ route("admintorneoresultados") }}?torneo_id=' + torneoId;
    });
    
    // Botón confirmar cruces
    $('#btn-confirmar-cruces').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Guardando...');
        
        console.log('Cruces globales antes de preparar:', cruces);
        
        // Preparar datos de cruces para enviar desde el array global cruces
        // que ahora contiene tanto octavos como cuartos
        var necesitaOctavos = {{ ($necesitaOctavos ?? false) ? 'true' : 'false' }};
        var crucesParaEnviar = cruces.map(function(cruce, index) {
            // Validar que el cruce tenga las parejas necesarias
            if (!cruce.pareja_1 || !cruce.pareja_2) {
                console.error('Cruce ' + index + ' no tiene pareja_1 o pareja_2:', cruce);
                return null;
            }
            
            if (!cruce.pareja_1.jugador_1 || !cruce.pareja_1.jugador_2 || 
                !cruce.pareja_2.jugador_1 || !cruce.pareja_2.jugador_2) {
                console.error('Cruce ' + index + ' no tiene todos los jugadores:', cruce);
                return null;
            }
            
            // Determinar la ronda correcta
            var ronda = cruce.ronda;
            if (!ronda) {
                // Si no tiene ronda, determinar según si necesitamos octavos
                if (necesitaOctavos) {
                    // Si hay 8 cruces, todos son octavos
                    ronda = (cruces.length === 8) ? 'octavos' : 'cuartos';
                } else {
                    ronda = 'cuartos';
                }
            }
            
            // Si necesitamos octavos y hay 8 cruces, asegurar que todos sean octavos
            if (necesitaOctavos && cruces.length === 8 && ronda === 'cuartos') {
                ronda = 'octavos';
            }
            
            return {
                ronda: ronda,
                pareja_1: {
                    jugador_1: parseInt(cruce.pareja_1.jugador_1),
                    jugador_2: parseInt(cruce.pareja_1.jugador_2),
                    zona: cruce.pareja_1.zona || null,
                    posicion: cruce.pareja_1.posicion || null
                },
                pareja_2: {
                    jugador_1: parseInt(cruce.pareja_2.jugador_1),
                    jugador_2: parseInt(cruce.pareja_2.jugador_2),
                    zona: cruce.pareja_2.zona || null,
                    posicion: cruce.pareja_2.posicion || null
                }
            };
        }).filter(function(cruce) {
            return cruce !== null;
        });
        
        console.log('Cruces a enviar:', crucesParaEnviar);
        
        if (crucesParaEnviar.length === 0) {
            alert('No hay cruces válidos para guardar. Por favor, verifique que todas las parejas estén completas.');
            btn.prop('disabled', false).text('Confirmar Cruces');
            return;
        }
        
        $.ajax({
            type: 'POST',
            url: '{{ route("guardarcruceseditados") }}',
            data: {
                torneo_id: torneoId,
                cruces: crucesParaEnviar,
                config_cruces_puntuable_id: $('#config_cruces_puntuable').val() || null,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                if (response.success) {
                    alert('Cruces confirmados correctamente');
                    // Verificar el tipo de torneo para redirigir apropiadamente
                    var tipoTorneo = '{{ $tipoTorneo ?? "puntuable" }}';
                    if (tipoTorneo === 'puntuable') {
                        window.location.href = '{{ route("admintorneopuntuablecrucesv2") }}?torneo_id=' + torneoId;
                    } else {
                        window.location.href = '{{ route("admintorneoamericanocruces") }}?torneo_id=' + torneoId;
                    }
                } else {
                    alert('Error: ' + (response.message || 'Error desconocido'));
                    btn.prop('disabled', false).text('Confirmar Cruces');
                }
            },
            error: function(xhr) {
                console.error('Error completo:', xhr);
                console.error('Response text:', xhr.responseText);
                var errorMsg = 'Error al guardar los cruces';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg += ': ' + xhr.responseJSON.message;
                }
                alert(errorMsg);
                btn.prop('disabled', false).text('Confirmar Cruces');
            }
        });
    });
});
</script>

@endsection

