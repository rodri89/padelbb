@extends('bahia_padel/admin/plantilla')

@section('title_header','Armar Torneo Americano')

@section('contenedor')
<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .animate-fade-in {
        animation: fadeIn 0.5s ease-in;
    }
</style>
<div class="container-fluid body_admin">
    <div class="row">
        <div class="col-12">
            <h2 class="text-center mb-4">Armar Torneo Americano</h2>
            <input type="hidden" id="torneo_id" value="{{ $torneo->id ?? 0 }}">
            
            <!-- Sección 1: Seleccionar Parejas -->
            <div id="seccion-seleccion-parejas" class="card shadow bg-white p-4 mb-4">
                <h4 class="mb-3">Seleccionar Parejas</h4>
                <div class="mb-3">
                    <button type="button" class="btn btn-primary btn-lg" id="btn-agregar-pareja-lista">
                        + Agregar Pareja
                    </button>
                </div>
                <div id="listado-parejas-seleccionadas" class="mb-4">
                    <h5>Parejas Seleccionadas: <span id="contador-parejas" class="badge badge-primary">0</span></h5>
                    <div id="contenedor-parejas-lista" class="row">
                        <!-- Las parejas se mostrarán aquí -->
                    </div>
                </div>
                <div class="row align-items-end mb-3">
                    <div class="col-md-3">
                        <label for="cantidad_grupos" class="form-label">Cantidad de Grupos</label>
                        <input type="number" class="form-control form-control-lg" id="cantidad_grupos" min="1" max="20" value="1">
                    </div>
                    <div class="col-md-6">
                        <label for="config_cruces_americano" class="form-label">Configuración de Cruces</label>
                        <select class="form-control form-control-lg" id="config_cruces_americano" name="config_cruces_americano">
                            <option value="">-- Seleccionar configuración --</option>
                            @if(isset($configsCrucesAmericanos) && count($configsCrucesAmericanos) > 0)
                                @foreach($configsCrucesAmericanos as $config)
                                    <option value="{{ $config->id }}" 
                                        data-parejas="{{ $config->cantidad_parejas }}"
                                        data-zonas="{{ $config->cantidad_zonas }}"
                                        @if(isset($torneo) && $torneo->config_cruces_americano_id == $config->id) selected @endif>
                                        {{ $config->nombre ?? $config->cantidad_parejas . ' parejas' }} 
                                        ({{ $config->cantidad_zonas }} zonas, 
                                        {{ $config->clasifican_primeros }}° + {{ $config->clasifican_segundos }} 2°
                                        @if($config->clasifican_terceros > 0) + {{ $config->clasifican_terceros }} mejores 3° @endif)
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <small class="text-muted">Define cómo se arman los cruces al finalizar los grupos. <a href="{{ route('adminconfigamericano') }}" target="_blank">Crear nueva</a></small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-warning btn-lg w-100" id="btn-guardar-borrador-inicial" disabled>
                            <i class="fa fa-save"></i> Guardar como Borrador
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-success btn-lg w-100" id="btn-comenzar-distribucion" disabled>
                            <i class="fa fa-play"></i> Comenzar
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('tvtorneoamericanosorteo') }}?torneo_id={{ $torneo->id ?? 0 }}" target="_blank" class="btn btn-info btn-lg w-100" id="btn-proyectar-sorteo-tv" style="display:none;">
                            <i class="fa fa-desktop"></i> Proyectar Pantalla Sorteo TV
                        </a>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 text-center">
                        <a href="{{ route('tvtorneoamericanosorteo') }}?torneo_id={{ $torneo->id ?? 0 }}" target="_blank" class="btn btn-info btn-lg" id="btn-proyectar-sorteo-tv-top" style="display:none;">
                            <i class="fa fa-desktop"></i> Proyectar Pantalla Sorteo TV
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sección 2: Distribución de Grupos con Animación -->
            <div id="seccion-distribucion" style="display:none;">
                <div class="card shadow bg-white p-4 mb-4">
                    <h4 class="mb-3">Grupos del Torneo</h4>
                    <div class="text-center mb-3">
                        <a href="{{ route('tvtorneoamericanosorteo') }}?torneo_id={{ $torneo->id ?? 0 }}" target="_blank" class="btn btn-info btn-lg mr-2" id="btn-proyectar-sorteo-tv-distribucion">
                            <i class="fa fa-desktop"></i> Proyectar Pantalla Sorteo TV
                        </a>
                        <button type="button" class="btn btn-primary btn-lg" id="btn-mezclar-grupos">
                            <i class="fa fa-random"></i> Mezclar Grupos
                        </button>
                    </div>
                    <div id="contenedor-grupos-animacion" class="row">
                        <!-- Los grupos se crearán dinámicamente aquí -->
                    </div>
                </div>
            </div>

            <!-- Sección 3: Grupos Finales -->
            <div id="seccion-grupos-finales" style="display:none;">
                <div class="card shadow bg-white p-4">
                    <h4 class="mb-3">Grupos del Torneo</h4>
                    <div id="contenedor-grupos-finales" class="row">
                        <!-- Los grupos finales se mostrarán aquí -->
                    </div>
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-warning btn-lg mr-2" id="btn-guardar-borrador">
                            <i class="fa fa-save"></i> Guardar como Borrador
                        </button>
                        <button type="button" class="btn btn-success btn-lg mr-2" id="btn-guardar-americano">
                            <i class="fa fa-check"></i> Guardar Torneo Americano
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" id="btn-comenzar-americano">
                            <i class="fa fa-play"></i> Comenzar Torneo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('bahia_padel.modal.jugadores')

