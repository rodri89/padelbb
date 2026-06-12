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
            <!-- Cuartos de Final -->
            <div class="col-md-4">
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
                                            <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador1_1->nombre ?? '' }}">
                                            <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador1_2->nombre ?? '' }}">
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
                                            <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador2_1->nombre ?? '' }}">
                                            <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador2_2->nombre ?? '' }}">
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
                                    <input type="text" 
                                           class="form-control form-control-sm mr-2" 
                                           style="width: 120px; display: inline-block;" 
                                           id="semifinal-input-{{ $index }}" 
                                           data-cruce-id="{{ $index }}"
                                           data-ronda="cuartos"
                                           placeholder="{{ $loop->iteration <= 2 ? 'Semifinal 1' : 'Semifinal 2' }}"
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
            <div class="col-md-4">
                <div class="bracket-round" id="semifinales-container">
                    <div class="bracket-round-title">SEMIFINALES</div>
                    <div id="semifinales-content">
                        @foreach($cruces as $index => $cruce)
                            @if($cruce['ronda'] == 'semifinales')
                                @php
                                    $jugador1_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                                    $jugador1_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                                    $jugador2_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                                    $jugador2_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                                @endphp
                                <div class="match-card" data-cruce-id="{{ $cruce['id'] ?? $index }}" data-ronda="semifinales">
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
                                                data-cruce-id="{{ $cruce['id'] ?? $index }}"
                                                data-ronda="semifinales">
                                            Guardar
                                        </button>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            
            <!-- Final -->
            <div class="col-md-4">
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


@endsection