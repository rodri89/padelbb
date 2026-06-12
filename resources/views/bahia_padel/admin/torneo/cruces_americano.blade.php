@extends('bahia_padel/admin/plantilla')

@section('title_header','Cruces Eliminatorios - Torneo Americano')

@section('contenedor')
<link rel="stylesheet" href="{{ asset('css/bracket.css') }}">
<link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}">

<div class="bracket-container">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button type="button" class="btn btn-secondary" id="btn-volver-clasificacion">
                        ← Volver a Clasificación
                    </button>
                    
                    <h2 class="text-center flex-grow-1 mb-0" style="color: #000;">{{ $torneo->nombre ?? 'Torneo' }}</h2>
                    
                    <div class="d-flex align-items-center">
                        <a href="{{ route('tvtorneoamericanocruces') }}?torneo_id={{ $torneo->id }}" target="_blank" class="btn btn-primary ml-2">
                            <i class="fa fa-desktop"></i> TV
                        </a>
                        <a href="{{ route('tvtorneosrotacion') }}?torneos={{ $torneo->id }}&intervalo=60" target="_blank" class="btn btn-info ml-2">
                            <i class="fa fa-tv"></i> Rotación
                        </a>
                    </div>
                </div>
                <!-- Hack removed, clearer structure -->
                <input type="hidden" id="torneo_id" value="{{ $torneo->id ?? 0 }}">
            </div>
        </div>
        
        <div class="row">
            <!-- Octavos de Final -->
            @php
                $tieneOctavos = $tieneOctavos ?? false;
                $octavosAgrupados = [];
                \Log::info('Total cruces recibidos en vista: ' . count($cruces));
                foreach($cruces as $index => $cruce) {
                    \Log::info('Cruce ' . $index . ': ronda=' . ($cruce['ronda'] ?? 'NO DEFINIDA') . ', partido_id=' . ($cruce['partido_id'] ?? 'NO DEFINIDO'));
                    if(isset($cruce['ronda']) && $cruce['ronda'] == 'octavos') {
                        $tieneOctavos = true;
                        $partidoId = $cruce['partido_id'] ?? null;
                        if($partidoId) {
                            if(!isset($octavosAgrupados[$partidoId])) {
                                $octavosAgrupados[$partidoId] = $cruce;
                            }
                        } else {
                            $octavosAgrupados['sin_partido_' . $index] = $cruce;
                        }
                    }
                }
                \Log::info('Octavos agrupados: ' . count($octavosAgrupados) . ', tieneOctavos=' . ($tieneOctavos ? 'true' : 'false'));
            @endphp
            @if($tieneOctavos)
            <div class="col-md-3" id="octavos-container">
                <div class="bracket-round">
                    <div class="bracket-round-title">OCTAVOS DE FINAL</div>
                    <div id="octavos-content">
                        @foreach($octavosAgrupados as $partidoId => $cruce)
                            @php
                                $jugador1_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                                $jugador1_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                                $jugador2_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                                $jugador2_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                            @endphp
                            <div class="match-card" data-cruce-id="{{ $cruce['id'] }}" data-ronda="octavos" data-partido-id="{{ $partidoId }}">
                                <!-- Pareja 1 -->
                                <div class="player-pair pareja-cruce" 
                                     data-pareja="1"
                                     data-jugador-1="{{ $cruce['pareja_1']['jugador_1'] }}"
                                     data-jugador-2="{{ $cruce['pareja_1']['jugador_2'] }}">
                                    <div class="player-pair-content">
                                        <div class="player-images">
                                            <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}?v={{ time() }}" alt="{{ $jugador1_1->nombre ?? '' }}" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                            <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}?v={{ time() }}" alt="{{ $jugador1_2->nombre ?? '' }}" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                        </div>
                                        <div class="player-names">
                                            <div class="player-name">{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}</div>
                                            <div class="player-name">{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}</div>
                                        </div>
                                        @if(isset($cruce['pareja_1']['zona']) && isset($cruce['pareja_1']['posicion']))
                                            <span class="badge badge-info">{{ $cruce['pareja_1']['zona'] }}{{ $cruce['pareja_1']['posicion'] }}º</span>
                                        @endif
                                    </div>
                                    <div class="player-pair-input">
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-ronda="octavos"
                                               min="0"
                                               max="99"
                                               placeholder="0">
                                    </div>
                                </div>
                                
                                <!-- Pareja 2 -->
                                <div class="player-pair pareja-cruce" 
                                     data-pareja="2"
                                     data-jugador-1="{{ $cruce['pareja_2']['jugador_1'] }}"
                                     data-jugador-2="{{ $cruce['pareja_2']['jugador_2'] }}">
                                    <div class="player-pair-content">
                                        <div class="player-images">
                                            <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}?v={{ time() }}" alt="{{ $jugador2_1->nombre ?? '' }}" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                            <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}?v={{ time() }}" alt="{{ $jugador2_2->nombre ?? '' }}" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                        </div>
                                        <div class="player-names">
                                            <div class="player-name">{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}</div>
                                            <div class="player-name">{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}</div>
                                        </div>
                                        @if(isset($cruce['pareja_2']['zona']) && isset($cruce['pareja_2']['posicion']))
                                            <span class="badge badge-info">{{ $cruce['pareja_2']['zona'] }}{{ $cruce['pareja_2']['posicion'] }}º</span>
                                        @endif
                                    </div>
                                    <div class="player-pair-input">
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-ronda="octavos"
                                               min="0"
                                               max="99"
                                               placeholder="0">
                                    </div>
                                </div>
                                
                                <!-- Botón guardar -->
                                <div class="text-center mt-2">
                                    <button type="button" 
                                            class="btn btn-primary btn-sm guardar-cruce" 
                                            data-cruce-id="{{ $cruce['id'] }}"
                                            data-ronda="octavos">
                                        Guardar
                                    </button>
                                </div>
                            </div>
                        @endforeach
                        @if(count($octavosAgrupados) == 0)
                            <p class="text-center text-muted p-3">No hay cruces de octavos de final</p>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Cuartos de Final -->
            <div class="col-md-3">
                <div class="bracket-round">
                    <div class="bracket-round-title">CUARTOS DE FINAL</div>
                    @foreach($cruces as $index => $cruce)
                        @if($cruce['ronda'] == 'cuartos')
                            @php
                                $jugador1_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                                $jugador1_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                                $jugador2_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                                $jugador2_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                                
                            @endphp
                            <div class="match-card" data-cruce-id="{{ $index }}" data-ronda="cuartos">
                                <!-- Pareja 1 -->
                                <div class="player-pair pareja-cruce" 
                                     data-pareja="1"
                                     data-jugador-1="{{ $cruce['pareja_1']['jugador_1'] }}"
                                     data-jugador-2="{{ $cruce['pareja_1']['jugador_2'] }}">
                                    <div class="player-pair-content">
                                        <div class="player-images">
                                            <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}?v={{ time() }}" alt="{{ $jugador1_1->nombre ?? '' }}" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                            <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}?v={{ time() }}" alt="{{ $jugador1_2->nombre ?? '' }}" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                        </div>
                                        <div class="player-names">
                                            <div class="player-name">{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}</div>
                                            <div class="player-name">{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}</div>
                                        </div>
                                        <span class="badge badge-info">{{ $cruce['pareja_1']['zona'] }}{{ $cruce['pareja_1']['posicion'] }}º</span>
                                    </div>
                                    <div class="player-pair-input">
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $index }}"
                                               data-pareja="1"
                                               data-ronda="cuartos"
                                               min="0"
                                               max="99"
                                               placeholder="0">
                                    </div>
                                </div>
                                
                                <!-- Pareja 2 -->
                                <div class="player-pair pareja-cruce" 
                                     data-pareja="2"
                                     data-jugador-1="{{ $cruce['pareja_2']['jugador_1'] }}"
                                     data-jugador-2="{{ $cruce['pareja_2']['jugador_2'] }}">
                                    <div class="player-pair-content">
                                        <div class="player-images">
                                            <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}?v={{ time() }}" alt="{{ $jugador2_1->nombre ?? '' }}" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                            <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}?v={{ time() }}" alt="{{ $jugador2_2->nombre ?? '' }}" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                        </div>
                                        <div class="player-names">
                                            <div class="player-name">{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}</div>
                                            <div class="player-name">{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}</div>
                                        </div>
                                        <span class="badge badge-info">{{ $cruce['pareja_2']['zona'] }}{{ $cruce['pareja_2']['posicion'] }}º</span>
                                    </div>
                                    <div class="player-pair-input">
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $index }}"
                                               data-pareja="2"
                                               data-ronda="cuartos"
                                               min="0"
                                               max="99"
                                               placeholder="0">
                                    </div>
                                </div>
                                
                                <!-- Botón guardar -->
                                <div class="text-center mt-2 d-flex align-items-center justify-content-center">
                                    <input type="hidden" 
                                           id="semifinal-input-{{ $index }}" 
                                           data-cruce-id="{{ $index }}"
                                           data-ronda="cuartos"
                                           value="{{ $loop->iteration <= 2 ? 'Semifinal 1' : 'Semifinal 2' }}">
                                    <button type="button" 
                                            class="btn btn-primary btn-sm guardar-cruce" 
                                            data-cruce-id="{{ $index }}" 
                                            data-ronda="cuartos">
                                        Guardar
                                    </button>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            
            <!-- Semifinales -->
            <div class="col-md-3">
                <div class="bracket-round" id="semifinales-container">
                    <div class="bracket-round-title">SEMIFINALES</div>
                    <div id="semifinales-content">
                        @php
                            // Agrupar cruces de semifinales por partido_id para evitar duplicados
                            $semifinalesAgrupadas = [];
                            foreach($cruces as $index => $cruce) {
                                if($cruce['ronda'] == 'semifinales') {
                                    $partidoId = $cruce['partido_id'] ?? null;
                                    if($partidoId) {
                                        // Si ya existe un cruce con este partido_id, no agregarlo de nuevo
                                        if(!isset($semifinalesAgrupadas[$partidoId])) {
                                            $semifinalesAgrupadas[$partidoId] = $cruce;
                                        }
                                    } else {
                                        // Si no tiene partido_id, usar el índice como clave
                                        $semifinalesAgrupadas['sin_partido_' . $index] = $cruce;
                                    }
                                }
                            }
                        @endphp
                        @foreach($semifinalesAgrupadas as $partidoId => $cruce)
                            @php
                                $jugador1_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                                $jugador1_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                                $jugador2_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                                $jugador2_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                            @endphp
                            <div class="match-card" data-cruce-id="{{ $cruce['id'] }}" data-ronda="semifinales" data-partido-id="{{ $partidoId }}">
                                <!-- Pareja 1 -->
                                <div class="player-pair pareja-cruce" 
                                     data-pareja="1"
                                     data-jugador-1="{{ $cruce['pareja_1']['jugador_1'] }}"
                                     data-jugador-2="{{ $cruce['pareja_1']['jugador_2'] }}">
                                    <div class="player-pair-content">
                                        <div class="player-images">
                                            <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}?v={{ time() }}" alt="{{ $jugador1_1->nombre ?? '' }}" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                            <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}?v={{ time() }}" alt="{{ $jugador1_2->nombre ?? '' }}" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                        </div>
                                        <div class="player-names">
                                            <div class="player-name">{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}</div>
                                            <div class="player-name">{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}</div>
                                        </div>
                                        @if(isset($cruce['pareja_1']['zona']) && isset($cruce['pareja_1']['posicion']))
                                            <span class="badge badge-info">{{ $cruce['pareja_1']['zona'] }}{{ $cruce['pareja_1']['posicion'] }}º</span>
                                        @endif
                                    </div>
                                    <div class="player-pair-input">
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-ronda="semifinales"
                                               min="0"
                                               max="99"
                                               placeholder="0">
                                    </div>
                                </div>
                                
                                <!-- Pareja 2 -->
                                <div class="player-pair pareja-cruce" 
                                     data-pareja="2"
                                     data-jugador-1="{{ $cruce['pareja_2']['jugador_1'] }}"
                                     data-jugador-2="{{ $cruce['pareja_2']['jugador_2'] }}">
                                    <div class="player-pair-content">
                                        <div class="player-images">
                                            <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}?v={{ time() }}" alt="{{ $jugador2_1->nombre ?? '' }}" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                            <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}?v={{ time() }}" alt="{{ $jugador2_2->nombre ?? '' }}" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                        </div>
                                        <div class="player-names">
                                            <div class="player-name">{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}</div>
                                            <div class="player-name">{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}</div>
                                        </div>
                                        @if(isset($cruce['pareja_2']['zona']) && isset($cruce['pareja_2']['posicion']))
                                            <span class="badge badge-info">{{ $cruce['pareja_2']['zona'] }}{{ $cruce['pareja_2']['posicion'] }}º</span>
                                        @endif
                                    </div>
                                    <div class="player-pair-input">
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-ronda="semifinales"
                                               min="0"
                                               max="99"
                                               placeholder="0">
                                    </div>
                                </div>
                                
                                <!-- Botón guardar -->
                                <div class="text-center mt-2">
                                    <button type="button" 
                                            class="btn btn-primary btn-sm guardar-cruce" 
                                            data-cruce-id="{{ $cruce['id'] }}"
                                            data-ronda="semifinales">
                                        Guardar
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <!-- Final -->
            <div class="col-md-3">
                <div class="bracket-round" id="final-container">
                    <div class="bracket-round-title">FINAL</div>
                    <div id="final-content">
                        @foreach($cruces as $index => $cruce)
                            @if($cruce['ronda'] == 'final')
                                @php
                                    $jugador1_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                                    $jugador1_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                                    $jugador2_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                                    $jugador2_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                                @endphp
                                <div class="match-card" data-cruce-id="{{ $cruce['id'] ?? $index }}" data-ronda="final">
                                    <!-- Pareja 1 -->
                                    <div class="player-pair pareja-cruce" 
                                         data-pareja="1"
                                         data-jugador-1="{{ $cruce['pareja_1']['jugador_1'] }}"
                                         data-jugador-2="{{ $cruce['pareja_1']['jugador_2'] }}">
                                        <div class="player-pair-content">
                                            <div class="player-images">
                                                <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador1_1->nombre ?? '' }}">
                                                <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador1_2->nombre ?? '' }}">
                                            </div>
                                            <div class="player-names">
                                                <div class="player-name">{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}</div>
                                                <div class="player-name">{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}</div>
                                            </div>
                                            @if(isset($cruce['pareja_1']['zona']) && isset($cruce['pareja_1']['posicion']))
                                                <span class="badge badge-info">{{ $cruce['pareja_1']['zona'] }}{{ $cruce['pareja_1']['posicion'] }}º</span>
                                            @endif
                                        </div>
                                        <div class="player-pair-input">
                                            <input type="number" 
                                                   class="form-control resultado-cruce" 
                                                   data-cruce-id="{{ $cruce['id'] ?? $index }}"
                                                   data-pareja="1"
                                                   data-ronda="final"
                                                   min="0"
                                                   max="99"
                                                   placeholder="0">
                                        </div>
                                    </div>
                                    
                                    <!-- Pareja 2 -->
                                    <div class="player-pair pareja-cruce" 
                                         data-pareja="2"
                                         data-jugador-1="{{ $cruce['pareja_2']['jugador_1'] }}"
                                         data-jugador-2="{{ $cruce['pareja_2']['jugador_2'] }}">
                                        <div class="player-pair-content">
                                            <div class="player-images">
                                                <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador2_1->nombre ?? '' }}">
                                                <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador2_2->nombre ?? '' }}">
                                            </div>
                                            <div class="player-names">
                                                <div class="player-name">{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}</div>
                                                <div class="player-name">{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}</div>
                                            </div>
                                            @if(isset($cruce['pareja_2']['zona']) && isset($cruce['pareja_2']['posicion']))
                                                <span class="badge badge-info">{{ $cruce['pareja_2']['zona'] }}{{ $cruce['pareja_2']['posicion'] }}º</span>
                                            @endif
                                        </div>
                                        <div class="player-pair-input">
                                            <input type="number" 
                                                   class="form-control resultado-cruce" 
                                                   data-cruce-id="{{ $cruce['id'] ?? $index }}"
                                                   data-pareja="2"
                                                   data-ronda="final"
                                                   min="0"
                                                   max="99"
                                                   placeholder="0">
                                        </div>
                                    </div>
                                    
                                    <!-- Botón guardar -->
                                    <div class="text-center mt-2">
                                        <button type="button" 
                                                class="btn btn-primary btn-sm guardar-cruce" 
                                                data-cruce-id="{{ $cruce['id'] ?? $index }}"
                                                data-ronda="final">
                                            Guardar
                                        </button>
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

