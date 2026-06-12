@extends('bahia_padel/admin/plantilla')

@section('title_header','Cruces Eliminatorios - Torneo Puntuable')

@section('contenedor')
<link rel="stylesheet" href="{{ asset('css/bracket.css') }}">
<link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}">
<style>
    /* Estilos para inputs de resultados - alineación y tamaño uniforme */
    .resultado-cruce {
        width: 70px !important;
        min-width: 70px !important;
        max-width: 70px !important;
        text-align: center;
        padding: 0.375rem 0.5rem;
        font-size: 0.9rem;
        height: 38px;
    }
    
    .d-flex.flex-column.align-items-center {
        min-width: 80px;
        flex: 0 0 auto;
    }
    
    .d-flex.align-items-center.gap-2 {
        justify-content: center;
        gap: 0.75rem !important;
    }
    
    .small.mb-1 {
        width: 100%;
        text-align: center;
        margin-bottom: 0.25rem !important;
        font-size: 0.75rem;
    }
    /* Scroll horizontal para las columnas de cruces */
    .bracket-columns-scroll {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 10px;
    }
    .bracket-columns-scroll .bracket-columns-row {
        display: flex;
        flex-wrap: nowrap;
        min-width: max-content;
        gap: 40px;
    }
    .bracket-columns-scroll .bracket-column {
        flex: 0 0 auto;
        min-width: 320px;
        max-width: 320px;
    }
</style>