<script type="text/javascript">
    let parejasLista = []; // Lista de parejas seleccionadas: [{jugador1: id, jugador2: id, nombre1: str, nombre2: str, foto1: str, foto2: str}, ...]
    let gruposCreados = [];
    let parejasSeleccionadas = {}; // {grupoId: [{jugador1: id1, jugador2: id2}, ...]}
    let torneoId = document.getElementById('torneo_id').value;
    let jugadorTemporal1 = null; // Para almacenar el primer jugador seleccionado
    let grupoActualPareja = null; // Para saber en qué grupo estamos agregando la pareja
    let distribucionEnProceso = false;
    
    // Función para obtener jugador por ID (debe estar antes de usarse)
    // Función helper para normalizar URLs de fotos
    function getFotoUrl(foto) {
        if (!foto || foto === '') {
            return '{{ asset('images/jugador_img.png') }}';
        }
        // Si ya es una URL completa (http/https), devolverla tal cual
        if (foto.startsWith('http://') || foto.startsWith('https://')) {
            return foto;
        }
        // Construir la URL completa usando asset() de Laravel
        // Si empieza con /, quitar el / inicial
        const ruta = foto.startsWith('/') ? foto.substring(1) : foto;
        // Usar asset() con la ruta completa
        return '{{ asset('') }}' + '/' + ruta;
    }
    
    function obtenerJugadorPorId(id) {
        let jugadores = @json($jugadores ?? []);
        return jugadores.find(j => j.id == id);
    }
    
    // Cargar grupos existentes si hay
    let gruposExistentes = @json($grupos ?? []);
    
    // Agrupar por zona y extraer parejas
    let gruposPorZona = {};
    gruposExistentes.forEach(function(grupo) {
        if (!gruposPorZona[grupo.zona]) {
            gruposPorZona[grupo.zona] = [];
        }
        // Si hay ambos jugadores, es una pareja
        if (grupo.jugador_1 && grupo.jugador_2) {
            gruposPorZona[grupo.zona].push({
                jugador1: grupo.jugador_1,
                jugador2: grupo.jugador_2
            });
        }
    });
    
    // Si hay grupos existentes (borrador), cargar las parejas en la lista pero mantener la pantalla de edición
    // NO cargar directamente los grupos finales, permitir seguir editando
    if (Object.keys(gruposPorZona).length > 0) {
        // Cargar parejas en la lista para poder seguir editando
        Object.keys(gruposPorZona).forEach(function(zona) {
            gruposPorZona[zona].forEach(function(pareja) {
                let jugador1 = obtenerJugadorPorId(pareja.jugador1);
                let jugador2 = obtenerJugadorPorId(pareja.jugador2);
                if (jugador1 && jugador2) {
                    // Verificar que la pareja no esté ya en la lista
                    let parejaExiste = parejasLista.some(function(p) {
                        return (p.jugador1 == pareja.jugador1 && p.jugador2 == pareja.jugador2) ||
                               (p.jugador1 == pareja.jugador2 && p.jugador2 == pareja.jugador1);
                    });
                    
                    if (!parejaExiste) {
                        parejasLista.push({
                            jugador1: pareja.jugador1,
                            jugador2: pareja.jugador2,
                            nombre1: jugador1.nombre + ' ' + jugador1.apellido,
                            nombre2: jugador2.nombre + ' ' + jugador2.apellido,
                            foto1: getFotoUrl(jugador1.foto),
                            foto2: getFotoUrl(jugador2.foto)
                        });
                    }
                }
            });
        });
        
        // Configurar cantidad de grupos según los grupos existentes
        let cantidadGrupos = Object.keys(gruposPorZona).length;
        if (cantidadGrupos > 0) {
            document.getElementById('cantidad_grupos').value = cantidadGrupos;
        }
        
        // NO cargar grupos existentes directamente en la pantalla final
        // Dejar que el usuario pueda seguir editando y mezclando
        // Si quiere ver los grupos guardados, puede hacer clic en "Comenzar" sin mezclar
    }
    
    // Inicializar lista de parejas al cargar
    $(document).ready(function() {
        actualizarListaParejas();
    });
    
    // Función para cargar grupos existentes sin mezclar
    function cargarGruposExistentes() {
        gruposCreados = [];
        parejasSeleccionadas = {};
        
        // Ordenar las zonas alfabéticamente
        let zonasOrdenadas = Object.keys(gruposPorZona).sort();
        
        zonasOrdenadas.forEach(function(zona, index) {
            let grupoId = index + 1;
            gruposCreados.push({
                id: grupoId,
                letra: zona,
                jugadores: []
            });
            parejasSeleccionadas[grupoId] = gruposPorZona[zona] || [];
        });
        
        // Ocultar sección de selección y mostrar grupos finales directamente
        $('#seccion-seleccion-parejas').hide();
        $('#seccion-distribucion').hide();
        $('#seccion-grupos-finales').show();
        
        // Renderizar grupos finales
        mostrarGruposFinales();
    }
    
    // Botón agregar pareja a la lista
    $('#btn-agregar-pareja-lista').on('click', function() {
        grupoActualPareja = 'lista'; // Marcar que estamos agregando a la lista
        jugadorTemporal1 = null;
        $('#modalSeleccionarJugador').modal('show');
        
        // Actualizar mensaje del modal
        if ($('#modalSeleccionarJugador .modal-body').find('.mensaje-pareja').length === 0) {
            $('#modalSeleccionarJugador .modal-body').prepend('<div class="alert alert-info mensaje-pareja">Seleccione el primer jugador de la pareja</div>');
        } else {
            $('#modalSeleccionarJugador .mensaje-pareja').text('Seleccione el primer jugador de la pareja').removeClass('alert-warning').addClass('alert-info');
        }
    });
    
    // Al seleccionar jugador para la lista
    $(document).on('click', '.jugador-option', function() {
        const jugadorId = parseInt($(this).data('id'));
        const jugador = obtenerJugadorPorId(jugadorId);
        
        if (grupoActualPareja === 'lista') {
            if (jugadorTemporal1 === null) {
                // Seleccionar primer jugador
                jugadorTemporal1 = {
                    id: jugadorId,
                    nombre: jugador.nombre + ' ' + jugador.apellido,
                    foto: getFotoUrl(jugador.foto)
                };
                $('#modalSeleccionarJugador .mensaje-pareja').text('Ahora seleccione el segundo jugador de la pareja').removeClass('alert-info').addClass('alert-warning');
            } else {
                // Seleccionar segundo jugador y crear pareja
                if (jugadorTemporal1.id == jugadorId) {
                    alert('No puede seleccionar el mismo jugador dos veces');
                    return;
                }
                
                // VALIDACIÓN: Verificar que ninguno de los dos jugadores ya esté en otra pareja
                let jugador1YaUsado = parejasLista.some(function(p) {
                    return p.jugador1 == jugadorTemporal1.id || p.jugador2 == jugadorTemporal1.id;
                });
                
                let jugador2YaUsado = parejasLista.some(function(p) {
                    return p.jugador1 == jugadorId || p.jugador2 == jugadorId;
                });
                
                if (jugador1YaUsado) {
                    alert('El jugador "' + jugadorTemporal1.nombre + '" ya está en otra pareja');
                    return;
                }
                
                if (jugador2YaUsado) {
                    alert('El jugador "' + jugador.nombre + ' ' + jugador.apellido + '" ya está en otra pareja');
                    return;
                }
                
                // Verificar que la pareja no esté ya en la lista
                let parejaExiste = parejasLista.some(function(p) {
                    return (p.jugador1 == jugadorTemporal1.id && p.jugador2 == jugadorId) ||
                           (p.jugador1 == jugadorId && p.jugador2 == jugadorTemporal1.id);
                });
                
                if (parejaExiste) {
                    alert('Esta pareja ya está en la lista');
                    jugadorTemporal1 = null;
                    $('#modalSeleccionarJugador').modal('hide');
                    return;
                }
                
                parejasLista.push({
                    jugador1: jugadorTemporal1.id,
                    jugador2: jugadorId,
                    nombre1: jugadorTemporal1.nombre,
                    nombre2: jugador.nombre + ' ' + jugador.apellido,
                    foto1: jugadorTemporal1.foto,
                    foto2: getFotoUrl(jugador.foto)
                });
                
                actualizarListaParejas();
                $('#modalSeleccionarJugador').modal('hide');
                jugadorTemporal1 = null;
            }
        }
    });
    
    function actualizarListaParejas() {
        let contenedor = $('#contenedor-parejas-lista');
        contenedor.empty();
        
        // Actualizar contador dinámicamente
        $('#contador-parejas').text(parejasLista.length);
        
        if (parejasLista.length === 0) {
            contenedor.append('<div class="col-12"><p class="text-muted">No hay parejas agregadas</p></div>');
            $('#btn-comenzar-distribucion').prop('disabled', true);
            $('#btn-guardar-borrador-inicial').prop('disabled', true);
            return;
        }
        
        parejasLista.forEach(function(pareja, index) {
            let parejaHtml = `
                <div class="col-md-6 col-lg-4 mb-3" data-pareja-index="${index}">
                    <div class="card border-secondary">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <img src="${pareja.foto1 || '{{ asset('images/jugador_img.png') }}'}" class="rounded-circle mr-2" style="width:40px; height:40px; object-fit:cover;" onerror="this.src='{{ asset('images/jugador_img.png') }}'">
                                    <img src="${pareja.foto2 || '{{ asset('images/jugador_img.png') }}'}" class="rounded-circle mr-2" style="width:40px; height:40px; object-fit:cover; margin-left:-15px; border:2px solid white;" onerror="this.src='{{ asset('images/jugador_img.png') }}'">
                                    <div class="mr-2">
                                        <div style="font-size:0.9rem;">${pareja.nombre1}</div>
                                        <div style="font-size:0.9rem;">${pareja.nombre2}</div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger" onclick="eliminarParejaLista(${index})">
                                    ×
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            contenedor.append(parejaHtml);
        });
        
        $('#btn-comenzar-distribucion').prop('disabled', parejasLista.length === 0);
        $('#btn-guardar-borrador-inicial').prop('disabled', parejasLista.length === 0);
    }
    
    function eliminarParejaLista(index) {
        parejasLista.splice(index, 1);
        actualizarListaParejas();
    }
    
    // Botón comenzar distribución
    $('#btn-comenzar-distribucion').on('click', function() {
        if (parejasLista.length === 0) {
            alert('Debe agregar al menos una pareja');
            return;
        }
        
        let cantidadGrupos = parseInt($('#cantidad_grupos').val());
        if (cantidadGrupos < 1 || cantidadGrupos > 20) {
            alert('Por favor ingrese un número entre 1 y 20');
            return;
        }
        
        // NO mezclar automáticamente, solo mostrar los grupos vacíos
        $('#seccion-seleccion-parejas').hide();
        $('#seccion-distribucion').show();
        $('#btn-proyectar-sorteo-tv').show();
        $('#btn-proyectar-sorteo-tv-top').show();
        $('#btn-proyectar-sorteo-tv-distribucion').show();
        
        // Crear grupos vacíos sin mezclar
        crearGruposVacios(cantidadGrupos);
    });
    
    // Botón mezclar grupos
    $('#btn-mezclar-grupos').on('click', function() {
        if (parejasLista.length === 0) {
            alert('Debe agregar al menos una pareja');
            return;
        }
        
        let cantidadGrupos = parseInt($('#cantidad_grupos').val());
        if (cantidadGrupos < 1 || cantidadGrupos > 20) {
            alert('Por favor ingrese un número entre 1 y 20');
            return;
        }
        
        if (distribucionEnProceso) {
            return;
        }
        
        distribucionEnProceso = true;
        distribuirParejasAleatoriamente(cantidadGrupos);
    });
    
    // Función para crear grupos vacíos sin mezclar
    function crearGruposVacios(cantidadGrupos) {
        gruposCreados = [];
        parejasSeleccionadas = {};
        let contenedor = $('#contenedor-grupos-animacion');
        contenedor.empty();
        
        for (let i = 1; i <= cantidadGrupos; i++) {
            let letraGrupo = String.fromCharCode(64 + i); // A, B, C, etc.
            gruposCreados.push({
                id: i,
                letra: letraGrupo,
                jugadores: []
            });
            parejasSeleccionadas[i] = [];
            
            let grupoHtml = `
                <div class="col-md-6 col-lg-4 mb-4" data-grupo-id="${i}">
                    <div class="card border-primary h-100">
                        <div class="card-header bg-primary text-white text-center">
                            <h5 class="mb-0">Grupo ${letraGrupo}</h5>
                        </div>
                        <div class="card-body">
                            <div id="parejas-grupo-anim-${i}" class="mb-3">
                                <p class="text-muted text-center">Esperando mezcla...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            contenedor.append(grupoHtml);
        }
    }
    
    function distribuirParejasAleatoriamente(cantidadGrupos) {
        // Crear grupos vacíos
        gruposCreados = [];
        parejasSeleccionadas = {};
        let contenedor = $('#contenedor-grupos-animacion');
        contenedor.empty();
        
        for (let i = 1; i <= cantidadGrupos; i++) {
            let letraGrupo = String.fromCharCode(64 + i); // A, B, C, etc.
            gruposCreados.push({
                id: i,
                letra: letraGrupo,
                jugadores: []
            });
            parejasSeleccionadas[i] = [];
            
            let grupoHtml = `
                <div class="col-md-6 col-lg-4 mb-4" data-grupo-id="${i}">
                    <div class="card border-primary h-100">
                        <div class="card-header bg-primary text-white text-center">
                            <h5 class="mb-0">Grupo ${letraGrupo}</h5>
                        </div>
                        <div class="card-body">
                            <div id="parejas-grupo-anim-${i}" class="mb-3">
                                <p class="text-muted text-center">Esperando parejas...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            contenedor.append(grupoHtml);
        }
        
        // Mezclar parejas aleatoriamente
        let parejasMezcladas = [...parejasLista];
        for (let i = parejasMezcladas.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [parejasMezcladas[i], parejasMezcladas[j]] = [parejasMezcladas[j], parejasMezcladas[i]];
        }
        
        // Distribuir parejas en grupos de forma circular
        let indiceGrupo = 0;
        parejasMezcladas.forEach(function(pareja, index) {
            let grupoId = (indiceGrupo % cantidadGrupos) + 1;
            indiceGrupo++;
            
            setTimeout(function() {
                agregarParejaAGrupoAnimacion(grupoId, pareja);
                
                // Si es la última pareja, mostrar sección final después de un delay
                if (index === parejasMezcladas.length - 1) {
                    setTimeout(function() {
                        mostrarGruposFinales();
                    }, 1500);
                }
            }, index * 1500); // Delay de 1500ms (1.5 segundos) entre cada pareja
        });
    }
    
    function agregarParejaAGrupoAnimacion(grupoId, pareja) {
        if (!parejasSeleccionadas[grupoId]) {
            parejasSeleccionadas[grupoId] = [];
        }
        
        parejasSeleccionadas[grupoId].push({
            jugador1: pareja.jugador1,
            jugador2: pareja.jugador2
        });
        
        let contenedor = $('#parejas-grupo-anim-' + grupoId);
        contenedor.empty();
        
        parejasSeleccionadas[grupoId].forEach(function(p) {
            let jugador1 = obtenerJugadorPorId(p.jugador1);
            let jugador2 = obtenerJugadorPorId(p.jugador2);
            
            if (jugador1 && jugador2) {
                let parejaHtml = `
                    <div class="list-group-item mb-2 animate-fade-in">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <img src="${getFotoUrl(jugador1.foto)}" 
                                     class="rounded-circle mr-2" 
                                     style="width:30px; height:30px; object-fit:cover;">
                                <span class="mr-2">${jugador1.nombre} ${jugador1.apellido}</span>
                                <span class="mx-2">+</span>
                                <img src="${getFotoUrl(jugador2.foto)}" 
                                     class="rounded-circle mr-2" 
                                     style="width:30px; height:30px; object-fit:cover;">
                                <span>${jugador2.nombre} ${jugador2.apellido}</span>
                            </div>
                        </div>
                    </div>
                `;
                contenedor.append(parejaHtml);
            }
        });
        
        // Guardar el estado actual en la base de datos para que la pantalla TV se actualice
        guardarEstadoGruposTemporal();
    }
    
    // Función para guardar el estado temporal de los grupos mientras se mezclan
    function guardarEstadoGruposTemporal() {
        if (gruposCreados.length === 0) return;
        
        let datos = {
            torneo_id: torneoId,
            grupos: [],
            config_cruces_americano_id: $('#config_cruces_americano').val() || null
        };
        
        gruposCreados.forEach(function(grupo) {
            let parejas = parejasSeleccionadas[grupo.id] || [];
            datos.grupos.push({
                zona: grupo.letra,
                parejas: parejas
            });
        });
        
        // Guardar en la base de datos (sin mostrar alertas)
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("guardartorneoamericano") }}',
            data: {
                ...datos,
                _token: '{{csrf_token()}}'
            },
            success: function(response) {
                // Silencioso, solo para actualizar la pantalla TV
            },
            error: function() {
                // Silencioso, no mostrar error
            }
        });
    }
    
    function mostrarGruposFinales() {
        distribucionEnProceso = false;
        $('#seccion-distribucion').hide();
        $('#seccion-grupos-finales').show();
        
        let contenedor = $('#contenedor-grupos-finales');
        contenedor.empty();
        
        gruposCreados.forEach(function(grupo) {
            let grupoHtml = `
                <div class="col-md-6 col-lg-4 mb-4" data-grupo-id="${grupo.id}">
                    <div class="card border-primary h-100">
                        <div class="card-header bg-primary text-white text-center">
                            <h5 class="mb-0">Grupo ${grupo.letra}</h5>
                        </div>
                        <div class="card-body">
                            <div id="parejas-grupo-final-${grupo.id}" class="mb-3">
                                ${renderizarParejasGrupo(grupo.id)}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            contenedor.append(grupoHtml);
        });
    }
    
    function crearGrupos(cantidad, cargarExistentes) {
        gruposCreados = [];
        parejasSeleccionadas = {};
        let contenedor = $('#contenedor-grupos');
        contenedor.empty();
        
        for (let i = 1; i <= cantidad; i++) {
            let letraGrupo = String.fromCharCode(64 + i); // A, B, C, etc.
            gruposCreados.push({
                id: i,
                letra: letraGrupo,
                jugadores: []
            });
            
            // Si hay grupos existentes, cargar parejas
            if (cargarExistentes && gruposPorZona[letraGrupo]) {
                parejasSeleccionadas[i] = gruposPorZona[letraGrupo] || [];
            } else {
                parejasSeleccionadas[i] = [];
            }
            
            let grupoHtml = `
                <div class="col-md-6 col-lg-4 mb-4" data-grupo-id="${i}">
                    <div class="card border-primary h-100">
                        <div class="card-header bg-primary text-white text-center">
                            <h5 class="mb-0">Grupo ${letraGrupo}</h5>
                        </div>
                        <div class="card-body">
                            <div id="parejas-grupo-${i}" class="mb-3">
                                ${renderizarParejasGrupo(i)}
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="abrirModalJugadores(${i})">
                                + Agregar Pareja
                            </button>
                        </div>
                    </div>
                </div>
            `;
            contenedor.append(grupoHtml);
        }
        
        $('#seccion-grupos').show();
    }
    
    function renderizarParejasGrupo(grupoId) {
        let parejas = parejasSeleccionadas[grupoId] || [];
        if (parejas.length === 0) {
            return '<p class="text-muted text-center">No hay parejas agregadas</p>';
        }
        
        let html = '<div class="list-group">';
        parejas.forEach(function(pareja, index) {
            let jugador1 = obtenerJugadorPorId(pareja.jugador1);
            let jugador2 = obtenerJugadorPorId(pareja.jugador2);
            if (jugador1 && jugador2) {
                html += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <img src="${getFotoUrl(jugador1.foto)}" 
                                     class="rounded-circle mr-2" 
                                     style="width:30px; height:30px; object-fit:cover;">
                                <span class="mr-2">${jugador1.nombre} ${jugador1.apellido}</span>
                                <span class="mx-2">+</span>
                                <img src="${getFotoUrl(jugador2.foto)}" 
                                     class="rounded-circle mr-2" 
                                     style="width:30px; height:30px; object-fit:cover;">
                                <span>${jugador2.nombre} ${jugador2.apellido}</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarParejaGrupo(${grupoId}, ${index})">
                                ×
                            </button>
                        </div>
                    </div>
                `;
            }
        });
        html += '</div>';
        return html;
    }
    
    function eliminarParejaGrupo(grupoId, indexPareja) {
        if (parejasSeleccionadas[grupoId]) {
            parejasSeleccionadas[grupoId].splice(indexPareja, 1);
            $('#parejas-grupo-final-' + grupoId).html(renderizarParejasGrupo(grupoId));
        }
    }
    
    // Resetear jugador temporal cuando se cierra el modal
    $('#modalSeleccionarJugador').on('hidden.bs.modal', function() {
        if (grupoActualPareja === 'lista') {
            jugadorTemporal1 = null;
        }
        grupoActualPareja = null;
        $('.mensaje-pareja').remove();
    });
    
    // Función para guardar torneo (compartida entre guardar y borrador)
    function guardarTorneoAmericano(esBorrador) {
        let cantidadGrupos = 0;
        let gruposParaGuardar = [];
        
        // Si ya hay grupos creados y mezclados, usar esos
        if (gruposCreados.length > 0 && Object.keys(parejasSeleccionadas).length > 0) {
            gruposCreados.forEach(function(grupo) {
                let parejas = parejasSeleccionadas[grupo.id] || [];
                gruposParaGuardar.push({
                    zona: grupo.letra,
                    parejas: parejas
                });
            });
        } else if (parejasLista.length > 0) {
            // Si solo hay parejas en la lista, crear grupos vacíos o distribuir automáticamente
            cantidadGrupos = parseInt($('#cantidad_grupos').val()) || 1;
            
            // Distribuir parejas en grupos de forma simple (sin animación)
            for (let i = 1; i <= cantidadGrupos; i++) {
                let letraGrupo = String.fromCharCode(64 + i); // A, B, C, etc.
                let parejasEnGrupo = [];
                
                // Distribuir parejas de forma circular
                parejasLista.forEach(function(pareja, index) {
                    let grupoId = (index % cantidadGrupos) + 1;
                    if (grupoId === i) {
                        parejasEnGrupo.push({
                            jugador1: pareja.jugador1,
                            jugador2: pareja.jugador2
                        });
                    }
                });
                
                gruposParaGuardar.push({
                    zona: letraGrupo,
                    parejas: parejasEnGrupo
                });
            }
        } else {
            alert('Debe agregar al menos una pareja o crear grupos');
            return;
        }
        
        if (gruposParaGuardar.length === 0) {
            alert('No hay grupos para guardar');
            return;
        }
        
        let datos = {
            torneo_id: torneoId,
            grupos: gruposParaGuardar,
            es_borrador: esBorrador ? 1 : 0,
            config_cruces_americano_id: $('#config_cruces_americano').val() || null
        };
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("guardartorneoamericano") }}',
            data: {
                ...datos,
                _token: '{{csrf_token()}}'
            },
            success: function(response) {
                if (response.success) {
                    if (esBorrador) {
                        alert('Borrador guardado correctamente. Puede continuar editando.');
                    } else {
                        alert('Torneo Americano guardado correctamente');
                    }
                } else {
                    alert('Error al guardar: ' + (response.message || 'Error desconocido'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al guardar torneo:', xhr, status, error);
                console.error('Response:', xhr.responseText);
                let errorMsg = 'Error al guardar el torneo';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert(errorMsg);
            }
        });
    }
    
    // Botón guardar como borrador desde la sección inicial
    $('#btn-guardar-borrador-inicial').on('click', function() {
        if (parejasLista.length === 0) {
            alert('Debe agregar al menos una pareja antes de guardar');
            return;
        }
        guardarTorneoAmericano(true);
    });
    
    // Botón guardar como borrador
    $('#btn-guardar-borrador').on('click', function() {
        guardarTorneoAmericano(true);
    });
    
    // Botón guardar torneo americano
    $('#btn-guardar-americano').on('click', function() {
        guardarTorneoAmericano(false);
    });
    
    // Botón comenzar torneo
    $('#btn-comenzar-americano').on('click', function() {
        if (gruposCreados.length === 0) {
            alert('Debe crear al menos un grupo antes de comenzar el torneo');
            return;
        }
        
        // Verificar que haya parejas en los grupos
        let gruposConParejas = 0;
        gruposCreados.forEach(function(grupo) {
            if (parejasSeleccionadas[grupo.id] && parejasSeleccionadas[grupo.id].length > 0) {
                gruposConParejas++;
            }
        });
        
        if (gruposConParejas === 0) {
            alert('Debe agregar al menos una pareja en algún grupo antes de comenzar el torneo');
            return;
        }
        
        // Primero guardar los grupos (siempre, sin preguntar)
        let datos = {
            torneo_id: torneoId,
            grupos: [],
            config_cruces_americano_id: $('#config_cruces_americano').val() || null
        };
        
        gruposCreados.forEach(function(grupo) {
            let parejas = parejasSeleccionadas[grupo.id] || [];
            datos.grupos.push({
                zona: grupo.letra,
                parejas: parejas
            });
        });
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("guardartorneoamericano") }}',
            data: {
                ...datos,
                _token: '{{csrf_token()}}'
            },
            success: function(response) {
                if (response.success) {
                    // Después de guardar, crear los partidos (todos contra todos)
                    $.ajax({
                        type: 'POST',
                        dataType: 'JSON',
                        url: '{{ route("crearpartidosamericano") }}',
                        data: {
                            torneo_id: torneoId,
                            _token: '{{csrf_token()}}'
                        },
                        success: function(response2) {
                            if (response2.success) {
                                // Redirigir a la pantalla de partidos
                                window.location.href = '{{ route("admintorneoamericanopartidos") }}?torneo_id=' + torneoId;
                            } else {
                                alert('Error al crear partidos: ' + (response2.message || 'Error desconocido'));
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error al crear partidos:', xhr, status, error);
                            console.error('Response:', xhr.responseText);
                            let errorMsg = 'Error al crear los partidos';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }
                            alert(errorMsg);
                        }
                    });
                } else {
                    alert('Error al guardar: ' + (response.message || 'Error desconocido'));
                }
            },
            error: function() {
                alert('Error al guardar el torneo');
            }
        });
    });
    
    // Cuando se selecciona una configuración, ajustar automáticamente la cantidad de grupos
    $('#config_cruces_americano').on('change', function() {
        let selectedOption = $(this).find('option:selected');
        let zonas = selectedOption.data('zonas');
        if (zonas) {
            $('#cantidad_grupos').val(zonas);
        }
    });
    
    // Función para sugerir configuración según cantidad de parejas
    function sugerirConfiguracion() {
        let cantidadParejas = parejasLista.length;
        let select = $('#config_cruces_americano');
        let mejorMatch = null;
        let mejorDiferencia = Infinity;
        
        select.find('option').each(function() {
            let parejasConfig = $(this).data('parejas');
            if (parejasConfig) {
                let diferencia = Math.abs(cantidadParejas - parejasConfig);
                if (diferencia < mejorDiferencia || (diferencia === mejorDiferencia && parejasConfig >= cantidadParejas)) {
                    mejorDiferencia = diferencia;
                    mejorMatch = $(this).val();
                }
            }
        });
        
        if (mejorMatch && !select.val()) {
            select.val(mejorMatch).trigger('change');
        }
    }
    
    // Al actualizar la lista de parejas, sugerir configuración
    let originalActualizarListaParejas = actualizarListaParejas;
    actualizarListaParejas = function() {
        originalActualizarListaParejas();
        // Sugerir configuración si hay al menos 4 parejas
        if (parejasLista.length >= 4) {
            sugerirConfiguracion();
        }
    };
</script>
@endsection
