@extends('bahia_padel/admin/plantilla')

@section('title_header','Resultados del Torneo')

@section('contenedor')

<style>
    /* Estilos para scroll horizontal en partidos */
    .partidos-container-scroll {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
    }
    
    .partidos-container-scroll::-webkit-scrollbar {
        height: 8px;
    }
    
    .partidos-container-scroll::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .partidos-container-scroll::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    
    .partidos-container-scroll::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    .partidos-container-scroll .d-flex {
        min-width: max-content;
        gap: 1rem;
    }
    
    .partido-item {
        min-width: 280px;
        flex-shrink: 0;
    }
    
    /* En pantallas pequeñas, hacer el scroll más visible */
    @media (max-width: 768px) {
        .partidos-container-scroll {
            margin-left: -15px;
            margin-right: -15px;
            padding-left: 15px;
            padding-right: 15px;
        }
        
        .partido-item {
            min-width: 260px;
        }
    }
    
    /* Estilos para alinear inputs de Tie Break con los inputs de Sets */
    .resultado-partido .d-flex.justify-content-center {
        display: flex;
        justify-content: center;
        align-items: center;
        padding-left: 0;
        padding-right: 0;
        width: 100%;
        max-width: 150px;
        margin: 0 auto;
    }
    
    .resultado-partido .d-flex.justify-content-center input[type="number"] {
        width: 60px !important;
        min-width: 60px !important;
        max-width: 60px !important;
    }
    
    .tie-break-container {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 0.25rem;
        padding-left: 0;
        padding-right: 0;
        width: 100%;
        max-width: 150px;
        margin-left: auto;
        margin-right: auto;
        position: relative;
    }
    
    .tie-break-container > div:first-child {
        position: absolute;
        left: -35px;
        width: 30px;
        text-align: right;
        padding-right: 0.25rem;
        font-size: 0.7rem;
        flex-shrink: 0;
    }
    
    .tie-break-container input[type="number"] {
        width: 60px !important;
        min-width: 60px !important;
        max-width: 60px !important;
    }
    
    .tie-break-container .mx-1 {
        margin-left: 0.5rem !important;
        margin-right: 0.5rem !important;
    }
    #modalHorarioCruces .modal-content,
    #modalHorarioCruces .modal-header,
    #modalHorarioCruces .modal-body,
    #modalHorarioCruces .modal-footer,
    #modalHorarioCruces .modal-title,
    #modalHorarioCruces label,
    #modalHorarioCruces p {
        color: #000 !important;
    }
    #modalHorarioCruces .form-control {
        color: #000;
    }
</style>

