<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bahia Padel - Rotación TV</title>
    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
    <style>
        /* ========================================
           ESTILOS BASE TV ROTACIÓN
           ======================================== */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        html, body {
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            background: #0a0f1a;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #e2e8f0;
        }
        
        /* Header con indicador de torneo actual */
        .header-tv {
            height: 6vh;
            background: rgba(0,0,0,0.4);
            border-bottom: 2px solid #fbbf24;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5vw;
        }
        
        .header-tv h2 {
            font-size: 2.5vh;
            font-weight: 300;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin: 0;
            transition: opacity 0.3s ease;
        }
        
        .header-indicadores {
            display: flex;
            align-items: center;
            gap: 1vw;
        }
        
        .indicador-torneos {
            display: flex;
            gap: 0.5vw;
        }
        
        .indicador-dot {
            width: 1.2vh;
            height: 1.2vh;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transition: all 0.3s ease;
        }
        
        .indicador-dot.active {
            background: #fbbf24;
            transform: scale(1.3);
        }
        
        .indicador-dot.updated {
            background: #22c55e;
            animation: pulse 0.5s ease-out;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.8); }
            100% { transform: scale(1.3); }
        }
        
        .countdown-display {
            font-size: 1.8vh;
            color: rgba(255,255,255,0.6);
            font-weight: 300;
        }
        
        /* ========================================
           SLIDES CONTAINER
           ======================================== */
        .slides-container {
            height: 94vh;
            position: relative;
            overflow: hidden;
        }
        
        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s ease-in-out;
        }
        
        .slide.active {
            opacity: 1;
            pointer-events: auto;
        }
        
        /* ========================================
           BRACKET VISUAL
           ======================================== */
        .bracket-container {
            height: 100%;
            display: flex;
            padding: 0;
        }
        
        .bracket-row {
            display: flex;
            width: 100%;
            height: 100%;
        }
        
        .bracket-column {
            display: flex;
            flex-direction: column;
            min-width: 0;
            position: relative;
        }
        
        .bracket-round {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
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
        
        .bracket-round-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
            min-height: 0;
        }
        
        /* PARTIDO */
        .match-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 0.3vw;
            position: relative;
            flex: 1;
            min-height: 0;
        }
        
        /* Distribución dinámica por ronda */
        .bracket-column--dieciseisavos .match-card { flex: 1; }
        .bracket-column--octavos .match-card { flex: 2; }
        .bracket-column--cuartos .match-card { flex: 4; }
        .bracket-column--semis .match-card { flex: 8; }
        .bracket-column--final .match-card { flex: 16; }
        
        /* PAREJA */
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
        
        .player-pair.winner .score-display {
            background: #22c55e;
            color: #000;
            font-weight: 300 !important;
            font-size: 2.5vh !important;
        }
        
        .match-card.placeholder .player-names {
            color: rgba(148,163,184,0.4);
            font-style: italic;
            font-weight: 300;
        }
        
        /* ========================================
           ANCHOS DE COLUMNAS POR CANTIDAD DE RONDAS
           ======================================== */
        
        /* 5 rondas */
        .rondas-5 .bracket-column--dieciseisavos { width: 22%; }
        .rondas-5 .bracket-column--octavos { width: 22%; }
        .rondas-5 .bracket-column--cuartos { width: 20%; }
        .rondas-5 .bracket-column--semis { width: 18%; }
        .rondas-5 .bracket-column--final { width: 18%; }
        .rondas-5 .player-names { font-size: 2vh; }
        .rondas-5 .score-display { font-size: 2.2vh; }
        .rondas-5 .bracket-round-title { font-size: 1.4vh; }
        .rondas-5 .player-pair.winner .score-display { font-size: 2.2vh !important; }
        
        /* 4 rondas */
        .rondas-4 .bracket-column--octavos { width: 28%; }
        .rondas-4 .bracket-column--cuartos { width: 24%; }
        .rondas-4 .bracket-column--semis { width: 24%; }
        .rondas-4 .bracket-column--final { width: 24%; }
        .rondas-4 .player-names { font-size: 2.3vh; }
        .rondas-4 .score-display { font-size: 2.5vh; }
        .rondas-4 .player-pair.winner .score-display { font-size: 2.5vh !important; }
        
        /* 3 rondas */
        .rondas-3 .bracket-column--cuartos { width: 36%; }
        .rondas-3 .bracket-column--semis { width: 32%; }
        .rondas-3 .bracket-column--final { width: 32%; }
        .rondas-3 .player-names { font-size: 2.3vh; }
        .rondas-3 .score-display { font-size: 2.5vh; }
        .rondas-3 .player-pair { padding: 0.4vh 0.5vw; }
        .rondas-3 .player-pair.winner .score-display { font-size: 2.5vh !important; }
        
        /* 2 rondas */
        .rondas-2 .bracket-column--semis { width: 50%; }
        .rondas-2 .bracket-column--final { width: 50%; }
        .rondas-2 .player-names { font-size: 2.5vh; }
        .rondas-2 .score-display { font-size: 2.8vh; }
        .rondas-2 .player-pair.winner .score-display { font-size: 2.8vh !important; }
        
        /* 1 ronda (solo final) */
        .rondas-1 .bracket-column--final { width: 100%; }
        .rondas-1 .player-names { font-size: 3vh; }
        .rondas-1 .score-display { font-size: 3.2vh; }
        .rondas-1 .player-pair.winner .score-display { font-size: 3.2vh !important; }
        
        /* ========================================
           NOTIFICACIÓN DE ACTUALIZACIÓN
           ======================================== */
        .update-notification {
            position: fixed;
            top: 8vh;
            left: 50%;
            transform: translateX(-50%);
            background: #22c55e;
            color: #000;
            padding: 1vh 2vw;
            border-radius: 0.5vh;
            font-size: 2vh;
            font-weight: 500;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .update-notification.visible {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-tv">
        <h2 id="torneo-nombre">{{ $torneosData[0]['nombre'] ?? 'Torneo' }} - CRUCES</h2>
        <div class="header-indicadores">
            <div class="indicador-torneos">
                @foreach($torneosData as $index => $torneo)
                    <div class="indicador-dot{{ $index === 0 ? ' active' : '' }}" 
                         data-torneo-id="{{ $torneo['id'] }}"
                         title="{{ $torneo['nombre'] }}"></div>
                @endforeach
            </div>
            <span class="countdown-display" id="countdown">{{ $intervalo }}s</span>
        </div>
    </div>
    
    <!-- Notificación de actualización -->
    <div class="update-notification" id="update-notification">
        ¡Resultado actualizado!
    </div>
    
    <!-- Container de slides -->
    <div class="slides-container">
        @foreach($torneosData as $torneoIndex => $torneoData)
            @php
                $cruces = $torneoData['cruces'] ?? [];
                $rondas = $torneoData['rondas'] ?? [];
                $totalRondas = $torneoData['totalRondas'] ?? 0;
                
                // Organizar cruces por ronda
                $crucesPorRonda = [
                    'dieciseisavos final' => [],
                    'octavos final' => [],
                    'cuartos final' => [],
                    'semifinal' => [],
                    'final' => []
                ];
                
                foreach ($cruces as $cruce) {
                    $rondaKey = $cruce['ronda'] ?? '';
                    if (isset($crucesPorRonda[$rondaKey])) {
                        $crucesPorRonda[$rondaKey][] = $cruce;
                    }
                }
                
                // Determinar qué rondas mostrar
                $rondasMostrar = [];
                if (count($crucesPorRonda['dieciseisavos final']) > 0) {
                    $rondasMostrar[] = ['key' => 'dieciseisavos final', 'title' => '16VOS', 'class' => 'dieciseisavos'];
                }
                if (count($crucesPorRonda['octavos final']) > 0) {
                    $rondasMostrar[] = ['key' => 'octavos final', 'title' => 'OCTAVOS', 'class' => 'octavos'];
                }
                if (count($crucesPorRonda['cuartos final']) > 0) {
                    $rondasMostrar[] = ['key' => 'cuartos final', 'title' => 'CUARTOS', 'class' => 'cuartos'];
                }
                // Siempre mostrar semis y final (aunque estén vacías)
                $rondasMostrar[] = ['key' => 'semifinal', 'title' => 'SEMIFINALES', 'class' => 'semis'];
                $rondasMostrar[] = ['key' => 'final', 'title' => 'FINAL', 'class' => 'final'];
                
                $numRondas = count($rondasMostrar);
                $jugadoresCollection = collect($jugadores);
            @endphp
            
            <div class="slide{{ $torneoIndex === 0 ? ' active' : '' }}" 
                 data-torneo-id="{{ $torneoData['id'] }}"
                 data-torneo-nombre="{{ $torneoData['nombre'] }}"
                 data-version="{{ $torneoData['version'] }}">
                <div class="bracket-container">
                    <div class="bracket-row rondas-{{ $numRondas }}">
                        @foreach($rondasMostrar as $rondaInfo)
                            @php
                                $crucesRonda = $crucesPorRonda[$rondaInfo['key']] ?? [];
                            @endphp
                            <div class="bracket-column bracket-column--{{ $rondaInfo['class'] }}">
                                <div class="bracket-round bracket-round--{{ $rondaInfo['class'] }}">
                                    <div class="bracket-round-title">{{ $rondaInfo['title'] }}</div>
                                    <div class="bracket-round-body">
                                        @forelse($crucesRonda as $index => $cruce)
                                            @php
                                                $pareja1 = $cruce['pareja1'] ?? [];
                                                $pareja2 = $cruce['pareja2'] ?? [];
                                                $resultado = $cruce['resultado'] ?? null;
                                                
                                                // Calcular scores (suma de sets ganados)
                                                $score1 = 0;
                                                $score2 = 0;
                                                if ($resultado) {
                                                    $s1p1 = $resultado['pareja_1_set_1'] ?? 0;
                                                    $s2p1 = $resultado['pareja_1_set_2'] ?? 0;
                                                    $s3p1 = $resultado['pareja_1_set_3'] ?? 0;
                                                    $s1p2 = $resultado['pareja_2_set_1'] ?? 0;
                                                    $s2p2 = $resultado['pareja_2_set_2'] ?? 0;
                                                    $s3p2 = $resultado['pareja_2_set_3'] ?? 0;
                                                    
                                                    // Contar sets ganados
                                                    if ($s1p1 > $s1p2) $score1++; elseif ($s1p2 > $s1p1) $score2++;
                                                    if ($s2p1 > $s2p2) $score1++; elseif ($s2p2 > $s2p1) $score2++;
                                                    if ($s3p1 > 0 || $s3p2 > 0) {
                                                        if ($s3p1 > $s3p2) $score1++; elseif ($s3p2 > $s3p1) $score2++;
                                                    }
                                                }
                                                
                                                $tieneResultado = $score1 > 0 || $score2 > 0;
                                                $nombre1 = $pareja1['nombre'] ?? 'TBD';
                                                $nombre2 = $pareja2['nombre'] ?? 'TBD';
                                            @endphp
                                            <div class="match-card{{ $tieneResultado ? ' jugado' : '' }}">
                                                <!-- Pareja 1 -->
                                                <div class="player-pair{{ $score1 > $score2 ? ' winner' : '' }}">
                                                    <div class="player-pair-content">
                                                        <div class="player-names">{{ $nombre1 }}</div>
                                                    </div>
                                                    <div class="player-pair-input">
                                                        <span class="score-display">{{ $score1 }}</span>
                                                    </div>
                                                </div>
                                                <!-- Pareja 2 -->
                                                <div class="player-pair{{ $score2 > $score1 ? ' winner' : '' }}">
                                                    <div class="player-pair-content">
                                                        <div class="player-names">{{ $nombre2 }}</div>
                                                    </div>
                                                    <div class="player-pair-input">
                                                        <span class="score-display">{{ $score2 }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="match-card placeholder">
                                                <div class="player-pair">
                                                    <div class="player-pair-content">
                                                        <div class="player-names">Esperando...</div>
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
            </div>
        @endforeach
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
    // Configuración
    const INTERVALO_ROTACION = {{ $intervalo }} * 1000; // milisegundos
    const INTERVALO_CHECK_VERSION = 2000; // 2 segundos
    const torneoIdsParam = '{{ $torneoIdsParam }}';
    
    // Estado
    let torneoActualIndex = 0;
    let versiones = {};
    let countdownSegundos = {{ $intervalo }};
    let rotacionPausada = false;
    
    // Inicializar versiones conocidas
    @foreach($torneosData as $torneo)
        versiones[{{ $torneo['id'] }}] = {{ $torneo['version'] }};
    @endforeach
    
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.indicador-dot');
    const nombreDisplay = document.getElementById('torneo-nombre');
    const countdownDisplay = document.getElementById('countdown');
    const notification = document.getElementById('update-notification');
    
    // Mostrar slide específico
    function mostrarSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
        
        const slideActivo = slides[index];
        nombreDisplay.textContent = slideActivo.dataset.torneoNombre + ' - CRUCES';
        torneoActualIndex = index;
        
        // Resetear countdown
        countdownSegundos = {{ $intervalo }};
        actualizarCountdown();
    }
    
    // Siguiente slide (rotación normal)
    function siguienteSlide() {
        if (rotacionPausada) return;
        
        const siguiente = (torneoActualIndex + 1) % slides.length;
        mostrarSlide(siguiente);
    }
    
    // Actualizar display del countdown
    function actualizarCountdown() {
        countdownDisplay.textContent = countdownSegundos + 's';
    }
    
    // Mostrar notificación de actualización
    function mostrarNotificacion() {
        notification.classList.add('visible');
        setTimeout(() => {
            notification.classList.remove('visible');
        }, 2000);
    }
    
    // Verificar versiones de todos los torneos
    function verificarVersiones() {
        $.get('{{ route("tvtorneosversiones") }}', { torneo_ids: torneoIdsParam })
            .done(function(response) {
                const nuevasVersiones = response.versiones || {};
                
                // Buscar cambios
                for (const torneoId in nuevasVersiones) {
                    const versionNueva = nuevasVersiones[torneoId];
                    const versionAnterior = versiones[torneoId] || 0;
                    
                    if (versionNueva > versionAnterior) {
                        console.log('Torneo ' + torneoId + ' actualizado: v' + versionAnterior + ' -> v' + versionNueva);
                        
                        // Actualizar versión conocida
                        versiones[torneoId] = versionNueva;
                        
                        // Encontrar índice del torneo actualizado
                        let torneoIndex = -1;
                        slides.forEach((slide, i) => {
                            if (slide.dataset.torneoId == torneoId) {
                                torneoIndex = i;
                            }
                        });
                        
                        if (torneoIndex >= 0) {
                            // Marcar el dot como actualizado
                            dots[torneoIndex].classList.add('updated');
                            setTimeout(() => dots[torneoIndex].classList.remove('updated'), 1000);
                            
                            // Si no estamos viendo ese torneo, saltar a él
                            if (torneoIndex !== torneoActualIndex) {
                                console.log('Saltando al torneo actualizado');
                                mostrarSlide(torneoIndex);
                            }
                            
                            // Mostrar notificación
                            mostrarNotificacion();
                            
                            // Recargar página para obtener datos frescos
                            setTimeout(() => {
                                window.location.reload();
                            }, 500);
                        }
                    }
                }
            })
            .fail(function() {
                console.log('Error verificando versiones');
            });
    }
    
    // Inicializar
    document.addEventListener('DOMContentLoaded', () => {
        // Rotación automática
        setInterval(() => {
            countdownSegundos--;
            if (countdownSegundos <= 0) {
                siguienteSlide();
            } else {
                actualizarCountdown();
            }
        }, 1000);
        
        // Verificación de versiones (polling inteligente)
        setInterval(verificarVersiones, INTERVALO_CHECK_VERSION);
        
        // Click en dots para cambio manual
        dots.forEach((dot, i) => {
            dot.addEventListener('click', () => {
                mostrarSlide(i);
            });
        });
    });
</script>
</body>
</html>