<div class="bracket-container">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button type="button" class="btn btn-secondary" id="btn-volver-clasificacion">
                        ← Volver a Clasificación
                    </button>
                    
                    <h2 class="text-center flex-grow-1 mb-0" style="color: #000;">{{ $torneo->nombre ?? 'Torneo' }}</h2>
                    
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-success" id="btn-asignar-puntos" title="Asignar puntos al ranking">
                            <i class="fa fa-trophy"></i> Asignar Puntos
                        </button>
                        <a href="{{ route('tvtorneoamericanocruces') }}?torneo_id={{ $torneo->id }}" target="_blank" class="btn btn-primary">
                            <i class="fa fa-desktop"></i> TV
                        </a>
                        <a href="{{ route('tvtorneosrotacion') }}?torneos={{ $torneo->id }}&intervalo=60" target="_blank" class="btn btn-info">
                            <i class="fa fa-tv"></i> Rotación
                        </a>
                    </div>
                </div>
                <input type="hidden" id="torneo_id" value="{{ $torneo->id ?? 0 }}">
            </div>
        </div>

        {{-- Depuración: cómo el servidor armó posiciones + cruces (solo PuntuableController pasa $crucesV2Debug). Consola del navegador. --}}
        @isset($crucesV2Debug)
        <script>
        (function () {
            var D = @json($crucesV2Debug);
            window.CRUZES_PUNT_V2_DEBUG = D;
            console.groupCollapsed('[Cruces puntuable v2] armado en servidor (debug)');
            console.log('Torneo id', D.torneo_id);
            console.log('Total parejas clasificadas (filas con posicion_grupo)', D.totalParejasClasificadas);
            console.log('Posiciones por zona (desde grupos.posicion_grupo)', D.posicionesPorZona);
            console.log('Orden de zonas y letra usada en config (A,B,…)', D.zonasOrdenadas, D.zonaALetra);
            console.log('Config aplicada', D.configResumen);
            if (D.nota) console.warn(D.nota);
            if (D.pasos) {
                if (D.pasos.desde_config_generador) {
                    console.log('Paso 1 — Tras generarCrucesDesdeConfiguracion (refs A1… resueltas a jugadores)', D.pasos.desde_config_generador);
                }
                if (D.pasos.solo_16avos_octavos_bd) {
                    console.log('Paso 2a — 16avos/octavos tomados solo desde BD (no desde config)', D.pasos.solo_16avos_octavos_bd);
                }
                if (D.pasos.cuartos_semifinal_final_desde_config) {
                    console.log('Paso 2b — Cuartos / semis / final desde config (+ enlaces a partidos BD)', D.pasos.cuartos_semifinal_final_desde_config);
                }
                if (D.pasos.resumen_merge) console.log('Resumen merge', D.pasos.resumen_merge);
            }
            console.log('Lista final enviada a la vista (merge)', D.crucesFinales);
            console.groupEnd();
            console.info('Objeto completo en window.CRUZES_PUNT_V2_DEBUG');
        })();
        </script>
        @endisset
        
        <div class="bracket-columns-scroll">
            <div class="bracket-columns-row">
            <!-- 16avos de Final -->
            @if(count($cruces16avos ?? []) > 0)
            <div class="bracket-column">
                <div class="bracket-round">
                    <div class="bracket-round-title">16avos de Final</div>
                    @foreach($cruces16avos as $cruce)
                        @php
                            $esPlaceholder1 = ((int)($cruce['pareja_1']['jugador_1'] ?? 0) === 0 && (int)($cruce['pareja_1']['jugador_2'] ?? 0) === 0);
                            $esPlaceholder2 = ((int)($cruce['pareja_2']['jugador_1'] ?? 0) === 0 && (int)($cruce['pareja_2']['jugador_2'] ?? 0) === 0);
                            $jugador1_1 = $esPlaceholder1 ? null : collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                            $jugador1_2 = $esPlaceholder1 ? null : collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                            $jugador2_1 = $esPlaceholder2 ? null : collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                            $jugador2_2 = $esPlaceholder2 ? null : collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                            $partido = $cruce['partido'] ?? null;
                            $__sv = isset($cruce['sets_visual']) && is_array($cruce['sets_visual']) ? $cruce['sets_visual'] : null;
                            $pareja1_set1 = $__sv ? ($__sv['pareja_1_set_1'] ?? 0) : ($partido ? ($partido->pareja_1_set_1 ?? 0) : 0);
                            $pareja1_set2 = $__sv ? ($__sv['pareja_1_set_2'] ?? 0) : ($partido ? ($partido->pareja_1_set_2 ?? 0) : 0);
                            $pareja1_set3 = $__sv ? ($__sv['pareja_1_set_3'] ?? 0) : ($partido ? ($partido->pareja_1_set_3 ?? 0) : 0);
                            $pareja2_set1 = $__sv ? ($__sv['pareja_2_set_1'] ?? 0) : ($partido ? ($partido->pareja_2_set_1 ?? 0) : 0);
                            $pareja2_set2 = $__sv ? ($__sv['pareja_2_set_2'] ?? 0) : ($partido ? ($partido->pareja_2_set_2 ?? 0) : 0);
                            $pareja2_set3 = $__sv ? ($__sv['pareja_2_set_3'] ?? 0) : ($partido ? ($partido->pareja_2_set_3 ?? 0) : 0);
                        @endphp
                        @include('bahia_padel.admin.torneo.partials.cruce_card_octavos', ['cruce' => $cruce, 'jugadores' => $jugadores, 'esPlaceholder1' => $esPlaceholder1, 'esPlaceholder2' => $esPlaceholder2, 'jugador1_1' => $jugador1_1, 'jugador1_2' => $jugador1_2, 'jugador2_1' => $jugador2_1, 'jugador2_2' => $jugador2_2, 'pareja1_set1' => $pareja1_set1, 'pareja1_set2' => $pareja1_set2, 'pareja1_set3' => $pareja1_set3, 'pareja2_set1' => $pareja2_set1, 'pareja2_set2' => $pareja2_set2, 'pareja2_set3' => $pareja2_set3])
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Octavos de Final -->
            <div class="bracket-column">
                <div class="bracket-round">
                    <div class="bracket-round-title">Octavos Final</div>
                    @foreach($crucesOctavos as $cruce)
                        @php
                            // Obtener datos de los jugadores (pueden ser null si jugador=0 placeholder)
                            $esPlaceholder1 = ((int)($cruce['pareja_1']['jugador_1'] ?? 0) === 0 && (int)($cruce['pareja_1']['jugador_2'] ?? 0) === 0);
                            $esPlaceholder2 = ((int)($cruce['pareja_2']['jugador_1'] ?? 0) === 0 && (int)($cruce['pareja_2']['jugador_2'] ?? 0) === 0);
                            $jugador1_1 = $esPlaceholder1 ? null : collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                            $jugador1_2 = $esPlaceholder1 ? null : collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                            $jugador2_1 = $esPlaceholder2 ? null : collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                            $jugador2_2 = $esPlaceholder2 ? null : collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                            
                            // Obtener resultados del partido si existen
                            $partido = $cruce['partido'] ?? null;
                            $__sv = isset($cruce['sets_visual']) && is_array($cruce['sets_visual']) ? $cruce['sets_visual'] : null;
                            $pareja1_set1 = $__sv ? ($__sv['pareja_1_set_1'] ?? 0) : ($partido ? ($partido->pareja_1_set_1 ?? 0) : 0);
                            $pareja1_set2 = $__sv ? ($__sv['pareja_1_set_2'] ?? 0) : ($partido ? ($partido->pareja_1_set_2 ?? 0) : 0);
                            $pareja1_set3 = $__sv ? ($__sv['pareja_1_set_3'] ?? 0) : ($partido ? ($partido->pareja_1_set_3 ?? 0) : 0);
                            $pareja2_set1 = $__sv ? ($__sv['pareja_2_set_1'] ?? 0) : ($partido ? ($partido->pareja_2_set_1 ?? 0) : 0);
                            $pareja2_set2 = $__sv ? ($__sv['pareja_2_set_2'] ?? 0) : ($partido ? ($partido->pareja_2_set_2 ?? 0) : 0);
                            $pareja2_set3 = $__sv ? ($__sv['pareja_2_set_3'] ?? 0) : ($partido ? ($partido->pareja_2_set_3 ?? 0) : 0);
                        @endphp
                        @php
                            $ref1 = $cruce['referencia_1'] ?? '';
                            $ref2 = $cruce['referencia_2'] ?? '';
                        @endphp
                        <!-- CARD DE PARTIDO -->
                        <div class="match-card" 
                             data-cruce-id="{{ $cruce['id'] }}" 
                             data-ronda="{{ $cruce['ronda'] }}" 
                             data-partido-id="{{ $cruce['partido_id'] ?? '' }}" 
                             data-llave-ref1="{{ $ref1 }}"
                             data-llave-ref2="{{ $ref2 }}"
                             style="padding: 15px; margin-bottom: 20px;">
                            @php
                                $diaVal = $cruce['dia'] ?? null;
                                $horarioVal = $cruce['horario'] ?? null;
                                $diaStr = is_string($diaVal) ? trim($diaVal) : '';
                                $horarioStr = is_string($horarioVal) ? trim(preg_replace('/^(\d{2}:\d{2})(:\d{2})?$/', '$1', $horarioVal)) : '';
                                $esDefault = (strpos($diaStr, '2000-01-01') !== false || $diaStr === '2000-01-01') && (empty($horarioStr) || $horarioStr === '00:00');
                                if ($esDefault) {
                                    $diaDisplay = 'N/A';
                                    $horarioDisplay = 'N/A';
                                } else {
                                    $diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
                                    $diaDisplay = $diaStr ? (in_array(strtolower($diaStr), ['viernes','sabado','domingo','lunes','martes','miercoles','jueves']) ? ucfirst($diaStr) : (preg_match('/^\d{4}-\d{2}-\d{2}$/', $diaStr) ? $diasSemana[date('w', strtotime($diaStr))] : $diaStr)) : '—';
                                    $horarioDisplay = $horarioStr ?: '—';
                                }
                            @endphp
                            <div class="small mb-2" style="color: #555;">
                                <span class="d-inline-block mr-2"><strong>Día:</strong> {{ $diaDisplay }}</span>
                                <span><strong>Horario:</strong> {{ $horarioDisplay }}</span>
                            </div>
                            <div class="small text-muted mb-2" style="font-weight: 600;">Llave: {{ $ref1 ?: '—' }} vs {{ $ref2 ?: '—' }}</div>
                            <!-- Pareja 1 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="1"
                                 data-jugador-1="{{ $cruce['pareja_1']['jugador_1'] }}"
                                 data-jugador-2="{{ $cruce['pareja_1']['jugador_2'] }}">
                                @if($esPlaceholder1)
                                <div class="d-flex align-items-center" style="min-height: 60px;">
                                    <span class="text-muted font-italic" style="font-size: 0.9rem;">{{ ($tiene16avos ?? false) ? 'Esperando ganador (de 16avos)' : 'Esperando clasificación' }}</span>
                                </div>
                                @else
                                <!-- Imágenes -->
                                <div class="d-flex mr-3">
                                    <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                </div>
                                <!-- Nombres a la derecha -->
                                <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}
                                    </div>
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}
                                    </div>
                                </div>
                                @endif
                            </div>
                            
                            <!-- Inputs Sets Pareja 1 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="1"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set1 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="2"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set2 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="3"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set3 }}"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inputs Sets Pareja 2 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="1"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set1 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="2"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set2 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="3"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set3 }}"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pareja 2 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="2"
                                 data-jugador-1="{{ $cruce['pareja_2']['jugador_1'] }}"
                                 data-jugador-2="{{ $cruce['pareja_2']['jugador_2'] }}">
                                @if($esPlaceholder2)
                                <div class="d-flex align-items-center" style="min-height: 60px;">
                                    <span class="text-muted font-italic" style="font-size: 0.9rem;">{{ ($tiene16avos ?? false) ? 'Esperando ganador (de 16avos)' : 'Esperando clasificación' }}</span>
                                </div>
                                @else
                                <!-- Imágenes -->
                                <div class="d-flex mr-3">
                                    <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                </div>
                                <!-- Nombres a la derecha -->
                                <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}
                                    </div>
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}
                                    </div>
                                </div>
                                @endif
                            </div>
                            
                            <!-- Botón guardar -->
                            <div class="text-center mt-2">
                                <button type="button" 
                                        class="btn btn-primary btn-sm guardar-cruce" 
                                        data-cruce-id="{{ $cruce['id'] }}"
                                        data-ronda="{{ $cruce['ronda'] }}">
                                    Guardar
                                </button>
                            </div>
                        </div>
                        <!-- FIN CARD DE PARTIDO -->
                    @endforeach
                </div>
            </div>
            
            <!-- Cuartos de Final -->
            @if(count($crucesCuartos) > 0)
            <div class="bracket-column">
                <div class="bracket-round">
                    <div class="bracket-round-title">Cuartos Final</div>
                    @foreach($crucesCuartos as $cruce)
                        @php
                            $p1j1 = isset($cruce['pareja_1']) ? ($cruce['pareja_1']['jugador_1'] ?? null) : null;
                            $p2j1 = isset($cruce['pareja_2']) ? ($cruce['pareja_2']['jugador_1'] ?? null) : null;
                            $pareja1Esperando = !isset($cruce['pareja_1']) || $p1j1 === null || (int)$p1j1 === 0;
                            $pareja2Esperando = !isset($cruce['pareja_2']) || $p2j1 === null || (int)$p2j1 === 0;
                            
                            // Obtener datos de los jugadores solo si no están esperando
                            $jugador1_1 = !$pareja1Esperando ? collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']) : null;
                            $jugador1_2 = !$pareja1Esperando ? collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']) : null;
                            $jugador2_1 = !$pareja2Esperando ? collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']) : null;
                            $jugador2_2 = !$pareja2Esperando ? collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']) : null;
                            
                            // Obtener resultados del partido si existen
                            $partido = $cruce['partido'] ?? null;
                            $__sv = isset($cruce['sets_visual']) && is_array($cruce['sets_visual']) ? $cruce['sets_visual'] : null;
                            $pareja1_set1 = $__sv ? ($__sv['pareja_1_set_1'] ?? 0) : ($partido ? ($partido->pareja_1_set_1 ?? 0) : 0);
                            $pareja1_set2 = $__sv ? ($__sv['pareja_1_set_2'] ?? 0) : ($partido ? ($partido->pareja_1_set_2 ?? 0) : 0);
                            $pareja1_set3 = $__sv ? ($__sv['pareja_1_set_3'] ?? 0) : ($partido ? ($partido->pareja_1_set_3 ?? 0) : 0);
                            $pareja2_set1 = $__sv ? ($__sv['pareja_2_set_1'] ?? 0) : ($partido ? ($partido->pareja_2_set_1 ?? 0) : 0);
                            $pareja2_set2 = $__sv ? ($__sv['pareja_2_set_2'] ?? 0) : ($partido ? ($partido->pareja_2_set_2 ?? 0) : 0);
                            $pareja2_set3 = $__sv ? ($__sv['pareja_2_set_3'] ?? 0) : ($partido ? ($partido->pareja_2_set_3 ?? 0) : 0);
                            $ref1 = $cruce['referencia_1'] ?? '';
                            $ref2 = $cruce['referencia_2'] ?? '';
                        @endphp
                        <!-- CARD DE PARTIDO -->
                        <div class="match-card" 
                             data-cruce-id="{{ $cruce['id'] }}" 
                             data-ronda="{{ $cruce['ronda'] }}" 
                             data-partido-id="{{ $cruce['partido_id'] ?? '' }}" 
                             data-llave-ref1="{{ $ref1 }}"
                             data-llave-ref2="{{ $ref2 }}"
                             style="padding: 15px; margin-bottom: 20px;">
                            @php
                                $diaVal = $cruce['dia'] ?? null;
                                $horarioVal = $cruce['horario'] ?? null;
                                $diaStr = is_string($diaVal) ? trim($diaVal) : '';
                                $horarioStr = is_string($horarioVal) ? trim(preg_replace('/^(\d{2}:\d{2})(:\d{2})?$/', '$1', $horarioVal ?? '')) : '';
                                $esDefault = (strpos($diaStr, '2000-01-01') !== false || $diaStr === '2000-01-01') && (empty($horarioStr) || $horarioStr === '00:00');
                                $diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
                                $diaDisplay = $esDefault ? 'N/A' : ($diaStr ? (in_array(strtolower($diaStr), ['viernes','sabado','domingo','lunes','martes','miercoles','jueves']) ? ucfirst($diaStr) : (strlen($diaStr) === 10 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $diaStr) ? $diasSemana[date('w', strtotime($diaStr))] : $diaStr)) : '—');
                                $horarioDisplay = $esDefault ? 'N/A' : ($horarioStr ?: '—');
                            @endphp
                            <div class="small mb-2" style="color: #555;">
                                <span class="d-inline-block mr-2"><strong>Día:</strong> {{ $diaDisplay }}</span>
                                <span><strong>Horario:</strong> {{ $horarioDisplay }}</span>
                            </div>
                            <div class="small text-muted mb-2" style="font-weight: 600;">Llave: {{ $ref1 ?: '—' }} vs {{ $ref2 ?: '—' }}</div>
                            <!-- Pareja 1 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="1"
                                 data-jugador-1="{{ $pareja1Esperando ? '' : ($cruce['pareja_1']['jugador_1'] ?? '') }}"
                                 data-jugador-2="{{ $pareja1Esperando ? '' : ($cruce['pareja_1']['jugador_2'] ?? '') }}">
                                @if($pareja1Esperando)
                                    <div class="d-flex align-items-center justify-content-center" style="width: 100%; padding: 20px; border: 2px dashed #ccc; border-radius: 8px; background-color: #f8f9fa;">
                                        <span style="color: #666; font-weight: bold; font-size: 0.9rem;">Esperando ganador ({{ $ref1 ?: '?' }})</span>
                                    </div>
                                @else
                                    <!-- Imágenes -->
                                    <div class="d-flex mr-3">
                                        <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" 
                                             alt="{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}" 
                                             class="rounded-circle"
                                             style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;"
                                             onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                        <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" 
                                             alt="{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}" 
                                             class="rounded-circle"
                                             style="width: 60px; height: 60px; object-fit: cover;"
                                             onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    </div>
                                    <!-- Nombres a la derecha -->
                                    <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                        <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                            {{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}
                                        </div>
                                        <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                            {{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Inputs Sets Pareja 1 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="1"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set1 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="2"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set2 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="3"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set3 }}"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inputs Sets Pareja 2 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="1"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set1 }}"
                                               placeholder="0"
                                               @if($pareja2Esperando) disabled @endif>
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="2"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set2 }}"
                                               placeholder="0"
                                               @if($pareja2Esperando) disabled @endif>
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="3"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set3 }}"
                                               placeholder="0"
                                               @if($pareja2Esperando) disabled @endif>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pareja 2 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="2"
                                 data-jugador-1="{{ $pareja2Esperando ? '' : ($cruce['pareja_2']['jugador_1'] ?? '') }}"
                                 data-jugador-2="{{ $pareja2Esperando ? '' : ($cruce['pareja_2']['jugador_2'] ?? '') }}">
                                @if($pareja2Esperando)
                                    <div class="d-flex align-items-center justify-content-center" style="width: 100%; padding: 20px; border: 2px dashed #ccc; border-radius: 8px; background-color: #f8f9fa;">
                                        <span style="color: #666; font-weight: bold; font-size: 0.9rem;">Esperando ganador ({{ $ref2 ?: '?' }})</span>
                                    </div>
                                @else
                                    <!-- Imágenes -->
                                    <div class="d-flex mr-3">
                                        <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" 
                                             alt="{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}" 
                                             class="rounded-circle"
                                             style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;"
                                             onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                        <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" 
                                             alt="{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}" 
                                             class="rounded-circle"
                                             style="width: 60px; height: 60px; object-fit: cover;"
                                             onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    </div>
                                    <!-- Nombres a la derecha -->
                                    <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                        <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                            {{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}
                                        </div>
                                        <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                            {{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Botón guardar (solo si ambas parejas están definidas) -->
                            @if(!$pareja1Esperando && !$pareja2Esperando)
                            <div class="text-center mt-2">
                                <button type="button" 
                                        class="btn btn-primary btn-sm guardar-cruce" 
                                        data-cruce-id="{{ $cruce['id'] }}"
                                        data-ronda="{{ $cruce['ronda'] }}">
                                    Guardar
                                </button>
                            </div>
                            @endif
                        </div>
                        <!-- FIN CARD DE PARTIDO -->
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Semifinales -->
            @if(count($crucesSemifinales) > 0)
            <div class="bracket-column">
                <div class="bracket-round">
                    <div class="bracket-round-title">Semifinales</div>
                    @foreach($crucesSemifinales as $cruce)
                        @php
                            $p1j1 = isset($cruce['pareja_1']) ? ($cruce['pareja_1']['jugador_1'] ?? null) : null;
                            $p2j1 = isset($cruce['pareja_2']) ? ($cruce['pareja_2']['jugador_1'] ?? null) : null;
                            $pareja1Esperando = !isset($cruce['pareja_1']) || $p1j1 === null || (int)$p1j1 === 0;
                            $pareja2Esperando = !isset($cruce['pareja_2']) || $p2j1 === null || (int)$p2j1 === 0;
                            $jugador1_1 = !$pareja1Esperando ? collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']) : null;
                            $jugador1_2 = !$pareja1Esperando ? collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']) : null;
                            $jugador2_1 = !$pareja2Esperando ? collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']) : null;
                            $jugador2_2 = !$pareja2Esperando ? collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']) : null;
                            $partido = $cruce['partido'] ?? null;
                            $__sv = isset($cruce['sets_visual']) && is_array($cruce['sets_visual']) ? $cruce['sets_visual'] : null;
                            $pareja1_set1 = $__sv ? ($__sv['pareja_1_set_1'] ?? 0) : ($partido ? ($partido->pareja_1_set_1 ?? 0) : 0);
                            $pareja1_set2 = $__sv ? ($__sv['pareja_1_set_2'] ?? 0) : ($partido ? ($partido->pareja_1_set_2 ?? 0) : 0);
                            $pareja1_set3 = $__sv ? ($__sv['pareja_1_set_3'] ?? 0) : ($partido ? ($partido->pareja_1_set_3 ?? 0) : 0);
                            $pareja2_set1 = $__sv ? ($__sv['pareja_2_set_1'] ?? 0) : ($partido ? ($partido->pareja_2_set_1 ?? 0) : 0);
                            $pareja2_set2 = $__sv ? ($__sv['pareja_2_set_2'] ?? 0) : ($partido ? ($partido->pareja_2_set_2 ?? 0) : 0);
                            $pareja2_set3 = $__sv ? ($__sv['pareja_2_set_3'] ?? 0) : ($partido ? ($partido->pareja_2_set_3 ?? 0) : 0);
                            $ref1 = $cruce['referencia_1'] ?? '';
                            $ref2 = $cruce['referencia_2'] ?? '';
                        @endphp
                        <!-- CARD DE PARTIDO -->
                        <div class="match-card" 
                             data-cruce-id="{{ $cruce['id'] }}" 
                             data-ronda="{{ $cruce['ronda'] }}" 
                             data-partido-id="{{ $cruce['partido_id'] ?? '' }}" 
                             data-llave-ref1="{{ $ref1 }}"
                             data-llave-ref2="{{ $ref2 }}"
                             style="padding: 15px; margin-bottom: 20px;">
                            @php
                                $diaVal = $cruce['dia'] ?? null;
                                $horarioVal = $cruce['horario'] ?? null;
                                $diaStr = is_string($diaVal) ? trim($diaVal) : '';
                                $horarioStr = is_string($horarioVal) ? trim(preg_replace('/^(\d{2}:\d{2})(:\d{2})?$/', '$1', $horarioVal ?? '')) : '';
                                $esDefault = (strpos($diaStr, '2000-01-01') !== false || $diaStr === '2000-01-01') && (empty($horarioStr) || $horarioStr === '00:00');
                                $diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
                                $diaDisplay = $esDefault ? 'N/A' : ($diaStr ? (in_array(strtolower($diaStr), ['viernes','sabado','domingo','lunes','martes','miercoles','jueves']) ? ucfirst($diaStr) : (strlen($diaStr) === 10 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $diaStr) ? $diasSemana[date('w', strtotime($diaStr))] : $diaStr)) : '—');
                                $horarioDisplay = $esDefault ? 'N/A' : ($horarioStr ?: '—');
                            @endphp
                            <div class="small mb-2" style="color: #555;">
                                <span class="d-inline-block mr-2"><strong>Día:</strong> {{ $diaDisplay }}</span>
                                <span><strong>Horario:</strong> {{ $horarioDisplay }}</span>
                            </div>
                            <div class="small text-muted mb-2" style="font-weight: 600;">Llave: {{ $ref1 ?: '—' }} vs {{ $ref2 ?: '—' }}</div>
                            <!-- Pareja 1 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="1"
                                 data-jugador-1="{{ $pareja1Esperando ? '' : ($cruce['pareja_1']['jugador_1'] ?? '') }}"
                                 data-jugador-2="{{ $pareja1Esperando ? '' : ($cruce['pareja_1']['jugador_2'] ?? '') }}">
                                @if($pareja1Esperando)
                                    <div class="d-flex align-items-center justify-content-center" style="width: 100%; padding: 20px; border: 2px dashed #ccc; border-radius: 8px; background-color: #f8f9fa;">
                                        <span style="color: #666; font-weight: bold; font-size: 0.9rem;">Esperando ganador ({{ $ref1 ?: '?' }})</span>
                                    </div>
                                @else
                                <!-- Imágenes -->
                                <div class="d-flex mr-3">
                                    <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                </div>
                                <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}
                                    </div>
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}
                                    </div>
                                </div>
                                @endif
                            </div>
                            
                            <!-- Inputs Sets Pareja 1 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="1" data-set="1" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja1_set1 }}" placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="1" data-set="2" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja1_set2 }}" placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="1" data-set="3" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja1_set3 }}" placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inputs Sets Pareja 2 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="2" data-set="1" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja2_set1 }}" placeholder="0" @if($pareja2Esperando) disabled @endif>
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="2" data-set="2" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja2_set2 }}" placeholder="0" @if($pareja2Esperando) disabled @endif>
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="2" data-set="3" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja2_set3 }}" placeholder="0" @if($pareja2Esperando) disabled @endif>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pareja 2 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="2"
                                 data-jugador-1="{{ $pareja2Esperando ? '' : ($cruce['pareja_2']['jugador_1'] ?? '') }}"
                                 data-jugador-2="{{ $pareja2Esperando ? '' : ($cruce['pareja_2']['jugador_2'] ?? '') }}">
                                @if($pareja2Esperando)
                                    <div class="d-flex align-items-center justify-content-center" style="width: 100%; padding: 20px; border: 2px dashed #ccc; border-radius: 8px; background-color: #f8f9fa;">
                                        <span style="color: #666; font-weight: bold; font-size: 0.9rem;">Esperando ganador ({{ $ref2 ?: '?' }})</span>
                                    </div>
                                @else
                                <div class="d-flex mr-3">
                                    <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" alt="" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" alt="" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                </div>
                                <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}</div>
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}</div>
                                </div>
                                @endif
                            </div>
                            
                            @if(!$pareja1Esperando && !$pareja2Esperando)
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="{{ $cruce['id'] }}" data-ronda="{{ $cruce['ronda'] }}">Guardar</button>
                            </div>
                            @endif
                        </div>
                        <!-- FIN CARD DE PARTIDO -->
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Final -->
            @if(count($crucesFinales) > 0)
            <div class="bracket-column">
                <div class="bracket-round">
                    <div class="bracket-round-title">Final</div>
                    @foreach($crucesFinales as $cruce)
                        @php
                            $p1j1 = isset($cruce['pareja_1']) ? ($cruce['pareja_1']['jugador_1'] ?? null) : null;
                            $p2j1 = isset($cruce['pareja_2']) ? ($cruce['pareja_2']['jugador_1'] ?? null) : null;
                            $pareja1Esperando = !isset($cruce['pareja_1']) || $p1j1 === null || (int)$p1j1 === 0;
                            $pareja2Esperando = !isset($cruce['pareja_2']) || $p2j1 === null || (int)$p2j1 === 0;
                            $jugador1_1 = !$pareja1Esperando ? collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']) : null;
                            $jugador1_2 = !$pareja1Esperando ? collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']) : null;
                            $jugador2_1 = !$pareja2Esperando ? collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']) : null;
                            $jugador2_2 = !$pareja2Esperando ? collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']) : null;
                            $partido = $cruce['partido'] ?? null;
                            $__sv = isset($cruce['sets_visual']) && is_array($cruce['sets_visual']) ? $cruce['sets_visual'] : null;
                            $pareja1_set1 = $__sv ? ($__sv['pareja_1_set_1'] ?? 0) : ($partido ? ($partido->pareja_1_set_1 ?? 0) : 0);
                            $pareja1_set2 = $__sv ? ($__sv['pareja_1_set_2'] ?? 0) : ($partido ? ($partido->pareja_1_set_2 ?? 0) : 0);
                            $pareja1_set3 = $__sv ? ($__sv['pareja_1_set_3'] ?? 0) : ($partido ? ($partido->pareja_1_set_3 ?? 0) : 0);
                            $pareja2_set1 = $__sv ? ($__sv['pareja_2_set_1'] ?? 0) : ($partido ? ($partido->pareja_2_set_1 ?? 0) : 0);
                            $pareja2_set2 = $__sv ? ($__sv['pareja_2_set_2'] ?? 0) : ($partido ? ($partido->pareja_2_set_2 ?? 0) : 0);
                            $pareja2_set3 = $__sv ? ($__sv['pareja_2_set_3'] ?? 0) : ($partido ? ($partido->pareja_2_set_3 ?? 0) : 0);
                            $ref1 = $cruce['referencia_1'] ?? '';
                            $ref2 = $cruce['referencia_2'] ?? '';
                        @endphp
                        <!-- CARD DE PARTIDO -->
                        <div class="match-card" 
                             data-cruce-id="{{ $cruce['id'] }}" 
                             data-ronda="{{ $cruce['ronda'] }}" 
                             data-partido-id="{{ $cruce['partido_id'] ?? '' }}" 
                             data-llave-ref1="{{ $ref1 }}"
                             data-llave-ref2="{{ $ref2 }}"
                             style="padding: 15px; margin-bottom: 20px;">
                            @php
                                $diaVal = $cruce['dia'] ?? null;
                                $horarioVal = $cruce['horario'] ?? null;
                                $diaStr = is_string($diaVal) ? trim($diaVal) : '';
                                $horarioStr = is_string($horarioVal) ? trim(preg_replace('/^(\d{2}:\d{2})(:\d{2})?$/', '$1', $horarioVal ?? '')) : '';
                                $esDefault = (strpos($diaStr, '2000-01-01') !== false || $diaStr === '2000-01-01') && (empty($horarioStr) || $horarioStr === '00:00');
                                $diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
                                $diaDisplay = $esDefault ? 'N/A' : ($diaStr ? (in_array(strtolower($diaStr), ['viernes','sabado','domingo','lunes','martes','miercoles','jueves']) ? ucfirst($diaStr) : (strlen($diaStr) === 10 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $diaStr) ? $diasSemana[date('w', strtotime($diaStr))] : $diaStr)) : '—');
                                $horarioDisplay = $esDefault ? 'N/A' : ($horarioStr ?: '—');
                            @endphp
                            <div class="small mb-2" style="color: #555;">
                                <span class="d-inline-block mr-2"><strong>Día:</strong> {{ $diaDisplay }}</span>
                                <span><strong>Horario:</strong> {{ $horarioDisplay }}</span>
                            </div>
                            <div class="small text-muted mb-2" style="font-weight: 600;">Llave: {{ $ref1 ?: '—' }} vs {{ $ref2 ?: '—' }}</div>
                            <!-- Pareja 1 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="1"
                                 data-jugador-1="{{ $pareja1Esperando ? '' : ($cruce['pareja_1']['jugador_1'] ?? '') }}"
                                 data-jugador-2="{{ $pareja1Esperando ? '' : ($cruce['pareja_1']['jugador_2'] ?? '') }}">
                                @if($pareja1Esperando)
                                    <div class="d-flex align-items-center justify-content-center" style="width: 100%; padding: 20px; border: 2px dashed #ccc; border-radius: 8px; background-color: #f8f9fa;">
                                        <span style="color: #666; font-weight: bold; font-size: 0.9rem;">Esperando ganador ({{ $ref1 ?: '?' }})</span>
                                    </div>
                                @else
                                <div class="d-flex mr-3">
                                    <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" alt="" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" alt="" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                </div>
                                <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}</div>
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}</div>
                                </div>
                                @endif
                            </div>
                            
                            <!-- Inputs Sets Pareja 1 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="1"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set1 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="2"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set2 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="3"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set3 }}"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inputs Sets Pareja 2 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="2" data-set="1" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja2_set1 }}" placeholder="0" @if($pareja2Esperando) disabled @endif>
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="2" data-set="2" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja2_set2 }}" placeholder="0" @if($pareja2Esperando) disabled @endif>
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="2" data-set="3" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja2_set3 }}" placeholder="0" @if($pareja2Esperando) disabled @endif>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pareja 2 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="2"
                                 data-jugador-1="{{ $pareja2Esperando ? '' : ($cruce['pareja_2']['jugador_1'] ?? '') }}"
                                 data-jugador-2="{{ $pareja2Esperando ? '' : ($cruce['pareja_2']['jugador_2'] ?? '') }}">
                                @if($pareja2Esperando)
                                    <div class="d-flex align-items-center justify-content-center" style="width: 100%; padding: 20px; border: 2px dashed #ccc; border-radius: 8px; background-color: #f8f9fa;">
                                        <span style="color: #666; font-weight: bold; font-size: 0.9rem;">Esperando ganador ({{ $ref2 ?: '?' }})</span>
                                    </div>
                                @else
                                <div class="d-flex mr-3">
                                    <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" alt="" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" alt="" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                </div>
                                <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}</div>
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}</div>
                                </div>
                                @endif
                            </div>
                            
                            @if(!$pareja1Esperando && !$pareja2Esperando)
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="{{ $cruce['id'] }}" data-ronda="{{ $cruce['ronda'] }}">Guardar</button>
                            </div>
                            @endif
                        </div>
                        <!-- FIN CARD DE PARTIDO -->
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Asignar Puntos al Ranking -->
<div class="modal fade" id="modalAsignarPuntos" tabindex="-1" role="dialog" aria-labelledby="modalAsignarPuntosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAsignarPuntosLabel">Asignar Puntos al Ranking</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Jugadores que participaron en el torneo. Asigne la posición y el puntaje a cada uno y pulse Guardar.</p>
                <div id="modal-asignar-puntos-loading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div>
                </div>
                <div id="modal-asignar-puntos-content" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Jugador</th>
                                    <th style="width: 220px;">Posición</th>
                                    <th style="width: 120px;">Puntaje</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-puntos-jugadores">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="modal-asignar-puntos-empty" class="text-muted text-center py-4" style="display: none;">
                    No hay jugadores participantes para este torneo.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-puntos-ranking">
                    <i class="fa fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Snackbar -->