<div class="container body_admin">
    <div class="row justify-content-center">
        <input hidden id="torneo_id" value="{{$torneo->id}}">            
        <div class="card shadow bg-white w-100 px-5 py-3 d-flex "
            style="border-radius: 12px; border: 1px solid #e3e6f0;">
            <div class="d-flex flex-column align-items-start flex-grow-1">
                <div class="categoria display-4 mb-2" style="font-size:2.2rem; font-weight:700; color:#4e73df;">
                    {{ $torneo->categoria ?? '-' }}º Categoría <small>- ({{ $torneo->tipo}})</small>
                </div>                    
                <div class="fechas" style="font-size:1.2rem; color:#555;">
                Fecha: {{ isset($torneo->fecha_inicio, $torneo->fecha_fin) ? (date('d', strtotime($torneo->fecha_inicio)).' '.__(strtolower(date('F', strtotime($torneo->fecha_inicio)))).' - '.date('d', strtotime($torneo->fecha_fin)).' '.__(strtolower(date('F', strtotime($torneo->fecha_fin)))) ) : '-' }}
                </div>
            </div>
            <div class="d-flex flex-column align-items-end premios" style="min-width:180px;">
                <div class="premio1" style="font-size:1.5rem; font-weight:600; color:#1a8917;">
                    1º Premio: ${{ $torneo->premio_1}}                        
                </div>
                <div class="premio2" style="font-size:1.2rem; font-weight:500; color:#555;">
                    2º Premio: ${{ $torneo->premio_2}}                        
                </div>
            </div>
        </div>
    </div>
    <br>

    @php
        $jugadoresMap = [];
        foreach($jugadores as $j) {
            $jugadoresMap[$j->id] = $j;
        }
        $zonasArray = array_keys($partidosPorZona);
        sort($zonasArray);
    @endphp

    <!-- Botones de navegación de zonas -->
    <div class="row justify-content-center mb-3">
        <div class="col-md-8 text-center">
            <button type="button" class="btn btn-secondary btn-lg mr-2" id="btn-zona-anterior-resultados">
                ← Zona Anterior
            </button>
            <span id="zona-actual-label" class="mx-3" style="font-size:1.2rem; font-weight:600; color:#4e73df;">
                Zona {{ $zonasArray[0] ?? 'A' }}
            </span>
            <button type="button" class="btn btn-secondary btn-lg ml-2" id="btn-zona-siguiente-resultados">
                Zona Siguiente →
            </button>
        </div>
    </div>

    <!-- Contenedor de zonas horizontal -->
    <div class="row justify-content-center">
        <div class="col-12">
            <div id="contenedor-zonas" style="position: relative; overflow: hidden;">
                @foreach($partidosPorZona as $zona => $partidos)
                <div class="zona-container" data-zona="{{ $zona }}" style="width: 100%; display: {{ $loop->first ? 'block' : 'none' }};">
                    <div class="card shadow bg-white px-5 py-3">
                        <h3 class="mb-4 text-center" style="color:#4e73df;">Zona {{ $zona }}</h3>
                        
                        @php
                            $numPartidos = count($partidos);
                            $tieneCuatroPartidos = $numPartidos == 4;
                        @endphp
                        
                        @if($tieneCuatroPartidos)
                        <!-- Contenedor con scroll horizontal para 4 partidos -->
                        <div class="partidos-container-scroll" style="overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; margin-bottom: 1rem;">
                            <div class="d-flex" style="min-width: max-content; gap: 1rem;">
                        @else
                        <!-- Contenedor normal para menos de 4 partidos -->
                        <div class="row">
                        @endif
                            @foreach($partidos as $partidoId => $partidoData)
                            @php
                                $resultados = $partidoData['resultados'];
                                $pareja1 = $partidoData['pareja_1'];
                                $pareja2 = $partidoData['pareja_2'];
                                $fecha = $partidoData['fecha'] ?? null;
                                $horario = $partidoData['horario'] ?? null;
                                
                                // Verificar si las parejas tienen jugadores asignados (no son 0)
                                $tienePareja1 = $pareja1 && $pareja1['jugador_1'] != 0 && $pareja1['jugador_1'] !== null && $pareja1['jugador_2'] != 0 && $pareja1['jugador_2'] !== null;
                                $tienePareja2 = $pareja2 && $pareja2['jugador_1'] != 0 && $pareja2['jugador_1'] !== null && $pareja2['jugador_2'] != 0 && $pareja2['jugador_2'] !== null;
                                
                                // Verificar si es un partido de Ganador o Perdedor (tiene jugador_1 = 0 o jugador_2 = 0)
                                $esPartidoGanadorPerdedor = ($pareja1 && ($pareja1['jugador_1'] == 0 || $pareja1['jugador_2'] == 0)) || 
                                                           ($pareja2 && ($pareja2['jugador_1'] == 0 || $pareja2['jugador_2'] == 0));
                                
                                // Mostrar el partido si tiene parejas asignadas O si es un partido de Ganador/Perdedor (aunque aún no tenga jugadores)
                                if (!$tienePareja1 && !$tienePareja2 && !$esPartidoGanadorPerdedor) {
                                    continue;
                                }
                                
                                $jugador1 = $tienePareja1 ? ($jugadoresMap[$pareja1['jugador_1']] ?? null) : null;
                                $jugador2 = $tienePareja1 ? ($jugadoresMap[$pareja1['jugador_2']] ?? null) : null;
                                $jugador3 = $tienePareja2 ? ($jugadoresMap[$pareja2['jugador_1']] ?? null) : null;
                                $jugador4 = $tienePareja2 ? ($jugadoresMap[$pareja2['jugador_2']] ?? null) : null;
                                
                                // Formatear fecha
                                $fechaFormateada = '';
                                if ($fecha && $fecha != '2000-01-01') {
                                    $diasSemana = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
                                    $timestamp = strtotime($fecha);
                                    $diaSemana = $diasSemana[date('w', $timestamp)];
                                    $diaMes = date('d', $timestamp);
                                    $fechaFormateada = ucfirst($diaSemana) . ' ' . $diaMes;
                                }
                            @endphp
                            
                            @if($tieneCuatroPartidos)
                            <!-- Partido en fila horizontal (4 partidos) -->
                            <div class="partido-item" style="min-width: 280px; flex-shrink: 0;">
                                <div class="card border" style="height: 100%;">
                                    <div class="card-body">
                            @else
                            <!-- Partido en grid normal (menos de 4) -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border" style="height: 100%;">
                                    <div class="card-body">
                            @endif
                                        @php
                                            // Determinar el título del partido
                                            $tipoPartido = $partidoData['tipo'] ?? 'normal';
                                            
                                            if ($tipoPartido === 'ganador') {
                                                $tituloPartido = 'Partido 3 - Ganador';
                                            } else if ($tipoPartido === 'perdedor') {
                                                $tituloPartido = 'Partido 4 - Perdedor';
                                            } else {
                                                $tituloPartido = 'Partido ' . $loop->iteration;
                                            }
                                        @endphp
                                        <h5 class="card-title text-center mb-2">{{ $tituloPartido }}</h5>
                                        @if($fechaFormateada || $horario)
                                        <div class="text-center mb-3" style="font-size:0.85rem; color:#555;">
                                            @if($fechaFormateada)
                                            <div><strong>Día:</strong> {{ $fechaFormateada }}</div>
                                            @endif
                                            @if($horario && $horario != '00:00')
                                            <div><strong>Horario:</strong> {{ $horario }}</div>
                                            @endif
                                        </div>
                                        @endif
                                        
                                        <!-- Jugadores -->
                                        <div class="d-flex justify-content-around align-items-center mb-3">
                                            <!-- Pareja 1 -->
                                            <div class="text-center pareja-container pareja-1-container" data-partido-id="{{ $partidoId }}" style="position: relative; padding: 10px; border-radius: 8px; transition: all 0.3s;">
                                                @if($tienePareja1 && $jugador1)
                                                <div class="mb-2">
                                                    <img src="{{ asset($jugador1->foto ?? 'images/jugador_img.png') }}" 
                                                        class="rounded-circle" 
                                                        style="width:70px; height:70px; object-fit:cover; border: 2px solid #4e73df;">
                                                    <div style="font-size:0.75rem; font-weight:600; margin-top:5px;">
                                                        {{ $jugador1->nombre ?? '' }} {{ $jugador1->apellido ?? '' }}
                                                    </div>
                                                </div>
                                                @elseif($esPartidoGanadorPerdedor && $pareja1 && ($pareja1['jugador_1'] == 0 || $pareja1['jugador_2'] == 0))
                                                <div class="mb-2">
                                                    <div style="width:70px; height:70px; border-radius:50%; background:#f0f0f0; display:flex; align-items:center; justify-content:center; margin:0 auto; border: 2px solid #ccc;">
                                                        <span style="font-size:0.7rem; color:#666;">?</span>
                                                    </div>
                                                    <div style="font-size:0.75rem; font-weight:600; margin-top:5px; color:#999;">
                                                        Esperando ganador
                                                    </div>
                                                </div>
                                                @endif
                                                @if($tienePareja1 && $jugador2)
                                                <div>
                                                    <img src="{{ asset($jugador2->foto ?? 'images/jugador_img.png') }}" 
                                                        class="rounded-circle" 
                                                        style="width:70px; height:70px; object-fit:cover; border: 2px solid #4e73df;">
                                                    <div style="font-size:0.75rem; font-weight:600; margin-top:5px;">
                                                        {{ $jugador2->nombre ?? '' }} {{ $jugador2->apellido ?? '' }}
                                                    </div>
                                                </div>
                                                @elseif($esPartidoGanadorPerdedor && $pareja1 && ($pareja1['jugador_1'] == 0 || $pareja1['jugador_2'] == 0) && !$tienePareja1)
                                                <div>
                                                    <div style="width:70px; height:70px; border-radius:50%; background:#f0f0f0; display:flex; align-items:center; justify-content:center; margin:0 auto; border: 2px solid #ccc;">
                                                        <span style="font-size:0.7rem; color:#666;">?</span>
                                                    </div>
                                                    <div style="font-size:0.75rem; font-weight:600; margin-top:5px; color:#999;">
                                                        Esperando ganador
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                            
                                            <!-- VS -->
                                            <div class="mx-3">
                                                <h4 style="color:#dc3545; font-weight:bold;">VS</h4>
                                            </div>
                                            
                                            <!-- Pareja 2 -->
                                            <div class="text-center pareja-container pareja-2-container" data-partido-id="{{ $partidoId }}" style="position: relative; padding: 10px; border-radius: 8px; transition: all 0.3s;">
                                                @if($tienePareja2 && $jugador3)
                                                <div class="mb-2">
                                                    <img src="{{ asset($jugador3->foto ?? 'images/jugador_img.png') }}" 
                                                        class="rounded-circle" 
                                                        style="width:70px; height:70px; object-fit:cover; border: 2px solid #1a8917;">
                                                    <div style="font-size:0.75rem; font-weight:600; margin-top:5px;">
                                                        {{ $jugador3->nombre ?? '' }} {{ $jugador3->apellido ?? '' }}
                                                    </div>
                                                </div>
                                                @elseif($esPartidoGanadorPerdedor && $pareja2 && ($pareja2['jugador_1'] == 0 || $pareja2['jugador_2'] == 0))
                                                <div class="mb-2">
                                                    <div style="width:70px; height:70px; border-radius:50%; background:#f0f0f0; display:flex; align-items:center; justify-content:center; margin:0 auto; border: 2px solid #ccc;">
                                                        <span style="font-size:0.7rem; color:#666;">?</span>
                                                    </div>
                                                    <div style="font-size:0.75rem; font-weight:600; margin-top:5px; color:#999;">
                                                        Esperando ganador
                                                    </div>
                                                </div>
                                                @endif
                                                @if($tienePareja2 && $jugador4)
                                                <div>
                                                    <img src="{{ asset($jugador4->foto ?? 'images/jugador_img.png') }}" 
                                                        class="rounded-circle" 
                                                        style="width:70px; height:70px; object-fit:cover; border: 2px solid #1a8917;">
                                                    <div style="font-size:0.75rem; font-weight:600; margin-top:5px;">
                                                        {{ $jugador4->nombre ?? '' }} {{ $jugador4->apellido ?? '' }}
                                                    </div>
                                                </div>
                                                @elseif($esPartidoGanadorPerdedor && $pareja2 && ($pareja2['jugador_1'] == 0 || $pareja2['jugador_2'] == 0) && !$tienePareja2)
                                                <div>
                                                    <div style="width:70px; height:70px; border-radius:50%; background:#f0f0f0; display:flex; align-items:center; justify-content:center; margin:0 auto; border: 2px solid #ccc;">
                                                        <span style="font-size:0.7rem; color:#666;">?</span>
                                                    </div>
                                                    <div style="font-size:0.75rem; font-weight:600; margin-top:5px; color:#999;">
                                                        Esperando ganador
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <!-- Resultados -->
                                        <div class="resultado-partido" data-partido-id="{{ $partidoId }}">
                                            <!-- Set 1 -->
                                            <div class="mb-2">
                                                <label style="font-size:0.8rem; font-weight:600;">Set 1</label>
                                                <div class="d-flex justify-content-center align-items-center">
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_1_set_1" 
                                                        value="{{ $resultados->pareja_1_set_1 ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                    <span class="mx-2">-</span>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_2_set_1" 
                                                        value="{{ $resultados->pareja_2_set_1 ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                </div>
                                                <div class="tie-break-container">
                                                    <div style="width: 30px; text-align: right; padding-right: 0.25rem; font-size: 0.7rem;">TB:</div>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        name="pareja_1_set_1_tie_break" 
                                                        value="{{ $resultados->pareja_1_set_1_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                    <span class="mx-1">-</span>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        name="pareja_2_set_1_tie_break" 
                                                        value="{{ $resultados->pareja_2_set_1_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                </div>
                                            </div>
                                            
                                            <!-- Set 2 -->
                                            <div class="mb-2">
                                                <label style="font-size:0.8rem; font-weight:600;">Set 2</label>
                                                <div class="d-flex justify-content-center align-items-center">
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_1_set_2" 
                                                        value="{{ $resultados->pareja_1_set_2 ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                    <span class="mx-2">-</span>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_2_set_2" 
                                                        value="{{ $resultados->pareja_2_set_2 ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                </div>
                                                <div class="tie-break-container">
                                                    <div style="width: 30px; text-align: right; padding-right: 0.25rem; font-size: 0.7rem;">TB:</div>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        name="pareja_1_set_2_tie_break" 
                                                        value="{{ $resultados->pareja_1_set_2_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                    <span class="mx-1">-</span>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        name="pareja_2_set_2_tie_break" 
                                                        value="{{ $resultados->pareja_2_set_2_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                </div>
                                            </div>
                                            
                                            <!-- Set 3 -->
                                            <div class="mb-2">
                                                <label style="font-size:0.8rem; font-weight:600;">Set 3</label>
                                                <div class="d-flex justify-content-center align-items-center">
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_1_set_3" 
                                                        value="{{ $resultados->pareja_1_set_3 ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                    <span class="mx-2">-</span>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_2_set_3" 
                                                        value="{{ $resultados->pareja_2_set_3 ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                </div>
                                                <div class="tie-break-container">
                                                    <div style="width: 30px; text-align: right; padding-right: 0.25rem; font-size: 0.7rem;">TB:</div>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        name="pareja_1_set_3_tie_break" 
                                                        value="{{ $resultados->pareja_1_set_3_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                    <span class="mx-1">-</span>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        name="pareja_2_set_3_tie_break" 
                                                        value="{{ $resultados->pareja_2_set_3_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                </div>
                                            </div>
                                            
                                            <!-- Super TB -->
                                            <div class="mb-2">
                                                <label style="font-size:0.8rem; font-weight:600;">Super TB</label>
                                                <div class="d-flex justify-content-center align-items-center">
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_1_set_super_tie_break" 
                                                        value="{{ $resultados->pareja_1_set_super_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                    <span class="mx-2">-</span>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_2_set_super_tie_break" 
                                                        value="{{ $resultados->pareja_2_set_super_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                </div>
                                            </div>
                                            
                                            <!-- Botón Guardar -->
                                            <div class="text-center mt-3">
                                                <button type="button" class="btn btn-sm btn-primary guardar-resultado" 
                                                    data-partido-id="{{ $partidoId }}">
                                                    Guardar Resultado
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @if($tieneCuatroPartidos)
                            </div>
                            @else
                            </div>
                            @endif
                            @endforeach
                        @if($tieneCuatroPartidos)
                        </div>
                        </div>
                        @else
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Sección de Clasificación -->
    <div class="row justify-content-center mt-5 mb-4" id="seccion-clasificacion" style="display: none;">
        <div class="col-12">
            <div class="card shadow bg-white px-5 py-4">
                <h3 class="text-center mb-4" style="color:#4e73df;">Clasificación Zona <span id="zona-clasificacion-label"></span></h3>
                <div id="contenedor-podio" class="row justify-content-center">
                    <!-- Se llenará dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mt-4 mb-4">
        <div class="col-md-8 text-center">
            <button type="button" class="btn btn-info btn-lg mr-3" id="btn-horario-cruces">
                Horario cruces
            </button>
            <button type="button" class="btn btn-success btn-lg mr-3" id="btn-validar-cruces" style="display: none;">
                Validar Cruces
            </button>
            <a href="/admin_torneos" class="btn btn-secondary btn-lg">
                Volver a Torneos
            </a>
        </div>
    </div>