<!-- Snackbar -->
<div id="snackbar" class="snackbar">Resultado guardado correctamente</div>

<!-- Modal de Ganadores -->
<div id="modal-ganadores" class="modal-ganadores">
    <div class="modal-content-ganadores">
        <h2>🏆 ¡GANADORES! 🏆</h2>
        <div class="ganadores-fotos" id="ganadores-fotos">
            <!-- Se llenará dinámicamente -->
        </div>
        <button type="button" class="btn-cerrar-modal" onclick="cerrarModalGanadores()">Cerrar</button>
    </div>
</div>

<script type="text/javascript">
    // Función para mostrar snackbar
    function mostrarSnackbar(mensaje) {
        let snackbar = document.getElementById("snackbar");
        snackbar.textContent = mensaje;
        snackbar.className = "snackbar show";
        setTimeout(function(){ snackbar.className = snackbar.className.replace("show", ""); }, 3000);
    }
    
    // Botón volver a clasificación
    $('#btn-volver-clasificacion').on('click', function() {
        let torneoId = $('#torneo_id').val();
        window.location.href = '{{ route("admintorneoamericanopartidos") }}?torneo_id=' + torneoId;
    });
    
    let cruces = @json($cruces ?? []);
    let cuartosCompletos = @json($cuartosCompletos ?? false);
    let jugadores = @json($jugadores ?? []);
    let resultadosGuardados = @json($resultadosGuardados ?? []);
    let primerosClasificados = @json($primerosClasificados ?? []);
    let totalClasificados = {{ $totalClasificados ?? 0 }};
    let torneoId = $('#torneo_id').val();
    let baseUrl = '{{ url("/") }}';
    let resultadosOctavos = {};
    let resultadosCuartos = {};
    let resultadosSemifinales = {};
    
    // Función helper para obtener URL de foto con timestamp para evitar caché
    function getFotoUrlWithCache(foto) {
        if (!foto || foto === '') {
            return baseUrl + '/images/jugador_img.png?v=' + Date.now();
        }
        // Si ya es una URL completa, agregar timestamp
        if (foto.startsWith('http://') || foto.startsWith('https://')) {
            return foto + (foto.indexOf('?') > -1 ? '&' : '?') + 'v=' + Date.now();
        }
        // Construir URL completa con timestamp
        let ruta = foto.startsWith('/') ? foto.substring(1) : foto;
        return baseUrl + '/' + ruta + (ruta.indexOf('?') > -1 ? '&' : '?') + 'v=' + Date.now();
    }
    let resultadoFinal = null;
    
    // Cargar resultados guardados al iniciar
    function cargarResultadosGuardados() {
        // Primero cargar resultados de cuartos
        resultadosGuardados.forEach(function(resultado) {
            if (resultado.ronda === 'cuartos') {
                // Buscar el cruce de cuartos que coincida con estas parejas
                let cruceIndex = cruces.findIndex(function(c) {
                    if (c.ronda !== 'cuartos') return false;
                    let p1 = c.pareja_1;
                    let p2 = c.pareja_2;
                    return (p1.jugador_1 == resultado.pareja_1_jugador_1 && p1.jugador_2 == resultado.pareja_1_jugador_2 &&
                            p2.jugador_1 == resultado.pareja_2_jugador_1 && p2.jugador_2 == resultado.pareja_2_jugador_2) ||
                           (p1.jugador_1 == resultado.pareja_2_jugador_1 && p1.jugador_2 == resultado.pareja_2_jugador_2 &&
                            p2.jugador_1 == resultado.pareja_1_jugador_1 && p2.jugador_2 == resultado.pareja_1_jugador_2);
                });
                
                if (cruceIndex !== -1) {
                    // Cargar valores en los inputs
                    $(`.resultado-cruce[data-cruce-id="${cruceIndex}"][data-ronda="cuartos"]`).each(function() {
                        let pareja = $(this).data('pareja');
                        if (pareja == 1) {
                            $(this).val(resultado.pareja_1_set_1);
                        } else if (pareja == 2) {
                            $(this).val(resultado.pareja_2_set_1);
                        }
                    });
                    
                    // Guardar resultado localmente
                    let cruce = cruces[cruceIndex];
                    let ganador = resultado.pareja_1_set_1 > resultado.pareja_2_set_1 ? cruce.pareja_1 : cruce.pareja_2;
                    resultadosCuartos[cruceIndex] = {
                        ganador: ganador,
                        perdedor: resultado.pareja_1_set_1 > resultado.pareja_2_set_1 ? cruce.pareja_2 : cruce.pareja_1,
                        score1: resultado.pareja_1_set_1,
                        score2: resultado.pareja_2_set_1
                    };
                    
                    // Marcar visualmente
                    let matchCard = $(`.match-card[data-cruce-id="${cruceIndex}"]`);
                    matchCard.addClass('winner');
                    if (resultado.pareja_1_set_1 > resultado.pareja_2_set_1) {
                        matchCard.find('.pareja-cruce[data-pareja="1"]').addClass('winner');
                    } else {
                        matchCard.find('.pareja-cruce[data-pareja="2"]').addClass('winner');
                    }
                }
            }
        });
        
        // Actualizar semifinales y final con los resultados guardados de cuartos
        verificarAvance();
        
        // Cargar resultados de semifinales y final después de que se generen
        setTimeout(function() {
            resultadosGuardados.forEach(function(resultado) {
                if (resultado.ronda === 'semifinales' || resultado.ronda === 'final') {
                    // Buscar el cruce que coincida por parejas o por cruce_id
                    let cruce = null;
                    if (resultado.cruce_id) {
                        // Si hay cruce_id, buscar por ID primero
                        cruce = cruces.find(c => c.id === resultado.cruce_id && c.ronda === resultado.ronda);
                    }
                    
                    // Si no se encontró por ID, buscar por parejas
                    if (!cruce) {
                        cruce = cruces.find(function(c) {
                            if (c.ronda !== resultado.ronda) return false;
                            if (!c.pareja_1 || !c.pareja_2) return false;
                            let p1 = c.pareja_1;
                            let p2 = c.pareja_2;
                            return (p1.jugador_1 == resultado.pareja_1_jugador_1 && p1.jugador_2 == resultado.pareja_1_jugador_2 &&
                                    p2.jugador_1 == resultado.pareja_2_jugador_1 && p2.jugador_2 == resultado.pareja_2_jugador_2) ||
                                   (p1.jugador_1 == resultado.pareja_2_jugador_1 && p1.jugador_2 == resultado.pareja_2_jugador_2 &&
                                    p2.jugador_1 == resultado.pareja_1_jugador_1 && p2.jugador_2 == resultado.pareja_1_jugador_2);
                        });
                    }
                    
                    if (cruce) {
                        let cruceId = cruce.id;
                        // Buscar inputs por cruce_id o por parejas
                        $(`.resultado-cruce[data-ronda="${resultado.ronda}"]`).each(function() {
                            let inputCruceId = $(this).data('cruce-id');
                            let pareja = $(this).data('pareja');
                            
                            // Si el cruce_id coincide o si no hay cruce_id pero las parejas coinciden
                            if (inputCruceId === cruceId || (!inputCruceId && cruceId)) {
                                // Verificar que las parejas del input coincidan con el resultado
                                let matchCard = $(this).closest('.match-card');
                                if (matchCard.length > 0) {
                                    if (pareja == 1) {
                                        $(this).val(resultado.pareja_1_set_1);
                                    } else if (pareja == 2) {
                                        $(this).val(resultado.pareja_2_set_1);
                                    }
                                }
                            }
                        });
                        
                        // También buscar por atributo data-cruce-id específico
                        $(`.resultado-cruce[data-cruce-id="${cruceId}"][data-ronda="${resultado.ronda}"]`).each(function() {
                            let pareja = $(this).data('pareja');
                            if (pareja == 1) {
                                $(this).val(resultado.pareja_1_set_1);
                            } else if (pareja == 2) {
                                $(this).val(resultado.pareja_2_set_1);
                            }
                        });
                        
                        // Guardar resultado localmente y aplicar estilo de ganador
                        if (resultado.ronda === 'octavos') {
                            resultadosOctavos[cruceId] = {
                                ganador: resultado.pareja_1_set_1 > resultado.pareja_2_set_1 ? cruce.pareja_1 : cruce.pareja_2,
                                perdedor: resultado.pareja_1_set_1 > resultado.pareja_2_set_1 ? cruce.pareja_2 : cruce.pareja_1,
                                score1: resultado.pareja_1_set_1,
                                score2: resultado.pareja_2_set_1
                            };
                            
                            // Aplicar estilo de ganador
                            let matchCard = $(`.match-card[data-cruce-id="${cruceId}"][data-ronda="octavos"]`);
                            if (matchCard.length > 0) {
                                matchCard.addClass('winner');
                                matchCard.find('.player-pair').removeClass('winner');
                                if (resultado.pareja_1_set_1 > resultado.pareja_2_set_1) {
                                    matchCard.find('.player-pair[data-pareja="1"]').addClass('winner');
                                } else {
                                    matchCard.find('.player-pair[data-pareja="2"]').addClass('winner');
                                }
                            }
                        } else if (resultado.ronda === 'semifinales') {
                            let ganador = resultado.pareja_1_set_1 > resultado.pareja_2_set_1 ? cruce.pareja_1 : cruce.pareja_2;
                            resultadosSemifinales[cruceId] = {
                                ganador: ganador,
                                perdedor: resultado.pareja_1_set_1 > resultado.pareja_2_set_1 ? cruce.pareja_2 : cruce.pareja_1,
                                score1: resultado.pareja_1_set_1,
                                score2: resultado.pareja_2_set_1
                            };
                            
                            // Buscar el match-card usando el cruceId y la ronda
                            // Intentar múltiples formas de encontrar el match-card
                            let matchCard = $(`.match-card[data-cruce-id="${cruceId}"][data-ronda="semifinales"]`);
                            if (matchCard.length === 0) {
                                matchCard = $(`.match-card[data-cruce-id="${cruceId}"]`);
                            }
                            if (matchCard.length === 0) {
                                // Buscar por ronda y luego verificar el cruceId
                                $(`.match-card[data-ronda="semifinales"]`).each(function() {
                                    if ($(this).data('cruce-id') == cruceId) {
                                        matchCard = $(this);
                                        return false; // break
                                    }
                                });
                            }
                            
                            if (matchCard.length > 0) {
                                matchCard.addClass('winner');
                                // Remover winner de todas las parejas primero
                                matchCard.find('.player-pair').removeClass('winner');
                                // Agregar winner a la pareja ganadora
                                if (resultado.pareja_1_set_1 > resultado.pareja_2_set_1) {
                                    matchCard.find('.player-pair[data-pareja="1"]').addClass('winner');
                                } else {
                                    matchCard.find('.player-pair[data-pareja="2"]').addClass('winner');
                                }
                            } else {
                                console.warn('No se encontró match-card para semifinal con cruceId:', cruceId);
                            }
                        } else if (resultado.ronda === 'final') {
                            let ganador = resultado.pareja_1_set_1 > resultado.pareja_2_set_1 ? cruce.pareja_1 : cruce.pareja_2;
                            resultadoFinal = {
                                ganador: ganador,
                                perdedor: resultado.pareja_1_set_1 > resultado.pareja_2_set_1 ? cruce.pareja_2 : cruce.pareja_1,
                                score1: resultado.pareja_1_set_1,
                                score2: resultado.pareja_2_set_1
                            };
                            
                            // Buscar el match-card usando el cruceId y la ronda
                            // Intentar múltiples formas de encontrar el match-card
                            let matchCard = $(`.match-card[data-cruce-id="${cruceId}"][data-ronda="final"]`);
                            if (matchCard.length === 0) {
                                matchCard = $(`.match-card[data-cruce-id="${cruceId}"]`);
                            }
                            if (matchCard.length === 0) {
                                // Buscar por ronda y luego verificar el cruceId
                                $(`.match-card[data-ronda="final"]`).each(function() {
                                    if ($(this).data('cruce-id') == cruceId) {
                                        matchCard = $(this);
                                        return false; // break
                                    }
                                });
                            }
                            
                            if (matchCard.length > 0) {
                                matchCard.addClass('winner');
                                // Remover winner de todas las parejas primero
                                matchCard.find('.player-pair').removeClass('winner');
                                // Agregar winner a la pareja ganadora
                                if (resultado.pareja_1_set_1 > resultado.pareja_2_set_1) {
                                    matchCard.find('.player-pair[data-pareja="1"]').addClass('winner');
                                } else {
                                    matchCard.find('.player-pair[data-pareja="2"]').addClass('winner');
                                }
                            } else {
                                console.warn('No se encontró match-card para final con cruceId:', cruceId);
                            }
                        }
                    }
                }
            });
            
            // Verificar avance después de cargar todos los resultados
            verificarAvance();
        }, 1000);
    }
    
    // Convertir jugadores a objeto keyed por id
    let jugadoresObj = {};
    jugadores.forEach(function(j) {
        jugadoresObj[j.id] = j;
    });
    
    function obtenerJugadorPorId(id) {
        return jugadoresObj[id] || null;
    }
    
    function renderizarPareja(pareja, esGanador = false, cruceId = null, parejaNum = null, ronda = null, valor = '') {
        if (!pareja || !pareja.jugador_1 || !pareja.jugador_2) {
            return '<div class="player-pair"><div class="player-pair-content"><div class="player-names">Esperando ganador...</div></div></div>';
        }
        
        let jugador1 = obtenerJugadorPorId(pareja.jugador_1);
        let jugador2 = obtenerJugadorPorId(pareja.jugador_2);
        let claseGanador = esGanador ? ' winner' : '';
        let inputHtml = '';
        
        if (cruceId !== null && parejaNum !== null && ronda !== null) {
            inputHtml = `
                <div class="player-pair-input">
                    <input type="number" 
                           class="form-control resultado-cruce" 
                           data-cruce-id="${cruceId}"
                           data-pareja="${parejaNum}"
                           data-ronda="${ronda}"
                           min="0"
                           max="99"
                           placeholder="0"
                           value="${valor}">
                </div>
            `;
        }
        
        let badgeHtml = '';
        if (pareja.zona && pareja.posicion) {
            badgeHtml = `<span class="badge badge-info">${pareja.zona}${pareja.posicion}º</span>`;
        }
        
        // Construir rutas de imágenes correctamente con timestamp para evitar caché
        let foto1Url = getFotoUrlWithCache(jugador1 && jugador1.foto ? jugador1.foto : 'images/jugador_img.png');
        let foto2Url = getFotoUrlWithCache(jugador2 && jugador2.foto ? jugador2.foto : 'images/jugador_img.png');
        
        return `
            <div class="player-pair${claseGanador}" 
                 data-pareja="${parejaNum || ''}"
                 data-jugador-1="${pareja ? pareja.jugador_1 : ''}"
                 data-jugador-2="${pareja ? pareja.jugador_2 : ''}">
                <div class="player-pair-content">
                    <div class="player-images">
                        <img src="${foto1Url}" alt="${jugador1 ? (jugador1.nombre || '') + ' ' + (jugador1.apellido || '') : ''}" onerror="this.src='${baseUrl}/images/jugador_img.png?v=' + Date.now()">
                        <img src="${foto2Url}" alt="${jugador2 ? (jugador2.nombre || '') + ' ' + (jugador2.apellido || '') : ''}" onerror="this.src='${baseUrl}/images/jugador_img.png?v=' + Date.now()">
                    </div>
                    <div class="player-names">
                        <div class="player-name">${jugador1 ? ((jugador1.nombre || '') + ' ' + (jugador1.apellido || '')) : ''}</div>
                        <div class="player-name">${jugador2 ? ((jugador2.nombre || '') + ' ' + (jugador2.apellido || '')) : ''}</div>
                    </div>
                    ${badgeHtml}
                </div>
                ${inputHtml}
            </div>
        `;
    }
    
    // Guardar resultado de cruce
    $(document).on('click', '.guardar-cruce', function() {
        let cruceId = $(this).data('cruce-id');
        let ronda = $(this).data('ronda');
        let pareja1Input = $(`.resultado-cruce[data-cruce-id="${cruceId}"][data-pareja="1"]`);
        let pareja2Input = $(`.resultado-cruce[data-cruce-id="${cruceId}"][data-pareja="2"]`);
        
        let pareja1Puntos = parseInt(pareja1Input.val()) || 0;
        let pareja2Puntos = parseInt(pareja2Input.val()) || 0;
        
        if (pareja1Puntos === 0 && pareja2Puntos === 0) {
            mostrarSnackbar('Debe ingresar al menos un resultado');
            return;
        }
        
        // Obtener información de las parejas directamente del DOM
        let matchCard = $(this).closest('.match-card');
        let pareja1Element = matchCard.find('.player-pair[data-pareja="1"]').first();
        let pareja2Element = matchCard.find('.player-pair[data-pareja="2"]').first();
        
        // jQuery convierte data-jugador-1 a jugador1, así que usamos attr() directamente
        let pareja1Jugador1 = pareja1Element.attr('data-jugador-1');
        let pareja1Jugador2 = pareja1Element.attr('data-jugador-2');
        let pareja2Jugador1 = pareja2Element.attr('data-jugador-1');
        let pareja2Jugador2 = pareja2Element.attr('data-jugador-2');
        
        // Convertir a número si son strings
        if (pareja1Jugador1) pareja1Jugador1 = parseInt(pareja1Jugador1);
        if (pareja1Jugador2) pareja1Jugador2 = parseInt(pareja1Jugador2);
        if (pareja2Jugador1) pareja2Jugador1 = parseInt(pareja2Jugador1);
        if (pareja2Jugador2) pareja2Jugador2 = parseInt(pareja2Jugador2);
        
        // Si no se encontraron en el DOM, intentar buscar en el array de cruces
        let cruce = null;
        if (!pareja1Jugador1 || !pareja1Jugador2 || !pareja2Jugador1 || !pareja2Jugador2) {
            if (ronda === 'cuartos') {
                // Para cuartos, usar el índice numérico
                cruce = cruces[cruceId];
            } else if (ronda === 'semifinales' || ronda === 'final') {
                // Para semifinales y final, buscar por ID primero
                cruce = cruces.find(c => c.ronda === ronda && c.id === cruceId);
                
                // Si no se encuentra por ID, buscar en todos los cruces de esa ronda
                if (!cruce) {
                    let crucesRonda = cruces.filter(c => c.ronda === ronda);
                    if (crucesRonda.length > 0) {
                        // Si solo hay uno, usarlo
                        if (crucesRonda.length === 1) {
                            cruce = crucesRonda[0];
                        } else {
                            // Si hay múltiples, intentar buscar por el índice si cruceId es numérico
                            let cruceIndex = parseInt(cruceId);
                            if (!isNaN(cruceIndex) && crucesRonda[cruceIndex] !== undefined) {
                                cruce = crucesRonda[cruceIndex];
                            } else {
                                // Si no se encuentra, usar el primero de la ronda
                                cruce = crucesRonda[0];
                            }
                        }
                    }
                }
            }
            
            if (cruce && cruce.pareja_1 && cruce.pareja_2) {
                pareja1Jugador1 = cruce.pareja_1.jugador_1;
                pareja1Jugador2 = cruce.pareja_1.jugador_2;
                pareja2Jugador1 = cruce.pareja_2.jugador_1;
                pareja2Jugador2 = cruce.pareja_2.jugador_2;
            }
        }
        
        // Validar que todos los valores sean números válidos
        if (!pareja1Jugador1 || isNaN(pareja1Jugador1) || !pareja1Jugador2 || isNaN(pareja1Jugador2) || 
            !pareja2Jugador1 || isNaN(pareja2Jugador1) || !pareja2Jugador2 || isNaN(pareja2Jugador2)) {
            console.error('No se pudo obtener información de las parejas:', {
                cruceId: cruceId,
                ronda: ronda,
                pareja1Jugador1: pareja1Jugador1,
                pareja1Jugador2: pareja1Jugador2,
                pareja2Jugador1: pareja2Jugador1,
                pareja2Jugador2: pareja2Jugador2,
                pareja1Element: pareja1Element.length,
                pareja2Element: pareja2Element.length,
                pareja1Attr1: pareja1Element.attr('data-jugador-1'),
                pareja1Attr2: pareja1Element.attr('data-jugador-2'),
                pareja2Attr1: pareja2Element.attr('data-jugador-1'),
                pareja2Attr2: pareja2Element.attr('data-jugador-2'),
                cruces: cruces,
                crucesFinal: cruces.filter(c => c.ronda === 'final'),
                matchCardHTML: matchCard[0] ? matchCard[0].outerHTML.substring(0, 500) : 'No encontrado'
            });
            alert('Error: No se encontró el cruce o falta información de las parejas. Ver consola para más detalles.');
            return;
        }
        
        // Obtener el valor del input de semifinal (solo para cuartos)
        let semifinalInput = null;
        if (ronda === 'cuartos') {
            let semifinalInputElement = $(`#semifinal-input-${cruceId}`);
            if (semifinalInputElement.length > 0) {
                semifinalInput = semifinalInputElement.val() || null;
            }
        }
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("guardarresultadocruceamericano") }}',
            data: {
                torneo_id: torneoId,
                ronda: ronda,
                pareja_1_jugador_1: pareja1Jugador1,
                pareja_1_jugador_2: pareja1Jugador2,
                pareja_2_jugador_1: pareja2Jugador1,
                pareja_2_jugador_2: pareja2Jugador2,
                pareja_1_set_1: pareja1Puntos,
                pareja_2_set_1: pareja2Puntos,
                semifinal: semifinalInput, // Valor del input de semifinal
                _token: '{{csrf_token()}}'
            },
            success: function(response) {
                if (response.success) {
                    // Crear objetos de pareja para uso local
                    let pareja1Obj = {
                        jugador_1: pareja1Jugador1,
                        jugador_2: pareja1Jugador2
                    };
                    let pareja2Obj = {
                        jugador_1: pareja2Jugador1,
                        jugador_2: pareja2Jugador2
                    };
                    
                    // Guardar resultado localmente
                    if (ronda === 'cuartos') {
                        resultadosCuartos[cruceId] = {
                            ganador: pareja1Puntos > pareja2Puntos ? pareja1Obj : pareja2Obj,
                            perdedor: pareja1Puntos > pareja2Puntos ? pareja2Obj : pareja1Obj,
                            score1: pareja1Puntos,
                            score2: pareja2Puntos
                        };
                        
                        // Actualizar visualización
                        let matchCard = $(`.match-card[data-cruce-id="${cruceId}"]`);
                        matchCard.addClass('winner');
                        
                        if (pareja1Puntos > pareja2Puntos) {
                            matchCard.find('.pareja-cruce[data-pareja="1"]').addClass('winner');
                            matchCard.find('.pareja-cruce[data-pareja="2"]').removeClass('winner');
                        } else {
                            matchCard.find('.pareja-cruce[data-pareja="2"]').addClass('winner');
                            matchCard.find('.pareja-cruce[data-pareja="1"]').removeClass('winner');
                        }
                        
                        // Si es octavos, verificar octavos primero
                        if (ronda === 'octavos') {
                            // Guardar resultado localmente para octavos
                            resultadosOctavos[cruceId] = {
                                ganador: pareja1Puntos > pareja2Puntos ? pareja1Obj : pareja2Obj,
                                perdedor: pareja1Puntos > pareja2Puntos ? pareja2Obj : pareja1Obj,
                                score1: pareja1Puntos,
                                score2: pareja2Puntos
                            };
                            
                            // Actualizar visualización
                            let matchCard = $(`.match-card[data-cruce-id="${cruceId}"]`);
                            matchCard.addClass('winner');
                            
                            if (pareja1Puntos > pareja2Puntos) {
                                matchCard.find('.pareja-cruce[data-pareja="1"]').addClass('winner');
                                matchCard.find('.pareja-cruce[data-pareja="2"]').removeClass('winner');
                            } else {
                                matchCard.find('.pareja-cruce[data-pareja="2"]').addClass('winner');
                                matchCard.find('.pareja-cruce[data-pareja="1"]').removeClass('winner');
                            }
                            
                            // Verificar si los octavos están completos
                            verificarOctavosCompletos();
                            
                            // Si los octavos están completos, generar cuartos automáticamente
                            if (octavosCompletos) {
                                generarCuartosDesdeOctavos();
                                return; // La función generarCuartosDesdeOctavos recargará la página
                            } else {
                                // Si no están completos, solo mostrar mensaje de éxito
                                mostrarSnackbar('Resultado guardado correctamente');
                            }
                        } else {
                            // Verificar si todos los cuartos están completos antes de recargar
                            verificarCuartosCompletos();
                            
                            // Si todos los cuartos están completos, actualizar semifinales antes de recargar
                            if (cuartosCompletos) {
                                actualizarSemifinales();
                            }
                            
                            // Recargar la página para mostrar las actualizaciones
                            setTimeout(function() {
                                window.location.reload();
                            }, 500);
                        }
                    } else if (ronda === 'semifinales') {
                        resultadosSemifinales[cruceId] = {
                            ganador: pareja1Puntos > pareja2Puntos ? pareja1Obj : pareja2Obj,
                            perdedor: pareja1Puntos > pareja2Puntos ? pareja2Obj : pareja1Obj,
                            score1: pareja1Puntos,
                            score2: pareja2Puntos
                        };
                        
                        // Buscar el match-card usando el cruceId y la ronda
                        let matchCard = $(`.match-card[data-cruce-id="${cruceId}"][data-ronda="semifinales"]`);
                        if (matchCard.length === 0) {
                            // Si no se encuentra, buscar solo por cruceId
                            matchCard = $(`.match-card[data-cruce-id="${cruceId}"]`);
                        }
                        
                        matchCard.addClass('winner');
                        
                        // Remover winner de todas las parejas primero
                        matchCard.find('.player-pair').removeClass('winner');
                        
                        // Agregar winner a la pareja ganadora
                        if (pareja1Puntos > pareja2Puntos) {
                            matchCard.find('.player-pair[data-pareja="1"]').addClass('winner');
                        } else {
                            matchCard.find('.player-pair[data-pareja="2"]').addClass('winner');
                        }
                        
                        // Recargar la página para mostrar la final actualizada correctamente
                        setTimeout(function() {
                            window.location.reload();
                        }, 500);
                    } else if (ronda === 'final') {
                        resultadoFinal = {
                            ganador: pareja1Puntos > pareja2Puntos ? pareja1Obj : pareja2Obj,
                            perdedor: pareja1Puntos > pareja2Puntos ? pareja2Obj : pareja1Obj,
                            score1: pareja1Puntos,
                            score2: pareja2Puntos
                        };
                        
                        // Buscar el match-card usando el cruceId y la ronda
                        let matchCard = $(`.match-card[data-cruce-id="${cruceId}"][data-ronda="final"]`);
                        if (matchCard.length === 0) {
                            // Si no se encuentra, buscar solo por cruceId
                            matchCard = $(`.match-card[data-cruce-id="${cruceId}"]`);
                        }
                        
                        matchCard.addClass('winner');
                        
                        // Remover winner de todas las parejas primero
                        matchCard.find('.player-pair').removeClass('winner');
                        
                        // Agregar winner a la pareja ganadora
                        if (pareja1Puntos > pareja2Puntos) {
                            matchCard.find('.player-pair[data-pareja="1"]').addClass('winner');
                        } else {
                            matchCard.find('.player-pair[data-pareja="2"]').addClass('winner');
                        }
                        
                        // Mostrar modal de ganadores con confetti
                        mostrarModalGanadores(resultadoFinal.ganador);
                        crearConfetti();
                    }
                    
                    mostrarSnackbar('Resultado guardado correctamente');
                } else {
                    mostrarSnackbar('Error al guardar: ' + (response.message || 'Error desconocido'));
                }
            },
            error: function() {
                mostrarSnackbar('Error al guardar el resultado');
            }
        });
    });
    
    // Función para verificar en tiempo real si todos los cuartos tienen resultados
    let octavosCompletos = false;
    
    function verificarOctavosCompletos() {
        let crucesOctavos = cruces.filter(c => c.ronda === 'octavos');
        if (crucesOctavos.length === 0) {
            octavosCompletos = false;
            return false;
        }
        
        let todosCompletos = true;
        crucesOctavos.forEach(function(cruce) {
            let pareja1Input = $(`.resultado-cruce[data-cruce-id="${cruce.id}"][data-pareja="1"][data-ronda="octavos"]`);
            let pareja2Input = $(`.resultado-cruce[data-cruce-id="${cruce.id}"][data-pareja="2"][data-ronda="octavos"]`);
            
            let valor1 = pareja1Input.val();
            let valor2 = pareja2Input.val();
            
            if (!valor1 || valor1 === '' || !valor2 || valor2 === '' || valor1 === valor2) {
                todosCompletos = false;
                return false; // Salir del each
            }
        });
        
        octavosCompletos = todosCompletos;
        
        // Si los octavos están completos, generar cuartos automáticamente
        if (octavosCompletos) {
            generarCuartosDesdeOctavos();
        }
        
        return todosCompletos;
    }
    
    function generarCuartosDesdeOctavos() {
        // Obtener ganadores de octavos
        let ganadoresOctavos = [];
        let crucesOctavos = cruces.filter(c => c.ronda === 'octavos');
        
        crucesOctavos.forEach(function(cruce) {
            let pareja1Input = $(`.resultado-cruce[data-cruce-id="${cruce.id}"][data-pareja="1"][data-ronda="octavos"]`);
            let pareja2Input = $(`.resultado-cruce[data-cruce-id="${cruce.id}"][data-pareja="2"][data-ronda="octavos"]`);
            
            let valor1 = parseInt(pareja1Input.val()) || 0;
            let valor2 = parseInt(pareja2Input.val()) || 0;
            
            if (valor1 > valor2) {
                ganadoresOctavos.push(cruce.pareja_1);
            } else if (valor2 > valor1) {
                ganadoresOctavos.push(cruce.pareja_2);
            }
        });
        
        // Si tenemos 8 ganadores, crear 4 cruces de cuartos
        if (ganadoresOctavos.length === 8) {
            // Crear cruces de cuartos: 1vs2, 3vs4, 5vs6, 7vs8
            let crucesCuartos = [
                { pareja_1: ganadoresOctavos[0], pareja_2: ganadoresOctavos[1] },
                { pareja_1: ganadoresOctavos[2], pareja_2: ganadoresOctavos[3] },
                { pareja_1: ganadoresOctavos[4], pareja_2: ganadoresOctavos[5] },
                { pareja_1: ganadoresOctavos[6], pareja_2: ganadoresOctavos[7] }
            ];
            
            // Enviar al servidor para crear los partidos de cuartos
            $.ajax({
                url: '{{ route("crearcuartosdesdeoctavos") }}',
                method: 'POST',
                data: {
                    torneo_id: $('#torneo_id').val(),
                    cruces: JSON.stringify(crucesCuartos),
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        // Recargar la página para mostrar los nuevos cruces de cuartos
                        location.reload();
                    }
                }
            });
        }
    }
    
    function verificarCuartosCompletos() {
        // Obtener todos los inputs de resultados de cuartos
        let todosCompletos = true;
        let partidosCuartos = $('.resultado-cruce[data-ronda="cuartos"]').closest('.match-card');
        
        if (partidosCuartos.length === 0) {
            cuartosCompletos = false;
            return;
        }
        
        partidosCuartos.each(function() {
            let pareja1Input = $(this).find('.resultado-cruce[data-pareja="1"]');
            let pareja2Input = $(this).find('.resultado-cruce[data-pareja="2"]');
            
            let valor1 = parseInt(pareja1Input.val()) || 0;
            let valor2 = parseInt(pareja2Input.val()) || 0;
            
            // Si ambos valores son 0, el partido no está completo
            if (valor1 === 0 && valor2 === 0) {
                todosCompletos = false;
                return false; // Salir del each
            }
        });
        
        // Actualizar la variable global
        cuartosCompletos = todosCompletos;
        
        return todosCompletos;
    }
    
    function verificarAvance() {
        // Verificar si los cuartos están completos y actualizar semifinales
        verificarCuartosCompletos();
        actualizarSemifinales();
        
        // Actualizar final cada vez que se guarda un resultado de semifinales
        actualizarFinal();
    }
    
    function actualizarSemifinales() {
        // Verificar si los cuartos están completos antes de mostrar semifinales
        // Primero verificar en tiempo real si todos los cuartos tienen resultados
        verificarCuartosCompletos();
        
        if (!cuartosCompletos) {
            // Mantener el contenedor visible pero mostrar mensaje
            $('#semifinales-content').html('<p class="text-center text-muted p-3">Completa todos los partidos de cuartos para ver las semifinales</p>');
            return;
        }
        
        // PRIMERO: Verificar si ya hay semifinales del backend con partido_id
        // Si las hay, agruparlas por partido_id y renderizarlas directamente
        let semifinalesBackend = cruces.filter(c => c.ronda === 'semifinales' && c.partido_id);
        
        if (semifinalesBackend.length > 0) {
            // Agrupar por partido_id para evitar duplicados
            let semifinalesAgrupadas = {};
            semifinalesBackend.forEach(function(sf) {
                let partidoId = sf.partido_id;
                if (!semifinalesAgrupadas[partidoId]) {
                    semifinalesAgrupadas[partidoId] = sf;
                }
            });
            
            // Guardar valores actuales de los inputs antes de regenerar
            let valoresGuardados = {};
            $('.resultado-cruce[data-ronda="semifinales"]').each(function() {
                let cruceId = $(this).data('cruce-id');
                let pareja = $(this).data('pareja');
                let valor = $(this).val();
                if (!valoresGuardados[cruceId]) {
                    valoresGuardados[cruceId] = {};
                }
                valoresGuardados[cruceId][pareja] = valor;
            });
            
            let html = '';
            Object.values(semifinalesAgrupadas).forEach(function(sf) {
                let sfId = sf.id;
                let valor1 = valoresGuardados[sfId] && valoresGuardados[sfId][1] ? valoresGuardados[sfId][1] : '';
                let valor2 = valoresGuardados[sfId] && valoresGuardados[sfId][2] ? valoresGuardados[sfId][2] : '';
                
                html += `
                    <div class="match-card" data-cruce-id="${sfId}" data-ronda="semifinales" data-partido-id="${sf.partido_id}">
                        ${renderizarPareja(sf.pareja_1, false, sfId, 1, 'semifinales', valor1)}
                        ${sf.pareja_2 ? renderizarPareja(sf.pareja_2, false, sfId, 2, 'semifinales', valor2) : '<div class="player-pair"><div class="player-pair-content"><div class="player-names">Esperando ganador...</div></div></div>'}
                        <div class="text-center mt-2">
                            <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="${sfId}" data-ronda="semifinales" ${!sf.pareja_2 ? 'disabled' : ''}>Guardar</button>
                        </div>
                    </div>
                `;
            });
            
            if (html) {
                $('#semifinales-content').html(html);
                $('#semifinales-container').show();
            }
            return; // Salir temprano si ya hay semifinales del backend
        }
        
        // Si no hay semifinales del backend, usar la lógica anterior para generarlas
        // Obtener ganadores de cuartos en orden (0, 1, 2, 3)
        let ganadoresCuartos = [];
        for (let i = 0; i < 4; i++) {
            if (resultadosCuartos[i] && resultadosCuartos[i].ganador) {
                ganadoresCuartos[i] = resultadosCuartos[i].ganador;
            } else {
                ganadoresCuartos[i] = null;
            }
        }
        
        // Guardar valores actuales de los inputs antes de regenerar
        let valoresGuardados = {};
        $('.resultado-cruce[data-ronda="semifinales"]').each(function() {
            let cruceId = $(this).data('cruce-id');
            let pareja = $(this).data('pareja');
            let valor = $(this).val();
            if (!valoresGuardados[cruceId]) {
                valoresGuardados[cruceId] = {};
            }
            valoresGuardados[cruceId][pareja] = valor;
        });
        
        let html = '';
        
        // Caso especial: 6 clasificados
        // Los primeros pasan directo a semifinales, los ganadores de cuartos juegan contra ellos
        if (totalClasificados === 6 && primerosClasificados.length > 0) {
            // Mostrar semifinales cuando hay ganadores de cuartos o cuando ya hay primeros
            if (ganadoresCuartos.filter(g => g !== null).length >= 1 || primerosClasificados.length > 0) {
                // Crear semifinales: Primer primero vs Ganador cuartos 1, Segundo primero vs Ganador cuartos 2
                for (let i = 0; i < primerosClasificados.length && i < 2; i++) {
                    let primero = primerosClasificados[i];
                    let ganadorCuartos = ganadoresCuartos[i] || null;
                    
                    let sfId = 'sf' + (i + 1);
                    let sf = cruces.find(c => c.ronda === 'semifinales' && c.id === sfId);
                    
                    if (!sf) {
                        sf = {
                            id: sfId,
                            pareja_1: primero,
                            pareja_2: ganadorCuartos,
                            ronda: 'semifinales'
                        };
                        cruces.push(sf);
                    } else {
                        sf.pareja_1 = primero;
                        if (ganadorCuartos) {
                            sf.pareja_2 = ganadorCuartos;
                        }
                    }
                    
                    let valor1 = valoresGuardados[sfId] && valoresGuardados[sfId][1] ? valoresGuardados[sfId][1] : '';
                    let valor2 = valoresGuardados[sfId] && valoresGuardados[sfId][2] ? valoresGuardados[sfId][2] : '';
                    
                    html += `
                        <div class="match-card" data-cruce-id="${sfId}" data-ronda="semifinales">
                            ${renderizarPareja(sf.pareja_1, false, sfId, 1, 'semifinales', valor1)}
                            ${sf.pareja_2 ? renderizarPareja(sf.pareja_2, false, sfId, 2, 'semifinales', valor2) : '<div class="player-pair"><div class="player-pair-content"><div class="player-names">Esperando ganador de cuartos...</div></div></div>'}
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="${sfId}" data-ronda="semifinales" ${!sf.pareja_2 ? 'disabled' : ''}>Guardar</button>
                            </div>
                        </div>
                    `;
                }
            }
        } else {
            // Lógica estándar para 8 o más clasificados
            // Mostrar semifinales cuando hay al menos 1 ganador de cuartos
            let ganadoresCount = ganadoresCuartos.filter(g => g !== null).length;
            if (ganadoresCount >= 1) {
                // Ordenar ganadores según el orden de los cuartos
                // SF1: Ganador QF1 vs Ganador QF3
                // SF2: Ganador QF2 vs Ganador QF4
                let sf1Pareja1 = ganadoresCuartos[0] || null;
                let sf1Pareja2 = ganadoresCuartos[2] || null;
                let sf2Pareja1 = ganadoresCuartos[1] || null;
                let sf2Pareja2 = ganadoresCuartos[3] || null;
                
                // Semifinal 1: Ganador QF1 vs Ganador QF3
                if (sf1Pareja1) {
                    // Buscar si ya existe un cruce de semifinales con estas parejas (viene de la base de datos)
                    let sf1 = cruces.find(c => {
                        if (c.ronda !== 'semifinales') return false;
                        if (!c.pareja_1 || !c.pareja_2) return false;
                        // Verificar si coincide con las parejas esperadas
                        let p1Match = (c.pareja_1.jugador_1 == sf1Pareja1.jugador_1 && c.pareja_1.jugador_2 == sf1Pareja1.jugador_2) ||
                                     (c.pareja_2.jugador_1 == sf1Pareja1.jugador_1 && c.pareja_2.jugador_2 == sf1Pareja1.jugador_2);
                        let p2Match = sf1Pareja2 ? ((c.pareja_1.jugador_1 == sf1Pareja2.jugador_1 && c.pareja_1.jugador_2 == sf1Pareja2.jugador_2) ||
                                                   (c.pareja_2.jugador_1 == sf1Pareja2.jugador_1 && c.pareja_2.jugador_2 == sf1Pareja2.jugador_2)) : false;
                        return p1Match && (sf1Pareja2 ? p2Match : true);
                    });
                    
                    let sf1Id = sf1 ? sf1.id : 'sf1';
                    
                    if (!sf1) {
                        sf1 = {
                            id: sf1Id,
                            pareja_1: sf1Pareja1,
                            pareja_2: sf1Pareja2 || null,
                            ronda: 'semifinales'
                        };
                        cruces.push(sf1);
                    } else {
                        sf1.pareja_1 = sf1Pareja1;
                        if (sf1Pareja2) {
                            sf1.pareja_2 = sf1Pareja2;
                        }
                    }
                    
                    let valor1 = valoresGuardados[sf1Id] && valoresGuardados[sf1Id][1] ? valoresGuardados[sf1Id][1] : '';
                    let valor2 = valoresGuardados[sf1Id] && valoresGuardados[sf1Id][2] ? valoresGuardados[sf1Id][2] : '';
                    
                    html += `
                        <div class="match-card" data-cruce-id="${sf1Id}" data-ronda="semifinales">
                            ${renderizarPareja(sf1.pareja_1, false, sf1Id, 1, 'semifinales', valor1)}
                            ${sf1Pareja2 ? renderizarPareja(sf1.pareja_2, false, sf1Id, 2, 'semifinales', valor2) : '<div class="player-pair"><div class="player-pair-content"><div class="player-names">Esperando ganador...</div></div></div>'}
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="${sf1Id}" data-ronda="semifinales" ${!sf1Pareja2 ? 'disabled' : ''}>Guardar</button>
                            </div>
                        </div>
                    `;
                }
                
                // Semifinal 2: Ganador QF2 vs Ganador QF4
                if (sf2Pareja1) {
                    // Buscar si ya existe un cruce de semifinales con estas parejas (viene de la base de datos)
                    let sf2 = cruces.find(c => {
                        if (c.ronda !== 'semifinales') return false;
                        if (!c.pareja_1 || !c.pareja_2) return false;
                        // Verificar si coincide con las parejas esperadas
                        let p1Match = (c.pareja_1.jugador_1 == sf2Pareja1.jugador_1 && c.pareja_1.jugador_2 == sf2Pareja1.jugador_2) ||
                                     (c.pareja_2.jugador_1 == sf2Pareja1.jugador_1 && c.pareja_2.jugador_2 == sf2Pareja1.jugador_2);
                        let p2Match = sf2Pareja2 ? ((c.pareja_1.jugador_1 == sf2Pareja2.jugador_1 && c.pareja_1.jugador_2 == sf2Pareja2.jugador_2) ||
                                                   (c.pareja_2.jugador_1 == sf2Pareja2.jugador_1 && c.pareja_2.jugador_2 == sf2Pareja2.jugador_2)) : false;
                        return p1Match && (sf2Pareja2 ? p2Match : true);
                    });
                    
                    let sf2Id = sf2 ? sf2.id : 'sf2';
                    
                    if (!sf2) {
                        sf2 = {
                            id: sf2Id,
                            pareja_1: sf2Pareja1,
                            pareja_2: sf2Pareja2 || null,
                            ronda: 'semifinales'
                        };
                        cruces.push(sf2);
                    } else {
                        sf2.pareja_1 = sf2Pareja1;
                        if (sf2Pareja2) {
                            sf2.pareja_2 = sf2Pareja2;
                        }
                    }
                    
                    let valor1 = valoresGuardados[sf2Id] && valoresGuardados[sf2Id][1] ? valoresGuardados[sf2Id][1] : '';
                    let valor2 = valoresGuardados[sf2Id] && valoresGuardados[sf2Id][2] ? valoresGuardados[sf2Id][2] : '';
                    
                    html += `
                        <div class="match-card" data-cruce-id="${sf2Id}" data-ronda="semifinales">
                            ${renderizarPareja(sf2.pareja_1, false, sf2Id, 1, 'semifinales', valor1)}
                            ${sf2Pareja2 ? renderizarPareja(sf2.pareja_2, false, sf2Id, 2, 'semifinales', valor2) : '<div class="player-pair"><div class="player-pair-content"><div class="player-names">Esperando ganador...</div></div></div>'}
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="${sf2Id}" data-ronda="semifinales" ${!sf2Pareja2 ? 'disabled' : ''}>Guardar</button>
                            </div>
                        </div>
                    `;
                }
            }
        }
        
        if (html) {
            $('#semifinales-content').html(html);
            $('#semifinales-container').show();
        }
    }
    
    function actualizarFinal() {
        let ganadoresSemifinales = Object.values(resultadosSemifinales).map(r => r.ganador);
        
        // Guardar valores actuales de los inputs antes de regenerar
        let valoresGuardados = {};
        $('.resultado-cruce[data-ronda="final"]').each(function() {
            let cruceId = $(this).data('cruce-id');
            let pareja = $(this).data('pareja');
            let valor = $(this).val();
            if (!valoresGuardados[cruceId]) {
                valoresGuardados[cruceId] = {};
            }
            valoresGuardados[cruceId][pareja] = valor;
        });
        
        if (ganadoresSemifinales.length >= 1) {
            // Buscar si ya existe un cruce de final con estas parejas (viene de la base de datos)
            let final = cruces.find(c => {
                if (c.ronda !== 'final') return false;
                if (!c.pareja_1 || !c.pareja_2) return false;
                // Verificar si coincide con las parejas esperadas
                let p1Match = (c.pareja_1.jugador_1 == ganadoresSemifinales[0].jugador_1 && c.pareja_1.jugador_2 == ganadoresSemifinales[0].jugador_2) ||
                             (c.pareja_2.jugador_1 == ganadoresSemifinales[0].jugador_1 && c.pareja_2.jugador_2 == ganadoresSemifinales[0].jugador_2);
                let p2Match = ganadoresSemifinales[1] ? ((c.pareja_1.jugador_1 == ganadoresSemifinales[1].jugador_1 && c.pareja_1.jugador_2 == ganadoresSemifinales[1].jugador_2) ||
                                                       (c.pareja_2.jugador_1 == ganadoresSemifinales[1].jugador_1 && c.pareja_2.jugador_2 == ganadoresSemifinales[1].jugador_2)) : false;
                return p1Match && (ganadoresSemifinales[1] ? p2Match : true);
            });
            
            let finalId = final ? final.id : 'final';
            
            if (!final) {
                final = {
                    id: finalId,
                    pareja_1: ganadoresSemifinales[0],
                    pareja_2: ganadoresSemifinales[1] || null,
                    ronda: 'final'
                };
                cruces.push(final);
            } else {
                final.pareja_1 = ganadoresSemifinales[0];
                if (ganadoresSemifinales[1]) {
                    final.pareja_2 = ganadoresSemifinales[1];
                }
            }
            
            let valor1 = valoresGuardados[finalId] && valoresGuardados[finalId][1] ? valoresGuardados[finalId][1] : '';
            let valor2 = valoresGuardados[finalId] && valoresGuardados[finalId][2] ? valoresGuardados[finalId][2] : '';
            
            let html = `
                <div class="match-card" data-cruce-id="${finalId}" data-ronda="final">
                    ${renderizarPareja(final.pareja_1, false, finalId, 1, 'final', valor1)}
                    ${ganadoresSemifinales[1] ? renderizarPareja(final.pareja_2, false, finalId, 2, 'final', valor2) : '<div class="player-pair"><div class="player-pair-content"><div class="player-names">Esperando ganador...</div></div></div>'}
                    <div class="text-center mt-2">
                        <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="${finalId}" data-ronda="final" ${!ganadoresSemifinales[1] ? 'disabled' : ''}>Guardar</button>
                    </div>
                </div>
            `;
            
            $('#final-content').html(html);
            $('#final-container').show();
        }
    }
    
    // Función para mostrar modal de ganadores
    function mostrarModalGanadores(ganador) {
        let jugador1 = obtenerJugadorPorId(ganador.jugador_1);
        let jugador2 = obtenerJugadorPorId(ganador.jugador_2);
        
        let html = '';
        if (jugador1) {
            html += `
                <div class="ganador-foto">
                    <img src="${getFotoUrlWithCache(jugador1.foto || 'images/jugador_img.png')}" alt="${jugador1.nombre}" onerror="this.src='${baseUrl}/images/jugador_img.png?v=' + Date.now()">
                    <div class="nombre">${jugador1.nombre} ${jugador1.apellido}</div>
                </div>
            `;
        }
        if (jugador2) {
            html += `
                <div class="ganador-foto">
                    <img src="${getFotoUrlWithCache(jugador2.foto || 'images/jugador_img.png')}" alt="${jugador2.nombre}" onerror="this.src='${baseUrl}/images/jugador_img.png?v=' + Date.now()">
                    <div class="nombre">${jugador2.nombre} ${jugador2.apellido}</div>
                </div>
            `;
        }
        
        $('#ganadores-fotos').html(html);
        $('#modal-ganadores').addClass('show');
    }
    
    // Función para cerrar modal
    function cerrarModalGanadores() {
        $('#modal-ganadores').removeClass('show');
    }
    
    // Función para crear confetti
    function crearConfetti() {
        const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff', '#ffa500'];
        const confettiCount = 100;
        
        for (let i = 0; i < confettiCount; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                confetti.style.animationDelay = Math.random() * 2 + 's';
                confetti.style.width = (Math.random() * 10 + 5) + 'px';
                confetti.style.height = (Math.random() * 10 + 5) + 'px';
                
                document.body.appendChild(confetti);
                
                setTimeout(() => {
                    confetti.remove();
                }, 5000);
            }, i * 10);
        }
    }
    
    // Cerrar modal al hacer click fuera
    $('#modal-ganadores').on('click', function(e) {
        if (e.target === this) {
            cerrarModalGanadores();
        }
    });
    
    // Inicializar al cargar la página
    $(document).ready(function() {
        // Verificar si hay cruces de octavos y mostrar la columna
        let crucesOctavos = cruces.filter(c => c.ronda === 'octavos');
        console.log('Cruces totales:', cruces.length);
        console.log('Cruces de octavos encontrados:', crucesOctavos.length);
        console.log('Cruces con ronda:', cruces.map(c => ({id: c.id, ronda: c.ronda})));
        if (crucesOctavos.length > 0 || {{ ($tieneOctavos ?? false) ? 'true' : 'false' }}) {
            $('#octavos-container').show();
        }
        
        // Siempre mostrar el contenedor de semifinales (el título debe estar visible)
        $('#semifinales-container').show();
        
        // Verificar si los octavos están completos (si existen) o los cuartos
        if (crucesOctavos.length > 0) {
            verificarOctavosCompletos();
        } else {
            verificarCuartosCompletos();
        }
        
        // Mostrar contenedores de semifinales y final solo si los cuartos están completos
        let crucesSemifinales = cruces.filter(c => c.ronda === 'semifinales');
        let crucesFinal = cruces.filter(c => c.ronda === 'final');
        
        // Renderizar cruces de semifinales que vienen de la base de datos
        // SOLO si los cuartos están completos
        if (crucesSemifinales.length > 0 && cuartosCompletos) {
            let htmlSemifinales = '';
            crucesSemifinales.forEach(function(sf) {
                if (sf.pareja_1 && sf.pareja_2) {
                    let sfId = sf.id;
                    let valor1 = '';
                    let valor2 = '';
                    let resultadoGuardado = null;
                    
                    // Buscar resultados guardados para este cruce
                    resultadosGuardados.forEach(function(resultado) {
                        if (resultado.ronda === 'semifinales' && resultado.cruce_id === sfId) {
                            valor1 = resultado.pareja_1_set_1 || '';
                            valor2 = resultado.pareja_2_set_1 || '';
                            resultadoGuardado = resultado;
                        }
                    });
                    
                    htmlSemifinales += `
                        <div class="match-card" data-cruce-id="${sfId}" data-ronda="semifinales">
                            ${renderizarPareja(sf.pareja_1, false, sfId, 1, 'semifinales', valor1)}
                            ${renderizarPareja(sf.pareja_2, false, sfId, 2, 'semifinales', valor2)}
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="${sfId}" data-ronda="semifinales">Guardar</button>
                            </div>
                        </div>
                    `;
                }
            });
            if (htmlSemifinales) {
                $('#semifinales-content').html(htmlSemifinales);
                
                // Aplicar estilo de ganador después de renderizar
                crucesSemifinales.forEach(function(sf) {
                    if (sf.pareja_1 && sf.pareja_2) {
                        let sfId = sf.id;
                        let resultadoGuardado = resultadosGuardados.find(function(r) {
                            return r.ronda === 'semifinales' && r.cruce_id === sfId;
                        });
                        
                        if (resultadoGuardado && resultadoGuardado.pareja_1_set_1 > 0 && resultadoGuardado.pareja_2_set_1 > 0) {
                            let matchCard = $(`.match-card[data-cruce-id="${sfId}"][data-ronda="semifinales"]`);
                            if (matchCard.length > 0) {
                                matchCard.addClass('winner');
                                matchCard.find('.player-pair').removeClass('winner');
                                
                                if (resultadoGuardado.pareja_1_set_1 > resultadoGuardado.pareja_2_set_1) {
                                    matchCard.find('.player-pair[data-pareja="1"]').addClass('winner');
                                } else {
                                    matchCard.find('.player-pair[data-pareja="2"]').addClass('winner');
                                }
                            }
                        }
                    }
                });
            } else if (cuartosCompletos) {
                // Si los cuartos están completos pero no hay semifinales del backend, generarlas
                actualizarSemifinales();
            }
        } else if (cuartosCompletos) {
            // Si los cuartos están completos pero no hay semifinales del backend, generarlas
            actualizarSemifinales();
        } else {
            // Si los cuartos no están completos, mostrar mensaje
            $('#semifinales-content').html('<p class="text-center text-muted p-3">Completa todos los partidos de cuartos para ver las semifinales</p>');
        }
        
        // Renderizar cruces de final que vienen de la base de datos
        if (crucesFinal.length > 0) {
            $('#final-container').show();
            let htmlFinal = '';
            crucesFinal.forEach(function(final) {
                if (final.pareja_1 && final.pareja_2) {
                    let finalId = final.id;
                    let valor1 = '';
                    let valor2 = '';
                    let resultadoGuardado = null;
                    
                    // Buscar resultados guardados para este cruce
                    resultadosGuardados.forEach(function(resultado) {
                        if (resultado.ronda === 'final' && resultado.cruce_id === finalId) {
                            valor1 = resultado.pareja_1_set_1 || '';
                            valor2 = resultado.pareja_2_set_1 || '';
                            resultadoGuardado = resultado;
                        }
                    });
                    
                    htmlFinal += `
                        <div class="match-card" data-cruce-id="${finalId}" data-ronda="final">
                            ${renderizarPareja(final.pareja_1, false, finalId, 1, 'final', valor1)}
                            ${renderizarPareja(final.pareja_2, false, finalId, 2, 'final', valor2)}
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="${finalId}" data-ronda="final">Guardar</button>
                            </div>
                        </div>
                    `;
                }
            });
            if (htmlFinal) {
                $('#final-content').html(htmlFinal);
                
                // Aplicar estilo de ganador después de renderizar
                crucesFinal.forEach(function(final) {
                    if (final.pareja_1 && final.pareja_2) {
                        let finalId = final.id;
                        let resultadoGuardado = resultadosGuardados.find(function(r) {
                            return r.ronda === 'final' && r.cruce_id === finalId;
                        });
                        
                        if (resultadoGuardado && resultadoGuardado.pareja_1_set_1 > 0 && resultadoGuardado.pareja_2_set_1 > 0) {
                            let matchCard = $(`.match-card[data-cruce-id="${finalId}"][data-ronda="final"]`);
                            if (matchCard.length > 0) {
                                matchCard.addClass('winner');
                                matchCard.find('.player-pair').removeClass('winner');
                                
                                if (resultadoGuardado.pareja_1_set_1 > resultadoGuardado.pareja_2_set_1) {
                                    matchCard.find('.player-pair[data-pareja="1"]').addClass('winner');
                                } else {
                                    matchCard.find('.player-pair[data-pareja="2"]').addClass('winner');
                                }
                            }
                        }
                    }
                });
            }
        }
        
        cargarResultadosGuardados();
    });
</script>
@endsection