<div id="snackbar" class="snackbar">Resultado guardado correctamente</div>

<script type="text/javascript">
    console.log('Script cargado. Verificando jQuery...');
    console.log('jQuery disponible:', typeof jQuery !== 'undefined');
    console.log('$ disponible:', typeof $ !== 'undefined');
    
    $(document).ready(function() {
        console.log('Document ready ejecutado');
        console.log('Botones .guardar-cruce encontrados:', $('.guardar-cruce').length);
    });
    
    let torneoId = $('#torneo_id').val();
    let resultadosGuardados = @json($resultadosGuardados ?? []);
    
    console.log('Torneo ID:', torneoId);
    console.log('Resultados guardados:', resultadosGuardados.length);
    
    // Función para mostrar snackbar
    function mostrarSnackbar(mensaje) {
        let snackbar = document.getElementById("snackbar");
        snackbar.textContent = mensaje;
        snackbar.className = "snackbar show";
        setTimeout(function(){ snackbar.className = snackbar.className.replace("show", ""); }, 3000);
    }
    
    // Función para verificar ganador y deshabilitar set 3
    function verificarGanadorYDeshabilitarSet3(cruceId) {
        let matchCard = $(`.match-card[data-cruce-id="${cruceId}"]`);
        let pareja1Set1 = parseInt(matchCard.find('input[data-pareja="1"][data-set="1"]').val()) || 0;
        let pareja1Set2 = parseInt(matchCard.find('input[data-pareja="1"][data-set="2"]').val()) || 0;
        let pareja2Set1 = parseInt(matchCard.find('input[data-pareja="2"][data-set="1"]').val()) || 0;
        let pareja2Set2 = parseInt(matchCard.find('input[data-pareja="2"][data-set="2"]').val()) || 0;
        
        let pareja1SetsGanados = 0;
        let pareja2SetsGanados = 0;
        
        // Contar sets ganados (solo si ambos tienen score > 0)
        if (pareja1Set1 > 0 && pareja2Set1 > 0) {
            if (pareja1Set1 > pareja2Set1) {
                pareja1SetsGanados++;
            } else if (pareja2Set1 > pareja1Set1) {
                pareja2SetsGanados++;
            }
        }
        
        if (pareja1Set2 > 0 && pareja2Set2 > 0) {
            if (pareja1Set2 > pareja2Set2) {
                pareja1SetsGanados++;
            } else if (pareja2Set2 > pareja1Set2) {
                pareja2SetsGanados++;
            }
        }
        
        // Si alguna pareja ganó 2 sets, deshabilitar set 3
        let set3Pareja1 = matchCard.find('input[data-pareja="1"][data-set="3"]');
        let set3Pareja2 = matchCard.find('input[data-pareja="2"][data-set="3"]');
        
        if (pareja1SetsGanados >= 2 || pareja2SetsGanados >= 2) {
            set3Pareja1.prop('disabled', true).val('');
            set3Pareja2.prop('disabled', true).val('');
        } else {
            set3Pareja1.prop('disabled', false);
            set3Pareja2.prop('disabled', false);
        }
    }
    
    // Cargar resultados guardados al cargar la página
    function cargarResultadosGuardados() {
        if (!resultadosGuardados || resultadosGuardados.length === 0) {
            return;
        }
        
        resultadosGuardados.forEach(function(resultado) {
            let cruceId = resultado.cruce_id;
            let matchCard = $(`.match-card[data-cruce-id="${cruceId}"]`);
            
            if (matchCard.length === 0) {
                return;
            }
            
            // Cargar valores de los sets
            if (resultado.pareja_1_set_1 !== null && resultado.pareja_1_set_1 !== undefined) {
                matchCard.find('input[data-pareja="1"][data-set="1"]').val(resultado.pareja_1_set_1);
            }
            if (resultado.pareja_1_set_2 !== null && resultado.pareja_1_set_2 !== undefined) {
                matchCard.find('input[data-pareja="1"][data-set="2"]').val(resultado.pareja_1_set_2);
            }
            if (resultado.pareja_1_set_3 !== null && resultado.pareja_1_set_3 !== undefined) {
                matchCard.find('input[data-pareja="1"][data-set="3"]').val(resultado.pareja_1_set_3);
            }
            if (resultado.pareja_2_set_1 !== null && resultado.pareja_2_set_1 !== undefined) {
                matchCard.find('input[data-pareja="2"][data-set="1"]').val(resultado.pareja_2_set_1);
            }
            if (resultado.pareja_2_set_2 !== null && resultado.pareja_2_set_2 !== undefined) {
                matchCard.find('input[data-pareja="2"][data-set="2"]').val(resultado.pareja_2_set_2);
            }
            if (resultado.pareja_2_set_3 !== null && resultado.pareja_2_set_3 !== undefined) {
                matchCard.find('input[data-pareja="2"][data-set="3"]').val(resultado.pareja_2_set_3);
            }
            
            // Verificar ganador después de cargar
            verificarGanadorYDeshabilitarSet3(cruceId);
        });
    }
    
    /**
     * Actualiza la llave siguiente (cuartos, semifinales, final) mostrando al ganador en el slot correspondiente sin recargar.
     * @param {Object} ganadorLlave - { refs: string[], ronda_siguiente: string, jugador_1, jugador_2, nombre1, nombre2, foto1, foto2 }
     */
    function actualizarLlaveSiguienteConGanador(ganadorLlave) {
        if (!ganadorLlave || !ganadorLlave.refs || !ganadorLlave.ronda_siguiente) return;
        var titulosRonda = {
            'octavos': 'Octavos Final',
            'cuartos': 'Cuartos Final',
            'semifinales': 'Semifinales',
            'final': 'Final'
        };
        var titulo = titulosRonda[ganadorLlave.ronda_siguiente] || ganadorLlave.ronda_siguiente;
        var $ronda = $('.bracket-round-title').filter(function() { return $(this).text().trim() === titulo; }).closest('.bracket-round');
        if ($ronda.length === 0) return;
        var $cards = $ronda.find('.match-card');
        var refs = Array.isArray(ganadorLlave.refs) ? ganadorLlave.refs : [ganadorLlave.refs];
        var htmlGanador = '<div class="d-flex mr-3">' +
            '<img src="' + (ganadorLlave.foto1 || '') + '" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;" onerror="this.src=\'{{ asset("images/jugador_img.png") }}\'">' +
            '<img src="' + (ganadorLlave.foto2 || '') + '" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;" onerror="this.src=\'{{ asset("images/jugador_img.png") }}\'">' +
            '</div>' +
            '<div class="d-flex flex-column justify-content-center" style="height: 60px;">' +
            '<div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">' + (ganadorLlave.nombre1 || '') + '</div>' +
            '<div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">' + (ganadorLlave.nombre2 || '') + '</div>' +
            '</div>';
        $cards.each(function() {
            var $card = $(this);
            var ref1 = ($card.attr('data-llave-ref1') || '').trim();
            var ref2 = ($card.attr('data-llave-ref2') || '').trim();
            var slotActualizar = null;
            var matchRef = function(r) { return refs.some(function(ref) { return String(ref).toUpperCase() === String(r).toUpperCase(); }); };
            if (matchRef(ref1)) slotActualizar = 1;
            else if (matchRef(ref2)) slotActualizar = 2;
            if (!slotActualizar) return;
            // Slot es el div con data-pareja y data-jugador-1 (no los inputs de sets)
            var $slot = $card.find('div[data-pareja="' + slotActualizar + '"][data-jugador-1]').first();
            if ($slot.length === 0) return;
            if ($slot.find('.d-flex.mr-3 img.rounded-circle').length >= 2) return; // ya tiene pareja asignada (2 fotos)
            $slot.attr('data-jugador-1', ganadorLlave.jugador_1 || '').attr('data-jugador-2', ganadorLlave.jugador_2 || '');
            $slot.empty().append(htmlGanador);
            $card.find('input.resultado-cruce[data-pareja="2"]').prop('disabled', false);
            if (ganadorLlave.partido_id_siguiente) {
                $card.attr('data-partido-id', ganadorLlave.partido_id_siguiente);
            }
            // Si ambas parejas ya están definidas, mostrar botón Guardar si no existe
            if ($card.find('[data-pareja="1"] .d-flex.mr-3').length && $card.find('[data-pareja="2"] .d-flex.mr-3').length && $card.find('.guardar-cruce').length === 0) {
                $card.append('<div class="text-center mt-2"><button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="' + ($card.attr('data-cruce-id') || '') + '" data-ronda="' + ($card.attr('data-ronda') || '') + '">Guardar</button></div>');
            }
        });
    }
    
    // Event listener para cambios en inputs de sets 1 y 2
    $(document).on('input change', '.resultado-cruce[data-set="1"], .resultado-cruce[data-set="2"]', function() {
        let cruceId = $(this).data('cruce-id');
        verificarGanadorYDeshabilitarSet3(cruceId);
    });
    
    // Guardar resultado de cruce
    $(document).on('click', '.guardar-cruce', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        try {
            console.log('=== BOTÓN GUARDAR CLICKEADO ===');
            
            let cruceId = $(this).data('cruce-id');
            let ronda = $(this).data('ronda');
            let matchCard = $(this).closest('.match-card');
            
            console.log('Cruce ID:', cruceId);
            console.log('Ronda:', ronda);
            console.log('Match Card encontrado:', matchCard.length > 0);
            
            if (matchCard.length === 0) {
                console.error('ERROR: No se encontró el match-card');
                mostrarSnackbar('Error: No se encontró la tarjeta del partido');
                return;
            }
            
            // Obtener información de las parejas
            console.log('Buscando elementos de pareja...');
            // Buscar el div que tiene los atributos data-jugador-1 y data-jugador-2 (no los inputs)
            let pareja1Element = matchCard.find('[data-pareja="1"][data-jugador-1]').first();
            let pareja2Element = matchCard.find('[data-pareja="2"][data-jugador-1]').first();
        
            console.log('Pareja 1 element encontrado:', pareja1Element.length);
            console.log('Pareja 2 element encontrado:', pareja2Element.length);
            
            if (pareja1Element.length === 0 || pareja2Element.length === 0) {
                console.error('ERROR: No se encontraron los elementos de pareja');
                mostrarSnackbar('Error: No se encontraron los elementos de pareja');
                return;
            }
            
            let pareja1Jugador1 = parseInt(pareja1Element.attr('data-jugador-1'));
            let pareja1Jugador2 = parseInt(pareja1Element.attr('data-jugador-2'));
            let pareja2Jugador1 = parseInt(pareja2Element.attr('data-jugador-1'));
            let pareja2Jugador2 = parseInt(pareja2Element.attr('data-jugador-2'));
            
            console.log('Jugadores obtenidos - Pareja 1:', pareja1Jugador1, pareja1Jugador2);
            console.log('Jugadores obtenidos - Pareja 2:', pareja2Jugador1, pareja2Jugador2);
        
            // Obtener valores de los sets
            console.log('Obteniendo valores de los sets...');
            let pareja1Set1 = parseInt(matchCard.find('input[data-pareja="1"][data-set="1"]').val()) || 0;
            let pareja1Set2 = parseInt(matchCard.find('input[data-pareja="1"][data-set="2"]').val()) || 0;
            let pareja1Set3 = parseInt(matchCard.find('input[data-pareja="1"][data-set="3"]').val()) || 0;
            let pareja2Set1 = parseInt(matchCard.find('input[data-pareja="2"][data-set="1"]').val()) || 0;
            let pareja2Set2 = parseInt(matchCard.find('input[data-pareja="2"][data-set="2"]').val()) || 0;
            let pareja2Set3 = parseInt(matchCard.find('input[data-pareja="2"][data-set="3"]').val()) || 0;
            
            console.log('Sets obtenidos - Pareja 1:', pareja1Set1, pareja1Set2, pareja1Set3);
            console.log('Sets obtenidos - Pareja 2:', pareja2Set1, pareja2Set2, pareja2Set3);
            
            // Validar que haya al menos un resultado
            if (pareja1Set1 === 0 && pareja1Set2 === 0 && pareja1Set3 === 0 && 
                pareja2Set1 === 0 && pareja2Set2 === 0 && pareja2Set3 === 0) {
                console.log('VALIDACIÓN FALLIDA: No hay resultados ingresados');
                mostrarSnackbar('Debe ingresar al menos un resultado');
                return;
            }
            
            console.log('Validación de resultados: OK');
            
            // Validar que las parejas estén completas
            if (!pareja1Jugador1 || !pareja1Jugador2 || !pareja2Jugador1 || !pareja2Jugador2) {
                console.log('VALIDACIÓN FALLIDA: Parejas incompletas');
                console.log('Pareja 1:', pareja1Jugador1, pareja1Jugador2);
                console.log('Pareja 2:', pareja2Jugador1, pareja2Jugador2);
                mostrarSnackbar('Error: No se encontró información completa de las parejas');
                return;
            }
            
            console.log('Validación de parejas: OK');
            
            // Deshabilitar botón mientras se guarda
            let btnGuardar = $(this);
            btnGuardar.prop('disabled', true).text('Guardando...');
            
            console.log('Preparando datos para enviar...');
            console.log('Torneo ID:', torneoId);
            console.log('Partido ID:', matchCard.data('partido-id'));
            console.log('Sets Pareja 1:', pareja1Set1, pareja1Set2, pareja1Set3);
            console.log('Sets Pareja 2:', pareja2Set1, pareja2Set2, pareja2Set3);
            
            console.log('Iniciando llamada AJAX a guardarresultadopartidopuntuable...');
            console.log('URL:', '{{ route("guardarresultadopartidopuntuable") }}');
            
            let partidoId = matchCard.data('partido-id');
            let datosEnvio = {
                torneo_id: torneoId,
                partido_id: partidoId,
                ronda: ronda,
                cruce_id: cruceId,
                pareja_1_jugador_1: pareja1Jugador1,
                pareja_1_jugador_2: pareja1Jugador2,
                pareja_2_jugador_1: pareja2Jugador1,
                pareja_2_jugador_2: pareja2Jugador2,
                pareja_1_set_1: pareja1Set1,
                pareja_1_set_2: pareja1Set2,
                pareja_1_set_3: pareja1Set3,
                pareja_2_set_1: pareja2Set1,
                pareja_2_set_2: pareja2Set2,
                pareja_2_set_3: pareja2Set3,
                _token: '{{ csrf_token() }}'
            };
            
            // Si no hay partido_id, usar endpoint que busca/crea partido por jugadores (octavos, cuartos, semifinales, final)
            let urlGuardar = (partidoId && partidoId !== '' && partidoId !== '0') 
                ? '{{ route("guardarresultadopartidopuntuable") }}' 
                : '{{ route("guardarresultadocrucepuntuable") }}';
            
            console.log('Datos a enviar:', datosEnvio);
            console.log('URL:', urlGuardar);
            
            $.ajax({
                type: 'POST',
                dataType: 'JSON',
                url: urlGuardar,
                data: datosEnvio,
            success: function(response) {
                console.log('=== RESPUESTA GUARDAR RESULTADO ===');
                console.log('Response completa:', response);
                console.log('Success:', response.success);
                console.log('Message:', response.message);
                console.log('===================================');
                
                if (response.success) {
                    mostrarSnackbar('Resultado guardado correctamente');
                    // Pequeña pausa para ver el aviso, luego recargar (datos alineados con BD: llave, grupos, partido_id)
                    setTimeout(function() {
                        window.location.reload();
                    }, 350);
                } else {
                    btnGuardar.prop('disabled', false).text('Guardar');
                    console.error('Error al guardar resultado:', response);
                    mostrarSnackbar(response.message || 'Error al guardar el resultado');
                }
            },
            error: function(xhr) {
                console.error('=== ERROR AL GUARDAR RESULTADO ===');
                console.error('Status:', xhr.status);
                console.error('Status Text:', xhr.statusText);
                console.error('Response Text:', xhr.responseText);
                if (xhr.responseJSON) {
                    console.error('Response JSON:', xhr.responseJSON);
                }
                console.error('================================');
                
                btnGuardar.prop('disabled', false).text('Guardar');
                let errorMsg = 'Error al guardar el resultado';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                mostrarSnackbar(errorMsg);
            }
        });
        } catch (error) {
            console.error('=== ERROR EN EL CÓDIGO DE GUARDAR ===');
            console.error('Error:', error);
            console.error('Stack:', error.stack);
            console.error('=====================================');
            mostrarSnackbar('Error inesperado: ' + error.message);
            btnGuardar.prop('disabled', false).text('Guardar');
        }
    });
    
    // --- Modal Asignar Puntos al Ranking ---
    let referenciasPuntos = [];
    $('#btn-asignar-puntos').on('click', function() {
        $('#modalAsignarPuntos').modal('show');
        $('#modal-asignar-puntos-loading').show();
        $('#modal-asignar-puntos-content').hide();
        $('#modal-asignar-puntos-empty').hide();
        let tid = $('#torneo_id').val();
        $.ajax({
            url: '{{ route("obtenerparticipantestorneopuntuable") }}',
            type: 'GET',
            data: { torneo_id: tid },
            dataType: 'json',
            success: function(res) {
                $('#modal-asignar-puntos-loading').hide();
                referenciasPuntos = res.referencias || [];
                let jugadores = res.jugadores || [];
                if (jugadores.length === 0) {
                    $('#modal-asignar-puntos-empty').show();
                    return;
                }
                let tbody = $('#tbody-puntos-jugadores').empty();
                let refMap = {};
                referenciasPuntos.forEach(function(r) { refMap[r.codigo] = r.puntos; });
                jugadores.forEach(function(j) {
                    let puntosActual = (j.puntos !== undefined && j.puntos !== null) ? j.puntos : (refMap[j.referencia_codigo] !== undefined ? refMap[j.referencia_codigo] : '');
                    let selectOpts = referenciasPuntos.map(function(r) {
                        let sel = (j.referencia_codigo === r.codigo) ? ' selected' : '';
                        return '<option value="' + r.codigo + '" data-puntos="' + r.puntos + '"' + sel + '>' + r.nombre + '</option>';
                    }).join('');
                    let fila = '<tr data-jugador-id="' + j.id + '">' +
                        '<td>' + (j.nombre || '') + ' ' + (j.apellido || '') + '</td>' +
                        '<td><select class="form-control form-control-sm select-posicion" data-jugador-id="' + j.id + '">' + selectOpts + '</select></td>' +
                        '<td><input type="number" min="0" class="form-control form-control-sm input-puntos" data-jugador-id="' + j.id + '" value="' + puntosActual + '" placeholder="0"></td>' +
                        '</tr>';
                    tbody.append(fila);
                });
                $(document).off('change', '.select-posicion').on('change', '.select-posicion', function() {
                    let opt = $(this).find('option:selected');
                    let puntos = opt.data('puntos');
                    $(this).closest('tr').find('.input-puntos').val(puntos);
                });
                $('#modal-asignar-puntos-content').show();
            },
            error: function() {
                $('#modal-asignar-puntos-loading').hide();
                $('#modal-asignar-puntos-empty').text('Error al cargar participantes.').show();
            }
        });
    });
    $('#btn-guardar-puntos-ranking').on('click', function() {
        let tid = $('#torneo_id').val();
        let items = [];
        $('#tbody-puntos-jugadores tr').each(function() {
            let jugadorId = $(this).data('jugador-id');
            let posicion = $(this).find('.select-posicion').val();
            let puntos = parseInt($(this).find('.input-puntos').val(), 10);
            if (isNaN(puntos)) puntos = 0;
            items.push({ jugador_id: jugadorId, referencia_codigo: posicion, puntos: puntos });
        });
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
        $.ajax({
            url: '{{ route("guardarpuntosrankingtorneo") }}',
            type: 'POST',
            data: {
                torneo_id: tid,
                items: items,
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
                if (res.success) {
                    mostrarSnackbar(res.message || 'Puntos guardados en el ranking.');
                    $('#modalAsignarPuntos').modal('hide');
                } else {
                    mostrarSnackbar(res.message || 'Error al guardar.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
                let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error al guardar los puntos.';
                mostrarSnackbar(msg);
            }
        });
    });

    // Botón volver a clasificación
    $('#btn-volver-clasificacion').on('click', function() {
        let torneoId = $('#torneo_id').val();
        window.location.href = '{{ route("admintorneoamericanopartidos") }}?torneo_id=' + torneoId;
    });
    
    // Cargar resultados al cargar la página
    $(document).ready(function() {
        setTimeout(function() {
            cargarResultadosGuardados();
        }, 500);
    });
</script>

@endsection
