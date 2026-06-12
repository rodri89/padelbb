<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Bahia Padel - Sorteo TV</title>
    <!-- Minimal CSS, evitando conflictos con frameworks externos -->
    <style>
        * { box-sizing: border-box; }
        
        body { 
            overflow: hidden; 
            font-family: 'Nunito', sans-serif; 
            background-color: #1a1a1a;
            color: #e0e0e0;
            padding: 1vh 1vw;
            height: 100vh;
            width: 100vw;
            display: flex;
            flex-direction: column;
        }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .tv-header { 
            font-size: 4vh; 
            margin-bottom: 2vh; 
            text-align: center; 
            color: #fff; 
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 800;
            text-shadow: 0.2vh 0.2vh 0.4vh rgba(0,0,0,0.5);
            flex: 0 0 auto;
        }
        
        .grupos-container {
            display: flex;
            flex-wrap: wrap;
            gap: 2vw;
            width: 100%;
            height: 100%;
            justify-content: center;
            align-items: flex-start;
            overflow: hidden;
            flex: 1;
        }
        
        /* Modificación para que si son muchos, se ajusten mejor, o necesitaria JS para slide */
        
        .tv-card { 
            background-color: #252525; 
            border: 0.2vh solid #3d3d3d; 
            border-radius: 1.5vh; 
            margin-bottom: 0; 
            height: auto;
            max-height: 100%;
            overflow: hidden; 
            box-shadow: 0 1vh 2vh rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
            flex: 1 1 30vw; /* Base width */
            min-width: 30vw;
        }

        .tv-card-header {
            background-color: #1f1f1f;
            padding: 1.5vh;
            border-bottom: 0.2vh solid #3d3d3d;
            text-align: center;
            flex: 0 0 auto;
        }

        .tv-card-header h3 {
            margin: 0;
            color: #4e73df;
            font-weight: 700;
            font-size: 3vh;
        }
        
        .grupo-container {
            padding: 1.5vh;
            flex: 1;
            overflow-y: hidden; /* Hide scrollbar, maybe add marquee later */
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        
        .pareja-item {
            background-color: #2d2d2d;
            border: 0.1vh solid #3d3d3d;
            border-radius: 1vh;
            padding: 1vh;
            margin-bottom: 1vh;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            flex: 0 0 auto;
        }
        
        .pareja-item:hover {
            background-color: #353535;
            transform: translateX(0.5vw);
        }
        
        .pareja-item.animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        .player-img { 
            width: 5vh; 
            height: 5vh; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 0.2vh solid #4e73df;
            margin-right: 1vh;
        }
        
        .player-info {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-around;
        }
        
        .player-name {
            font-size: 2.5vh;
            font-weight: 600;
            color: #fff;
            margin: 0;
            white-space: nowrap;
        }
        
        .player-plus {
            font-size: 2vh;
            color: #4e73df;
            margin: 0 1vw;
            font-weight: bold;
        }
        
        .grupo-vacio {
            text-align: center;
            padding: 4vh;
            color: #888;
            font-size: 2vh;
        }
        
        .btn-navegar {
            position: fixed;
            top: 2vh;
            right: 2vw;
            background-color: #2d2d2d;
            color: #fff;
            border: 0.1vh solid #3d3d3d;
            border-radius: 0.5vh;
            padding: 1vh 1.5vw;
            font-size: 2vh;
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
    <!-- Mensaje para navegadores sin JavaScript -->
    <noscript>
        <style>
            .noscript-warning {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: #1a1a1a;
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 3vh;
                text-align: center;
                padding: 5vh;
            }
        </style>
        <div class="noscript-warning">
            JavaScript está deshabilitado. El navegador de esta TV no soporta la aplicación.<br>
            Intente usar otro dispositivo o navegador.
        </div>
    </noscript>
</head>
<body>
    <input type="hidden" id="torneo_id" value="{{ $torneo->id ?? 0 }}">
    
    <a href="{{ route('tvtorneoamericano') }}?torneo_id={{ $torneo->id ?? 0 }}" class="btn-navegar">></a>
    
    <div class="tv-header">
        {{ $torneo->nombre ?? 'Sorteo Torneo' }}
    </div>
    
    <div class="grupos-container" id="grupos-container">
        @php
            $zonas = array_keys($gruposPorZona ?? []);
            sort($zonas); // Ordenar zonas alfabéticamente
        @endphp
        
        @if(!empty($gruposPorZona) && count($zonas) > 0)
            @foreach($zonas as $zona)
                @php
                    $grupos = $gruposPorZona[$zona] ?? [];
                @endphp
                <div class="tv-card" data-zona="{{ $zona }}">
                    <div class="tv-card-header">
                        <h3>Grupo {{ $zona }}</h3>
                    </div>
                    <div class="grupo-container" id="grupo-container-{{ $zona }}">
                        @if(count($grupos) > 0)
                            @foreach($grupos as $grupo)
                                @php
                                    $jugador1 = $jugadores[$grupo->jugador_1] ?? null;
                                    $jugador2 = $jugadores[$grupo->jugador_2] ?? null;
                                @endphp
                                @if($jugador1 && $jugador2)
                                    <div class="pareja-item">
                                        <img src="{{ asset($jugador1->foto ?? 'images/jugador_img.png') }}" 
                                             alt="{{ $jugador1->nombre }} {{ $jugador1->apellido }}" 
                                             class="player-img">
                                        <div class="player-info">
                                            <div class="player-name">{{ $jugador1->nombre }} {{ $jugador1->apellido }}</div>
                                        </div>
                                        <span class="player-plus">+</span>
                                        <img src="{{ asset($jugador2->foto ?? 'images/jugador_img.png') }}" 
                                             alt="{{ $jugador2->nombre }} {{ $jugador2->apellido }}" 
                                             class="player-img">
                                        <div class="player-info">
                                            <div class="player-name">{{ $jugador2->nombre }} {{ $jugador2->apellido }}</div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <div class="grupo-vacio">
                                Esperando parejas...
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div class="tv-card">
                <div class="tv-card-header">
                    <h3>Sorteo</h3>
                </div>
                <div class="grupo-container">
                    <div class="grupo-vacio">
                        No hay grupos configurados aún
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Usar jQuery local en vez de CDN (las Smart TVs tienen problemas con CDNs externos) -->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script>
        // Verificar que jQuery se cargó correctamente
        if (typeof jQuery === 'undefined') {
            document.body.innerHTML = '<div style="color:red;padding:20px;font-size:24px;">Error: No se pudo cargar jQuery. Verifica la conexión.</div>';
        }
        
        $(document).ready(function() {
            const torneoId = {{ $torneo->id ?? 0 }};
            let jugadores = @json($jugadores ?? []);
            let intervaloActualizacion = null;
            let ultimaVersionConocida = {{ $torneo->version ?? 0 }};
            
            // Polling inteligente: verificar versión cada 2 segundos
            function verificarVersionYActualizar() {
                if (!torneoId) return;
                
                $.get('{{ route("tvtorneoversion") }}', { torneo_id: torneoId })
                    .done(function(response) {
                        const versionActual = response.version || 0;
                        
                        if (versionActual > ultimaVersionConocida) {
                            console.log('Versión cambió:', ultimaVersionConocida, '->', versionActual);
                            ultimaVersionConocida = versionActual;
                            
                            // Actualizar datos
                            actualizarGrupos();
                        }
                    })
                    .fail(function() {
                        // Silencioso, reintentar en el próximo intervalo
                    });
            }
            
            // Polling inteligente cada 2 segundos
            setInterval(verificarVersionYActualizar, 2000);
            
            // Actualizar grupos cuando detecta cambios
            function actualizarGrupos() {
                if (!torneoId) return;
                
                $.ajax({
                    type: 'POST',
                    url: '{{ route("tvtorneoamericanosorteoactualizar") }}',
                    data: {
                        torneo_id: torneoId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success && response.gruposPorZona) {
                            // Actualizar el objeto jugadores con la respuesta del servidor
                            if (response.jugadores) {
                                jugadores = Object.assign({}, jugadores, response.jugadores);
                            }
                            
                            const container = $('#grupos-container');
                            let html = '';
                            
                            // Ordenar zonas alfabéticamente
                            const zonas = Object.keys(response.gruposPorZona).sort();
                            
                            zonas.forEach(function(zona) {
                                const grupos = response.gruposPorZona[zona];
                                
                                html += `
                                    <div class="tv-card" data-zona="${zona}">
                                        <div class="tv-card-header">
                                            <h3>Grupo ${zona}</h3>
                                        </div>
                                        <div class="grupo-container" id="grupo-container-${zona}">
                                `;
                                
                                if (grupos.length > 0) {
                                    grupos.forEach(function(grupo) {
                                        const jugador1 = jugadores[grupo.jugador_1];
                                        const jugador2 = jugadores[grupo.jugador_2];
                                        
                                        if (jugador1 && jugador2) {
                                            const foto1 = jugador1.foto ? (jugador1.foto.startsWith('/') ? jugador1.foto : '/' + jugador1.foto) : '/images/jugador_img.png';
                                            const foto2 = jugador2.foto ? (jugador2.foto.startsWith('/') ? jugador2.foto : '/' + jugador2.foto) : '/images/jugador_img.png';
                                            
                                            html += `
                                                <div class="pareja-item animate-fade-in">
                                                    <img src="${foto1}" 
                                                         alt="${jugador1.nombre} ${jugador1.apellido}" 
                                                         class="player-img">
                                                    <div class="player-info">
                                                        <div class="player-name">${jugador1.nombre} ${jugador1.apellido}</div>
                                                    </div>
                                                    <span class="player-plus">+</span>
                                                    <img src="${foto2}" 
                                                         alt="${jugador2.nombre} ${jugador2.apellido}" 
                                                         class="player-img">
                                                    <div class="player-info">
                                                        <div class="player-name">${jugador2.nombre} ${jugador2.apellido}</div>
                                                    </div>
                                                </div>
                                            `;
                                        }
                                    });
                                } else {
                                    html += '<div class="grupo-vacio">Esperando parejas...</div>';
                                }
                                
                                html += `
                                        </div>
                                    </div>
                                `;
                            });
                            
                            if (zonas.length === 0) {
                                html = `
                                    <div class="tv-card">
                                        <div class="tv-card-header">
                                            <h3>Sorteo</h3>
                                        </div>
                                        <div class="grupo-container">
                                            <div class="grupo-vacio">No hay grupos configurados aún</div>
                                        </div>
                                    </div>
                                `;
                            }
                            
                            container.html(html);
                            
                            // NO detener la actualización automática - siempre seguir actualizando
                            // Esto permite que se reflejen cambios si se agregan más parejas o se modifican
                        }
                    },
                    error: function() {
                        // Silencioso, no mostrar error si falla
                    }
                });
            }
            
            // Primera actualización después de 3 segundos
            setTimeout(actualizarGrupos, 3000);
            
            // Nota: El polling inteligente (verificarVersionYActualizar) ya se ejecuta cada 2s
            // y llama a actualizarGrupos() cuando detecta cambios
        });
    </script>
</body>
</html>