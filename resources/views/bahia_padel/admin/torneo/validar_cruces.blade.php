@extends('bahia_padel/admin/plantilla')

@section('title_header','Validar Cruces del Torneo')

@section('contenedor')

<div class="container body_admin">
    <div class="row justify-content-center mb-4">
        <div class="col-12">
            <div class="card shadow bg-white px-5 py-3" style="border-radius: 12px; border: 1px solid #e3e6f0;">
                <div class="d-flex flex-column align-items-start flex-grow-1">
                    <div class="categoria display-4 mb-2" style="font-size:2.2rem; font-weight:700; color:#4e73df;">
                        {{ $torneo->categoria ?? '-' }}º Categoría <small>- ({{ $torneo->tipo}})</small>
                    </div>                    
                    <div class="fechas" style="font-size:1.2rem; color:#555;">
                    Fecha: {{ isset($torneo->fecha_inicio, $torneo->fecha_fin) ? (date('d', strtotime($torneo->fecha_inicio)).' '.__(strtolower(date('F', strtotime($torneo->fecha_inicio)))).' - '.date('d', strtotime($torneo->fecha_fin)).' '.__(strtolower(date('F', strtotime($torneo->fecha_fin)))) ) : '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $jugadoresMap = [];
        foreach($jugadores as $j) {
            $jugadoresMap[$j->id] = $j;
        }
        $zonasArray = array_keys($posicionesPorZona);
        sort($zonasArray);
        
        // Preparar datos de posiciones para JavaScript
        $posicionesJS = [];
        foreach ($posicionesPorZona as $zona => $posiciones) {
            foreach ($posiciones as $index => $pareja) {
                $posicionesJS[$zona][$index + 1] = [
                    'jugador_1' => $pareja['jugador_1'],
                    'jugador_2' => $pareja['jugador_2'],
                    'zona' => $zona,
                    'posicion' => $index + 1
                ];
            }
        }
    @endphp

    <!-- Contenedor con tablas de posiciones a la izquierda y tabla de cruces a la derecha -->
    <div class="row justify-content-center">
        <!-- Tablas de posiciones (scroll horizontal) -->
        <div class="col-lg-8">
            <div style="overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch;">
                <div style="display: flex; flex-direction: row; min-width: max-content; padding-bottom: 20px;">
                    @foreach($zonasArray as $zona)
                    <div style="min-width: 500px; margin-right: 30px; flex-shrink: 0;">
                        <div class="card shadow bg-white px-4 py-3" style="border-radius: 12px; border: 1px solid #e3e6f0; height: 100%;">
                            <h3 class="text-center mb-4" style="color:#4e73df; font-weight:700;">Zona {{ $zona }}</h3>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" style="font-size: 0.9rem;">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 50px; text-align: center;">Pos</th>
                                            <th style="width: 100px; text-align: center;">Pareja</th>
                                            <th style="text-align: center;">Nombre Pareja</th>
                                            <th style="width: 80px; text-align: center;">PG</th>
                                            <th style="width: 80px; text-align: center;">GG</th>
                                            <th style="width: 80px; text-align: center;">GP</th>
                                            <th style="width: 80px; text-align: center;">Dif</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($posicionesPorZona[$zona] as $index => $pareja)
                                        @php
                                            $jugador1 = $jugadoresMap[$pareja['jugador_1']] ?? null;
                                            $jugador2 = $jugadoresMap[$pareja['jugador_2']] ?? null;
                                            $diferenciaGames = $pareja['juegos_ganados'] - $pareja['juegos_perdidos'];
                                        @endphp
                                        <tr>
                                            <td style="text-align: center; font-weight: 700; font-size: 1.1rem;">{{ $index + 1 }}º</td>
                                            <td style="text-align: center;">
                                                <div class="d-flex justify-content-center align-items-center">
                                                    @if($jugador1)
                                                    <img src="{{ asset($jugador1->foto ?? 'images/jugador_img.png') }}" 
                                                        class="rounded-circle mr-1" 
                                                        style="width:40px; height:40px; object-fit:cover; border: 2px solid #4e73df;">
                                                    @endif
                                                    @if($jugador2)
                                                    <img src="{{ asset($jugador2->foto ?? 'images/jugador_img.png') }}" 
                                                        class="rounded-circle ml-1" 
                                                        style="width:40px; height:40px; object-fit:cover; border: 2px solid #4e73df;">
                                                    @endif
                                                </div>
                                            </td>
                                            <td style="text-align: left; padding-left: 10px;">
                                                @if($jugador1 && $jugador2)
                                                <div style="font-size: 0.85rem;">
                                                    {{ $jugador1->nombre ?? '' }} {{ $jugador1->apellido ?? '' }}<br>
                                                    {{ $jugador2->nombre ?? '' }} {{ $jugador2->apellido ?? '' }}
                                                </div>
                                                @else
                                                <div style="font-size: 0.85rem; color: #999;">-</div>
                                                @endif
                                            </td>
                                            <td style="text-align: center; font-weight: 600;">{{ $pareja['partidos_ganados'] ?? 0 }}</td>
                                            <td style="text-align: center; font-weight: 600; color: #1a8917;">{{ $pareja['juegos_ganados'] ?? 0 }}</td>
                                            <td style="text-align: center; font-weight: 600; color: #dc3545;">{{ $pareja['juegos_perdidos'] ?? 0 }}</td>
                                            <td style="text-align: center; font-weight: 700; font-size: 1rem; 
                                                color: {{ $diferenciaGames >= 0 ? '#1a8917' : '#dc3545' }};">
                                                {{ $diferenciaGames >= 0 ? '+' : '' }}{{ $diferenciaGames }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <!-- Tabla de selección de cruces a la derecha -->
        <div class="col-lg-4">
            <div class="card shadow bg-white px-4 py-3" style="border-radius: 12px; border: 1px solid #e3e6f0;">
                <h3 class="text-center mb-4" style="color:#4e73df; font-weight:700;">Armar Cruces</h3>
                
                <!-- Tabla de selección 4x2 -->
                <div class="table-responsive">
                    <table class="table table-bordered" style="font-size: 0.9rem;">
                        <thead class="thead-light">
                            <tr>
                                <th style="text-align: center; width: 50%;">Pareja 1</th>
                                <th style="text-align: center; width: 50%;">Pareja 2</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="text-align: center; padding: 8px;">
                                    <select class="form-control form-control-sm select-pareja-cruce" data-fila="1" data-columna="1">
                                        <option value="">Seleccionar...</option>
                                    </select>
                                </td>
                                <td style="text-align: center; padding: 8px;">
                                    <select class="form-control form-control-sm select-pareja-cruce" data-fila="1" data-columna="2">
                                        <option value="">Seleccionar...</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center; padding: 8px;">
                                    <select class="form-control form-control-sm select-pareja-cruce" data-fila="2" data-columna="1">
                                        <option value="">Seleccionar...</option>
                                    </select>
                                </td>
                                <td style="text-align: center; padding: 8px;">
                                    <select class="form-control form-control-sm select-pareja-cruce" data-fila="2" data-columna="2">
                                        <option value="">Seleccionar...</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center; padding: 8px;">
                                    <select class="form-control form-control-sm select-pareja-cruce" data-fila="3" data-columna="1">
                                        <option value="">Seleccionar...</option>
                                    </select>
                                </td>
                                <td style="text-align: center; padding: 8px;">
                                    <select class="form-control form-control-sm select-pareja-cruce" data-fila="3" data-columna="2">
                                        <option value="">Seleccionar...</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center; padding: 8px;">
                                    <select class="form-control form-control-sm select-pareja-cruce" data-fila="4" data-columna="1">
                                        <option value="">Seleccionar...</option>
                                    </select>
                                </td>
                                <td style="text-align: center; padding: 8px;">
                                    <select class="form-control form-control-sm select-pareja-cruce" data-fila="4" data-columna="2">
                                        <option value="">Seleccionar...</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-primary btn-lg" id="btn-armar-cruces">
                        Armar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Cruces Propuestos -->
    @if(count($crucesPropuestos) > 0)
    <div class="row justify-content-center mt-5">
        <div class="col-12">
            <div class="card shadow bg-white px-5 py-4" style="border-radius: 12px; border: 1px solid #e3e6f0;">
                <h3 class="text-center mb-4" style="color:#4e73df; font-weight:700;">Cruces Propuestos (Borrador)</h3>
                <p class="text-center text-muted mb-4">Puedes editar los cruces antes de confirmarlos</p>
                
                @php
                    $crucesCuartos = array_filter($crucesPropuestos, function($c) { return isset($c['ronda']) && $c['ronda'] === 'cuartos'; });
                @endphp
                
                @if(count($crucesCuartos) > 0)
                <h4 class="mb-3" style="color:#4e73df; font-weight:600;">Cuartos:</h4>
                @endif
                
                <div id="cruces-container">
                    @foreach($crucesPropuestos as $index => $cruce)
                    @php
                        $jugador1_p1 = $jugadoresMap[$cruce['pareja_1']['jugador_1']] ?? null;
                        $jugador2_p1 = $jugadoresMap[$cruce['pareja_1']['jugador_2']] ?? null;
                        $jugador1_p2 = $jugadoresMap[$cruce['pareja_2']['jugador_1']] ?? null;
                        $jugador2_p2 = $jugadoresMap[$cruce['pareja_2']['jugador_2']] ?? null;
                    @endphp
                    <div class="card border mb-3 cruce-item" data-cruce-id="{{ $cruce['id'] }}" style="border-radius: 8px;">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <!-- Pareja 1 -->
                                <div class="col-md-5">
                                    <div class="d-flex align-items-center">
                                        <div class="text-center mr-3">
                                            @if($jugador1_p1)
                                            <img src="{{ asset($jugador1_p1->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle mb-1" 
                                                style="width:50px; height:50px; object-fit:cover; border: 2px solid #4e73df;">
                                            @endif
                                            @if($jugador2_p1)
                                            <img src="{{ asset($jugador2_p1->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle" 
                                                style="width:50px; height:50px; object-fit:cover; border: 2px solid #4e73df;">
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="font-weight-bold" style="color:#4e73df; font-size: 1.1rem;">
                                                {{ $cruce['pareja_1']['posicion'] }}{{ $cruce['pareja_1']['zona'] }}
                                            </div>
                                            <div class="small text-muted mt-1">
                                                @if($jugador1_p1 && $jugador2_p1)
                                                {{ $jugador1_p1->nombre ?? '' }} {{ $jugador1_p1->apellido ?? '' }} / 
                                                {{ $jugador2_p1->nombre ?? '' }} {{ $jugador2_p1->apellido ?? '' }}
                                                @else
                                                Pareja 1
                                                @endif
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2 editar-pareja" 
                                                data-cruce-id="{{ $cruce['id'] }}" 
                                                data-pareja="1"
                                                data-zona="{{ $cruce['pareja_1']['zona'] }}"
                                                data-posicion="{{ $cruce['pareja_1']['posicion'] }}">
                                                Editar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- VS -->
                                <div class="col-md-2 text-center">
                                    <h4 style="color:#dc3545; font-weight:bold; font-size: 1.2rem;">-</h4>
                                </div>
                                
                                <!-- Pareja 2 -->
                                <div class="col-md-5">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1 text-right mr-3">
                                            <div class="font-weight-bold" style="color:#1a8917; font-size: 1.1rem;">
                                                {{ $cruce['pareja_2']['posicion'] }}{{ $cruce['pareja_2']['zona'] }}
                                            </div>
                                            <div class="small text-muted mt-1">
                                                @if($jugador1_p2 && $jugador2_p2)
                                                {{ $jugador1_p2->nombre ?? '' }} {{ $jugador1_p2->apellido ?? '' }} / 
                                                {{ $jugador2_p2->nombre ?? '' }} {{ $jugador2_p2->apellido ?? '' }}
                                                @else
                                                Pareja 2
                                                @endif
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-success mt-2 editar-pareja" 
                                                data-cruce-id="{{ $cruce['id'] }}" 
                                                data-pareja="2"
                                                data-zona="{{ $cruce['pareja_2']['zona'] }}"
                                                data-posicion="{{ $cruce['pareja_2']['posicion'] }}">
                                                Editar
                                            </button>
                                        </div>
                                        <div class="text-center">
                                            @if($jugador1_p2)
                                            <img src="{{ asset($jugador1_p2->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle mb-1" 
                                                style="width:50px; height:50px; object-fit:cover; border: 2px solid #1a8917;">
                                            @endif
                                            @if($jugador2_p2)
                                            <img src="{{ asset($jugador2_p2->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle" 
                                                style="width:50px; height:50px; object-fit:cover; border: 2px solid #1a8917;">
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-success btn-lg" id="btn-confirmar-cruces">
                        Confirmar Cruces
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row justify-content-center mt-4 mb-4">
        <div class="col-md-8 text-center">
            <a href="{{ route('admintorneoresultados') }}?torneo_id={{ $torneo->id }}" class="btn btn-secondary btn-lg mr-3">
                Volver a Resultados
            </a>
            <a href="/admin_torneos" class="btn btn-secondary btn-lg">
                Volver a Torneos
            </a>
        </div>
    </div>
</div>

<style>
    /* Estilos para el scroll horizontal */
    .container {
        max-width: 100%;
    }
    
    /* Scrollbar personalizado */
    div[style*="overflow-x: auto"]::-webkit-scrollbar {
        height: 12px;
    }
    
    div[style*="overflow-x: auto"]::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    div[style*="overflow-x: auto"]::-webkit-scrollbar-thumb {
        background: #4e73df;
        border-radius: 10px;
    }
    
    div[style*="overflow-x: auto"]::-webkit-scrollbar-thumb:hover {
        background: #375a7f;
    }
</style>

<script>
$(document).ready(function() {
    var posiciones = @json($posicionesJS);
    var cruces = @json($crucesPropuestos ?? []);
    var jugadores = @json($jugadores);
    var jugadoresMap = {};
    var crucesGenerados = [];
    
    // Crear mapa de jugadores
    jugadores.forEach(function(j) {
        jugadoresMap[j.id] = j;
    });
    
    // Poblar los selectores con opciones dinámicas
    function poblarSelectoresParejas() {
        var opcionesHTML = '<option value="">Seleccionar...</option>';
        
        // Recorrer todas las zonas y posiciones
        for (var zona in posiciones) {
            for (var pos in posiciones[zona]) {
                var pareja = posiciones[zona][pos];
                var jugador1 = jugadoresMap[pareja.jugador_1] || {};
                var jugador2 = jugadoresMap[pareja.jugador_2] || {};
                var nombrePareja = (jugador1.nombre || '') + ' ' + (jugador1.apellido || '') + ' / ' + 
                                   (jugador2.nombre || '') + ' ' + (jugador2.apellido || '');
                var valor = zona + '_' + pos;
                var texto = pos + zona + ' - ' + nombrePareja;
                opcionesHTML += '<option value="' + valor + '">' + texto + '</option>';
            }
        }
        
        // Aplicar a todos los selectores
        $('.select-pareja-cruce').html(opcionesHTML);
    }
    
    // Llamar a poblar selectores al cargar
    poblarSelectoresParejas();
    
    // Función para generar cruces desde la tabla
    function generarCrucesDesdeTabla() {
        crucesGenerados = [];
        var crucesTemp = [];
        
        // Leer las selecciones de la tabla 4x2 (4 filas, 2 columnas)
        // Cada fila es un cruce: pareja1 (columna 1) vs pareja2 (columna 2)
        
        for (var fila = 1; fila <= 4; fila++) {
            var pareja1Select = $('.select-pareja-cruce[data-fila="' + fila + '"][data-columna="1"]');
            var pareja2Select = $('.select-pareja-cruce[data-fila="' + fila + '"][data-columna="2"]');
            
            var valor1 = pareja1Select.val();
            var valor2 = pareja2Select.val();
            
            if (valor1 && valor2) {
                var partes1 = valor1.split('_');
                var partes2 = valor2.split('_');
                var zona1 = partes1[0];
                var pos1 = parseInt(partes1[1]);
                var zona2 = partes2[0];
                var pos2 = parseInt(partes2[1]);
                
                var pareja1Data = posiciones[zona1][pos1];
                var pareja2Data = posiciones[zona2][pos2];
                
                crucesTemp.push({
                    id: 'cruce_manual_' + fila,
                    ronda: 'cuartos',
                    pareja_1: {
                        jugador_1: pareja1Data.jugador_1,
                        jugador_2: pareja1Data.jugador_2,
                        zona: zona1,
                        posicion: pos1
                    },
                    pareja_2: {
                        jugador_1: pareja2Data.jugador_1,
                        jugador_2: pareja2Data.jugador_2,
                        zona: zona2,
                        posicion: pos2
                    }
                });
            }
        }
        
        crucesGenerados = crucesTemp;
        return crucesTemp;
    }
    
    // Función para renderizar cruces generados
    function renderizarCrucesGenerados(crucesData) {
        var container = $('#cruces-container');
        container.empty();
        
        if (!crucesData || crucesData.length === 0) {
            container.html('<p class="text-center text-muted">No hay cruces para mostrar. Selecciona parejas en la tabla superior.</p>');
            return;
        }
        
        // Mostrar el título "Cuartos:" si hay cruces
        if (crucesData.length > 0) {
            var cardContainer = container.closest('.card');
            var tituloCuartos = cardContainer.find('h4');
            if (tituloCuartos.length === 0) {
                container.before('<h4 class="mb-3" style="color:#4e73df; font-weight:600;">Cuartos:</h4>');
            }
        }
        
        crucesData.forEach(function(cruce) {
            var jugador1_p1 = jugadoresMap[cruce.pareja_1.jugador_1] || null;
            var jugador2_p1 = jugadoresMap[cruce.pareja_1.jugador_2] || null;
            var jugador1_p2 = jugadoresMap[cruce.pareja_2.jugador_1] || null;
            var jugador2_p2 = jugadoresMap[cruce.pareja_2.jugador_2] || null;
            
            var nombreP1 = '';
            var nombreP2 = '';
            
            if (jugador1_p1 && jugador2_p1) {
                nombreP1 = (jugador1_p1.nombre || '') + ' ' + (jugador1_p1.apellido || '') + ' / ' + 
                          (jugador2_p1.nombre || '') + ' ' + (jugador2_p1.apellido || '');
            }
            
            if (jugador1_p2 && jugador2_p2) {
                nombreP2 = (jugador1_p2.nombre || '') + ' ' + (jugador1_p2.apellido || '') + ' / ' + 
                          (jugador2_p2.nombre || '') + ' ' + (jugador2_p2.apellido || '');
            }
            
            var foto1_p1 = jugador1_p1 && jugador1_p1.foto ? '{{ url("/") }}/' + jugador1_p1.foto : '{{ url("/") }}/images/jugador_img.png';
            var foto2_p1 = jugador2_p1 && jugador2_p1.foto ? '{{ url("/") }}/' + jugador2_p1.foto : '{{ url("/") }}/images/jugador_img.png';
            var foto1_p2 = jugador1_p2 && jugador1_p2.foto ? '{{ url("/") }}/' + jugador1_p2.foto : '{{ url("/") }}/images/jugador_img.png';
            var foto2_p2 = jugador2_p2 && jugador2_p2.foto ? '{{ url("/") }}/' + jugador2_p2.foto : '{{ url("/") }}/images/jugador_img.png';
            
            var posicion1 = cruce.pareja_1.posicion + cruce.pareja_1.zona;
            var posicion2 = cruce.pareja_2.posicion + cruce.pareja_2.zona;
            
            var cruceHTML = `
                <div class="card border mb-3 cruce-item" data-cruce-id="${cruce.id}" style="border-radius: 8px;">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <!-- Pareja 1 -->
                            <div class="col-md-5">
                                <div class="d-flex align-items-center">
                                    <div class="text-center mr-3">
                                        ${jugador1_p1 ? '<img src="' + foto1_p1 + '" class="rounded-circle mb-1" style="width:50px; height:50px; object-fit:cover; border: 2px solid #4e73df;">' : ''}
                                        ${jugador2_p1 ? '<img src="' + foto2_p1 + '" class="rounded-circle" style="width:50px; height:50px; object-fit:cover; border: 2px solid #4e73df;">' : ''}
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="font-weight-bold" style="color:#4e73df; font-size: 1.1rem;">
                                            ${posicion1}
                                        </div>
                                        <div class="small text-muted mt-1">
                                            ${nombreP1 || 'Pareja 1'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- VS -->
                            <div class="col-md-2 text-center">
                                <h4 style="color:#dc3545; font-weight:bold; font-size: 1.2rem;">VS</h4>
                            </div>
                            
                            <!-- Pareja 2 -->
                            <div class="col-md-5">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 text-right mr-3">
                                        <div class="font-weight-bold" style="color:#1a8917; font-size: 1.1rem;">
                                            ${posicion2}
                                        </div>
                                        <div class="small text-muted mt-1">
                                            ${nombreP2 || 'Pareja 2'}
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        ${jugador1_p2 ? '<img src="' + foto1_p2 + '" class="rounded-circle mb-1" style="width:50px; height:50px; object-fit:cover; border: 2px solid #1a8917;">' : ''}
                                        ${jugador2_p2 ? '<img src="' + foto2_p2 + '" class="rounded-circle" style="width:50px; height:50px; object-fit:cover; border: 2px solid #1a8917;">' : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.append(cruceHTML);
        });
        
        // Mostrar el contenedor
        // Actualizar el array de cruces para que funcione con el botón de confirmar existente
        cruces = crucesData;
    }
    
    // Botón Generar Cruces
    $('#btn-armar-cruces').on('click', function() {
        var cruces = generarCrucesDesdeTabla();
        if (cruces.length === 0) {
            alert('Por favor, selecciona al menos un cruce completo (pareja 1 y pareja 2 en la misma fila)');
            return;
        }
        renderizarCrucesGenerados(cruces);
    });
    
    // Botón Confirmar Cruces Generados
    $('#btn-confirmar-cruces-generados').on('click', function() {
        if (crucesGenerados.length === 0) {
            alert('No hay cruces para confirmar');
            return;
        }
        
        if (confirm('¿Estás seguro de confirmar estos cruces? Una vez confirmados, se crearán los partidos eliminatorios.')) {
            var torneoId = '{{ $torneo->id }}';
            var btn = $(this);
            btn.prop('disabled', true).text('Guardando...');
            
            $.ajax({
                type: 'POST',
                dataType: 'JSON',
                url: '{{ route("confirmarcruces") }}',
                data: {
                    torneo_id: torneoId,
                    cruces: JSON.stringify(crucesGenerados),
                    _token: '{{csrf_token()}}'
                },
                success: function(response) {
                    if (response.success) {
                        // Redirigir a la pantalla de cruces puntuable
                        window.location.href = '{{ route("admintorneopuntuablecruces") }}?torneo_id=' + torneoId;
                    } else {
                        alert('Error al confirmar los cruces: ' + (response.message || 'Error desconocido'));
                        btn.prop('disabled', false).text('Confirmar Cruces');
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'Error al confirmar los cruces';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ': ' + xhr.responseJSON.message;
                    }
                    alert(errorMsg);
                    btn.prop('disabled', false).text('Confirmar Cruces');
                }
            });
        }
    });
    
    // Función para transformar grupos individuales en cruces
    function transformarGruposEnCruces(gruposJSON) {
        if (!gruposJSON || !Array.isArray(gruposJSON)) {
            return [];
        }
        
        // Agrupar grupos por partido_id
        var gruposPorPartido = {};
        gruposJSON.forEach(function(grupo) {
            var partidoId = grupo.partido_id;
            if (!partidoId || partidoId === 0) {
                return; // Saltar grupos sin partido_id válido
            }
            
            if (!gruposPorPartido[partidoId]) {
                gruposPorPartido[partidoId] = [];
            }
            gruposPorPartido[partidoId].push(grupo);
        });
        
        // Crear cruces desde los grupos agrupados
        var crucesTransformados = [];
        var cruceIndex = 1;
        
        Object.keys(gruposPorPartido).forEach(function(partidoId) {
            var grupos = gruposPorPartido[partidoId];
            
            // Solo procesar si hay al menos 2 grupos (pareja 1 y pareja 2)
            if (grupos.length >= 2) {
                var grupo1 = grupos[0];
                var grupo2 = grupos[1];
                
                // Verificar que ambos grupos tengan jugadores válidos
                if (grupo1.jugador_1 && grupo1.jugador_2 && grupo2.jugador_1 && grupo2.jugador_2) {
                    var cruce = {
                        id: 'cruce_' + cruceIndex++,
                        partido_id: partidoId,
                        ronda: 'cuartos', // Por defecto, se puede determinar según la zona
                        pareja_1: {
                            jugador_1: grupo1.jugador_1,
                            jugador_2: grupo1.jugador_2,
                            zona: null,
                            posicion: null
                        },
                        pareja_2: {
                            jugador_1: grupo2.jugador_1,
                            jugador_2: grupo2.jugador_2,
                            zona: null,
                            posicion: null
                        },
                        fecha: grupo1.fecha || grupo2.fecha,
                        horario: grupo1.horario || grupo2.horario
                    };
                    
                    crucesTransformados.push(cruce);
                }
            }
        });
        
        return crucesTransformados;
    }
    
    // Función para renderizar cruces en la tabla
    function renderizarCruces(crucesData) {
        var container = $('#cruces-container');
        container.empty();
        
        if (!crucesData || crucesData.length === 0) {
            container.html('<p class="text-center text-muted">No hay cruces para mostrar</p>');
            return;
        }
        
        crucesData.forEach(function(cruce) {
            var jugador1_p1 = jugadoresMap[cruce.pareja_1.jugador_1] || null;
            var jugador2_p1 = jugadoresMap[cruce.pareja_1.jugador_2] || null;
            var jugador1_p2 = jugadoresMap[cruce.pareja_2.jugador_1] || null;
            var jugador2_p2 = jugadoresMap[cruce.pareja_2.jugador_2] || null;
            
            var nombreP1 = '';
            var nombreP2 = '';
            
            if (jugador1_p1 && jugador2_p1) {
                nombreP1 = (jugador1_p1.nombre || '') + ' ' + (jugador1_p1.apellido || '') + ' / ' + 
                          (jugador2_p1.nombre || '') + ' ' + (jugador2_p1.apellido || '');
            }
            
            if (jugador1_p2 && jugador2_p2) {
                nombreP2 = (jugador1_p2.nombre || '') + ' ' + (jugador1_p2.apellido || '') + ' / ' + 
                          (jugador2_p2.nombre || '') + ' ' + (jugador2_p2.apellido || '');
            }
            
            var foto1_p1 = jugador1_p1 && jugador1_p1.foto ? '{{ url("/") }}/' + jugador1_p1.foto : '{{ url("/") }}/images/jugador_img.png';
            var foto2_p1 = jugador2_p1 && jugador2_p1.foto ? '{{ url("/") }}/' + jugador2_p1.foto : '{{ url("/") }}/images/jugador_img.png';
            var foto1_p2 = jugador1_p2 && jugador1_p2.foto ? '{{ url("/") }}/' + jugador1_p2.foto : '{{ url("/") }}/images/jugador_img.png';
            var foto2_p2 = jugador2_p2 && jugador2_p2.foto ? '{{ url("/") }}/' + jugador2_p2.foto : '{{ url("/") }}/images/jugador_img.png';
            
            var posicion1 = cruce.pareja_1.posicion ? cruce.pareja_1.posicion + cruce.pareja_1.zona : 'Pareja 1';
            var posicion2 = cruce.pareja_2.posicion ? cruce.pareja_2.posicion + cruce.pareja_2.zona : 'Pareja 2';
            
            var cruceHTML = `
                <div class="card border mb-3 cruce-item" data-cruce-id="${cruce.id}" style="border-radius: 8px;">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <!-- Pareja 1 -->
                            <div class="col-md-5">
                                <div class="d-flex align-items-center">
                                    <div class="text-center mr-3">
                                        ${jugador1_p1 ? '<img src="' + foto1_p1 + '" class="rounded-circle mb-1" style="width:50px; height:50px; object-fit:cover; border: 2px solid #4e73df;">' : ''}
                                        ${jugador2_p1 ? '<img src="' + foto2_p1 + '" class="rounded-circle" style="width:50px; height:50px; object-fit:cover; border: 2px solid #4e73df;">' : ''}
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="font-weight-bold" style="color:#4e73df; font-size: 1.1rem;">
                                            ${posicion1}
                                        </div>
                                        <div class="small text-muted mt-1">
                                            ${nombreP1 || 'Pareja 1'}
                                        </div>
                                        ${cruce.pareja_1.zona ? '<button type="button" class="btn btn-sm btn-outline-primary mt-2 editar-pareja" data-cruce-id="' + cruce.id + '" data-pareja="1" data-zona="' + cruce.pareja_1.zona + '" data-posicion="' + cruce.pareja_1.posicion + '">Editar</button>' : ''}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- VS -->
                            <div class="col-md-2 text-center">
                                <h4 style="color:#dc3545; font-weight:bold; font-size: 1.2rem;">-</h4>
                            </div>
                            
                            <!-- Pareja 2 -->
                            <div class="col-md-5">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 text-right mr-3">
                                        <div class="font-weight-bold" style="color:#1a8917; font-size: 1.1rem;">
                                            ${posicion2}
                                        </div>
                                        <div class="small text-muted mt-1">
                                            ${nombreP2 || 'Pareja 2'}
                                        </div>
                                        ${cruce.pareja_2.zona ? '<button type="button" class="btn btn-sm btn-outline-success mt-2 editar-pareja" data-cruce-id="' + cruce.id + '" data-pareja="2" data-zona="' + cruce.pareja_2.zona + '" data-posicion="' + cruce.pareja_2.posicion + '">Editar</button>' : ''}
                                    </div>
                                    <div class="text-center">
                                        ${jugador1_p2 ? '<img src="' + foto1_p2 + '" class="rounded-circle mb-1" style="width:50px; height:50px; object-fit:cover; border: 2px solid #1a8917;">' : ''}
                                        ${jugador2_p2 ? '<img src="' + foto2_p2 + '" class="rounded-circle" style="width:50px; height:50px; object-fit:cover; border: 2px solid #1a8917;">' : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.append(cruceHTML);
        });
    }
    
    // Si se recibe JSON de grupos, transformarlo y renderizar
    // Esta función puede ser llamada desde fuera si se recibe JSON vía AJAX
    window.procesarGruposJSON = function(gruposJSON) {
        var crucesTransformados = transformarGruposEnCruces(gruposJSON);
        cruces = crucesTransformados;
        renderizarCruces(crucesTransformados);
    };
    
    // Modal para editar pareja
    function mostrarModalEditarPareja(cruceId, parejaNum, zonaActual, posicionActual) {
        // Crear opciones de zonas y posiciones
        var opcionesHTML = '<option value="">Seleccionar...</option>';
        
        for (var zona in posiciones) {
            for (var pos in posiciones[zona]) {
                var pareja = posiciones[zona][pos];
                var jugador1 = jugadoresMap[pareja.jugador_1] || {};
                var jugador2 = jugadoresMap[pareja.jugador_2] || {};
                var nombrePareja = (jugador1.nombre || '') + ' ' + (jugador1.apellido || '') + ' / ' + 
                                   (jugador2.nombre || '') + ' ' + (jugador2.apellido || '');
                var selected = (zona === zonaActual && pos == posicionActual) ? 'selected' : '';
                opcionesHTML += '<option value="' + zona + '_' + pos + '" ' + selected + '>' + 
                               pos + 'º Zona ' + zona + ' - ' + nombrePareja + '</option>';
            }
        }
        
        var modalHTML = `
            <div class="modal fade" id="modalEditarPareja" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar Pareja ${parejaNum}</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Seleccionar Pareja:</label>
                                <select class="form-control" id="selectPareja">
                                    ${opcionesHTML}
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="btn-guardar-pareja">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remover modal anterior si existe
        $('#modalEditarPareja').remove();
        $('body').append(modalHTML);
        
        $('#modalEditarPareja').modal('show');
        
        // Guardar pareja
        $('#btn-guardar-pareja').off('click').on('click', function() {
            var seleccion = $('#selectPareja').val();
            if (seleccion) {
                var partes = seleccion.split('_');
                var nuevaZona = partes[0];
                var nuevaPosicion = parseInt(partes[1]);
                
                // Actualizar el cruce
                actualizarCruce(cruceId, parejaNum, nuevaZona, nuevaPosicion);
                $('#modalEditarPareja').modal('hide');
            }
        });
    }
    
    // Actualizar cruce en la vista
    function actualizarCruce(cruceId, parejaNum, nuevaZona, nuevaPosicion) {
        var pareja = posiciones[nuevaZona][nuevaPosicion];
        var jugador1 = jugadoresMap[pareja.jugador_1] || {};
        var jugador2 = jugadoresMap[pareja.jugador_2] || {};
        var nombrePareja = (jugador1.nombre || '') + ' ' + (jugador1.apellido || '') + ' / ' + 
                         (jugador2.nombre || '') + ' ' + (jugador2.apellido || '');
        
        var cruceItem = $('.cruce-item[data-cruce-id="' + cruceId + '"]');
        var parejaDiv = cruceItem.find('.col-md-5').eq(parejaNum - 1);
        
        // Actualizar formato: 1A, 2B, etc.
        parejaDiv.find('.font-weight-bold').text(nuevaPosicion + nuevaZona);
        
        // Actualizar nombre de la pareja
        parejaDiv.find('.small.text-muted').text(nombrePareja);
        
        // Actualizar imágenes
        var baseUrl = '{{ url("/") }}';
        var foto1 = jugador1.foto ? baseUrl + '/' + jugador1.foto : baseUrl + '/images/jugador_img.png';
        var foto2 = jugador2.foto ? baseUrl + '/' + jugador2.foto : baseUrl + '/images/jugador_img.png';
        
        if (parejaNum === 1) {
            parejaDiv.find('img').eq(0).attr('src', foto1);
            parejaDiv.find('img').eq(1).attr('src', foto2);
        } else {
            parejaDiv.find('img').eq(0).attr('src', foto1);
            parejaDiv.find('img').eq(1).attr('src', foto2);
        }
        
        // Actualizar atributos del botón
        parejaDiv.find('.editar-pareja')
            .attr('data-zona', nuevaZona)
            .attr('data-posicion', nuevaPosicion);
        
        // Actualizar en el array de cruces
        var cruceIndex = cruces.findIndex(function(c) { return c.id === cruceId; });
        if (cruceIndex !== -1) {
            cruces[cruceIndex]['pareja_' + parejaNum] = {
                jugador_1: pareja.jugador_1,
                jugador_2: pareja.jugador_2,
                zona: nuevaZona,
                posicion: nuevaPosicion
            };
        }
    }
    
    // Evento para editar pareja
    $(document).on('click', '.editar-pareja', function() {
        var cruceId = $(this).data('cruce-id');
        var parejaNum = $(this).data('pareja');
        var zonaActual = $(this).data('zona');
        var posicionActual = $(this).data('posicion');
        
        mostrarModalEditarPareja(cruceId, parejaNum, zonaActual, posicionActual);
    });
    
    // Confirmar cruces
    $('#btn-confirmar-cruces').on('click', function() {
        if (confirm('¿Estás seguro de confirmar estos cruces? Una vez confirmados, se crearán los partidos eliminatorios.')) {
            var torneoId = '{{ $torneo->id }}';
            var btn = $(this);
            btn.prop('disabled', true).text('Guardando...');
            
            $.ajax({
                type: 'POST',
                dataType: 'JSON',
                url: '{{ route("confirmarcruces") }}',
                data: {
                    torneo_id: torneoId,
                    cruces: JSON.stringify(cruces),
                    _token: '{{csrf_token()}}'
                },
                success: function(response) {
                    if (response.success) {
                        // Redirigir a la pantalla de cruces puntuable
                        window.location.href = '{{ route("admintorneopuntuablecruces") }}?torneo_id=' + torneoId;
                    } else {
                        alert('Error al confirmar los cruces: ' + (response.message || 'Error desconocido'));
                        btn.prop('disabled', false).text('Confirmar Cruces');
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'Error al confirmar los cruces';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ': ' + xhr.responseJSON.message;
                    }
                    alert(errorMsg);
                    btn.prop('disabled', false).text('Confirmar Cruces');
                }
            });
        }
    });
    
    // Ejemplo de uso: Si recibes JSON de grupos desde un endpoint AJAX, puedes procesarlo así:
    // 
    // $.ajax({
    //     url: 'tu_endpoint',
    //     success: function(data) {
    //         // data debe ser un array de grupos con formato:
    //         // [{id, jugador_1, jugador_2, fecha, horario, partido_id}, ...]
    //         window.procesarGruposJSON(data);
    //     }
    // });
    //
    // O directamente si ya tienes el JSON:
    // var gruposJSON = [{id: 1061, jugador_1: 1, jugador_2: 5, fecha: "2026-01-16", horario: "01:00", partido_id: 1159}, ...];
    // window.procesarGruposJSON(gruposJSON);
});
</script>
@else
<script>
$(document).ready(function() {
    var posiciones = @json($posicionesJS);
    var jugadores = @json($jugadores);
    var jugadoresMap = {};
    var crucesGenerados = [];
    
    // Crear mapa de jugadores
    jugadores.forEach(function(j) {
        jugadoresMap[j.id] = j;
    });
    
    // Poblar los selectores con opciones dinámicas
    function poblarSelectoresParejas() {
        var opcionesHTML = '<option value="">Seleccionar...</option>';
        
        // Recorrer todas las zonas y posiciones
        for (var zona in posiciones) {
            for (var pos in posiciones[zona]) {
                var pareja = posiciones[zona][pos];
                var jugador1 = jugadoresMap[pareja.jugador_1] || {};
                var jugador2 = jugadoresMap[pareja.jugador_2] || {};
                var nombrePareja = (jugador1.nombre || '') + ' ' + (jugador1.apellido || '') + ' / ' + 
                                   (jugador2.nombre || '') + ' ' + (jugador2.apellido || '');
                var valor = zona + '_' + pos;
                var texto = pos + zona + ' - ' + nombrePareja;
                opcionesHTML += '<option value="' + valor + '">' + texto + '</option>';
            }
        }
        
        // Aplicar a todos los selectores
        $('.select-pareja-cruce').html(opcionesHTML);
    }
    
    // Llamar a poblar selectores al cargar
    poblarSelectoresParejas();
    
    // Función para generar cruces desde la tabla
    function generarCrucesDesdeTabla() {
        crucesGenerados = [];
        var crucesTemp = [];
        
        // Leer las selecciones de la tabla 2x4
        var selecciones = [];
        $('.select-pareja-cruce').each(function() {
            var fila = $(this).data('fila');
            var columna = $(this).data('columna');
            var valor = $(this).val();
            selecciones.push({
                fila: fila,
                columna: columna,
                valor: valor
            });
        });
        
        // Formar cruces: pareja de fila 1 columna X vs pareja de fila 2 columna X
        for (var col = 1; col <= 4; col++) {
            var pareja1 = selecciones.find(function(s) { return s.fila == 1 && s.columna == col; });
            var pareja2 = selecciones.find(function(s) { return s.fila == 2 && s.columna == col; });
            
            if (pareja1 && pareja1.valor && pareja2 && pareja2.valor) {
                var partes1 = pareja1.valor.split('_');
                var partes2 = pareja2.valor.split('_');
                var zona1 = partes1[0];
                var pos1 = parseInt(partes1[1]);
                var zona2 = partes2[0];
                var pos2 = parseInt(partes2[1]);
                
                var pareja1Data = posiciones[zona1][pos1];
                var pareja2Data = posiciones[zona2][pos2];
                
                crucesTemp.push({
                    id: 'cruce_manual_' + col,
                    ronda: 'cuartos',
                    pareja_1: {
                        jugador_1: pareja1Data.jugador_1,
                        jugador_2: pareja1Data.jugador_2,
                        zona: zona1,
                        posicion: pos1
                    },
                    pareja_2: {
                        jugador_1: pareja2Data.jugador_1,
                        jugador_2: pareja2Data.jugador_2,
                        zona: zona2,
                        posicion: pos2
                    }
                });
            }
        }
        
        crucesGenerados = crucesTemp;
        return crucesTemp;
    }
    
    // Función para renderizar cruces generados
    function renderizarCrucesGenerados(crucesData) {
        var container = $('#cruces-container');
        container.empty();
        
        if (!crucesData || crucesData.length === 0) {
            container.html('<p class="text-center text-muted">No hay cruces para mostrar. Selecciona parejas en la tabla superior.</p>');
            return;
        }
        
        // Mostrar el título "Cuartos:" si hay cruces
        if (crucesData.length > 0) {
            var cardContainer = container.closest('.card');
            var tituloCuartos = cardContainer.find('h4');
            if (tituloCuartos.length === 0) {
                container.before('<h4 class="mb-3" style="color:#4e73df; font-weight:600;">Cuartos:</h4>');
            }
        }
        
        crucesData.forEach(function(cruce) {
            var jugador1_p1 = jugadoresMap[cruce.pareja_1.jugador_1] || null;
            var jugador2_p1 = jugadoresMap[cruce.pareja_1.jugador_2] || null;
            var jugador1_p2 = jugadoresMap[cruce.pareja_2.jugador_1] || null;
            var jugador2_p2 = jugadoresMap[cruce.pareja_2.jugador_2] || null;
            
            var nombreP1 = '';
            var nombreP2 = '';
            
            if (jugador1_p1 && jugador2_p1) {
                nombreP1 = (jugador1_p1.nombre || '') + ' ' + (jugador1_p1.apellido || '') + ' / ' + 
                          (jugador2_p1.nombre || '') + ' ' + (jugador2_p1.apellido || '');
            }
            
            if (jugador1_p2 && jugador2_p2) {
                nombreP2 = (jugador1_p2.nombre || '') + ' ' + (jugador1_p2.apellido || '') + ' / ' + 
                          (jugador2_p2.nombre || '') + ' ' + (jugador2_p2.apellido || '');
            }
            
            var foto1_p1 = jugador1_p1 && jugador1_p1.foto ? '{{ url("/") }}/' + jugador1_p1.foto : '{{ url("/") }}/images/jugador_img.png';
            var foto2_p1 = jugador2_p1 && jugador2_p1.foto ? '{{ url("/") }}/' + jugador2_p1.foto : '{{ url("/") }}/images/jugador_img.png';
            var foto1_p2 = jugador1_p2 && jugador1_p2.foto ? '{{ url("/") }}/' + jugador1_p2.foto : '{{ url("/") }}/images/jugador_img.png';
            var foto2_p2 = jugador2_p2 && jugador2_p2.foto ? '{{ url("/") }}/' + jugador2_p2.foto : '{{ url("/") }}/images/jugador_img.png';
            
            var posicion1 = cruce.pareja_1.posicion + cruce.pareja_1.zona;
            var posicion2 = cruce.pareja_2.posicion + cruce.pareja_2.zona;
            
            var cruceHTML = `
                <div class="card border mb-3 cruce-item" data-cruce-id="${cruce.id}" style="border-radius: 8px;">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <!-- Pareja 1 -->
                            <div class="col-md-5">
                                <div class="d-flex align-items-center">
                                    <div class="text-center mr-3">
                                        ${jugador1_p1 ? '<img src="' + foto1_p1 + '" class="rounded-circle mb-1" style="width:50px; height:50px; object-fit:cover; border: 2px solid #4e73df;">' : ''}
                                        ${jugador2_p1 ? '<img src="' + foto2_p1 + '" class="rounded-circle" style="width:50px; height:50px; object-fit:cover; border: 2px solid #4e73df;">' : ''}
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="font-weight-bold" style="color:#4e73df; font-size: 1.1rem;">
                                            ${posicion1}
                                        </div>
                                        <div class="small text-muted mt-1">
                                            ${nombreP1 || 'Pareja 1'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- VS -->
                            <div class="col-md-2 text-center">
                                <h4 style="color:#dc3545; font-weight:bold; font-size: 1.2rem;">VS</h4>
                            </div>
                            
                            <!-- Pareja 2 -->
                            <div class="col-md-5">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 text-right mr-3">
                                        <div class="font-weight-bold" style="color:#1a8917; font-size: 1.1rem;">
                                            ${posicion2}
                                        </div>
                                        <div class="small text-muted mt-1">
                                            ${nombreP2 || 'Pareja 2'}
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        ${jugador1_p2 ? '<img src="' + foto1_p2 + '" class="rounded-circle mb-1" style="width:50px; height:50px; object-fit:cover; border: 2px solid #1a8917;">' : ''}
                                        ${jugador2_p2 ? '<img src="' + foto2_p2 + '" class="rounded-circle" style="width:50px; height:50px; object-fit:cover; border: 2px solid #1a8917;">' : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.append(cruceHTML);
        });
        
        // Mostrar el contenedor
        // Actualizar el array de cruces para que funcione con el botón de confirmar existente
        cruces = crucesData;
    }
    
    // Botón Generar Cruces
    $('#btn-armar-cruces').on('click', function() {
        var cruces = generarCrucesDesdeTabla();
        if (cruces.length === 0) {
            alert('Por favor, selecciona al menos un cruce completo (pareja 1 y pareja 2 en la misma fila)');
            return;
        }
        renderizarCrucesGenerados(cruces);
    });
    
    // Botón Confirmar Cruces Generados
    $('#btn-confirmar-cruces-generados').on('click', function() {
        if (crucesGenerados.length === 0) {
            alert('No hay cruces para confirmar');
            return;
        }
        
        if (confirm('¿Estás seguro de confirmar estos cruces? Una vez confirmados, se crearán los partidos eliminatorios.')) {
            var torneoId = '{{ $torneo->id }}';
            var btn = $(this);
            btn.prop('disabled', true).text('Guardando...');
            
            $.ajax({
                type: 'POST',
                dataType: 'JSON',
                url: '{{ route("confirmarcruces") }}',
                data: {
                    torneo_id: torneoId,
                    cruces: JSON.stringify(crucesGenerados),
                    _token: '{{csrf_token()}}'
                },
                success: function(response) {
                    if (response.success) {
                        // Redirigir a la pantalla de cruces puntuable
                        window.location.href = '{{ route("admintorneopuntuablecruces") }}?torneo_id=' + torneoId;
                    } else {
                        alert('Error al confirmar los cruces: ' + (response.message || 'Error desconocido'));
                        btn.prop('disabled', false).text('Confirmar Cruces');
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'Error al confirmar los cruces';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ': ' + xhr.responseJSON.message;
                    }
                    alert(errorMsg);
                    btn.prop('disabled', false).text('Confirmar Cruces');
                }
            });
        }
    });
});
</script>
@endif

@endsection

