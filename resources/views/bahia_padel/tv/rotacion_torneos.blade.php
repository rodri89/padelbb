<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bahia Padel - Rotación TV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            background: radial-gradient(circle at 15% 20%, rgba(255, 255, 255, 0.03), transparent 55%),
                        radial-gradient(circle at 80% 5%, rgba(255, 255, 255, 0.02), transparent 50%),
                        linear-gradient(180deg, var(--bg-1), var(--bg-2));
            font-family: "Space Grotesk", sans-serif;
            color: var(--text);
            font-weight: 300;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            letter-spacing: 0.01em;
        }

        :root {
            --bg-1: #050505;
            --bg-2: #0b0b0b;
            --panel: rgba(5, 5, 5, 0.92);
            --panel-border: rgba(255, 255, 255, 0.08);
            --text: #e6e6e6;
            --muted: #9aa0a6;
            --accent: #f97316;
            --accent-2: #06b6d4;
            --accent-3: #34d399;
            --header-height: 7vh;
            --content-height: calc(100vh - var(--header-height));
            --slides-height: calc(var(--content-height) * 0.8);
            --sponsors-height: calc(var(--content-height) * 0.2);
        }
        
        /* ========================================
           HEADER CON COLOR DE CATEGORÍA
           ======================================== */
        .header-tv {
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5vw;
            transition: background 0.5s ease, border-color 0.5s ease;
            backdrop-filter: blur(10px);
            background: rgba(5, 5, 5, 0.8);
            border-bottom: 1px solid var(--panel-border);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 1.5vw;
        }
        
        .categoria-badge {
            font-size: 2.1vh;
            font-weight: 700;
            padding: 0.5vh 1.2vw;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: #04101c;
            background: var(--accent-2);
            text-shadow: none;
        }
        
        .header-tv h2 {
            font-size: 3vh;
            font-weight: 600;
            color: var(--text);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin: 0;
            transition: opacity 0.3s ease;
        }
        
        .fase-badge {
            font-size: 1.6vh;
            font-weight: 600;
            padding: 0.45vh 1vw;
            border-radius: 999px;
            background: rgba(10, 10, 10, 0.8);
            border: 1px solid var(--panel-border);
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.15em;
        }
        
        .header-indicadores {
            display: flex;
            align-items: center;
            gap: 1vw;
        }
        
        .indicador-torneos {
            display: flex;
            gap: 0.8vw;
        }
        
        .indicador-dot {
            width: 1.5vh;
            height: 1.5vh;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .indicador-dot.active {
            transform: scale(1.4);
        }
        
        .indicador-dot.updated {
            animation: pulse 0.5s ease-out;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(2); }
            100% { transform: scale(1.4); }
        }
        
        .countdown-display {
            font-size: 1.8vh;
            color: var(--muted);
            font-weight: 500;
        }
        
        /* ========================================
           SLIDES CONTAINER
           ======================================== */
        .slides-container {
            height: var(--slides-height);
            position: relative;
            overflow: hidden;
        }

        .sponsors-mini {
            height: var(--sponsors-height);
            background: rgba(5, 5, 5, 0.9);
            border-top: 1px solid var(--panel-border);
            backdrop-filter: blur(3px);
            overflow: hidden;
            display: flex;
            align-items: center;
            padding: 0 1vw;
            gap: 0.8vw;
        }

        .sponsors-mini-label {
            flex-shrink: 0;
            font-size: 1.3vh;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(226, 232, 240, 0.7);
            padding: 0.6vh 0.8vw;
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 999px;
        }

        .sponsors-mini-track-wrap {
            flex: 1;
            overflow: hidden;
        }

        .sponsors-mini-track {
            display: flex;
            align-items: center;
            gap: 0.85vw;
            will-change: transform;
        }

        .sponsors-mini-card {
            height: calc(var(--sponsors-height) - 1.8vh);
            min-width: 10vw;
            max-width: 13vw;
            background: rgba(10, 10, 10, 0.9);
            border: 1px solid var(--panel-border);
            border-radius: 0.5vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.35vh 0.45vw;
            overflow: hidden;
            flex-shrink: 0;
        }

        .sponsors-mini-card img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            filter: saturate(1.05);
        }

        .sponsors-mini-card span {
            font-size: 1.45vh;
            font-weight: 400;
            color: rgba(226, 232, 240, 0.85);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
           FASE: CRUCES (BRACKET)
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
            font-weight: 600;
            color: var(--muted);
            text-align: center;
            padding: 0.4vh 0;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            flex-shrink: 0;
            height: 3vh;
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid var(--panel-border);
        }
        
        .bracket-round-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
            min-height: 0;
        }

        .bracket-round-body.auto-scroll {
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding-right: 0.2vw;
        }

        .bracket-round-body.auto-scroll::-webkit-scrollbar {
            display: none;
        }
        
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

        .bracket-column--dieciseisavos .bracket-round-body.auto-scroll .match-card,
        .bracket-column--octavos .bracket-round-body.auto-scroll .match-card {
            flex: 0 0 10vh;
            min-height: 10vh;
        }
        
        .player-pair {
            display: flex;
            align-items: center;
            padding: 0.25vh 0.45vw;
            background: rgba(10, 10, 10, 0.85);
            border: 1px solid var(--panel-border);
            border-left: 3px solid rgba(255, 255, 255, 0.14);
            border-radius: 8px;
            margin: 0.15vh 0;
        }
        
        .player-pair.winner {
            border-left-color: var(--accent-3);
            background: rgba(52, 211, 153, 0.12);
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
            font-weight: 500;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        
        .player-pair-input { 
            flex-shrink: 0; 
            margin-left: 0.5vw;
            display: flex;
            gap: 0.3vw;
        }
        
        .set-score {
            font-size: 2vh;
            font-weight: 600;
            color: var(--text);
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            padding: 0.2vh 0.5vw;
            border-radius: 3px;
            min-width: 1.8vw;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .set-score.won {
            background: rgba(52, 211, 153, 0.18);
            border-color: rgba(52, 211, 153, 0.35);
            color: var(--accent-3);
        }
        
        .set-score.lost {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.08);
            color: var(--muted);
        }
        
        .player-pair.winner .player-names {
            color: var(--accent-3);
        }
        
        .match-card.placeholder .player-names {
            color: var(--muted);
            opacity: 0.55;
            font-style: italic;
        }
        
        /* Anchos de columnas por cantidad de rondas */
        .rondas-5 .bracket-column--dieciseisavos { width: 22%; }
        .rondas-5 .bracket-column--octavos { width: 22%; }
        .rondas-5 .bracket-column--cuartos { width: 20%; }
        .rondas-5 .bracket-column--semis { width: 18%; }
        .rondas-5 .bracket-column--final { width: 18%; }
        .rondas-5 .player-names { font-size: 2vh; }
        .rondas-5 .score-display { font-size: 2.2vh; }
        
        .rondas-4 .bracket-column--octavos { width: 28%; }
        .rondas-4 .bracket-column--cuartos { width: 24%; }
        .rondas-4 .bracket-column--semis { width: 24%; }
        .rondas-4 .bracket-column--final { width: 24%; }
        
        .rondas-3 .bracket-column--cuartos { width: 36%; }
        .rondas-3 .bracket-column--semis { width: 32%; }
        .rondas-3 .bracket-column--final { width: 32%; }
        
        .rondas-2 .bracket-column--semis { width: 50%; }
        .rondas-2 .bracket-column--final { width: 50%; }
        
        .rondas-1 .bracket-column--final { width: 100%; }
        
        /* ========================================
           FASE: GRUPOS (TABLAS)
           ======================================== */
        .grupos-container {
            height: 100%;
            display: flex;
            flex-wrap: wrap;
            padding: 1vh 1vw;
            gap: 1vw;
            overflow: hidden;
        }
        
        .zona-card {
            flex: 1;
            min-width: 45%;
            max-width: 49%;
            background: rgba(10, 10, 10, 0.85);
            border: 1px solid var(--panel-border);
            border-radius: 1vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        /* Si hay 1-2 zonas, ocupan más espacio */
        .grupos-container.zonas-1 .zona-card,
        .grupos-container.zonas-2 .zona-card {
            min-width: 48%;
            max-width: 49%;
        }
        
        /* Si hay 3-4 zonas */
        .grupos-container.zonas-3 .zona-card,
        .grupos-container.zonas-4 .zona-card {
            min-width: 48%;
            max-width: 49%;
            max-height: 48%;
        }
        
        .zona-header {
            padding: 1vh 1vw;
            text-align: center;
            font-size: 2.5vh;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #fff;
        }
        
        .zona-table {
            flex: 1;
            overflow: hidden;
        }
        
        .zona-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .zona-table th {
            background: rgba(0,0,0,0.3);
            color: rgba(255,255,255,0.7);
            font-size: 1.6vh;
            font-weight: 500;
            padding: 0.8vh 0.5vw;
            text-align: center;
            text-transform: uppercase;
        }
        
        .zona-table th:first-child {
            text-align: left;
            padding-left: 1vw;
            width: 40%;
        }
        
        .zona-table td {
            font-size: 2vh;
            font-weight: 300;
            padding: 0.8vh 0.5vw;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #e2e8f0;
        }
        
        .zona-table td:first-child {
            text-align: left;
            padding-left: 1vw;
            font-weight: 400;
            text-transform: uppercase;
        }
        
        /* Resaltado de posiciones - se aplica dinámicamente con JS según reglas del torneo */
        .zona-table tr.clasificado td:first-child {
            font-weight: 500;
        }
        
        .zona-table .pts {
            font-weight: 600;
            font-size: 2.2vh;
        }
        
        /* ========================================
           NOTIFICACIÓN DE ACTUALIZACIÓN
           ======================================== */
        .update-notification {
            position: fixed;
            top: 9vh;
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
    <!-- Header dinámico por categoría -->
    <div class="header-tv" id="header-tv">
        <div class="header-left">
            <span class="categoria-badge" id="categoria-badge">{{ $torneosData[0]['colorCategoria']['nombre'] ?? '6TA' }}</span>
            <h2 id="torneo-nombre">{{ $torneosData[0]['nombre'] ?? 'Torneo' }}</h2>
            <span class="fase-badge" id="fase-badge">{{ $torneosData[0]['fase'] === 'cruces' ? 'CRUCES' : 'GRUPOS' }}</span>
        </div>
        <div class="header-indicadores">
            <div class="indicador-torneos">
                @foreach($torneosData as $index => $torneo)
                    <div class="indicador-dot{{ $index === 0 ? ' active' : '' }}" 
                         data-torneo-id="{{ $torneo['id'] }}"
                         data-color="{{ $torneo['colorCategoria']['bg'] }}"
                         style="background: {{ $index === 0 ? $torneo['colorCategoria']['bg'] : 'rgba(255,255,255,0.3)' }}"
                         title="{{ $torneo['nombre'] }} - {{ $torneo['colorCategoria']['nombre'] }}"></div>
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
            @php $esAmericano = ($torneoData['tipo_torneo_formato'] ?? 'puntuable') === 'americano'; @endphp
            <div class="slide{{ $torneoIndex === 0 ? ' active' : '' }}" 
                 data-torneo-id="{{ $torneoData['id'] }}"
                 data-torneo-nombre="{{ $torneoData['nombre'] }}"
                 data-categoria="{{ $torneoData['categoria'] }}"
                 data-categoria-nombre="{{ $torneoData['colorCategoria']['nombre'] }}"
                 data-categoria-bg="{{ $torneoData['colorCategoria']['bg'] }}"
                 data-categoria-border="{{ $torneoData['colorCategoria']['border'] }}"
                 data-fase="{{ $torneoData['fase'] }}"
                 data-tipo-torneo="{{ $torneoData['tipo_torneo_formato'] ?? 'puntuable' }}"
                 data-version="{{ $torneoData['version'] }}">
                
                @if($torneoData['fase'] === 'cruces')
                    {{-- VISTA DE CRUCES --}}
                    @php
                        $cruces = $torneoData['cruces'] ?? [];
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

                        // Normalizar cantidad máxima por ronda para TV.
                        // Algunos torneos pueden tener llaves auxiliares y generan más cruces de lo esperado.
                        $crucesPorRonda['cuartos final'] = array_slice($crucesPorRonda['cuartos final'], 0, 4);
                        $crucesPorRonda['semifinal'] = array_slice($crucesPorRonda['semifinal'], 0, 2);
                        $crucesPorRonda['final'] = array_slice($crucesPorRonda['final'], 0, 1);
                        
                        $rondasConScroll = ['dieciseisavos final', 'octavos final'];
                    @endphp
                    @php
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
                        if (count($crucesPorRonda['semifinal']) > 0) {
                            $rondasMostrar[] = ['key' => 'semifinal', 'title' => 'SEMIS', 'class' => 'semis'];
                        }
                        if (count($crucesPorRonda['final']) > 0) {
                            $rondasMostrar[] = ['key' => 'final', 'title' => 'FINAL', 'class' => 'final'];
                        }
                        $numRondas = count($rondasMostrar);
                    @endphp

                    <div class="bracket-container scroll-mode">
                        <div class="bracket-row rondas-{{ $numRondas }}">
                            @foreach($rondasMostrar as $rondaInfo)
                                @php $crucesRonda = $crucesPorRonda[$rondaInfo['key']] ?? []; @endphp
                                <div class="bracket-column bracket-column--{{ $rondaInfo['class'] }}">
                                    <div class="bracket-round">
                                        <div class="bracket-round-title">{{ $rondaInfo['title'] }}</div>
                                        <div class="bracket-round-body{{ in_array($rondaInfo['key'], $rondasConScroll) ? ' auto-scroll' : '' }}" @if(in_array($rondaInfo['key'], $rondasConScroll)) data-auto-scroll="1" @endif>
                                            @forelse($crucesRonda as $cruce)
                                                @include('bahia_padel.tv.partials.match_card', ['cruce' => $cruce, 'esAmericano' => $esAmericano])
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
                @else
                    {{-- VISTA DE GRUPOS/ZONAS --}}
                    @php
                        $zonas = $torneoData['zonas'] ?? [];
                        $tablas = $torneoData['tablasPosiciones'] ?? [];
                        $numZonas = count($zonas);
                    @endphp
                    
                    <div class="grupos-container zonas-{{ min($numZonas, 4) }}">
                        @foreach($zonas as $zona)
                            @php $posiciones = $tablas[$zona] ?? []; @endphp
                            <div class="zona-card">
                                <div class="zona-header" style="background: {{ $torneoData['colorCategoria']['bg'] }};">
                                    {{ $zona }}
                                </div>
                                <div class="zona-table">
                                    <table>
                                        <thead>
                                            @if($esAmericano)
                                                {{-- COLUMNAS AMERICANO: Sin sets, solo games --}}
                                                <tr>
                                                    <th>Pareja</th>
                                                    <th>PJ</th>
                                                    <th>PG</th>
                                                    <th>PP</th>
                                                    <th>GF</th>
                                                    <th>GC</th>
                                                </tr>
                                            @else
                                                {{-- COLUMNAS PUNTUABLE: Con sets y puntos --}}
                                                <tr>
                                                    <th>Pareja</th>
                                                    <th>PJ</th>
                                                    <th>PG</th>
                                                    <th>PP</th>
                                                    <th>SF</th>
                                                    <th>SC</th>
                                                    <th>PTS</th>
                                                </tr>
                                            @endif
                                        </thead>
                                        <tbody>
                                            @forelse($posiciones as $pos)
                                                @if($esAmericano)
                                                    {{-- FILA AMERICANO --}}
                                                    <tr>
                                                        <td>{{ $pos['nombre'] }}</td>
                                                        <td>{{ $pos['pj'] }}</td>
                                                        <td>{{ $pos['pg'] }}</td>
                                                        <td>{{ $pos['pp'] }}</td>
                                                        <td>{{ $pos['gf'] }}</td>
                                                        <td>{{ $pos['gc'] }}</td>
                                                    </tr>
                                                @else
                                                    {{-- FILA PUNTUABLE --}}
                                                    <tr>
                                                        <td>{{ $pos['nombre'] }}</td>
                                                        <td>{{ $pos['pj'] }}</td>
                                                        <td>{{ $pos['pg'] }}</td>
                                                        <td>{{ $pos['pp'] }}</td>
                                                        <td>{{ $pos['sf'] }}</td>
                                                        <td>{{ $pos['sc'] }}</td>
                                                        <td class="pts">{{ $pos['pts'] }}</td>
                                                    </tr>
                                                @endif
                                            @empty
                                                <tr>
                                                    <td colspan="{{ $esAmericano ? 6 : 7 }}" style="text-align:center; color:rgba(255,255,255,0.5);">Sin datos</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                        
                        @if($numZonas === 0)
                            <div style="width:100%; text-align:center; padding:10vh 0; color:rgba(255,255,255,0.5); font-size:3vh;">
                                No hay zonas configuradas
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="sponsors-mini">
  
        <div class="sponsors-mini-track-wrap">
            <div class="sponsors-mini-track" data-sponsors-mini-track>
                @php
                    $displaySponsors = $sponsors ?? collect();
                    $totalSponsors = count($displaySponsors);
                    $sponsorsLoop = $totalSponsors > 0
                        ? $displaySponsors->concat($displaySponsors->take(min(6, $totalSponsors)))
                        : collect();
                @endphp

                @foreach($sponsorsLoop as $s)
                    <div class="sponsors-mini-card">
                        @if(!empty($s->imagen))
                            <img src="{{ asset(str_starts_with($s->imagen, 'images/ads/') ? $s->imagen : ('images/ads/' . $s->imagen)) }}" alt="{{ $s->nombre ?? 'Sponsor' }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <span style="display:none;">{{ $s->nombre ?? 'Sponsor' }}</span>
                        @else
                            <span>{{ $s->nombre ?? 'Sponsor' }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
    // Configuración
    const INTERVALO_ROTACION = {{ $intervalo }} * 1000;
    const INTERVALO_CHECK_VERSION = 2000;
    const torneoIdsParam = '{{ $torneoIdsParam }}';
    const INTERVALO_SEGUNDOS = {{ $intervalo }};
    
    // Estado
    let torneoActualIndex = 0;
    let versiones = {};
    let countdownSegundos = INTERVALO_SEGUNDOS;
    let autoScrollTimer = null;
    
    // Inicializar versiones conocidas
    @foreach($torneosData as $torneo)
        versiones[{{ $torneo['id'] }}] = {{ $torneo['version'] }};
    @endforeach
    
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.indicador-dot');
    const headerTv = document.getElementById('header-tv');
    const categoriaBadge = document.getElementById('categoria-badge');
    const nombreDisplay = document.getElementById('torneo-nombre');
    const faseBadge = document.getElementById('fase-badge');
    const countdownDisplay = document.getElementById('countdown');
    const notification = document.getElementById('update-notification');
    const sponsorsTrack = document.querySelector('[data-sponsors-mini-track]');
    const totalOriginalSponsors = {{ count($sponsors ?? []) }};
    
    function detenerAutoScrollCruces() {
        if (autoScrollTimer) {
            clearInterval(autoScrollTimer);
            autoScrollTimer = null;
        }
    }

    function iniciarAutoScrollCruces(slide) {
        detenerAutoScrollCruces();

        const targets = Array.from(slide.querySelectorAll('[data-auto-scroll]'));
        if (!targets.length) return;

        const estados = targets.map(el => ({ el, dir: 1 }));

        autoScrollTimer = setInterval(() => {
            estados.forEach(state => {
                const el = state.el;
                const maxScroll = el.scrollHeight - el.clientHeight;
                if (maxScroll <= 0) return;

                // Velocidad adaptativa: con intervalos cortos (ej: 5s) el scroll sigue siendo visible.
                const segundosSlide = Math.max(3, INTERVALO_ROTACION / 1000);
                const recorridoObjetivo = Math.min(maxScroll, Math.max(140, maxScroll * 0.45));
                const pxPorSegundo = recorridoObjetivo / segundosSlide;
                const paso = Math.max(0.5, pxPorSegundo * 0.04);
                const next = el.scrollTop + (paso * state.dir);

                if (next >= maxScroll) {
                    el.scrollTop = maxScroll;
                    state.dir = -1;
                } else if (next <= 0) {
                    el.scrollTop = 0;
                    state.dir = 1;
                } else {
                    el.scrollTop = next;
                }
            });
        }, 40);
    }
    
    // Mostrar slide específico
    function mostrarSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        
        // Actualizar dots
        dots.forEach((dot, i) => {
            if (i === index) {
                dot.classList.add('active');
                dot.style.background = dot.dataset.color;
            } else {
                dot.classList.remove('active');
                dot.style.background = 'rgba(255,255,255,0.3)';
            }
        });
        
        const slideActivo = slides[index];
        const bg = slideActivo.dataset.categoriaBg;
        const border = slideActivo.dataset.categoriaBorder;
        const fase = slideActivo.dataset.fase;
        
        // Actualizar header con color de categoría
        headerTv.style.background = `linear-gradient(135deg, ${bg} 0%, ${border} 100%)`;
        headerTv.style.borderBottom = `2px solid ${border}`;
        
        // Actualizar textos
        categoriaBadge.textContent = slideActivo.dataset.categoriaNombre;
        categoriaBadge.style.background = 'rgba(0,0,0,0.3)';
        nombreDisplay.textContent = slideActivo.dataset.torneoNombre;
        faseBadge.textContent = fase === 'cruces' ? 'CRUCES' : 'GRUPOS';
        
        // Si la vista de cruces lo requiere, iniciar auto-scroll lento en rondas largas
        iniciarAutoScrollCruces(slideActivo);
        
        torneoActualIndex = index;
        countdownSegundos = INTERVALO_SEGUNDOS;
        actualizarCountdown();
    }
    
    function siguienteSlide() {
        const siguiente = (torneoActualIndex + 1) % slides.length;
        mostrarSlide(siguiente);
    }
    
    function actualizarCountdown() {
        countdownDisplay.textContent = countdownSegundos + 's';
    }
    
    function mostrarNotificacion() {
        notification.classList.add('visible');
        setTimeout(() => {
            notification.classList.remove('visible');
        }, 2000);
    }
    
    function verificarVersiones() {
        $.get('{{ route("tvtorneosversiones") }}', { torneo_ids: torneoIdsParam })
            .done(function(response) {
                const nuevasVersiones = response.versiones || {};
                
                for (const torneoId in nuevasVersiones) {
                    const versionNueva = nuevasVersiones[torneoId];
                    const versionAnterior = versiones[torneoId] || 0;
                    
                    if (versionNueva > versionAnterior) {
                        console.log('Torneo ' + torneoId + ' actualizado: v' + versionAnterior + ' -> v' + versionNueva);
                        versiones[torneoId] = versionNueva;
                        
                        let torneoIndex = -1;
                        slides.forEach((slide, i) => {
                            if (slide.dataset.torneoId == torneoId) {
                                torneoIndex = i;
                            }
                        });
                        
                        if (torneoIndex >= 0) {
                            dots[torneoIndex].classList.add('updated');
                            setTimeout(() => dots[torneoIndex].classList.remove('updated'), 1000);
                            
                            if (torneoIndex !== torneoActualIndex) {
                                mostrarSlide(torneoIndex);
                            }
                            
                            mostrarNotificacion();
                            
                            // Recargar para datos frescos
                            setTimeout(() => window.location.reload(), 500);
                        }
                    }
                }
            });
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        // Inicializar header con el primer torneo
        mostrarSlide(0);
        
        // Rotación automática uniforme entre slides
        setInterval(() => {
            countdownSegundos--;

            if (countdownSegundos <= 0) {
                siguienteSlide();
            } else {
                actualizarCountdown();
            }
        }, 1000);
        
        // Verificación de versiones
        setInterval(verificarVersiones, INTERVALO_CHECK_VERSION);
        
        // Click en dots para cambio manual
        dots.forEach((dot, i) => {
            dot.addEventListener('click', () => mostrarSlide(i));
        });

        if (sponsorsTrack && totalOriginalSponsors > 0) {
            let sponsorIndex = 0;

            const getSponsorStep = () => {
                const firstCard = sponsorsTrack.querySelector('.sponsors-mini-card');
                if (!firstCard) return 0;
                const style = window.getComputedStyle(sponsorsTrack);
                const gap = parseFloat(style.columnGap || style.gap || 0) || 0;
                return firstCard.getBoundingClientRect().width + gap;
            };

            setInterval(() => {
                const step = getSponsorStep();
                if (!step) return;

                sponsorIndex++;
                sponsorsTrack.style.transition = 'transform 0.55s ease';
                sponsorsTrack.style.transform = `translateX(-${sponsorIndex * step}px)`;

                if (sponsorIndex >= totalOriginalSponsors) {
                    setTimeout(() => {
                        sponsorIndex = 0;
                        sponsorsTrack.style.transition = 'none';
                        sponsorsTrack.style.transform = 'translateX(0)';
                    }, 280);
                }
            }, 2600);
        }
    });
</script>
</body>
</html>
