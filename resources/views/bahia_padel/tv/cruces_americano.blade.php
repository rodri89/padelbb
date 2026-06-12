<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bahia Padel - Cruces TV</title>
    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
    <style>
        /* BRACKET VISUAL - DISEÑO TIPO ÁRBOL */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        html, body {
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            background: #0a0f1a;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #e2e8f0;
        }
        
        /* Header compacto */
        .header-tv {
            height: 5vh;
            background: rgba(0,0,0,0.4);
            border-bottom: 2px solid #fbbf24;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5vw;
        }
        
        .header-tv h2 {
            font-size: 2.2vh;
            font-weight: 300;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin: 0;
        }
        
        #indicador-mitad {
            color: #fbbf24;
            font-size: 1.8vh;
            font-weight: 300;
            margin-left: 2vw;
        }
        
        /* Container principal */
        .bracket-container {
            height: 95vh;
            display: flex;
            padding: 0;
        }
        
        /* Fila de columnas */
        .bracket-row {
            display: flex;
            width: 100%;
            height: 100%;
        }
        
        /* Columna de ronda */
        .bracket-column {
            display: flex;
            flex-direction: column;
            min-width: 0;
            position: relative;
        }
        
        /* Ronda contenedor */
        .bracket-round {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        /* Título de ronda */
        .bracket-round-title {
            font-size: 1.6vh;
            font-weight: 300;
            color: #fbbf24;
            text-align: center;
            padding: 0.4vh 0;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            flex-shrink: 0;
            height: 3vh;
        }
        
        /* Body de ronda - altura calculada */
        .bracket-round-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
            min-height: 0;
        }
        
        /* FOTOS DE JUGADORES - Circulitos que sobresalen */
        .player-images {
            display: flex;
            align-items: center;
            gap: 0.3vw;
            margin-right: 0.6vw;
            margin-left: -0.2vw;
            flex-shrink: 0;
        }
        
        .player-images img {
            width: 5.5vh;
            height: 5.5vh;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.4);
            box-shadow: 0 2px 8px rgba(0,0,0,0.4);
        }
        
        .player-pair.winner .player-images img {
            border-color: #22c55e;
            box-shadow: 0 2px 8px rgba(34,197,94,0.4);
        }
        
        /* PARTIDO */
        .match-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 0.3vw;
            position: relative;
        }
        
        /* PAREJA - línea simple */
        .player-pair {
            display: flex;
            align-items: center;
            padding: 0.2vh 0.4vw;
            background: rgba(30,41,59,0.8);
            border-left: 3px solid rgba(100,116,139,0.5);
            margin: 1px 0;
        }
        
        .player-pair.winner {
            border-left-color: #22c55e;
            background: rgba(34,197,94,0.15);
        }
        
        /* Asegurar que el ganador tenga el mismo tamaño de texto */
        .player-pair.winner .player-names {
            font-weight: 300 !important;
            font-size: inherit !important;
        }
        
        .player-pair-content {
            flex: 1;
            display: flex;
            align-items: center;
            min-width: 0;
        }
        
        /* Nombres - FINO Y GRANDE */
        .player-names {
            flex: 1;
            font-size: 2.3vh;
            font-weight: 300;
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        
        /* Score - FINO */
        .player-pair-input { flex-shrink: 0; margin-left: 0.5vw; }
        
        .score-display {
            font-size: 2.5vh;
            font-weight: 300;
            color: #e2e8f0;
            background: rgba(71,85,105,0.5);
            padding: 0.2vh 0.8vw;
            border-radius: 3px;
            min-width: 2.5vw;
            text-align: center;
        }
        
        /* Ganador - SCORE NEGRO para nitidez */
        .player-pair.winner .score-display {
            background: #22c55e;
            color: #000;
            font-weight: 300 !important;
            font-size: 2.5vh !important;
        }
        
        /* Placeholder */
        .match-card.placeholder .player-names {
            color: rgba(148,163,184,0.4);
            font-style: italic;
            font-weight: 300;
        }
        
        /* ========================================
           DISTRIBUCIÓN ALTURA DINÁMICA POR RONDA
           Usando flex: 1 los partidos se distribuyen equitativamente
           ======================================== */
        
        .match-card {
            flex: 1;
            min-height: 0;
        }
        
        /* Cuando NO hay mitades activas - mostrar todos */
        /* 16AVOS: 16 partidos (o 8 si es solo octavos) */
        .bracket-column--dieciseisavos .match-card { flex: 1; }
        
        /* OCTAVOS: cada partido ocupa el doble de altura que uno de 16avos */
        .bracket-column--octavos .match-card { flex: 2; }
        
        /* CUARTOS: cada partido ocupa el doble de altura que uno de octavos */
        .bracket-column--cuartos .match-card { flex: 4; }
        
        /* SEMIFINALES: cada partido ocupa el doble de altura que uno de cuartos */
        .bracket-column--semis .match-card { flex: 8; }
        
        /* FINAL: centrada verticalmente */
        .bracket-column--final .match-card { flex: 16; }
        
        /* ========================================
           ANCHOS DE COLUMNAS
           ======================================== */
        
        /* 5 rondas: 16avos, octavos, cuartos, semis, final */
        .rondas-5 .bracket-column--dieciseisavos { width: 22%; }
        .rondas-5 .bracket-column--octavos { width: 22%; }
        .rondas-5 .bracket-column--cuartos { width: 20%; }
        .rondas-5 .bracket-column--semis { width: 18%; }
        .rondas-5 .bracket-column--final { width: 18%; }
        
        .rondas-5 .player-names { font-size: 2vh; }
        .rondas-5 .score-display { font-size: 2.2vh; }
        .rondas-5 .bracket-round-title { font-size: 1.4vh; }
        .rondas-5 .player-pair.winner .score-display { font-size: 2.2vh !important; }
        
        /* 4 rondas: octavos, cuartos, semis, final */
        .rondas-4 .bracket-column--octavos { width: 28%; }
        .rondas-4 .bracket-column--cuartos { width: 24%; }
        .rondas-4 .bracket-column--semis { width: 24%; }
        .rondas-4 .bracket-column--final { width: 24%; }
        
        .rondas-4 .player-names { font-size: 2.3vh; }
        .rondas-4 .score-display { font-size: 2.5vh; }
        .rondas-4 .player-pair.winner .score-display { font-size: 2.5vh !important; }
        
        /* 3 rondas: cuartos, semis, final */
        .rondas-3 .bracket-column--cuartos { width: 36%; }
        .rondas-3 .bracket-column--semis { width: 32%; }
        .rondas-3 .bracket-column--final { width: 32%; }
        
        .rondas-3 .player-names { font-size: 2.3vh; }
        .rondas-3 .score-display { font-size: 2.5vh; }
        .rondas-3 .player-pair { padding: 0.4vh 0.5vw; }
        .rondas-3 .player-pair.winner .score-display { font-size: 2.5vh !important; }
        

        
        /* Modal ganadores */
        .modal-ganadores { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; justify-content: center; align-items: center; }
        .modal-content-ganadores { background: #1a1f2e; padding: 3vh; border-radius: 1vh; text-align: center; border: 3px solid #fbbf24; }
        .modal-content-ganadores h2 { color: #fbbf24; font-size: 4vh; margin-bottom: 2vh; font-weight: 300; }
        .btn-cerrar-modal { background: #4e73df; color: #fff; border: none; padding: 1vh 2vw; font-size: 2vh; cursor: pointer; border-radius: 0.5vh; margin-top: 2vh; font-weight: 300; }
        
        /* Botón tema */
        .theme-toggle {
            position: fixed;
            top: 1vh;
            right: 1vw;
            z-index: 1001;
            background: #4e73df;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 4vh;
            height: 4vh;
            font-size: 2vh;
            cursor: pointer;
        }
        
        /* ========================================
           SISTEMA DE MITADES
           ======================================== */
        .bracket-row[data-mitad-activa="superior"] .match-card[data-mitad-index="inferior"],
        .bracket-row[data-mitad-activa="inferior"] .match-card[data-mitad-index="superior"] {
            display: none !important;
        }
        
        /* La final (ambas) siempre visible */
        .bracket-row[data-mitad-activa] .match-card[data-mitad-index="ambas"] {
            display: flex !important;
        }
        
        /* Con mitades activas, los elementos visibles se distribuyen con flex
           Los valores flex mantienen la proporción correcta del bracket */
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-tv">
        <h2>{{ $torneo->titulo ?? 'Torneo' }} - CRUCES</h2>
        <span id="indicador-mitad"></span>
    </div>
    
    <!-- Container principal -->
    <div class="bracket-container">
        @php
            // Organizar cruces por ronda si no viene ya organizado
            if (!isset($crucesPorRonda)) {
                $crucesPorRonda = [
                    'dieciseisavos' => collect($cruces ?? [])->filter(fn($c) => ($c['ronda'] ?? '') === 'dieciseisavos')->values()->toArray(),
                    'octavos' => collect($cruces ?? [])->filter(fn($c) => ($c['ronda'] ?? '') === 'octavos')->values()->toArray(),
                    'cuartos' => collect($cruces ?? [])->filter(fn($c) => ($c['ronda'] ?? '') === 'cuartos')->values()->toArray(),
                    'semifinales' => collect($cruces ?? [])->filter(fn($c) => ($c['ronda'] ?? '') === 'semifinales')->values()->toArray(),
                    'final' => collect($cruces ?? [])->filter(fn($c) => ($c['ronda'] ?? '') === 'final')->values()->toArray(),
                ];
            }
            
            // SISTEMA ADAPTATIVO: Filtrar rondas completadas (ocultar anteriores que ya terminaron)
            $rondasCompletadas = $rondasCompletadas ?? [];
            $todasLasRondas = ['dieciseisavos', 'octavos', 'cuartos', 'semifinales', 'final'];
            
            // Encontrar la primera ronda NO completada
            $primeraRondaActiva = null;
            foreach ($todasLasRondas as $rondaNombre) {
                if (!in_array($rondaNombre, $rondasCompletadas)) {
                    $primeraRondaActiva = $rondaNombre;
                    break;
                }
            }
            
            // Si todas están completadas, mostrar todas (para ver el campeón)
            if ($primeraRondaActiva === null) {
                $primeraRondaActiva = 'dieciseisavos';
            }
            
            // Determinar qué rondas mostrar (solo las activas y futuras)
            $tieneDieciseisavos = ($tieneDieciseisavos ?? count($crucesPorRonda['dieciseisavos'] ?? []) > 0) 
                                  && array_search($primeraRondaActiva, $todasLasRondas) <= array_search('dieciseisavos', $todasLasRondas);
            $tieneOctavos = ($tieneOctavos ?? count($crucesPorRonda['octavos'] ?? []) > 0) 
                            && array_search($primeraRondaActiva, $todasLasRondas) <= array_search('octavos', $todasLasRondas);
            $tieneCuartos = ($tieneCuartos ?? count($crucesPorRonda['cuartos'] ?? []) > 0) 
                            && array_search($primeraRondaActiva, $todasLasRondas) <= array_search('cuartos', $todasLasRondas);
            
            // Calcular número de rondas ACTIVAS para ajustar CSS
            $numRondas = 2; // siempre semis y final
            if ($tieneCuartos) $numRondas++;
            if ($tieneOctavos) $numRondas++;
            if ($tieneDieciseisavos) $numRondas++;
            
            // Definir las rondas a mostrar en orden (solo activas)
            $rondasMostrar = [];
            if ($tieneDieciseisavos) $rondasMostrar[] = ['key' => 'dieciseisavos', 'title' => '16VOS', 'class' => 'dieciseisavos'];
            if ($tieneOctavos) $rondasMostrar[] = ['key' => 'octavos', 'title' => 'OCTAVOS', 'class' => 'octavos'];
            if ($tieneCuartos) $rondasMostrar[] = ['key' => 'cuartos', 'title' => 'CUARTOS', 'class' => 'cuartos'];
            $rondasMostrar[] = ['key' => 'semifinales', 'title' => 'SEMIFINALES', 'class' => 'semis'];
            $rondasMostrar[] = ['key' => 'final', 'title' => 'FINAL', 'class' => 'final'];
            
            // Determinar si necesitamos alternar por mitades (4+ rondas activas)
            $necesitaAlternar = $numRondas >= 4;
            
            $jugadoresCollection = collect($jugadores);
        @endphp

        <div class="bracket-row rondas-{{ $numRondas }}" data-necesita-alternar="{{ $necesitaAlternar ? 'true' : 'false' }}">
            @foreach($rondasMostrar as $rondaInfo)
                @php
                    $crucesRonda = $crucesPorRonda[$rondaInfo['key']] ?? [];
                    
                    // Para alternar mitades: si es 16avos u octavos, asignar mitad
                    $esPrimeraColumna = $rondaInfo['key'] === 'dieciseisavos' || $rondaInfo['key'] === 'octavos';
                    $mitad = '';
                    if ($necesitaAlternar && $esPrimeraColumna) {
                        // Las rondas tempranas se dividen por mitades
                        $mitad = 'tiene-mitades'; // Indicador para JavaScript
                    } elseif ($rondaInfo['key'] === 'final') {
                        $mitad = 'final'; // La final siempre visible
                    }
                @endphp
                <div class="bracket-column bracket-column--{{ $rondaInfo['class'] }}" data-mitad="{{ $mitad }}">
                    <div class="bracket-round bracket-round--{{ $rondaInfo['class'] }}">
                        <div class="bracket-round-title">{{ $rondaInfo['title'] }}</div>
                        <div class="bracket-round-body">
                            @forelse($crucesRonda as $index => $cruce)
                                @php
                                    $pareja1 = is_array($cruce['pareja_1'] ?? null) ? $cruce['pareja_1'] : [];
                                    $pareja2 = is_array($cruce['pareja_2'] ?? null) ? $cruce['pareja_2'] : [];
                                    $jugador1Id = $pareja1['jugador_1'] ?? null;
                                    $jugador1PartnerId = $pareja1['jugador_2'] ?? null;
                                    $jugador2Id = $pareja2['jugador_1'] ?? null;
                                    $jugador2PartnerId = $pareja2['jugador_2'] ?? null;
                                    $jugador1_1 = $jugador1Id !== null ? $jugadoresCollection->firstWhere('id', $jugador1Id) : null;
                                    $jugador1_2 = $jugador1PartnerId !== null ? $jugadoresCollection->firstWhere('id', $jugador1PartnerId) : null;
                                    $jugador2_1 = $jugador2Id !== null ? $jugadoresCollection->firstWhere('id', $jugador2Id) : null;
                                    $jugador2_2 = $jugador2PartnerId !== null ? $jugadoresCollection->firstWhere('id', $jugador2PartnerId) : null;
                                    
                                    // Buscar resultado guardado para este cruce
                                    $resultadoCruce = null;
                                    $cruceId = $cruce['id'] ?? $index;
                                    foreach($resultadosGuardados ?? [] as $resultado) {
                                        if(($resultado['cruce_id'] ?? null) == $cruceId && ($resultado['ronda'] ?? '') == $rondaInfo['key']) {
                                            $resultadoCruce = $resultado;
                                            break;
                                        }
                                    }
                                    $score1 = $resultadoCruce['pareja_1_set_1'] ?? 0;
                                    $score2 = $resultadoCruce['pareja_2_set_1'] ?? 0;
                                    $tieneResultado = $score1 > 0 || $score2 > 0;
                                    
                                    // Determinar mitad (superior o inferior) para alternar
                                    // Cada ronda se divide por mitades: primera mitad = superior, segunda mitad = inferior
                                    $mitadIndex = '';
                                    if ($necesitaAlternar) {
                                        $totalCruces = count($crucesRonda);
                                        if ($rondaInfo['key'] === 'final') {
                                            $mitadIndex = 'ambas'; // La final se muestra siempre
                                        } elseif ($rondaInfo['key'] === 'semifinales') {
                                            // Semis: partido 0 = superior, partido 1 = inferior
                                            $mitadIndex = $index === 0 ? 'superior' : 'inferior';
                                        } else {
                                            // 16avos, octavos, cuartos: primera mitad = superior, segunda = inferior
                                            $mitadIndex = $index < ($totalCruces / 2) ? 'superior' : 'inferior';
                                        }
                                    }
                                @endphp
                                <div class="match-card{{ $tieneResultado ? ' winner' : '' }}" 
                                     data-cruce-id="{{ $cruceId }}" 
                                     data-ronda="{{ $rondaInfo['key'] }}"
                                     data-mitad-index="{{ $mitadIndex }}">
                                    <!-- Pareja 1 -->
                                    <div class="player-pair pareja-cruce{{ $score1 > $score2 ? ' winner' : '' }}"
                                         data-pareja="1"
                                         data-jugador-1="{{ $pareja1['jugador_1'] ?? '' }}"
                                         data-jugador-2="{{ $pareja1['jugador_2'] ?? '' }}">
                                        <div class="player-pair-content">
                                            <div class="player-images">
                                                <img src="{{ asset(optional($jugador1_1)->foto ?? 'images/jugador_img.png') }}" alt="">
                                                <img src="{{ asset(optional($jugador1_2)->foto ?? 'images/jugador_img.png') }}" alt="">
                                            </div>
                                            <div class="player-names">
                                                {{ optional($jugador1_1)->apellido ?? 'TBD' }} - {{ optional($jugador1_2)->apellido ?? '' }}
                                            </div>
                                        </div>
                                        <div class="player-pair-input">
                                            <span class="score-display" data-cruce-id="{{ $cruceId }}" data-pareja="1">{{ $score1 }}</span>
                                        </div>
                                    </div>

                                    <!-- Pareja 2 -->
                                    <div class="player-pair pareja-cruce{{ $score2 > $score1 ? ' winner' : '' }}"
                                         data-pareja="2"
                                         data-jugador-1="{{ $pareja2['jugador_1'] ?? '' }}"
                                         data-jugador-2="{{ $pareja2['jugador_2'] ?? '' }}">
                                        <div class="player-pair-content">
                                            <div class="player-images">
                                                <img src="{{ asset(optional($jugador2_1)->foto ?? 'images/jugador_img.png') }}" alt="">
                                                <img src="{{ asset(optional($jugador2_2)->foto ?? 'images/jugador_img.png') }}" alt="">
                                            </div>
                                            <div class="player-names">
                                                {{ optional($jugador2_1)->apellido ?? 'TBD' }} - {{ optional($jugador2_2)->apellido ?? '' }}
                                            </div>
                                        </div>
                                        <div class="player-pair-input">
                                            <span class="score-display" data-cruce-id="{{ $cruceId }}" data-pareja="2">{{ $score2 }}</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="match-card placeholder" data-ronda="{{ $rondaInfo['key'] }}">
                                    <div class="player-pair">
                                        <div class="player-pair-content">
                                            <div class="player-names">
                                                Esperando definiciones...
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<script type="text/javascript">
    const cruces = @json($cruces ?? []);
    const jugadores = @json($jugadores ?? []);
    let resultadosGuardados = @json($resultadosGuardados ?? []);
    const baseUrl = '{{ url("/") }}';
    const jugadoresMap = new Map(jugadores.map(j => [Number(j.id), j]));
    let torneoId = {{ $torneo->id ?? 0 }};
    let ultimaVersionConocida = {{ $torneo->version ?? 0 }};

    document.addEventListener('DOMContentLoaded', () => {
        if (!torneoId) {
            torneoId = Number(document.getElementById('torneo_id')?.value || 0);
        }
        inicializarTema();
        inicializarBotonVolver();
        cargarResultadosGuardados();

        // Ajustar escala para que todo quepa en pantalla (sin scroll)
        setTimeout(ajustarEscalaBracket, 120);
        window.addEventListener('resize', () => setTimeout(ajustarEscalaBracket, 120));
        
        // SISTEMA ADAPTATIVO: Alternar mitades si es necesario
        inicializarAlternanciaInteligente();
        
        // Polling inteligente: verificar versión cada 2 segundos
        setInterval(verificarVersionYActualizar, 2000);
    });
    
    // Sistema adaptativo: alterna entre mitad superior e inferior cuando hay 4+ rondas
    let mitadActual = 'superior';
    let intervalAlternancia = null;
    
    function inicializarAlternanciaInteligente() {
        const bracketRow = document.querySelector('.bracket-row');
        const necesitaAlternar = bracketRow?.dataset.necesitaAlternar === 'true';
        
        if (!necesitaAlternar) {
            // No necesita alternar, mostrar todo
            mostrarTodasLasMitades();
            return;
        }
        
        // Necesita alternar: iniciar alternancia cada 15 segundos
        mitadActual = 'superior';
        mostrarMitad(mitadActual);
        
        if (intervalAlternancia) clearInterval(intervalAlternancia);
        intervalAlternancia = setInterval(() => {
            mitadActual = mitadActual === 'superior' ? 'inferior' : 'superior';
            mostrarMitad(mitadActual);
        }, 15000); // Alternar cada 15 segundos
    }
    
    function mostrarMitad(mitad) {
        const bracketRow = document.querySelector('.bracket-row');
        const indicador = document.getElementById('indicador-mitad');
        
        // Usar el atributo data-mitad-activa para CSS
        if (bracketRow) {
            bracketRow.dataset.mitadActiva = mitad;
        }
        
        // Actualizar indicador visual
        if (indicador) {
            indicador.textContent = mitad === 'superior' ? '▲ MITAD SUPERIOR' : '▼ MITAD INFERIOR';
            indicador.style.display = 'inline';
        }
    }
    
    function mostrarTodasLasMitades() {
        const bracketRow = document.querySelector('.bracket-row');
        const indicador = document.getElementById('indicador-mitad');
        
        // Quitar el atributo para mostrar todo
        if (bracketRow) {
            delete bracketRow.dataset.mitadActiva;
        }
        
        // Ocultar indicador
        if (indicador) {
            indicador.style.display = 'none';
        }
    }
    
    // Polling inteligente: solo actualiza si la versión del torneo cambió
    function verificarVersionYActualizar() {
        if (!torneoId) return;
        
        $.get('{{ route("tvtorneoversion") }}', { torneo_id: torneoId })
            .done(function(response) {
                const versionActual = response.version || 0;
                
                if (versionActual > ultimaVersionConocida) {
                    console.log('Versión cambió:', ultimaVersionConocida, '->', versionActual);
                    ultimaVersionConocida = versionActual;
                    
                    // Recargar la página para obtener datos actualizados (incluidos nuevos cruces)
                    window.location.reload();
                }
            })
            .fail(function() {
                // Silencioso, reintentar en el próximo intervalo
            });
    }

    function inicializarTema() {
        const themeToggle = document.getElementById('theme-toggle');
        if (!themeToggle) return;

        const body = document.body;
        const icon = themeToggle.querySelector('i');

        aplicarTema(localStorage.getItem('theme') || 'dark', body, icon);

        themeToggle.addEventListener('click', () => {
            const nextTheme = body.classList.contains('dark-mode') ? 'light' : 'dark';
            aplicarTema(nextTheme, body, icon);
        });
    }

    function aplicarTema(theme, body, icon) {
        if (theme === 'dark') {
            body.classList.add('dark-mode');
            body.style.background = 'linear-gradient(135deg, #0f172a 0%, #111827 40%, #1f2937 100%)';
            body.style.color = '#e2e8f0';
            icon?.classList.remove('fa-sun');
            icon?.classList.add('fa-moon');
        } else {
            body.classList.remove('dark-mode');
            body.style.background = 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 55%, #cbd5f5 100%)';
            body.style.color = '#0f172a';
            icon?.classList.remove('fa-moon');
            icon?.classList.add('fa-sun');
        }

        localStorage.setItem('theme', theme);
    }

    // Ajusta el layout del bracket para que todo quepa en la pantalla sin scroll
    function ajustarEscalaBracket() {
        const container = document.querySelector('.bracket-container');
        const bracket = document.querySelector('.bracket-row');
        const header = document.querySelector('.header-tv');

        if (!container || !bracket) return;

        // Calcular espacio disponible
        const headerHeight = header ? header.getBoundingClientRect().height : 0;
        const availableHeight = window.innerHeight - headerHeight;
        const availableWidth = window.innerWidth;

        // Obtener tamaño actual del bracket
        const bracketRect = bracket.getBoundingClientRect();
        const bracketHeight = bracketRect.height;
        const bracketWidth = bracketRect.width;

        // Si el contenido cabe naturalmente, no hacer nada especial
        if (bracketHeight <= availableHeight && bracketWidth <= availableWidth) {
            container.style.transform = 'scale(1)';
            return;
        }

        // Calcular escala necesaria para que quepa
        const scaleHeight = availableHeight / bracketHeight;
        const scaleWidth = availableWidth / bracketWidth;
        const scale = Math.min(1, scaleHeight, scaleWidth) * 0.98; // Dejar pequeño margen

        container.style.transform = `scale(${scale})`;
        container.style.transformOrigin = 'top center';
    }


    function inicializarBotonVolver() {
        const boton = document.getElementById('btn-volver-clasificacion');
        if (!boton) return;

        boton.addEventListener('click', () => {
            if (!torneoId) return;
            window.location.href = '{{ route("admintorneoamericanopartidos") }}?torneo_id=' + torneoId;
        });
    }

    function clavePareja(jugador1, jugador2) {
        return [Number(jugador1) || 0, Number(jugador2) || 0].sort((a, b) => a - b).join('-');
    }

    function parejasCoinciden(pareja, claveObjetivo) {
        if (!pareja) return false;
        return clavePareja(pareja.jugador_1, pareja.jugador_2) === claveObjetivo;
    }

    function encontrarCruce(resultado) {
        const ronda = resultado.ronda;
        const cruceId = resultado.cruce_id;

        if (cruceId !== undefined && cruceId !== null) {
            const crucePorId = cruces.find(c => String(c.id) === String(cruceId));
            if (crucePorId) {
                return crucePorId;
            }
        }

        const clavePareja1 = clavePareja(resultado.pareja_1_jugador_1, resultado.pareja_1_jugador_2);
        const clavePareja2 = clavePareja(resultado.pareja_2_jugador_1, resultado.pareja_2_jugador_2);

        return cruces.find(c => c.ronda === ronda && parejasCoinciden(c.pareja_1, clavePareja1) && parejasCoinciden(c.pareja_2, clavePareja2)) ||
               cruces.find(c => c.ronda === ronda && parejasCoinciden(c.pareja_1, clavePareja2) && parejasCoinciden(c.pareja_2, clavePareja1));
    }

    function obtenerMatchCard(cruce, ronda, resultado) {
        const candidatos = [];

        if (cruce?.id !== undefined && cruce?.id !== null) {
            candidatos.push(cruce.id);
        }

        const posicion = cruces.indexOf(cruce);
        if (posicion >= 0) {
            candidatos.push(posicion);
        }

        if (resultado?.cruce_id !== undefined && resultado?.cruce_id !== null) {
            candidatos.push(resultado.cruce_id);
        }

        for (const candidato of candidatos) {
            const card = $(`.match-card[data-cruce-id="${candidato}"][data-ronda="${ronda}"]`);
            if (card.length) {
                return card;
            }
        }

        const clavePareja1 = clavePareja(resultado.pareja_1_jugador_1, resultado.pareja_1_jugador_2);
        const clavePareja2 = clavePareja(resultado.pareja_2_jugador_1, resultado.pareja_2_jugador_2);

        return $(`.match-card[data-ronda="${ronda}"]`).filter(function() {
            const parejaDom1 = $(this).find('.pareja-cruce[data-pareja="1"]');
            const parejaDom2 = $(this).find('.pareja-cruce[data-pareja="2"]');
            const domClave1 = clavePareja(parejaDom1.data('jugador-1'), parejaDom1.data('jugador-2'));
            const domClave2 = clavePareja(parejaDom2.data('jugador-1'), parejaDom2.data('jugador-2'));
            return (domClave1 === clavePareja1 && domClave2 === clavePareja2) ||
                   (domClave1 === clavePareja2 && domClave2 === clavePareja1);
        }).first();
    }

    function actualizarMarcador(matchCard, resultado, cruce) {
        const score1 = Number(resultado.pareja_1_set_1) || 0;
        const score2 = Number(resultado.pareja_2_set_1) || 0;

        matchCard.find(`.score-display[data-pareja="1"]`).text(score1);
        matchCard.find(`.score-display[data-pareja="2"]`).text(score2);

        matchCard.removeClass('winner');
        matchCard.find('.player-pair').removeClass('winner');

        const hayResultado = score1 > 0 || score2 > 0;
        if (!hayResultado || score1 === score2) {
            return null;
        }

        const parejaGanadora = score1 > score2 ? cruce.pareja_1 : cruce.pareja_2;
        const parejaGanadoraNumero = score1 > score2 ? 1 : 2;

        matchCard.addClass('winner');
        matchCard.find(`.player-pair[data-pareja="${parejaGanadoraNumero}"]`).addClass('winner');

        return parejaGanadora;
    }

    function cargarResultadosGuardados() {
        let parejaGanadoraFinal = null;
        let aplicados = 0;

        // Guardar estado de reintentos en una variable global simple
        window.__crucesResultadosRetries = window.__crucesResultadosRetries || 0;

        resultadosGuardados.forEach(resultado => {
            const cruce = encontrarCruce(resultado);
            if (!cruce) return;

            const matchCard = obtenerMatchCard(cruce, resultado.ronda, resultado);
            if (!matchCard || !matchCard.length) return;

            const parejaGanadora = actualizarMarcador(matchCard, resultado, cruce);
            
            // Asegurar que los scores se muestren
            const score1 = Number(resultado.pareja_1_set_1) || 0;
            const score2 = Number(resultado.pareja_2_set_1) || 0;
            matchCard.find(`.score-display[data-pareja="1"]`).text(score1);
            matchCard.find(`.score-display[data-pareja="2"]`).text(score2);

            aplicados++;

            if (resultado.ronda === 'final' && parejaGanadora) {
                parejaGanadoraFinal = parejaGanadora;
            }
        });

        // Si no se aplicaron todos los resultados, reintentamos un par de veces (por si el DOM estaba incompleto)
        if (aplicados < resultadosGuardados.length && window.__crucesResultadosRetries < 3) {
            window.__crucesResultadosRetries++;
            setTimeout(cargarResultadosGuardados, 400);
            return;
        }

        if (parejaGanadoraFinal) {
            mostrarModalGanadores(parejaGanadoraFinal);
        }

        // Recalcular escala y aplicar compact si es necesario
        setTimeout(() => {
            ajustarEscalaBracket();
        }, 80);
    }

    function obtenerJugadorPorId(id) {
        return jugadoresMap.get(Number(id)) || null;
    }

    function mostrarModalGanadores(parejaGanadora) {
        if (!parejaGanadora) return;

        const jugador1 = obtenerJugadorPorId(parejaGanadora.jugador_1);
        const jugador2 = obtenerJugadorPorId(parejaGanadora.jugador_2);

        const getFotoUrl = (jugador) => {
            if (!jugador || !jugador.foto) return `${baseUrl}/images/jugador_img.png`;
            const ruta = jugador.foto.startsWith('/') ? jugador.foto.substring(1) : jugador.foto;
            return `${baseUrl}/${ruta}`;
        };

        const nombreJugador = (jugador) => {
            if (!jugador) return 'A confirmar';
            const nombre = jugador.nombre ?? '';
            const apellido = jugador.apellido ?? '';
            return `${nombre} ${apellido}`.trim() || 'A confirmar';
        };

        const html = `
            <div class="ganador-foto">
                <img src="${getFotoUrl(jugador1)}" alt="${nombreJugador(jugador1)}">
                <div class="nombre">${nombreJugador(jugador1)}</div>
            </div>
            <div class="ganador-foto">
                <img src="${getFotoUrl(jugador2)}" alt="${nombreJugador(jugador2)}">
                <div class="nombre">${nombreJugador(jugador2)}</div>
            </div>
        `;

        $('#ganadores-fotos').html(html);
        $('#modal-ganadores').addClass('show');
        crearConfetti();
    }

    function cerrarModalGanadores() {
        $('#modal-ganadores').removeClass('show');
        $('.confetti').remove();
    }

    function crearConfetti() {
        for (let i = 0; i < 100; i++) {
            const confetti = $('<div class="confetti"></div>');
            confetti.css({
                left: Math.random() * 100 + 'vw',
                animationDuration: (Math.random() * 3 + 2) + 's',
                animationDelay: Math.random() * 2 + 's',
                backgroundColor: `hsl(${Math.random() * 360}, 100%, 50%)`
            });
            $('body').append(confetti);
        }
    }
    
    // Función para actualizar resultados de cruces vía AJAX
    function actualizarResultadosCruces() {
        if (!torneoId) return;
        
        $.ajax({
            type: 'POST',
            url: '{{ route("tvtorneoamericanocrucesactualizar") }}',
            data: {
                torneo_id: torneoId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success && response.resultadosGuardados) {
                    // Limpiar clases de ganador antes de actualizar
                    $('.match-card').removeClass('winner');
                    $('.player-pair').removeClass('winner');
                    
                    // Actualizar cada resultado
                    let parejaGanadoraFinal = null;
                    response.resultadosGuardados.forEach(function(resultado) {
                        const cruce = encontrarCruce(resultado);
                        if (!cruce) return;
                        
                        const matchCard = obtenerMatchCard(cruce, resultado.ronda, resultado);
                        if (!matchCard || !matchCard.length) return;
                        
                        const parejaGanadora = actualizarMarcador(matchCard, resultado, cruce);
                        
                        if (resultado.ronda === 'final' && parejaGanadora) {
                            parejaGanadoraFinal = parejaGanadora;
                        }
                    });
                    
                    // Mostrar modal de ganadores si hay final completa
                    if (parejaGanadoraFinal) {
                        mostrarModalGanadores(parejaGanadoraFinal);
                    }
                    
                    // Actualizar variable global
                    resultadosGuardados = response.resultadosGuardados;
                }
            },
            error: function() {
                // Silencioso, no mostrar error si falla
            }
        });
    }
</script>

</body>
</html>