</div>

<!-- Modal Horario cruces -->
<div class="modal fade" id="modalHorarioCruces" tabindex="-1" role="dialog" aria-labelledby="modalHorarioCrucesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="color: #000;">
            <div class="modal-header" style="color: #000;">
                <h5 class="modal-title" id="modalHorarioCrucesLabel" style="color: #000;">Día y horario de los partidos de cruces</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="color: #000;">
                <div id="horario-cruces-loading" class="text-center py-4" style="color: #000;">
                    <span class="spinner-border text-primary" role="status"></span>
                    <p class="mt-2" style="color: #000;">Cargando partidos...</p>
                </div>
                <div id="horario-cruces-content" style="display: none;">
                    <p class="small mb-3" style="color: #333;">Asigná día y horario a cada partido de la fase eliminatoria.</p>
                    <div id="horario-cruces-lista"></div>
                </div>
                <div id="horario-cruces-empty" class="text-center py-4" style="display: none; color: #333;">
                    No hay partidos de cruces cargados para este torneo.
                </div>
            </div>
            <div class="modal-footer" style="color: #000;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-horarios-cruces" style="display: none;">
                    Guardar horarios
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Navegación de zonas
    let zonas = @json($zonasArray);
    let zonaIndex = 0;
    
    function actualizarZona() {
        // Ocultar todas las zonas
        $('.zona-container').hide();
        // Mostrar la zona actual
        $('.zona-container[data-zona="' + zonas[zonaIndex] + '"]').show();
        // Actualizar el label
        $('#zona-actual-label').text('Zona ' + zonas[zonaIndex]);
        // Habilitar/deshabilitar botones
        $('#btn-zona-anterior-resultados').prop('disabled', zonaIndex === 0);
        $('#btn-zona-siguiente-resultados').prop('disabled', zonaIndex === zonas.length - 1);
        
        // Ocultar clasificación anterior y verificar la nueva zona
        $('#seccion-clasificacion').hide();
        $('#contenedor-podio').empty();
        
        // Verificar si la nueva zona tiene todos los partidos completos
        verificarYCalcularClasificacion();
    }
    
    $('#btn-zona-anterior-resultados').on('click', function() {
        if (zonaIndex > 0) {
            zonaIndex--;
            actualizarZona();
        }
    });
    
    $('#btn-zona-siguiente-resultados').on('click', function() {
        if (zonaIndex < zonas.length - 1) {
            zonaIndex++;
            actualizarZona();
        }
    });
    
    // Inicializar
    actualizarZona();
    
    // Verificar partidos completos al cargar la página
    verificarYCalcularClasificacion();
    
    // Verificar si todos los partidos de todas las zonas están completos
    function verificarTodosPartidosCompletos() {
        var torneoId = $('#torneo_id').val();
        var todasZonasCompletas = true;
        var zonasVerificadas = 0;
        
        zonas.forEach(function(zona) {
            $.ajax({
                type: 'POST',
                dataType: 'JSON',
                url: '{{ route("verificarpartidoscompletos") }}',
                data: {
                    torneo_id: torneoId,
                    zona: zona,
                    _token: '{{csrf_token()}}'
                },
                success: function(data) {
                    zonasVerificadas++;
                    if (!data.success || !data.todos_completos) {
                        todasZonasCompletas = false;
                    }
                    
                    // Cuando se hayan verificado todas las zonas
                    if (zonasVerificadas === zonas.length) {
                        if (todasZonasCompletas) {
                            $('#btn-validar-cruces').show();
                        } else {
                            $('#btn-validar-cruces').hide();
                        }
                    }
                },
                error: function() {
                    zonasVerificadas++;
                    todasZonasCompletas = false;
                    if (zonasVerificadas === zonas.length) {
                        $('#btn-validar-cruces').hide();
                    }
                }
            });
        });
    }
    
    // Verificar al cargar la página
    verificarTodosPartidosCompletos();
    
    // Navegar a la pantalla de cruces
    $('#btn-validar-cruces').on('click', function() {
        var torneoId = $('#torneo_id').val();
        var url = '{{ url("/admin_torneo_validar_cruces") }}?torneo_id=' + torneoId;
        window.location.href = url;
    });

    // Horario cruces: abrir modal y cargar partidos
    $('#btn-horario-cruces').on('click', function() {
        var torneoId = $('#torneo_id').val();
        $('#modalHorarioCruces').modal('show');
        $('#horario-cruces-loading').show();
        $('#horario-cruces-content').hide();
        $('#horario-cruces-empty').hide();
        $('#btn-guardar-horarios-cruces').hide();
        $.get('{{ route("obtenerhorarioscruces") }}', { torneo_id: torneoId }, function(res) {
            $('#horario-cruces-loading').hide();
            if (res.success && res.partidos && res.partidos.length > 0) {
                var html = '';
                res.partidos.forEach(function(p) {
                    var fecha = (p.fecha && p.fecha !== '2000-01-01') ? p.fecha : '';
                    var horario = (p.horario && p.horario !== '00:00') ? p.horario : '';
                    html += '<div class="form-group row align-items-center mb-2">' +
                        '<label class="col-md-4 col-form-label">' + (p.etiqueta || 'Partido') + '</label>' +
                        '<div class="col-md-4"><input type="date" class="form-control horario-cruce-fecha" data-partido-id="' + p.partido_id + '" value="' + fecha + '" placeholder="Día"></div>' +
                        '<div class="col-md-4"><input type="time" class="form-control horario-cruce-horario" data-partido-id="' + p.partido_id + '" value="' + horario + '" placeholder="Horario"></div>' +
                        '</div>';
                });
                $('#horario-cruces-lista').html(html);
                $('#horario-cruces-content').show();
                $('#btn-guardar-horarios-cruces').show();
            } else {
                $('#horario-cruces-empty').show();
            }
        }).fail(function() {
            $('#horario-cruces-loading').hide();
            $('#horario-cruces-empty').show().html('Error al cargar los partidos.');
        });
    });

    $('#btn-guardar-horarios-cruces').on('click', function() {
        var torneoId = $('#torneo_id').val();
        var partidos = [];
        $('.horario-cruce-fecha').each(function() {
            var partidoId = $(this).data('partido-id');
            var fecha = $(this).val() || '';
            var horario = $('.horario-cruce-horario[data-partido-id="' + partidoId + '"]').val() || '';
            partidos.push({ partido_id: partidoId, fecha: fecha, horario: horario });
        });
        var btn = $(this);
        btn.prop('disabled', true).text('Guardando...');
        $.ajax({
            type: 'POST',
            url: '{{ route("guardarhorarioscruces") }}',
            data: {
                torneo_id: torneoId,
                partidos: partidos,
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                btn.prop('disabled', false).text('Guardar horarios');
                if (res.success) {
                    $('#modalHorarioCruces').modal('hide');
                    alert('Horarios guardados correctamente.');
                } else {
                    alert(res.message || 'Error al guardar');
                }
            },
            error: function() {
                btn.prop('disabled', false).text('Guardar horarios');
                alert('Error al guardar los horarios.');
            }
        });
    });
    
    // Guardar resultado cuando se hace clic en el botón
    $(document).on('click', '.guardar-resultado', function() {
        var partidoId = $(this).data('partido-id');
        var resultadoPartido = $(this).closest('.resultado-partido');
        
        console.log('=== INICIANDO GUARDAR RESULTADO ===');
        console.log('Partido ID obtenido del botón:', partidoId);
        console.log('Tipo de partidoId:', typeof partidoId);
        console.log('Partido ID es null/undefined?:', partidoId === null || partidoId === undefined);
        console.log('Partido ID es string vacío?:', partidoId === '');
        console.log('Partido ID es "null"?:', partidoId === 'null');
        
        // Verificar que el partidoId sea válido
        if (!partidoId || partidoId === null || partidoId === undefined || partidoId === '' || partidoId === 'null') {
            console.error('ERROR: Partido ID inválido:', partidoId);
            alert('Error: No se pudo obtener el ID del partido. Por favor, recarga la página.');
            return;
        }
        
        // Verificar que el elemento resultadoPartido existe
        if (!resultadoPartido || resultadoPartido.length === 0) {
            console.error('ERROR: No se encontró el contenedor de resultados');
            alert('Error: No se pudo encontrar el contenedor de resultados.');
            return;
        }
        
        console.log('Contenedor de resultados encontrado:', resultadoPartido);
        console.log('Buscando inputs con data-partido-id="' + partidoId + '"');
        
        var datos = {
            partido_id: partidoId,
            pareja_1_set_1: resultadoPartido.find('input[name="pareja_1_set_1"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_1_tie_break: resultadoPartido.find('input[name="pareja_1_set_1_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_1: resultadoPartido.find('input[name="pareja_2_set_1"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_1_tie_break: resultadoPartido.find('input[name="pareja_2_set_1_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_2: resultadoPartido.find('input[name="pareja_1_set_2"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_2_tie_break: resultadoPartido.find('input[name="pareja_1_set_2_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_2: resultadoPartido.find('input[name="pareja_2_set_2"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_2_tie_break: resultadoPartido.find('input[name="pareja_2_set_2_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_3: resultadoPartido.find('input[name="pareja_1_set_3"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_3_tie_break: resultadoPartido.find('input[name="pareja_1_set_3_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_3: resultadoPartido.find('input[name="pareja_2_set_3"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_3_tie_break: resultadoPartido.find('input[name="pareja_2_set_3_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_super_tie_break: resultadoPartido.find('input[name="pareja_1_set_super_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_super_tie_break: resultadoPartido.find('input[name="pareja_2_set_super_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            _token: '{{csrf_token()}}'
        };
        
        console.log('=== DATOS A ENVIAR ===');
        console.log('Datos completos:', datos);
        console.log('partido_id en datos:', datos.partido_id);
        console.log('Tipo de partido_id en datos:', typeof datos.partido_id);
        
        var btn = $(this);
        btn.prop('disabled', true).text('Guardando...');
        
        console.log('=== ENVIANDO REQUEST AJAX ===');
        console.log('URL:', '{{ route("guardarresultadopartido") }}');
        console.log('Método: POST');
        console.log('Datos enviados:', JSON.stringify(datos, null, 2));
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("guardarresultadopartido") }}',
            data: datos,
            beforeSend: function() {
                console.log('=== REQUEST ENVIADO ===');
                console.log('Esperando respuesta del servidor...');
            },
            success: function(data) {
                console.log('=== RESPUESTA DEL SERVIDOR ===');
                console.log('Respuesta completa:', data);
                console.log('Success:', data.success);
                console.log('Recargar:', data.recargar);
                if (data.debug) {
                    console.log('=== DEBUG INFO ===');
                    console.log('Debug:', data.debug);
                }
                if (data.error) {
                    console.error('Error:', data.error);
                }
                
                if (data.success) {
                    console.log('✓ Resultado guardado exitosamente');
                    btn.removeClass('btn-primary').addClass('btn-success').text('✓ Guardado');
                    
                    // Determinar ganador y aplicar estilo verde
                    determinarGanador(partidoId, resultadoPartido);
                    
                    // Si se actualizaron partidos de Ganador/Perdedor, recargar la página
                    if (data.recargar && data.partidos_actualizados) {
                        console.log('⚠️ Se actualizaron partidos de Ganador/Perdedor. Recargando página...');
                        setTimeout(function() {
                            window.location.reload();
                        }, 500);
                    } else {
                        console.log('No se requiere actualizar partidos de Ganador/Perdedor');
                    }
                    
                    // Verificar si todos los partidos están completos
                    verificarYCalcularClasificacion();
                    
                    // Verificar si todas las zonas están completas para mostrar el botón
                    setTimeout(function() {
                        verificarTodosPartidosCompletos();
                    }, 500);
                    
                    setTimeout(function() {
                        btn.removeClass('btn-success').addClass('btn-primary').text('Guardar Resultado');
                    }, 2000);
                    btn.prop('disabled', false);
                } else {
                    console.error('=== ERROR AL GUARDAR ===');
                    console.error('Success: false');
                    console.error('Mensaje:', data.message || 'Error desconocido');
                    console.error('Datos completos de error:', data);
                    alert('Error al guardar el resultado: ' + (data.message || 'Error desconocido'));
                    btn.prop('disabled', false).text('Guardar Resultado');
                }
            },
            error: function(xhr, status, error) {
                console.error('=== ERROR EN AJAX ===');
                console.error('Status HTTP:', status);
                console.error('Error:', error);
                console.error('Status Code:', xhr.status);
                console.error('Response Text:', xhr.responseText);
                console.error('Response JSON:', xhr.responseJSON);
                console.error('Request URL:', xhr.responseURL || '{{ route("guardarresultadopartido") }}');
                
                // Intentar parsear la respuesta si es JSON
                try {
                    var errorResponse = JSON.parse(xhr.responseText);
                    console.error('Error parseado:', errorResponse);
                    if (errorResponse.message) {
                        console.error('Mensaje de error:', errorResponse.message);
                    }
                } catch(e) {
                    console.error('No se pudo parsear la respuesta como JSON');
                }
                
                alert('Error al guardar el resultado. Revisa la consola para más detalles.');
                btn.prop('disabled', false).text('Guardar Resultado');
            }
        });
    });
    
    // Auto-guardar cuando se cambia un valor (opcional)
    $(document).on('change', '.resultados-sets input', function() {
        var partidoId = $(this).data('partido-id');
        var btn = $('.guardar-resultado[data-partido-id="' + partidoId + '"]');
        if (btn.hasClass('btn-success')) {
            btn.removeClass('btn-success').addClass('btn-primary').text('Guardar Resultado');
        }
        // Remover estilo verde cuando se cambia un valor
        $('.pareja-1-container[data-partido-id="' + partidoId + '"], .pareja-2-container[data-partido-id="' + partidoId + '"]')
            .removeClass('ganador').css('background-color', '').css('border', '');
    });
    
    // Función para determinar el ganador
    function determinarGanador(partidoId, resultadoPartido) {
        // Obtener valores de los sets
        var set1_p1 = parseInt(resultadoPartido.find('input[name="pareja_1_set_1"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set1_p2 = parseInt(resultadoPartido.find('input[name="pareja_2_set_1"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set2_p1 = parseInt(resultadoPartido.find('input[name="pareja_1_set_2"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set2_p2 = parseInt(resultadoPartido.find('input[name="pareja_2_set_2"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set3_p1 = parseInt(resultadoPartido.find('input[name="pareja_1_set_3"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set3_p2 = parseInt(resultadoPartido.find('input[name="pareja_2_set_3"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var superTB_p1 = parseInt(resultadoPartido.find('input[name="pareja_1_set_super_tie_break"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var superTB_p2 = parseInt(resultadoPartido.find('input[name="pareja_2_set_super_tie_break"][data-partido-id="' + partidoId + '"]').val()) || 0;
        
        // Remover estilos anteriores
        $('.pareja-1-container[data-partido-id="' + partidoId + '"], .pareja-2-container[data-partido-id="' + partidoId + '"]')
            .removeClass('ganador').css('background-color', '').css('border', '');
        
        // Si hay super tie break, ese determina el ganador
        if (superTB_p1 > 0 || superTB_p2 > 0) {
            if (superTB_p1 > superTB_p2) {
                $('.pareja-1-container[data-partido-id="' + partidoId + '"]')
                    .addClass('ganador')
                    .css('background-color', '#d4edda')
                    .css('border', '3px solid #28a745');
            } else if (superTB_p2 > superTB_p1) {
                $('.pareja-2-container[data-partido-id="' + partidoId + '"]')
                    .addClass('ganador')
                    .css('background-color', '#d4edda')
                    .css('border', '3px solid #28a745');
            }
            return;
        }
        
        // Contar sets ganados
        var setsGanadosP1 = 0;
        var setsGanadosP2 = 0;
        
        if (set1_p1 > set1_p2) setsGanadosP1++;
        else if (set1_p2 > set1_p1) setsGanadosP2++;
        
        if (set2_p1 > set2_p2) setsGanadosP1++;
        else if (set2_p2 > set2_p1) setsGanadosP2++;
        
        if (set3_p1 > set3_p2) setsGanadosP1++;
        else if (set3_p2 > set3_p1) setsGanadosP2++;
        
        // Aplicar estilo verde al ganador
        if (setsGanadosP1 > setsGanadosP2) {
            $('.pareja-1-container[data-partido-id="' + partidoId + '"]')
                .addClass('ganador')
                .css('background-color', '#d4edda')
                .css('border', '3px solid #28a745');
        } else if (setsGanadosP2 > setsGanadosP1) {
            $('.pareja-2-container[data-partido-id="' + partidoId + '"]')
                .addClass('ganador')
                .css('background-color', '#d4edda')
                .css('border', '3px solid #28a745');
        }
    }
    
    // Función para actualizar dinámicamente los partidos de Ganador/Perdedor
    function actualizarPartidosGanadorPerdedor(partidosActualizados) {
        @php
            $jugadoresArray = [];
            foreach($jugadores as $j) {
                $jugadoresArray[] = [
                    'id' => $j->id,
                    'nombre' => $j->nombre ?? '',
                    'apellido' => $j->apellido ?? '',
                    'foto' => $j->foto ?? asset('images/jugador_img.png')
                ];
            }
        @endphp
        var jugadores = @json($jugadoresArray);
        var baseUrl = '{{ url("/") }}';
        var zonaActual = $('.zona-container:visible').data('zona');
        
        console.log('Actualizando partidos de Ganador/Perdedor:', partidosActualizados);
        console.log('Zona actual:', zonaActual);
        
        // Función helper para obtener URL de foto
        function getFotoUrl(foto) {
            if (!foto || foto === '') {
                return baseUrl + '/images/jugador_img.png?v=' + Date.now();
            }
            if (foto.startsWith('http://') || foto.startsWith('https://')) {
                return foto + '?v=' + Date.now();
            }
            if (foto.startsWith('/')) {
                return baseUrl + foto + '?v=' + Date.now();
            }
            return baseUrl + '/' + foto + '?v=' + Date.now();
        }
        
        // Función helper para obtener jugador por ID
        function obtenerJugadorPorId(id) {
            return jugadores.find(function(j) {
                return j.id == id;
            });
        }
        
        // Función helper para formatear fecha
        function formatearFecha(fecha) {
            if (!fecha || fecha === '2000-01-01' || fecha === '') {
                return '';
            }
            var diasSemana = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
            var fechaObj = new Date(fecha);
            var diaSemana = diasSemana[fechaObj.getDay()];
            var diaMes = fechaObj.getDate();
            return diaSemana.charAt(0).toUpperCase() + diaSemana.slice(1) + ' ' + diaMes;
        }
        
        // Función helper para crear HTML de pareja
        function crearHTMLPareja(pareja, esPareja1) {
            if (!pareja || !pareja.jugador_1 || pareja.jugador_1 == 0) {
                return '<div class="mb-2"><div style="width:70px; height:70px; border-radius:50%; background:#f0f0f0; display:flex; align-items:center; justify-content:center; margin:0 auto; border: 2px solid #ccc;"><span style="font-size:0.7rem; color:#666;">?</span></div><div style="font-size:0.75rem; font-weight:600; margin-top:5px; color:#999;">Esperando ganador</div></div>';
            }
            
            var jugador1 = obtenerJugadorPorId(pareja.jugador_1);
            var jugador2 = obtenerJugadorPorId(pareja.jugador_2);
            var colorBorde = esPareja1 ? '#4e73df' : '#1a8917';
            
            if (!jugador1 || !jugador2) {
                return '<div class="mb-2"><div style="width:70px; height:70px; border-radius:50%; background:#f0f0f0; display:flex; align-items:center; justify-content:center; margin:0 auto; border: 2px solid #ccc;"><span style="font-size:0.7rem; color:#666;">?</span></div><div style="font-size:0.75rem; font-weight:600; margin-top:5px; color:#999;">Esperando ganador</div></div>';
            }
            
            return '<div class="mb-2">' +
                '<img src="' + getFotoUrl(jugador1.foto) + '" class="rounded-circle" style="width:70px; height:70px; object-fit:cover; border: 2px solid ' + colorBorde + ';" onerror="this.src=\'' + baseUrl + '/images/jugador_img.png?v=' + Date.now() + '\'">' +
                '<div style="font-size:0.75rem; font-weight:600; margin-top:5px;">' + jugador1.nombre + ' ' + jugador1.apellido + '</div>' +
                '</div>' +
                '<div>' +
                '<img src="' + getFotoUrl(jugador2.foto) + '" class="rounded-circle" style="width:70px; height:70px; object-fit:cover; border: 2px solid ' + colorBorde + ';" onerror="this.src=\'' + baseUrl + '/images/jugador_img.png?v=' + Date.now() + '\'">' +
                '<div style="font-size:0.75rem; font-weight:600; margin-top:5px;">' + jugador2.nombre + ' ' + jugador2.apellido + '</div>' +
                '</div>';
        }
        
        // Función helper para crear HTML completo de un partido
        function crearHTMLPartido(partidoData) {
            var partidoId = partidoData.partido_id;
            var tipo = partidoData.tipo || 'normal';
            var titulo = tipo === 'ganador' ? 'Partido 3 - Ganador' : (tipo === 'perdedor' ? 'Partido 4 - Perdedor' : 'Partido');
            var fechaFormateada = formatearFecha(partidoData.fecha);
            var horario = (partidoData.horario && partidoData.horario !== '00:00') ? partidoData.horario : '';
            
            var htmlFechaHorario = '';
            if (fechaFormateada || horario) {
                htmlFechaHorario = '<div class="text-center mb-3" style="font-size:0.85rem; color:#555;">';
                if (fechaFormateada) {
                    htmlFechaHorario += '<div><strong>Día:</strong> ' + fechaFormateada + '</div>';
                }
                if (horario) {
                    htmlFechaHorario += '<div><strong>Horario:</strong> ' + horario + '</div>';
                }
                htmlFechaHorario += '</div>';
            }
            
            var htmlPareja1 = crearHTMLPareja(partidoData.pareja_1, true);
            var htmlPareja2 = crearHTMLPareja(partidoData.pareja_2, false);
            
            // Verificar si la zona tiene 4 partidos para usar el layout correcto
            var zonaContainer = $('.zona-container[data-zona="' + zonaActual + '"]');
            var tieneCuatroPartidos = zonaContainer.find('.partidos-container-scroll').length > 0 || zonaContainer.find('.partido-item, .col-md-6').length >= 3; // Ya hay al menos 3, agregaremos el 4to
            
            var htmlPartido = '';
            if (tieneCuatroPartidos) {
                // Layout horizontal (4 partidos)
                htmlPartido = '<div class="partido-item" style="min-width: 280px; flex-shrink: 0;">' +
                    '<div class="card border" style="height: 100%;">' +
                    '<div class="card-body">';
            } else {
                // Layout normal (grid)
                htmlPartido = '<div class="col-md-6 col-lg-4 mb-4">' +
                    '<div class="card border" style="height: 100%;">' +
                    '<div class="card-body">';
            }
            
            htmlPartido += '<h5 class="card-title text-center mb-2">' + titulo + '</h5>' +
                htmlFechaHorario +
                '<div class="d-flex justify-content-around align-items-center mb-3">' +
                '<div class="text-center pareja-container pareja-1-container" data-partido-id="' + partidoId + '" style="position: relative; padding: 10px; border-radius: 8px; transition: all 0.3s;">' +
                htmlPareja1 +
                '</div>' +
                '<div class="mx-3"><h4 style="color:#dc3545; font-weight:bold;">VS</h4></div>' +
                '<div class="text-center pareja-container pareja-2-container" data-partido-id="' + partidoId + '" style="position: relative; padding: 10px; border-radius: 8px; transition: all 0.3s;">' +
                htmlPareja2 +
                '</div>' +
                '</div>' +
                '<div class="resultado-partido" data-partido-id="' + partidoId + '">' +
                '<div class="mb-2"><label style="font-size:0.8rem; font-weight:600;">Set 1</label>' +
                '<div class="d-flex justify-content-center align-items-center">' +
                '<input type="number" min="0" max="99" class="form-control form-control-sm" style="width:60px;" name="pareja_1_set_1" value="0" data-partido-id="' + partidoId + '">' +
                '<span class="mx-2">-</span>' +
                '<input type="number" min="0" max="99" class="form-control form-control-sm" style="width:60px;" name="pareja_2_set_1" value="0" data-partido-id="' + partidoId + '">' +
                '</div>' +
                '<div class="d-flex justify-content-center align-items-center mt-1">' +
                '<small style="font-size:0.7rem;">TB:</small>' +
                '<input type="number" min="0" max="99" class="form-control form-control-sm ml-1" style="width:50px;" name="pareja_1_set_1_tie_break" value="0" data-partido-id="' + partidoId + '">' +
                '<span class="mx-1">-</span>' +
                '<input type="number" min="0" max="99" class="form-control form-control-sm" style="width:50px;" name="pareja_2_set_1_tie_break" value="0" data-partido-id="' + partidoId + '">' +
                '</div></div>' +
                '<div class="mb-2"><label style="font-size:0.8rem; font-weight:600;">Set 2</label>' +
                '<div class="d-flex justify-content-center align-items-center">' +
                '<input type="number" min="0" max="99" class="form-control form-control-sm" style="width:60px;" name="pareja_1_set_2" value="0" data-partido-id="' + partidoId + '">' +
                '<span class="mx-2">-</span>' +
                '<input type="number" min="0" max="99" class="form-control form-control-sm" style="width:60px;" name="pareja_2_set_2" value="0" data-partido-id="' + partidoId + '">' +
                '</div>' +
                '<div class="d-flex justify-content-center align-items-center mt-1">' +
                '<small style="font-size:0.7rem;">TB:</small>' +
                '<input type="number" min="0" max="99" class="form-control form-control-sm ml-1" style="width:50px;" name="pareja_1_set_2_tie_break" value="0" data-partido-id="' + partidoId + '">' +
                '<span class="mx-1">-</span>' +
                '<input type="number" min="0" max="99" class="form-control form-control-sm" style="width:50px;" name="pareja_2_set_2_tie_break" value="0" data-partido-id="' + partidoId + '">' +
                '</div></div>' +
                '<div class="mb-2"><label style="font-size:0.8rem; font-weight:600;">Set 3</label>' +
                '<div class="d-flex justify-content-center align-items-center">' +
                '<input type="number" min="0" max="99" class="form-control form-control-sm" style="width:60px;" name="pareja_1_set_3" value="0" data-partido-id="' + partidoId + '">' +
                '<span class="mx-2">-</span>' +
                '<input type="number" min="0" max="99" class="form-control form-control-sm" style="width:60px;" name="pareja_2_set_3" value="0" data-partido-id="' + partidoId + '">' +
                '</div>' +
                '<div class="d-flex justify-content-center align-items-center mt-1">' +
                '<small style="font-size:0.7rem;">TB:</small>' +
                '<input type="number" min="0" max="99" class="form-control form-control-sm ml-1" style="width:50px;" name="pareja_1_set_3_tie_break" value="0" data-partido-id="' + partidoId + '">' +
                '<span class="mx-1">-</span>' +
                '<input type="number" min="0" max="99" class="form-control form-control-sm" style="width:50px;" name="pareja_2_set_3_tie_break" value="0" data-partido-id="' + partidoId + '">' +
                '</div></div>' +
                '<div class="mb-2"><label style="font-size:0.8rem; font-weight:600;">Super TB</label>' +
                '<div class="d-flex justify-content-center align-items-center">' +
                '<input type="number" min="0" max="99" class="form-control form-control-sm" style="width:60px;" name="pareja_1_set_super_tie_break" value="0" data-partido-id="' + partidoId + '">' +
                '<span class="mx-2">-</span>' +
                '<input type="number" min="0" max="99" class="form-control form-control-sm" style="width:60px;" name="pareja_2_set_super_tie_break" value="0" data-partido-id="' + partidoId + '">' +
                '</div></div>' +
                '<div class="text-center mt-3">' +
                '<button type="button" class="btn btn-sm btn-primary guardar-resultado" data-partido-id="' + partidoId + '">Guardar Resultado</button>' +
                '</div>' +
                '</div>' +
                '</div></div>';
            
            if (tieneCuatroPartidos) {
                htmlPartido += '</div>';
            } else {
                htmlPartido += '</div></div>';
            }
            
            return htmlPartido;
        }
        
        // Actualizar partido Ganador
        if (partidosActualizados.ganador) {
            var partidoGanadorId = partidosActualizados.ganador.partido_id;
            var pareja1Ganador = partidosActualizados.ganador.pareja_1;
            var pareja2Ganador = partidosActualizados.ganador.pareja_2;
            
            console.log('Actualizando partido Ganador ID:', partidoGanadorId);
            
            // Buscar el contenedor del partido Ganador en la zona actual
            var zonaContainer = $('.zona-container[data-zona="' + zonaActual + '"]');
            var partidoGanadorContainer = zonaContainer.find('.partido-item').filter(function() {
                return $(this).find('[data-partido-id="' + partidoGanadorId + '"]').length > 0;
            });
            
            if (partidoGanadorContainer.length > 0) {
                // El partido existe, actualizar solo las parejas
                if (pareja1Ganador && pareja1Ganador.jugador_1 && pareja1Ganador.jugador_1 != 0) {
                    var pareja1Container = partidoGanadorContainer.find('.pareja-1-container[data-partido-id="' + partidoGanadorId + '"]');
                    pareja1Container.empty();
                    pareja1Container.html(crearHTMLPareja(pareja1Ganador, true));
                }
                
                if (pareja2Ganador && pareja2Ganador.jugador_1 && pareja2Ganador.jugador_1 != 0) {
                    var pareja2Container = partidoGanadorContainer.find('.pareja-2-container[data-partido-id="' + partidoGanadorId + '"]');
                    pareja2Container.empty();
                    pareja2Container.html(crearHTMLPareja(pareja2Ganador, false));
                }
            } else {
                // El partido no existe, crearlo dinámicamente
                console.log('Partido Ganador no existe, creándolo dinámicamente...');
                var contenedorPartidos = null;
                
                // Buscar contenedor de partidos (puede ser .partidos-container-scroll .d-flex o .row)
                var scrollContainer = zonaContainer.find('.partidos-container-scroll');
                if (scrollContainer.length > 0) {
                    contenedorPartidos = scrollContainer.find('.d-flex').first();
                } else {
                    contenedorPartidos = zonaContainer.find('.row').first();
                }
                
                if (contenedorPartidos.length > 0) {
                    var htmlPartido = crearHTMLPartido(partidosActualizados.ganador);
                    contenedorPartidos.append(htmlPartido);
                    
                    // Si ahora tiene 4 partidos, cambiar el layout
                    var numPartidos = zonaContainer.find('.partido-item, .col-md-6').length;
                    if (numPartidos === 4 && scrollContainer.length === 0) {
                        // Reorganizar para layout horizontal
                        var partidos = zonaContainer.find('.partido-item, .col-md-6');
                        var nuevoContenedor = $('<div class="partidos-container-scroll" style="overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; margin-bottom: 1rem;"><div class="d-flex" style="min-width: max-content; gap: 1rem;"></div></div>');
                        partidos.each(function() {
                            var $this = $(this);
                            if ($this.hasClass('col-md-6')) {
                                $this.removeClass('col-md-6 col-lg-4 mb-4').addClass('partido-item').css({'min-width': '280px', 'flex-shrink': '0'});
                            }
                            nuevoContenedor.find('.d-flex').append($this);
                        });
                        contenedorPartidos.replaceWith(nuevoContenedor);
                    }
                } else {
                    console.error('No se encontró contenedor de partidos para insertar el partido Ganador');
                }
            }
        }
        
        // Actualizar partido Perdedor
        if (partidosActualizados.perdedor) {
            var partidoPerdedorId = partidosActualizados.perdedor.partido_id;
            var pareja1Perdedor = partidosActualizados.perdedor.pareja_1;
            var pareja2Perdedor = partidosActualizados.perdedor.pareja_2;
            
            console.log('Actualizando partido Perdedor ID:', partidoPerdedorId);
            
            // Buscar el contenedor del partido Perdedor en la zona actual
            var zonaContainer = $('.zona-container[data-zona="' + zonaActual + '"]');
            var partidoPerdedorContainer = zonaContainer.find('.partido-item, .col-md-6').filter(function() {
                return $(this).find('[data-partido-id="' + partidoPerdedorId + '"]').length > 0;
            });
            
            if (partidoPerdedorContainer.length > 0) {
                // El partido existe, actualizar solo las parejas
                if (pareja1Perdedor && pareja1Perdedor.jugador_1 && pareja1Perdedor.jugador_1 != 0) {
                    var pareja1Container = partidoPerdedorContainer.find('.pareja-1-container[data-partido-id="' + partidoPerdedorId + '"]');
                    pareja1Container.empty();
                    pareja1Container.html(crearHTMLPareja(pareja1Perdedor, true));
                }
                
                if (pareja2Perdedor && pareja2Perdedor.jugador_1 && pareja2Perdedor.jugador_1 != 0) {
                    var pareja2Container = partidoPerdedorContainer.find('.pareja-2-container[data-partido-id="' + partidoPerdedorId + '"]');
                    pareja2Container.empty();
                    pareja2Container.html(crearHTMLPareja(pareja2Perdedor, false));
                }
            } else {
                // El partido no existe, crearlo dinámicamente
                console.log('Partido Perdedor no existe, creándolo dinámicamente...');
                var contenedorPartidos = null;
                
                // Buscar contenedor de partidos (puede ser .partidos-container-scroll .d-flex o .row)
                var scrollContainer = zonaContainer.find('.partidos-container-scroll');
                if (scrollContainer.length > 0) {
                    contenedorPartidos = scrollContainer.find('.d-flex').first();
                } else {
                    contenedorPartidos = zonaContainer.find('.row').first();
                }
                
                if (contenedorPartidos.length > 0) {
                    var htmlPartido = crearHTMLPartido(partidosActualizados.perdedor);
                    contenedorPartidos.append(htmlPartido);
                    
                    // Si ahora tiene 4 partidos, cambiar el layout
                    var numPartidos = zonaContainer.find('.partido-item, .col-md-6').length;
                    if (numPartidos === 4 && scrollContainer.length === 0) {
                        // Reorganizar para layout horizontal
                        var partidos = zonaContainer.find('.partido-item, .col-md-6');
                        var nuevoContenedor = $('<div class="partidos-container-scroll" style="overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; margin-bottom: 1rem;"><div class="d-flex" style="min-width: max-content; gap: 1rem;"></div></div>');
                        partidos.each(function() {
                            var $this = $(this);
                            if ($this.hasClass('col-md-6')) {
                                $this.removeClass('col-md-6 col-lg-4 mb-4').addClass('partido-item').css({'min-width': '280px', 'flex-shrink': '0'});
                            }
                            nuevoContenedor.find('.d-flex').append($this);
                        });
                        contenedorPartidos.replaceWith(nuevoContenedor);
                    }
                } else {
                    console.error('No se encontró contenedor de partidos para insertar el partido Perdedor');
                }
            }
        }
        
        console.log('✓ Partidos de Ganador/Perdedor actualizados dinámicamente');
    }
    
    // Aplicar ganador al cargar la página si ya hay resultados
    $('.resultado-partido').each(function() {
        var partidoId = $(this).data('partido-id');
        var resultadoPartido = $(this);
        determinarGanador(partidoId, resultadoPartido);
    });
    
    // Función para verificar si todos los partidos están completos y calcular clasificación
    function verificarYCalcularClasificacion() {
        var torneoId = $('#torneo_id').val();
        var zona = zonas[zonaIndex];
        
        // Ocultar clasificación mientras se verifica
        $('#seccion-clasificacion').hide();
        $('#contenedor-podio').empty();
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("verificarpartidoscompletos") }}',
            data: {
                torneo_id: torneoId,
                zona: zona,
                _token: '{{csrf_token()}}'
            },
            success: function(data) {
                if (data.success && data.todos_completos) {
                    // Todos los partidos están completos, calcular clasificación
                    calcularClasificacion();
                } else {
                    // No todos los partidos están completos, ocultar clasificación
                    $('#seccion-clasificacion').hide();
                }
            },
            error: function() {
                console.log('Error al verificar partidos completos');
                $('#seccion-clasificacion').hide();
            }
        });
    }
    
    // Función para calcular clasificación
    function calcularClasificacion() {
        var torneoId = $('#torneo_id').val();
        var zona = zonas[zonaIndex];
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("calcularposicioneszona") }}',
            data: {
                torneo_id: torneoId,
                zona: zona,
                _token: '{{csrf_token()}}'
            },
            success: function(data) {
                if (data.success && data.posiciones) {
                    mostrarPodio(data.posiciones, zona);
                }
            },
            error: function() {
                console.log('Error al calcular las posiciones');
            }
        });
    }
    
    // Calcular posiciones manualmente (botón opcional, puede ocultarse)
    $('#btn-calcular-posiciones').on('click', function() {
        calcularClasificacion();
    });
    
    // Función para mostrar el podio
    function mostrarPodio(posiciones, zona) {
        @php
            $jugadoresArray = [];
            foreach($jugadores as $j) {
                $jugadoresArray[] = [
                    'id' => $j->id,
                    'nombre' => $j->nombre ?? '',
                    'apellido' => $j->apellido ?? '',
                    'foto' => $j->foto ?? asset('images/jugador_img.png')
                ];
            }
        @endphp
        var jugadores = @json($jugadoresArray);
        
        var contenedor = $('#contenedor-podio');
        contenedor.empty();
        
        $('#zona-clasificacion-label').text(zona);
        $('#seccion-clasificacion').show();
        
        // Mostrar las 3 primeras posiciones
        var podios = [
            { pos: 1, clase: 'gold', icono: '🥇', titulo: '1º Lugar' },
            { pos: 2, clase: 'silver', icono: '🥈', titulo: '2º Lugar' },
            { pos: 3, clase: 'bronze', icono: '🥉', titulo: '3º Lugar' }
        ];
        
        // Mostrar podio de izquierda a derecha: 1º, 2º, 3º
        podios.forEach(function(podio) {
            if (posiciones[podio.pos - 1]) {
                var pareja = posiciones[podio.pos - 1];
                var jugador1 = jugadores.find(j => j.id == pareja.jugador_1);
                var jugador2 = jugadores.find(j => j.id == pareja.jugador_2);
                
                var html = `
                    <div class="col-md-4">
                        <div class="card text-center border-${podio.clase}" style="border-width: 3px !important; height: 100%;">
                            <div class="card-body">
                                <h4 class="mb-3">${podio.icono} ${podio.titulo}</h4>
                                <div class="d-flex justify-content-center align-items-center mb-2">
                                    ${jugador1 ? `
                                    <div class="text-center mx-2">
                                        <img src="${jugador1.foto}" class="rounded-circle" style="width:80px; height:80px; object-fit:cover; border: 3px solid #${podio.clase === 'gold' ? 'FFD700' : podio.clase === 'silver' ? 'C0C0C0' : 'CD7F32'};">
                                        <div style="font-size:0.9rem; font-weight:600; margin-top:5px;">
                                            ${jugador1.nombre} ${jugador1.apellido}
                                        </div>
                                    </div>
                                    ` : ''}
                                    ${jugador2 ? `
                                    <div class="text-center mx-2">
                                        <img src="${jugador2.foto}" class="rounded-circle" style="width:80px; height:80px; object-fit:cover; border: 3px solid #${podio.clase === 'gold' ? 'FFD700' : podio.clase === 'silver' ? 'C0C0C0' : 'CD7F32'};">
                                        <div style="font-size:0.9rem; font-weight:600; margin-top:5px;">
                                            ${jugador2.nombre} ${jugador2.apellido}
                                        </div>
                                    </div>
                                    ` : ''}
                                </div>
                                <div style="font-size:0.85rem; color:#555;">
                                    <div>Puntos: ${pareja.puntos || 0}</div>
                                    <div>Partidos: ${pareja.partidos_ganados || 0} - ${pareja.partidos_perdidos || 0}</div>
                                    <div>Sets: ${pareja.sets_ganados || 0} - ${pareja.sets_perdidos || 0}</div>
                                    <div>Juegos: ${pareja.juegos_ganados || 0} - ${pareja.juegos_perdidos || 0}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                contenedor.append(html);
            }
        });
        
        // Scroll suave hacia la clasificación
        $('html, body').animate({
            scrollTop: $('#seccion-clasificacion').offset().top - 100
        }, 500);
    }
});
</script>

@endsection

