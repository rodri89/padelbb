<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bahia Padel - Resultados TV</title>
    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/dark-mode.css') }}" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
        
        body { 
            overflow: hidden; 
            font-family: 'Nunito', sans-serif; 
            background-color: #1a1a1a;
            color: #e0e0e0;
            margin: 0;
            padding: 0;
            height: 100vh;
            width: 100vw;
        }
        
        .zona-slide { 
            display: none; 
            height: 100vh; 
            width: 100vw;
            padding: 1vh 1.5vw; 
            box-sizing: border-box;
            overflow: hidden;
        }
        
        .zona-slide.active { 
            display: flex; 
            flex-direction: column;
            animation: fadeIn 0.8s; 
        }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .tv-header { 
            font-size: 4vh; 
            margin: 0.5vh 0 1vh 0; 
            text-align: center; 
            color: #fff; 
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 800;
            text-shadow: 0.2vh 0.2vh 0.4vh rgba(0,0,0,0.5);
            flex-shrink: 0;
        }
        
        .tv-card { 
            background-color: #252525; 
            border: 0.1vh solid #3d3d3d; 
            border-radius: 1.5vh; 
            height: 0;
            flex: 1;
            min-height: 0;
            overflow: hidden; 
            box-shadow: 0 1vh 2vh rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }

        .tv-card-header {
            background-color: #1f1f1f;
            padding: 1.5vh 1vw;
            border-bottom: 0.2vh solid #3d3d3d;
            text-align: center;
            flex-shrink: 0;
        }

        .tv-card-header h3 {
            margin: 0;
            color: #4e73df;
            font-weight: 700;
            font-size: 3vh;
        }

        .tv-card-body {
            flex: 1;
            min-height: 0;
            overflow: hidden;
            padding: 0;
            display: block;
        }
        
        .tv-table { 
            width: 100%; 
            border-collapse: collapse;
            table-layout: fixed;
        }
        
        .tv-table th { 
            background-color: #2d2d2d; 
            color: #aaa; 
            text-transform: uppercase;
            font-size: 1.8vh;
            padding: 1vh 1vw;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .tv-table td { 
            padding: 1.2vh 1.2vw; 
            border-bottom: 0.1vh solid #333;
            vertical-align: middle;
            font-size: 2vh;
        }
        
        .tv-table tr:nth-child(even) {
            background-color: #222;
        }
        
        .player-img { 
            width: 5vh; 
            height: 5vh; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 0.2vh solid #555; 
            margin-right: 0.8vw; 
            flex-shrink: 0;
        }
        
        .player-img-overlap {
            margin-left: -2.5vh;
            border: 0.3vh solid #252525;
            position: relative;
            z-index: 1;
        }
        
        .player-img-container {
            display: flex;
            align-items: center;
            margin-right: 0.8vw;
            flex-shrink: 0;
        }
        
        .player-info {
            display: flex;
            align-items: center;
            width: 100%;
        }
        
        .player-names {
            line-height: 1.2;
            flex: 1;
            min-width: 0;
        }
        
        .player-name {
            display: block;
            font-size: clamp(1rem, 1.8vw, 1.5rem);
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .score-cell {
            font-size: clamp(1.5rem, 2.5vw, 2rem);
            font-weight: 800;
            color: #fff;
            text-align: center;
            min-width: 40px;
            flex-shrink: 0;
        }
        
        .score-container {
            display: flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }

        .score-active {
            color: #4e73df;
        }
        
        .pos-rank-1 { color: #FFD700; text-shadow: 0 0 10px rgba(255, 215, 0, 0.3); }
        .pos-rank-2 { color: #C0C0C0; }
        .pos-rank-3 { color: #CD7F32; }

        .progress-bar-top {
            position: fixed;
            top: 0;
            left: 0;
            height: 5px;
            background-color: #4e73df;
            width: 0%;
            z-index: 9999;
            transition: width 1s linear;
        }
        
        .btn-navegar {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #2d2d2d;
            color: #fff;
            border: 1px solid #3d3d3d;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 1.2rem;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease;
            z-index: 1000;
        }
        
        .btn-navegar:hover {
            background-color: #353535;
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <a href="{{ route('tvtorneoamericanocruces') }}?torneo_id={{ $torneo->id ?? 0 }}" class="btn-navegar">></a>
    <div class="progress-bar-top" id="progress-bar"></div>

    <div id="app-container">
        @if(empty($partidosPorZona))
            <div style="display:flex; justify-content:center; align-items:center; height:100vh; width:100vw;">
                <h1 style="font-size:clamp(2rem, 4vw, 3rem);">Esperando Partidos...</h1>
            </div>
        @else
            @php
                // Fetch groups for score ordering logic
                $gruposExistentes = collect(DB::table('grupos')
                    ->where('torneo_id', $torneo->id)
                    ->orderBy('partido_id')
                    ->orderBy('id')
                    ->get());
                
                $jugadoresArray = is_array($jugadores) ? $jugadores : (is_object($jugadores) ? $jugadores->toArray() : []);
                $jugadoresKeyed = collect($jugadoresArray)->keyBy('id');
            @endphp

            @foreach($partidosPorZona as $zona => $partidos)
              <div class="zona-slide" id="zona-{{ $loop->index }}">
                 <div style="display: flex; flex-direction: column; height: 100%; width: 100%; overflow: hidden;">
                    <h1 class="tv-header">Zona {{ $zona }} <span style="font-weight:300; font-size:clamp(1rem, 2vw, 1.5rem); color:#888;">| {{ $torneo->nombre ?? 'Torneo' }}</span></h1>
                    <div style="display: flex; gap: 1.5vw; flex: 1; min-height: 0; overflow: hidden;">
                        <!-- Partidos -->
                        <div style="flex: 2; min-width: 0; display: flex; flex-direction: column; overflow: hidden;">
                            <div class="tv-card">
                                 <div class="tv-card-header">
                                     <h3>Partidos</h3>
                                 </div>
                                 <div class="tv-card-body">
                                     <table class="tv-table">
                                        <thead>
                                            <tr>
                                                <th style="width:40%; text-align:left; padding-left:1.5vw;">Pareja 1</th>
                                                <th style="width:20%;" class="text-center">Score</th>
                                                <th style="width:40%; text-align:right; padding-right:1.5vw;">Pareja 2</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody-partidos-{{ $zona }}">
                                            @php
                                                // Function to render match row
                                                $renderRow = function($partido) use ($jugadoresKeyed, $gruposExistentes, $partidosConResultados) {
                                                    $jugador1_1 = $jugadoresKeyed[$partido['pareja_1']['jugador_1']] ?? null;
                                                    $jugador1_2 = $jugadoresKeyed[$partido['pareja_1']['jugador_2']] ?? null;
                                                    $jugador2_1 = $jugadoresKeyed[$partido['pareja_2']['jugador_1']] ?? null;
                                                    $jugador2_2 = $jugadoresKeyed[$partido['pareja_2']['jugador_2']] ?? null;
                                                    
                                                    $partidoIdKey = isset($partido['partido_id']) ? $partido['partido_id'] : null;
                                                    $resultado = ($partidoIdKey && isset($partidosConResultados[$partidoIdKey])) ? $partidosConResultados[$partidoIdKey] : null;
                                                    
                                                    $s1 = '-'; $s2 = '-';
                                                    
                                                    if ($resultado) {
                                                        $gruposPartido = $gruposExistentes->where('partido_id', $partidoIdKey)->sortBy('id')->values();
                                                        if ($gruposPartido->count() >= 2) {
                                                            $grupo1 = $gruposPartido[0];
                                                            if ($grupo1->jugador_1 == $partido['pareja_1']['jugador_1'] && 
                                                                $grupo1->jugador_2 == $partido['pareja_1']['jugador_2']) {
                                                                $s1 = $resultado->pareja_1_set_1;
                                                                $s2 = $resultado->pareja_2_set_1;
                                                            } else {
                                                                $s1 = $resultado->pareja_2_set_1;
                                                                $s2 = $resultado->pareja_1_set_1;
                                                            }
                                                        } else {
                                                            $s1 = $resultado->pareja_1_set_1;
                                                            $s2 = $resultado->pareja_2_set_1;
                                                        }
                                                    }
                                                    
                                                    return '<tr data-partido-id="' . $partidoIdKey . '">' .
                                                        '<td>' .
                                                            '<div class="player-info">' .
                                                                '<div class="player-img-container">' .
                                                                    ($jugador1_1 ? '<img src="' . asset($jugador1_1->foto ?? 'images/jugador_img.png') . '" class="player-img" onerror="this.src=\'' . asset('images/jugador_img.png') . '\'">' : '') .
                                                                    ($jugador1_2 ? '<img src="' . asset($jugador1_2->foto ?? 'images/jugador_img.png') . '" class="player-img player-img-overlap" onerror="this.src=\'' . asset('images/jugador_img.png') . '\'">' : '') .
                                                                '</div>' .
                                                                '<div class="player-names">' .
                                                                    '<span class="player-name">' . ($jugador1_1 ? ($jugador1_1->nombre ?? '') . ' ' . ($jugador1_1->apellido ?? '') : '') . '</span>' .
                                                                    ($jugador1_2 ? '<span class="player-name">' . ($jugador1_2->nombre ?? '') . ' ' . ($jugador1_2->apellido ?? '') . '</span>' : '') .
                                                                '</div>' .
                                                            '</div>' .
                                                        '</td>' .
                                                        '<td class="text-center">' .
                                                            '<div class="score-container">' .
                                                                '<div class="score-cell ' . ((is_numeric($s1) && is_numeric($s2) && $s1 > $s2) ? 'score-active' : '') . '" data-score-p1="' . $s1 . '">' . $s1 . '</div>' .
                                                                '<span style="color:#555; font-size:clamp(1.2rem, 2vw, 1.8rem); margin:0 0.5vw;">-</span>' .
                                                                '<div class="score-cell ' . ((is_numeric($s1) && is_numeric($s2) && $s2 > $s1) ? 'score-active' : '') . '" data-score-p2="' . $s2 . '">' . $s2 . '</div>' .
                                                            '</div>' .
                                                        '</td>' .
                                                        '<td style="text-align:right;">' .
                                                            '<div class="player-info" style="flex-direction:row-reverse; text-align:right;">' .
                                                                '<div class="player-img-container" style="flex-direction:row-reverse; margin-left:12px; margin-right:0;">' .
                                                                    ($jugador2_2 ? '<img src="' . asset($jugador2_2->foto ?? 'images/jugador_img.png') . '" class="player-img player-img-overlap" style="margin-left:0; margin-right:-20px;" onerror="this.src=\'' . asset('images/jugador_img.png') . '\'">' : '') .
                                                                    ($jugador2_1 ? '<img src="' . asset($jugador2_1->foto ?? 'images/jugador_img.png') . '" class="player-img" style="margin-right:0;" onerror="this.src=\'' . asset('images/jugador_img.png') . '\'">' : '') .
                                                                '</div>' .
                                                                '<div class="player-names" style="text-align:right;">' .
                                                                    '<span class="player-name">' . ($jugador2_1 ? ($jugador2_1->nombre ?? '') . ' ' . ($jugador2_1->apellido ?? '') : '') . '</span>' .
                                                                    ($jugador2_2 ? '<span class="player-name">' . ($jugador2_2->nombre ?? '') . ' ' . ($jugador2_2->apellido ?? '') . '</span>' : '') .
                                                                '</div>' .
                                                            '</div>' .
                                                        '</td>' .
                                                    '</tr>';
                                                };
                                                
                                                // If more than 8 matches, split into 2 columns
                                                // But table structure doesn't support columns easily inside table.
                                                // We can render two tables if needed.
                                                // For now, simpler compact view.
                                                foreach($partidos as $partido) {
                                                    echo $renderRow($partido);
                                                }
                                            @endphp
                                        </tbody>
                                     </table>
                                 </div>
                            </div>
                        </div>
                        
                        <!-- Posiciones -->
                        <div style="flex: 1; min-width: 0; display: flex; flex-direction: column;">
                            <div class="tv-card">
                                <div class="tv-card-header">
                                    <h3>Posiciones</h3>
                                </div>
                                <div class="tv-card-body">
                                    <table class="tv-table">
                                       <thead>
                                           <tr>
                                               <th class="text-center" style="width:15%;">#</th>
                                               <th style="width:50%;">Pareja</th>
                                               <th class="text-center" style="width:17.5%;">PG</th>
                                               <th class="text-center" style="width:17.5%;">Games</th>
                                           </tr>
                                       </thead>
                                       <tbody id="tbody-posiciones-{{ $zona }}">
                                            @if(isset($posicionesPorZona[$zona]))
                                                @foreach($posicionesPorZona[$zona] as $index => $pos)
                                                    @php
                                                        $p1 = $jugadoresKeyed[$pos['jugador_1']] ?? null;
                                                        $p2 = $jugadoresKeyed[$pos['jugador_2']] ?? null;
                                                    @endphp
                                                    <tr>
                                                        <td class="text-center"><span class="pos-rank pos-rank-{{ $index + 1 }}">{{ $index + 1 }}</span></td>
                                                        <td>
                                                            <div class="player-info">
                                                                <div class="player-img-container">
                                                                    @if($p1)
                                                                        <img src="{{ asset($p1->foto ?? 'images/jugador_img.png') }}" class="player-img" onerror="this.src='{{ asset('images/jugador_img.png') }}'">
                                                                    @endif
                                                                    @if($p2)
                                                                        <img src="{{ asset($p2->foto ?? 'images/jugador_img.png') }}" class="player-img player-img-overlap" onerror="this.src='{{ asset('images/jugador_img.png') }}'">
                                                                    @endif
                                                                </div>
                                                                <div class="player-names">
                                                                    <span class="player-name">{{ $p1 ? ($p1->nombre ?? '') . ' ' . ($p1->apellido ?? '') : '' }}</span>
                                                                    @if($p2)
                                                                        <span class="player-name">{{ ($p2->nombre ?? '') . ' ' . ($p2->apellido ?? '') }}</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center"><span style="font-size:clamp(1.2rem, 2vw, 1.5rem); font-weight:bold;">{{ $pos['partidos_ganados'] ?? 0 }}</span></td>
                                                        <td class="text-center">
                                                            @php
                                                                $diferencia = ($pos['puntos_ganados'] ?? 0) - ($pos['puntos_perdidos'] ?? 0);
                                                                $diferenciaTexto = $diferencia >= 0 ? '+' . $diferencia : (string)$diferencia;
                                                                $diferenciaClass = $diferencia >= 0 ? 'color:#4e73df;' : 'color:#e74a3b;';
                                                            @endphp
                                                            <span style="font-size:clamp(1.2rem, 2vw, 1.5rem); font-weight:bold; {{ $diferenciaClass }}">{{ $diferenciaTexto }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr><td colspan="4" class="text-center">No info</td></tr>
                                            @endif
                                       </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                 </div>
              </div>
            @endforeach
        @endif
    </div>

    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            let slides = $('.zona-slide');
            let currentIndex = 0;
            // Intervalo configurado desde el panel de control o 20 segundos por defecto
            let slideInterval = {{ ($intervalo ?? 20) * 1000 }}; 
            let torneoId = {{ $torneo->id ?? 0 }};
            let ultimaVersionConocida = {{ $torneo->version ?? 0 }};
            
            // Datos iniciales para comparar
            let jugadores = @json($jugadores ?? []);
            let partidosPorZona = @json($partidosPorZona ?? []);
            let gruposExistentes = @json($gruposExistentes->toArray() ?? []);
            
            // Polling inteligente: verificar versión cada 2 segundos
            function verificarVersionYActualizar() {
                if (!torneoId) return;
                
                $.get('{{ route("tvtorneoversion") }}', { torneo_id: torneoId })
                    .done(function(response) {
                        const versionActual = response.version || 0;
                        
                        if (versionActual > ultimaVersionConocida) {
                            console.log('Versión cambió:', ultimaVersionConocida, '->', versionActual);
                            ultimaVersionConocida = versionActual;
                            
                            // Actualizar datos en lugar de recargar toda la página
                            actualizarTablas();
                        }
                    })
                    .fail(function() {
                        // Silencioso, reintentar en el próximo intervalo
                    });
            }
            
            // Polling inteligente cada 2 segundos
            setInterval(verificarVersionYActualizar, 2000);
            
            function showSlide(index) {
                slides.removeClass('active');
                $(slides[index]).addClass('active');
                
                // Reset and animate progress bar
                $('#progress-bar').remove();
                $('body').append('<div class="progress-bar-top" id="progress-bar"></div>');
                
                setTimeout(function() {
                    $('#progress-bar').css('transition', 'width ' + (slideInterval/1000) + 's linear').css('width', '100%');
                }, 50);
            }
            
            // Función para actualizar tablas
            function actualizarTablas() {
                let torneoId = {{ $torneo->id ?? 0 }};
                
                $.ajax({
                    type: 'POST',
                    url: '{{ route("tvtorneoamericanoactualizar") }}',
                    data: {
                        torneo_id: torneoId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Actualizar posiciones por zona
                            if (response.posicionesPorZona) {
                                Object.keys(response.posicionesPorZona).forEach(function(zona) {
                                    actualizarPosiciones(zona, response.posicionesPorZona[zona]);
                                });
                            }
                            
                            // Actualizar resultados de partidos
                            if (response.partidosConResultados) {
                                actualizarResultadosPartidos(response.partidosConResultados);
                            }
                        }
                    },
                    error: function() {
                        // Silencioso, no mostrar error si falla
                    }
                });
            }
            
            // Función para actualizar tabla de posiciones
            function actualizarPosiciones(zona, posiciones) {
                let tbody = $('#tbody-posiciones-' + zona);
                if (tbody.length === 0) return;
                
                tbody.empty();
                
                if (posiciones.length === 0) {
                    tbody.append('<tr><td colspan="4" class="text-center">No info</td></tr>');
                    return;
                }
                
                posiciones.forEach(function(pos, index) {
                    let p1 = jugadores.find(j => j.id == pos.jugador_1) || null;
                    let p2 = jugadores.find(j => j.id == pos.jugador_2) || null;
                    
                    let diferencia = (pos.puntos_ganados || 0) - (pos.puntos_perdidos || 0);
                    let diferenciaTexto = diferencia >= 0 ? '+' + diferencia : diferencia.toString();
                    let diferenciaClass = diferencia >= 0 ? 'color:#4e73df;' : 'color:#e74a3b;';
                    
                    // Función helper para normalizar URLs de fotos
                    function getFotoUrlJS(foto) {
                        if (!foto || foto === '') {
                            return '{{ asset('images/jugador_img.png') }}';
                        }
                        if (foto.startsWith('http://') || foto.startsWith('https://')) {
                            return foto;
                        }
                        if (foto.startsWith('/')) {
                            return '{{ url('/') }}' + foto;
                        }
                        // Ruta relativa: usar asset() para construir la URL completa
                        return '{{ asset('') }}' + foto;
                    }
                    
                    let foto1 = p1 && p1.foto ? getFotoUrlJS(p1.foto) : '{{ asset('images/jugador_img.png') }}';
                    let foto2 = p2 && p2.foto ? getFotoUrlJS(p2.foto) : '{{ asset('images/jugador_img.png') }}';
                    
                    let row = '<tr>' +
                        '<td class="text-center"><span class="pos-rank pos-rank-' + (index + 1) + '">' + (index + 1) + '</span></td>' +
                        '<td>' +
                            '<div class="player-info">' +
                                '<div class="player-img-container">' +
                                    (p1 ? '<img src="' + foto1 + '" class="player-img" onerror="this.src=\'{{ asset('images/jugador_img.png') }}\'">' : '') +
                                    (p2 ? '<img src="' + foto2 + '" class="player-img player-img-overlap" onerror="this.src=\'{{ asset('images/jugador_img.png') }}\'">' : '') +
                                '</div>' +
                                '<div class="player-names">' +
                                    '<span class="player-name">' + (p1 ? ((p1.nombre || '') + ' ' + (p1.apellido || '')) : '') + '</span>' +
                                    (p2 ? '<span class="player-name">' + ((p2.nombre || '') + ' ' + (p2.apellido || '')) + '</span>' : '') +
                                '</div>' +
                            '</div>' +
                        '</td>' +
                                                        '<td class="text-center"><span style="font-size:2.5vh; font-weight:bold;">' + (pos.partidos_ganados || 0) + '</span></td>' +
                        '<td class="text-center">' +
                            '<span style="font-size:2.5vh; font-weight:bold; ' + diferenciaClass + '">' + diferenciaTexto + '</span>' +
                        '</td>' +
                    '</tr>';
                    
                    tbody.append(row);
                });
            }
            
            // Función para actualizar resultados de partidos
            function actualizarResultadosPartidos(partidosConResultados) {
                Object.keys(partidosPorZona).forEach(function(zona) {
                    partidosPorZona[zona].forEach(function(partido) {
                        let partidoId = partido.partido_id;
                        if (!partidoId || !partidosConResultados[partidoId]) return;
                        
                        let resultado = partidosConResultados[partidoId];
                        let row = $('tr[data-partido-id="' + partidoId + '"]');
                        if (row.length === 0) return;
                        
                        // Determinar scores
                        let gruposPartido = gruposExistentes.filter(function(g) { return g.partido_id == partidoId; }).sort(function(a, b) { return a.id - b.id; });
                        let s1 = '-', s2 = '-';
                        
                        if (gruposPartido.length >= 2) {
                            let grupo1 = gruposPartido[0];
                            if (grupo1.jugador_1 == partido.pareja_1.jugador_1 && grupo1.jugador_2 == partido.pareja_1.jugador_2) {
                                s1 = (resultado.pareja_1_set_1 !== null && resultado.pareja_1_set_1 !== undefined) ? resultado.pareja_1_set_1 : '-';
                                s2 = (resultado.pareja_2_set_1 !== null && resultado.pareja_2_set_1 !== undefined) ? resultado.pareja_2_set_1 : '-';
                            } else {
                                s1 = (resultado.pareja_2_set_1 !== null && resultado.pareja_2_set_1 !== undefined) ? resultado.pareja_2_set_1 : '-';
                                s2 = (resultado.pareja_1_set_1 !== null && resultado.pareja_1_set_1 !== undefined) ? resultado.pareja_1_set_1 : '-';
                            }
                        } else {
                            s1 = (resultado.pareja_1_set_1 !== null && resultado.pareja_1_set_1 !== undefined) ? resultado.pareja_1_set_1 : '-';
                            s2 = (resultado.pareja_2_set_1 !== null && resultado.pareja_2_set_1 !== undefined) ? resultado.pareja_2_set_1 : '-';
                        }
                        
                        // Actualizar scores
                        let scoreCells = row.find('.score-cell');
                        if (scoreCells.length >= 2) {
                            scoreCells.eq(0).text(s1).attr('data-score-p1', s1);
                            scoreCells.eq(1).text(s2).attr('data-score-p2', s2);
                            
                            // Actualizar clases de activo
                            scoreCells.removeClass('score-active');
                            if (s1 != '-' && s2 != '-' && !isNaN(s1) && !isNaN(s2)) {
                                if (parseInt(s1) > parseInt(s2)) {
                                    scoreCells.eq(0).addClass('score-active');
                                } else if (parseInt(s2) > parseInt(s1)) {
                                    scoreCells.eq(1).addClass('score-active');
                                }
                            }
                        }
                    });
                });
            }
            
            if (slides.length > 0) {
                showSlide(currentIndex);
                
                if (slides.length > 1) {
                    // Detectar si debe hacer solo un ciclo (cuando se usa desde tv_display)
                    const urlParams = new URLSearchParams(window.location.search);
                    const singleCycle = urlParams.has('single_cycle') || urlParams.has('intervalo_total');
                    let cycleCompleted = false;
                    
                    let rotationTimer = setInterval(function() {
                        const nextIndex = (currentIndex + 1) % slides.length;
                        
                        // Si ya completamos un ciclo y es single_cycle, detenerse
                        if (singleCycle && nextIndex === 0 && !cycleCompleted) {
                            cycleCompleted = true;
                            clearInterval(rotationTimer);
                            // Quedarse en el último slide
                            return;
                        }
                        
                        currentIndex = nextIndex;
                        showSlide(currentIndex);
                    }, slideInterval);
                } else {
                     // Single slide, just animate bar repeatedly to show "alive"
                     setInterval(function() {
                         showSlide(currentIndex);
                     }, slideInterval);
                }
            }
            
            // Primera actualización después de 3 segundos
            setTimeout(function() {
                actualizarTablas();
            }, 3000);
            
            // Nota: El polling inteligente (verificarVersionYActualizar) ya se ejecuta cada 2s
            // y llama a actualizarTablas() cuando detecta cambios
        });
    </script>
</body>
</html>