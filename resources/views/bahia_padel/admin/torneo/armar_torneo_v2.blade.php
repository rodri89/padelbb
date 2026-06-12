@extends('bahia_padel/admin/plantilla')

@section('title_header','Torneos')

@section('contenedor')

<style>
    /* Celda que muestra solo el icono de pareja: fondo gris oscuro, icono centrado */
    .celda-icono {
        background-color: #495057 !important;
        vertical-align: middle !important;
    }
    .celda-icono .seleccion-dia-horario {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 80px;
    }
</style>

<div class="container body_admin">
    <div class="row justify-content-center">
            <input hidden id="torneo_id" value="{{$torneo->id}}">
            <input hidden id="zona_actual" value="">            
            <div class="card shadow bg-white w-100 px-5 py-3 d-flex "
                style="border-radius: 12px; border: 1px solid #e3e6f0;">
                <div class="d-flex flex-column align-items-start flex-grow-1">
                    <div class="categoria display-4 mb-2" style="font-size:2.2rem; font-weight:700; color:#4e73df;">
                        {{ $torneo->categoria ?? '-' }}º Categoría <small>- ({{ $torneo->tipo}})</small>
                    </div>                    
                    <div class="fechas" style="font-size:1.2rem; color:#555;">
                    Fecha: {{ isset($torneo->fecha_inicio, $torneo->fecha_fin) ? (date('d', strtotime($torneo->fecha_inicio)).' '.__(strtolower(date('F', strtotime($torneo->fecha_inicio)))).' - '.date('d', strtotime($torneo->fecha_fin)).' '.__(strtolower(date('F', strtotime($torneo->fecha_fin)))) ) : '-' }}
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end premios" style="min-width:180px;">
                    <div class="premio1" style="font-size:1.5rem; font-weight:600; color:#1a8917;">
                        1º Premio: ${{ $torneo->premio_1}}                        
                    </div>
                    <div class="premio2" style="font-size:1.2rem; font-weight:500; color:#555;">
                        2º Premio: ${{ $torneo->premio_2}}                        
                    </div>
                </div>

        </div>
    </div>

    @if(isset($configsCrucesPuntuables) && count($configsCrucesPuntuables) > 0)
    <div class="row justify-content-center mt-3">
        <div class="col-lg-6">
            <div class="card shadow bg-white p-3" style="border-radius: 12px; border: 1px solid #e3e6f0;">
                <label for="config_cruces_puntuable" class="mb-2"><strong>Configuración de Cruces</strong></label>
                <select class="form-control" id="config_cruces_puntuable" name="config_cruces_puntuable">
                    <option value="">-- Seleccionar configuración --</option>
                    @foreach($configsCrucesPuntuables as $config)
                        <option value="{{ $config->id }}" @if(($torneo->config_cruces_puntuable_id ?? null) == $config->id) selected @endif>
                            {{ $config->cantidad_parejas }} parejas {{ $config->tiene_16avos_final ? '(con 16avos)' : '(sin 16avos)' }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Esta configuración se guarda al crear/armar el torneo.</small>
            </div>
        </div>
    </div>
    @endif

    <br>

    <div class="row justify-content-center" id="seccion_zonas">

    </div>
</div>

@include('bahia_padel.modal.jugadores')

<!-- Modal Seleccionar Día y Horario -->
<style>
  .pill-dia, .pill-hora, .pill-minuto {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    margin: 0.2rem;
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.9rem;
    border: 2px solid #dee2e6;
    background: #fff;
    transition: all 0.2s;
  }
  .pill-dia:hover, .pill-hora:hover, .pill-minuto:hover {
    border-color: #4e73df;
    background: #f8f9fc;
  }
  .pill-dia.active, .pill-hora.active, .pill-minuto.active {
    background: #4e73df;
    border-color: #4e73df;
    color: #fff;
  }
  .horario-armado {
    font-size: 1.5rem;
    font-weight: 600;
    color: #4e73df;
    padding: 0.5rem 0;
    min-height: 2rem;
  }
</style>
<div class="modal fade body_admin" id="modalDiaHorario" tabindex="-1" role="dialog" aria-labelledby="modalDiaHorarioLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="formDiaHorario">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalDiaHorarioLabel">Seleccionar Día y Horario</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label class="mb-2">Día</label>
            <div id="pills-dias" class="d-flex flex-wrap">
              {{-- Se rellenan por JS con los días del torneo (fecha_inicio a fecha_fin) --}}
            </div>
            <input type="hidden" id="dia" name="dia" value="">
          </div>
          <div class="form-group">
            <label class="mb-2">Horario</label>
            <div class="horario-armado mb-2" id="horario-armado">--:--</div>
            <div class="mb-2">
              <small class="text-muted d-block mb-1">Hora</small>
              <div id="pills-horas" class="d-flex flex-wrap">
                {{-- 08 a 23, luego 00 --}}
              </div>
            </div>
            <div>
              <small class="text-muted d-block mb-1">Minutos</small>
              <div id="pills-minutos" class="d-flex flex-wrap">
                <span class="pill-minuto" data-val="00">00</span>
                <span class="pill-minuto" data-val="15">15</span>
                <span class="pill-minuto" data-val="30">30</span>
                <span class="pill-minuto" data-val="45">45</span>
              </div>
            </div>
            <input type="hidden" id="hora" name="hora" value="">
            <input type="hidden" id="minuto" name="minuto" value="">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
    // Variables globales para manejar zonas
    let zonas = ['A']; // Array de zonas (A, B, C, etc.)
    let zonaIndex = 0; // Índice de la zona actual
    
    // Fechas del torneo para pills de días (fecha_inicio y fecha_fin)
    const torneoFechaInicio = '{{ $torneo->fecha_inicio ?? date("Y-m-d") }}';
    const torneoFechaFin = '{{ $torneo->fecha_fin ?? $torneo->fecha_inicio ?? date("Y-m-d") }}';
    
    function initModalDiaHorario() {
        const diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        const diasContainer = $('#pills-dias');
        diasContainer.empty();
        const inicio = new Date(torneoFechaInicio + 'T00:00:00');
        const fin = new Date(torneoFechaFin + 'T00:00:00');
        if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) return;
        for (let d = new Date(inicio.getTime()); d <= fin; d.setDate(d.getDate() + 1)) {
            const y = d.getFullYear(), mes = d.getMonth() + 1, day = d.getDate();
            const fechaStr = y + '-' + ('0' + mes).slice(-2) + '-' + ('0' + day).slice(-2);
            const diaNum = day;
            const nombreDia = diasSemana[d.getDay()];
            const label = nombreDia + ' ' + diaNum;
            diasContainer.append('<span class="pill-dia" data-fecha="' + fechaStr + '">' + label + '</span>');
        }
        // Horas: 08 a 23, luego 00
        const horasContainer = $('#pills-horas');
        horasContainer.empty();
        for (let h = 8; h <= 23; h++) {
            const v = ('0' + h).slice(-2);
            horasContainer.append('<span class="pill-hora" data-val="' + v + '">' + v + '</span>');
        }
        horasContainer.append('<span class="pill-hora" data-val="00">00</span>');
    }
    
    function actualizarHorarioArmado() {
        const h = $('#hora').val();
        const m = $('#minuto').val();
        $('#horario-armado').text((h && m) ? (h + ':' + m) : '--:--');
    }
    
    window.onload = function() {
        inicializarZonas();
        initModalDiaHorario();
        // Pills de día
        $(document).on('click', '.pill-dia', function() {
            $('.pill-dia').removeClass('active');
            $(this).addClass('active');
            $('#dia').val($(this).data('fecha'));
        });
        // Pills de hora
        $(document).on('click', '.pill-hora', function() {
            $('.pill-hora').removeClass('active');
            $(this).addClass('active');
            $('#hora').val($(this).data('val'));
            actualizarHorarioArmado();
        });
        // Pills de minuto
        $(document).on('click', '.pill-minuto', function() {
            $('.pill-minuto').removeClass('active');
            $(this).addClass('active');
            $('#minuto').val($(this).data('val'));
            actualizarHorarioArmado();
        });
    };
    
    // Función para inicializar las zonas desde la base de datos
    function inicializarZonas() {
        let torneoId = $('#torneo_id').val();
        
        if (!torneoId) {
            console.error('No hay torneo_id');
            zonas = ['A'];
            zonaIndex = 0;
            cargarZona();
            return;
        }
        
        // Obtener todas las zonas del torneo desde la base de datos
        $.ajax({
            url: '{{ route("obtenertodaslaszonas") }}',
            method: 'POST',
            data: {
                torneo_id: torneoId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success && response.zonas && response.zonas.length > 0) {
                    // Filtrar zonas para obtener solo letras simples (A, B, C, etc.)
                    // Excluir "ganador X" y "perdedor X"
                    zonas = response.zonas.filter(z => z.length === 1 && /^[A-Z]$/.test(z));
                    if (zonas.length === 0) {
                        zonas = ['A'];
                    }
                    zonaIndex = 0;
                    console.log('Zonas cargadas (filtradas):', zonas);
                } else {
                    zonas = ['A'];
                    zonaIndex = 0;
                }
                cargarZona();
            },
            error: function(xhr, status, error) {
                console.error('Error al obtener zonas:', error);
                zonas = ['A'];
                zonaIndex = 0;
                cargarZona();
            }
        });
    }
    
    function cargarZona() {
        let torneoId = $('#torneo_id').val();
        
        // Obtener la zona actual del array de zonas
        let zona = zonas[zonaIndex] || 'A';
        $('#zona_actual').val(zona);
        
        // Actualizar el label de la zona
        $('#zona-label').text('Zona ' + zona);
        
        // Hacer petición AJAX para obtener datos completos de la zona
        $.ajax({
            url: '{{ route("obtenerdatoszona") }}',
            method: 'POST',
            data: {
                torneo_id: torneoId,
                zona: zona,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success && response.datos) {
                    let datos = response.datos;
                    let numParejas = datos.tieneCuatroParejas ? 4 : 3;
                    
                    // Construir la tabla con el número correcto de parejas
                    armarTabla(numParejas);
                    
                    // Esperar un momento para que el DOM se actualice
                    setTimeout(function() {
                        cargarDatosEnTabla(datos);
                    }, 100);
                } else {
                    // Si no hay datos, construir tabla vacía con 3 parejas
                    armarTabla(3);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al obtener datos de zona:', error);
                // En caso de error, construir tabla vacía con 3 parejas
                armarTabla(3);
            }
        });
    }
    
    // Función para cargar los datos guardados en la tabla
    function cargarDatosEnTabla(datos) {
        let jugadoresInfo = datos.jugadores || {};
        let gruposLibres = datos.gruposLibres || [];
        let gruposLibresIndex = 0;
        
        // Cargar jugadores de cada pareja
        if (datos.parejas && datos.parejas.length > 0) {
            // Pareja 1
            if (datos.parejas[0]) {
                let pareja = datos.parejas[0];
                cargarJugadoresEnCelda('celda1', pareja.jugador_1, pareja.jugador_2, jugadoresInfo);
            }
            
            // Pareja 2
            if (datos.parejas[1]) {
                let pareja = datos.parejas[1];
                cargarJugadoresEnCelda('celda2', pareja.jugador_1, pareja.jugador_2, jugadoresInfo);
            }
            
            // Pareja 3
            if (datos.parejas[2]) {
                let pareja = datos.parejas[2];
                cargarJugadoresEnCelda('celda3', pareja.jugador_1, pareja.jugador_2, jugadoresInfo);
            }
            
            // Pareja 4
            if (datos.parejas[3]) {
                let pareja = datos.parejas[3];
                cargarJugadoresEnCelda('celda4', pareja.jugador_1, pareja.jugador_2, jugadoresInfo);
            }
        }
        
        // Cargar horarios según el formato
        if (datos.tieneCuatroParejas) {
            // Formato 4 parejas ELIMINATORIA
            // Primero cargar los horarios de ganador y perdedor en TODAS las celdas correspondientes
            if (datos.grupoGanador && datos.grupoGanador.fecha && datos.grupoGanador.fecha !== '2000-01-01') {
                // Cargar horario de ganador en una celda (la función cargarHorarioEnCelda actualizará todas)
                let primeraCeldaGanador = $('.seleccion-dia-horario[data-tipo-resultado="ganadores"]').first();
                if (primeraCeldaGanador.length) {
                    let celdaId = primeraCeldaGanador.data('celda');
                    cargarHorarioEnCelda(celdaId, datos.grupoGanador.fecha, datos.grupoGanador.horario, null);
                }
            }
            
            if (datos.grupoPerdedor && datos.grupoPerdedor.fecha && datos.grupoPerdedor.fecha !== '2000-01-01') {
                // Cargar horario de perdedor en una celda (la función cargarHorarioEnCelda actualizará todas)
                let primeraCeldaPerdedor = $('.seleccion-dia-horario[data-tipo-resultado="perdedores"]').first();
                if (primeraCeldaPerdedor.length) {
                    let celdaId = primeraCeldaPerdedor.data('celda');
                    cargarHorarioEnCelda(celdaId, datos.grupoPerdedor.fecha, datos.grupoPerdedor.horario, null);
                }
            }
            
            // Cargar partido A: Pareja 1 vs Pareja 2
            if (datos.parejas[0] && datos.parejas[0].grupos && datos.parejas[0].grupos.length > 0) {
                // Pareja 1: partido A (celda 2)
                if (datos.parejas[0].grupos[0]) {
                    cargarHorarioEnCelda(2, datos.parejas[0].grupos[0].fecha, datos.parejas[0].grupos[0].horario, 0);
                }
            }
            
            if (datos.parejas[1] && datos.parejas[1].grupos && datos.parejas[1].grupos.length > 0) {
                // Pareja 2: partido A (celda 4)
                if (datos.parejas[1].grupos[0]) {
                    cargarHorarioEnCelda(4, datos.parejas[1].grupos[0].fecha, datos.parejas[1].grupos[0].horario, 1);
                }
            }
            
            // Cargar partido B: Pareja 3 vs Pareja 4
            if (datos.parejas[2] && datos.parejas[2].grupos && datos.parejas[2].grupos.length > 0) {
                // Pareja 3: partido B (celda 15, columna 4)
                // Buscar el grupo que corresponde al partido B (el que tiene partido_id diferente al partido A)
                let partidoAId = datos.parejas[0] && datos.parejas[0].grupos[0] ? datos.parejas[0].grupos[0].partido_id : null;
                let partidoB = null;
                for (let grupo of datos.parejas[2].grupos) {
                    if (grupo.partido_id && grupo.partido_id !== partidoAId) {
                        partidoB = grupo;
                        break;
                    }
                }
                if (partidoB && partidoB.fecha && partidoB.fecha !== '2000-01-01') {
                    // Cargar en una celda (la función cargarHorarioEnCelda actualizará todas)
                    let primeraCeldaB = $('.seleccion-dia-horario[data-tipo-partido="B"]').first();
                    if (primeraCeldaB.length) {
                        let celdaId = primeraCeldaB.data('celda');
                        cargarHorarioEnCelda(celdaId, partidoB.fecha, partidoB.horario, null);
                    }
                }
            }
        } else {
            // Formato 3 parejas: todos contra todos
            if (datos.parejas[0] && datos.parejas[0].grupos) {
                // Pareja 1: partido 1 (celda 2)
                if (datos.parejas[0].grupos[0]) {
                    cargarHorarioEnCelda(2, datos.parejas[0].grupos[0].fecha, datos.parejas[0].grupos[0].horario, 0);
                }
                // Pareja 1: partido 2 (celda 3)
                if (datos.parejas[0].grupos[1]) {
                    cargarHorarioEnCelda(3, datos.parejas[0].grupos[1].fecha, datos.parejas[0].grupos[1].horario, 0);
                }
            }
            
            if (datos.parejas[1] && datos.parejas[1].grupos) {
                // Pareja 2: partido 1 (celda 4)
                if (datos.parejas[1].grupos[0]) {
                    cargarHorarioEnCelda(4, datos.parejas[1].grupos[0].fecha, datos.parejas[1].grupos[0].horario, 1);
                }
                // Pareja 2: partido 2 (celda 6)
                if (datos.parejas[1].grupos[1]) {
                    cargarHorarioEnCelda(6, datos.parejas[1].grupos[1].fecha, datos.parejas[1].grupos[1].horario, 1);
                }
            }
            
            if (datos.parejas[2] && datos.parejas[2].grupos) {
                // Pareja 3: partido 1 (celda 7)
                if (datos.parejas[2].grupos[0]) {
                    cargarHorarioEnCelda(7, datos.parejas[2].grupos[0].fecha, datos.parejas[2].grupos[0].horario, 2);
                }
                // Pareja 3: partido 2 (celda 8)
                if (datos.parejas[2].grupos[1]) {
                    cargarHorarioEnCelda(8, datos.parejas[2].grupos[1].fecha, datos.parejas[2].grupos[1].horario, 2);
                }
            }
        }
    }
    
    // Función para cargar jugadores en una celda
    function cargarJugadoresEnCelda(celda, jugador1Id, jugador2Id, jugadoresInfo) {
        if (!jugador1Id || !jugador2Id) return;
        
        let jugador1 = jugadoresInfo[jugador1Id];
        let jugador2 = jugadoresInfo[jugador2Id];
        
        if (jugador1) {
            $('.img-jugador-arriba[data-celda="' + celda + '"]').attr('src', jugador1.foto).attr('data-id', jugador1.id);
            $('.nombre-jugador-arriba[data-celda="' + celda + '"]').text((jugador1.nombre || '') + ' ' + (jugador1.apellido || ''));
        }
        
        if (jugador2) {
            $('.img-jugador-abajo[data-celda="' + celda + '"]').attr('src', jugador2.foto).attr('data-id', jugador2.id);
            $('.nombre-jugador-abajo[data-celda="' + celda + '"]').text((jugador2.nombre || '') + ' ' + (jugador2.apellido || ''));
        }
    }
    
    // Función para cargar horario en una celda
    function cargarHorarioEnCelda(celdaId, fecha, horario, filaIndex) {
        if (!fecha || fecha === '2000-01-01' || !horario || horario === '00:00') {
            return; // No cargar fechas/horarios por defecto
        }
        
        try {
            let celda = null;
            if (filaIndex !== undefined && filaIndex !== null) {
                // Obtener de una fila específica
                let filas = $('tbody tr');
                if (filaIndex >= 0 && filaIndex < filas.length) {
                    let fila = filas.eq(filaIndex);
                    if (fila.length) {
                        celda = fila.find('.seleccion-dia-horario[data-celda="' + celdaId + '"]').filter(function() {
                            return $(this).closest('td').is(':visible');
                        }).first();
                    }
                }
            } else {
                // Obtener la primera celda visible
                celda = $('.seleccion-dia-horario[data-celda="' + celdaId + '"]').filter(function() {
                    return $(this).closest('td').is(':visible');
                }).first();
            }
            
            if (celda && celda.length) {
                const tipoPartido = celda.data('tipo-partido');
                const tipoResultado = celda.data('tipo-resultado');
                
                // Si tiene tipo-resultado, actualizar TODAS las celdas con ese tipo
                if (tipoResultado === 'ganadores' || tipoResultado === 'perdedores') {
                    const texto = tipoResultado === 'ganadores' ? 'Ganador' : 'Perdedor';
                    const nombreDia = getNombreDia(fecha);
                    const fechaFormateada = nombreDia;
                    
                    $('.seleccion-dia-horario[data-tipo-resultado="' + tipoResultado + '"]').each(function() {
                        let celdaTipo = $(this);
                        let celdaTipoId = celdaTipo.data('celda');
                        
                        celdaTipo.data('dia', fecha);
                        celdaTipo.data('horario', horario);
                        celdaTipo.attr('data-dia', fecha);
                        celdaTipo.attr('data-horario', horario);
                        
                        celdaTipo.html(`
                            <div>
                                <div class="text-muted mb-1" style="font-size:0.85rem;">${texto}</div>
                                <div style="font-size:1.3rem; font-weight:600;"> ${fechaFormateada}</div>
                                <div style="font-size:1.3rem; font-weight:600;"> ${horario}</div>
                                <button type="button" class="btn btn-sm btn-secondary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="${celdaTipoId}">
                                    Editar
                                </button>
                            </div>
                        `);
                    });
                } 
                // Si tiene tipo-partido, actualizar TODAS las celdas con ese tipo
                else if (tipoPartido === 'A' || tipoPartido === 'B' || tipoPartido === 'C') {
                    let texto = 'Partido';
                    if (tipoPartido === 'A') texto = 'Partido A';
                    else if (tipoPartido === 'B') texto = 'Partido B';
                    else if (tipoPartido === 'C') texto = 'Partido C';
                    
                    const nombreDia = getNombreDia(fecha);
                    const fechaFormateada = nombreDia;
                    
                    $('.seleccion-dia-horario[data-tipo-partido="' + tipoPartido + '"]').each(function() {
                        let celdaTipo = $(this);
                        let celdaTipoId = celdaTipo.data('celda');
                        
                        celdaTipo.data('dia', fecha);
                        celdaTipo.data('horario', horario);
                        celdaTipo.attr('data-dia', fecha);
                        celdaTipo.attr('data-horario', horario);
                        
                        celdaTipo.html(`
                            <div>
                                <div class="text-muted mb-1" style="font-size:0.85rem;">${texto}</div>
                                <div style="font-size:1.3rem; font-weight:600;"> ${fechaFormateada}</div>
                                <div style="font-size:1.3rem; font-weight:600;"> ${horario}</div>
                                <button type="button" class="btn btn-sm btn-secondary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="${celdaTipoId}">
                                    Editar
                                </button>
                            </div>
                        `);
                    });
                }
                // Comportamiento normal para celdas sin tipo
                else {
                    celda.data('dia', fecha);
                    celda.data('horario', horario);
                    
                    const nombreDia = getNombreDia(fecha);
                    const fechaFormateada = nombreDia;
                    
                    let html = '<div>';
                    html += '<div style="font-size:1.3rem; font-weight:600;">' + fechaFormateada + '</div>';
                    html += '<div style="font-size:1.3rem; font-weight:600;">' + horario + '</div>';
                    html += '<button type="button" class="btn btn-sm btn-secondary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="' + celdaId + '">Editar</button>';
                    html += '</div>';
                    
                    celda.html(html);
                }
            }
        } catch (error) {
            console.error('Error al cargar horario en celda ' + celdaId + ':', error);
        }
    }
    
    function armarTabla(numParejas = 3) {
        // Limpiar contenido anterior
        $('#seccion_zonas').empty();
        
        // Crear estructura de la tabla
        let tablaHTML = `
            <div class="card shadow bg-white w-100 px-5 py-3 d-flex">
                <div class="table-responsive">
                    <table class="table table-bordered text-center w-100">
                        <thead class="thead-light">
                            <tr id="tabla-header">
                                <th id="zona-label">Zona {{ $zona ?? 'A' }}</th>
                                <th class="columna-partido" data-tipo="normal" id="col-header-1">1</th>
                                <th class="columna-partido" data-tipo="normal" id="col-header-2">2</th>
                                <th class="columna-partido" data-tipo="normal" id="columna-partido-3">3</th>
                                <th class="columna-partido" data-tipo="normal" id="columna-partido-4" style="display:none;">4</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${crearFilaPareja(1, numParejas)}
                            ${crearFilaPareja(2, numParejas)}
                            ${crearFilaPareja(3, numParejas)}
                            ${numParejas >= 4 ? crearFilaPareja(4, numParejas) : ''}
                            ${numParejas < 4 ? crearFilaBotonAgregar() : ''}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        // Agregar botones de navegación
        let botonesHTML = `
            <div class="row justify-content-center mt-4">
                <div class="col-md-8">
                    <div class="d-flex justify-content-center mb-3">
                        <button type="button" class="btn btn-secondary btn-lg mr-2" id="btn-zona-anterior">
                            Atrás
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg mr-2" id="btn-nueva-zona">
                            Nueva zona
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" id="btn-zona-siguiente">
                            Siguiente
                        </button>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-primary btn-lg mr-2" id="btn-guardar-torneo">
                            Guardar
                        </button>
                        <button type="button" class="btn btn-success btn-lg" id="btn-comenzar-torneo">
                            Comenzar Torneo
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        $('#seccion_zonas').html(tablaHTML + botonesHTML);
        
        // Actualizar el label de la zona después de construir la tabla
        let zona = zonas[zonaIndex] || 'A';
        $('#zona-label').text('Zona ' + zona);
        
        // Si tiene 4 parejas, mostrar la fila 4 y columna 4
        if (numParejas >= 4) {
            $('#fila-agregar-pareja').show();
            $('#fila-boton-agregar').hide();
            $('#columna-partido-4').show();
            $('.columna-partido-4').show();
        } else {
            $('#fila-agregar-pareja').hide();
            $('#fila-boton-agregar').show();
            $('#columna-partido-4').hide();
            $('.columna-partido-4').hide();
        }
    }
    
    function crearFilaPareja(numPareja, numParejas) {
        let celda = 'celda' + numPareja;
        let filaId = numPareja === 4 ? 'fila-agregar-pareja' : '';
        let displayStyle = numPareja === 4 ? 'style="display:none;"' : '';
        
        // Determinar qué celdas mostrar según la pareja y si hay 4 parejas
        let celdasHTML = '';
        
        if (numParejas >= 4) {
            // Formato 4 parejas
            if (numPareja === 1) {
                // Fila 1: img - partido A - partido 1 sin jugadores - partido 2 sin jugadores
                celdasHTML = `
                    <td class="celda-icono">
                        <div class="seleccion-dia-horario" data-celda="1">
                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                        </div>
                    </td>
                    <td>
                        <div class="seleccion-dia-horario" data-celda="2" data-tipo-partido="A">
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="2">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                    <td class="columna-partido-3">
                        <div class="seleccion-dia-horario" data-celda="3" data-tipo-resultado="perdedores">
                            <div class="text-muted mb-1" style="font-size:0.85rem;">Perdedor</div>
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="3">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                    <td class="columna-partido-4" style="display:none;">
                        <div class="seleccion-dia-horario" data-celda="10" data-tipo-resultado="ganadores">
                            <div class="text-muted mb-1" style="font-size:0.85rem;">Ganador</div>
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="10">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                `;
            } else if (numPareja === 2) {
                // Fila 2: partido A - img - partido 1 sin jugadores - partido 2 sin jugadores
                celdasHTML = `
                    <td>
                        <div class="seleccion-dia-horario" data-celda="4" data-tipo-partido="A">
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="4">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                    <td class="celda-icono">
                        <div class="seleccion-dia-horario" data-celda="5">
                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                        </div>
                    </td>
                    <td class="columna-partido-3">
                        <div class="seleccion-dia-horario" data-celda="6" data-tipo-resultado="ganadores">
                            <div class="text-muted mb-1" style="font-size:0.85rem;">Ganador</div>
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="6">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                    <td class="columna-partido-4" style="display:none;">
                        <div class="seleccion-dia-horario" data-celda="11" data-tipo-resultado="perdedores">
                            <div class="text-muted mb-1" style="font-size:0.85rem;">Perdedor</div>
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="11">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                `;
            } else if (numPareja === 3) {
                // Fila 3: partido 1 sin jugadores - partido 2 sin jugadores - img - partido B
                celdasHTML = `
                    <td>
                        <div class="seleccion-dia-horario" data-celda="7" data-tipo-resultado="perdedores">
                            <div class="text-muted mb-1" style="font-size:0.85rem;">Perdedor</div>
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="7">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                    <td>
                        <div class="seleccion-dia-horario" data-celda="8" data-tipo-resultado="ganadores">
                            <div class="text-muted mb-1" style="font-size:0.85rem;">Ganador</div>
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="8">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                    <td class="columna-partido-3 celda-icono">
                        <div class="seleccion-dia-horario" data-celda="9">
                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                        </div>
                    </td>
                    <td class="columna-partido-4" style="display:none;">
                        <div class="seleccion-dia-horario" data-celda="15" data-tipo-partido="B">
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="15">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                `;
            } else if (numPareja === 4) {
                // Fila 4: partido 1 sin jugadores - partido 2 sin jugadores - partido B - img
                celdasHTML = `
                    <td>
                        <div class="seleccion-dia-horario" data-celda="10" data-tipo-resultado="ganadores">
                            <div class="text-muted mb-1" style="font-size:0.85rem;">Ganador</div>
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="10">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                    <td>
                        <div class="seleccion-dia-horario" data-celda="11" data-tipo-resultado="perdedores">
                            <div class="text-muted mb-1" style="font-size:0.85rem;">Perdedor</div>
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="11">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                    <td class="columna-partido-3">
                        <div class="seleccion-dia-horario" data-celda="15" data-tipo-partido="B">
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="15">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                    <td class="columna-partido-4 celda-icono" style="display:none;">
                        <div class="seleccion-dia-horario" data-celda="14">
                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                        </div>
                    </td>
                `;
            }
        } else {
            // Formato 3 parejas
            if (numPareja === 1) {
                celdasHTML = `
                    <td class="celda-icono">
                        <div class="seleccion-dia-horario" data-celda="1">
                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                        </div>
                    </td>
                    <td>
                        <div class="seleccion-dia-horario" data-celda="2" data-tipo-partido="A">
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="2">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                    <td class="columna-partido-3">
                        <div class="seleccion-dia-horario" data-celda="3" data-tipo-partido="B">
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="3">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                `;
            } else if (numPareja === 2) {
                celdasHTML = `
                    <td>
                        <div class="seleccion-dia-horario" data-celda="4" data-tipo-partido="A">
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="4">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                    <td class="celda-icono">
                        <div class="seleccion-dia-horario" data-celda="5">
                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                        </div>
                    </td>
                    <td class="columna-partido-3">
                        <div class="seleccion-dia-horario" data-celda="6" data-tipo-partido="C">
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="6">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                `;
            } else if (numPareja === 3) {
                celdasHTML = `
                    <td>
                        <div class="seleccion-dia-horario" data-celda="7" data-tipo-partido="B">
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="7">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                    <td>
                        <div class="seleccion-dia-horario" data-celda="8" data-tipo-partido="C">
                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="8">
                                Seleccionar día/horario
                            </button>
                        </div>
                    </td>
                    <td class="columna-partido-3 celda-icono">
                        <div class="seleccion-dia-horario" data-celda="9">
                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                        </div>
                    </td>
                `;
            }
        }
        
        return `
            <tr ${filaId ? `id="${filaId}"` : ''} ${displayStyle}>
                <td style="width:1%; white-space:nowrap;">
                    <div class="d-flex flex-row align-items-center justify-content-between" style="min-width:110px; max-width:280px;">
                        <img src="{{ asset('images/jugador_img.png') }}" 
                            class="rounded-circle img-jugador-seleccionable img-jugador-arriba" 
                            style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                            data-celda="${celda}" data-posicion="arriba">
                        <div class="d-flex flex-column justify-content-between align-items-center mx-2" style="height:48px;">
                            <div class="nombre-jugador-arriba" data-celda="${celda}" style="font-size:1.2rem;">Seleccionar</div>
                            <div class="nombre-jugador-abajo" data-celda="${celda}" style="font-size:1.2rem;">Seleccionar</div>
                        </div>
                        <img src="{{ asset('images/jugador_img.png') }}" 
                            class="rounded-circle img-jugador-seleccionable img-jugador-abajo" 
                            style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                            data-celda="${celda}" data-posicion="abajo">
                    </div>
                </td>
                ${celdasHTML}
            </tr>
        `;
    }
    
    function crearFilaBotonAgregar() {
        return `
            <tr id="fila-boton-agregar">
                <td colspan="4" class="text-center py-3">
                    <button type="button" class="btn btn-success btn-lg" id="btn-agregar-pareja" style="font-size:2rem; width:60px; height:60px; border-radius:50%;">
                        +
                    </button>
                </td>
            </tr>
        `;
    }
    
    // Variables globales para la selección de jugadores
    let celdaJugadorActual = null;
    let posicionJugadorActual = null;
    
    // Al hacer clic en la imagen de jugador
    $(document).on('click', '.img-jugador-seleccionable', function() {
        celdaJugadorActual = $(this).closest('td');
        posicionJugadorActual = $(this).data('posicion'); // 'arriba' o 'abajo'
        $('#modalSeleccionarJugador').modal('show');
    });
    
    // Al seleccionar un jugador en el modal
    $(document).on('click', '.jugador-option', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const nombre = $(this).data('nombre');
        const img = $(this).data('img');
        const id = $(this).data('id');
        
        if (!celdaJugadorActual || !posicionJugadorActual) {
            console.error('Error: celdaJugadorActual o posicionJugadorActual no está definido');
            return;
        }
        
        try {
            // Actualizar la imagen y el nombre según la posición
            if (posicionJugadorActual === 'arriba') {
                celdaJugadorActual.find('.img-jugador-arriba').attr('src', img);
                celdaJugadorActual.find('.img-jugador-arriba').attr('data-id', id);
                celdaJugadorActual.find('.nombre-jugador-arriba').text(nombre);
            } else {
                celdaJugadorActual.find('.img-jugador-abajo').attr('src', img);
                celdaJugadorActual.find('.img-jugador-abajo').attr('data-id', id);
                celdaJugadorActual.find('.nombre-jugador-abajo').text(nombre);
            }
            
            // Cerrar el modal
            $('#modalSeleccionarJugador').modal('hide');
            
            // Limpiar variables
            celdaJugadorActual = null;
            posicionJugadorActual = null;
            
        } catch (error) {
            console.error('Error al seleccionar jugador:', error);
            $('#modalSeleccionarJugador').modal('hide');
            celdaJugadorActual = null;
            posicionJugadorActual = null;
        }
    });
    
    // Variable para rastrear la celda actual del modal de horarios
    let celdaActual = null;
    
    // Función para obtener el nombre del día
    function getNombreDia(fechaStr) {
        if (!fechaStr) return '';
        const fecha = new Date(fechaStr + 'T00:00:00');
        const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        return dias[fecha.getDay()];
    }
    // Función para obtener "Viernes 20" (día + número)
    function getDiaYNumero(fechaStr) {
        if (!fechaStr) return '';
        const fecha = new Date(fechaStr + 'T00:00:00');
        const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        return dias[fecha.getDay()] + ' ' + fecha.getDate();
    }
    
    // Al abrir el modal de horarios
    $(document).on('click', '.btn-abrir-modal', function() {
        celdaActual = $(this).closest('.seleccion-dia-horario');
        // Si ya hay valores, los pone en el modal
        const dia = celdaActual.data('dia') || '';
        const horario = celdaActual.data('horario') || '';
        $('#dia').val(dia);
        $('.pill-dia').removeClass('active');
        if (dia) {
            $('.pill-dia[data-fecha="' + dia + '"]').addClass('active');
        } else {
            // Por defecto seleccionar el primer día del torneo
            const primerPill = $('.pill-dia').first();
            if (primerPill.length) {
                primerPill.addClass('active');
                $('#dia').val(primerPill.data('fecha'));
            }
        }
        let h = '08', m = '00';
        if (horario) {
            const parts = horario.split(':');
            h = parts[0] || '08';
            m = (parts[1] !== undefined) ? parts[1] : '00';
        }
        $('#hora').val(h);
        $('#minuto').val(m);
        $('.pill-hora').removeClass('active');
        $('.pill-hora[data-val="' + h + '"]').addClass('active');
        $('.pill-minuto').removeClass('active');
        $('.pill-minuto[data-val="' + m + '"]').addClass('active');
        actualizarHorarioArmado();
    });
    
    // Al guardar en el modal de horarios
    $('#formDiaHorario').on('submit', function(e) {
        e.preventDefault();
        const dia = $('#dia').val();
        const hora = $('#hora').val();
        const minuto = $('#minuto').val();
        const horario = hora + ':' + minuto;
        
        if (celdaActual && dia && horario) {
            const fechaFormateada = getDiaYNumero(dia);
            celdaActual.data('dia', dia);
            celdaActual.data('horario', horario);
            
            // Obtener el tipo de resultado (ganadores/perdedores) o tipo de partido (A/B) si existe
            const tipoResultado = celdaActual.data('tipo-resultado');
            const tipoPartido = celdaActual.data('tipo-partido');
            const celdaId = celdaActual.data('celda');
            
            // Si tiene tipo-resultado, actualizar todas las celdas con el mismo tipo
            if (tipoResultado === 'ganadores' || tipoResultado === 'perdedores') {
                // Buscar todas las celdas con el mismo tipo-resultado
                $('.seleccion-dia-horario[data-tipo-resultado="' + tipoResultado + '"]').each(function() {
                    const celda = $(this);
                    celda.data('dia', dia);
                    celda.data('horario', horario);
                    
                    // Mantener el texto del tipo (ganadores/perdedores)
                    const texto = tipoResultado === 'ganadores' ? 'Ganador' : 'Perdedor';
                    celda.html(`
                        <div>
                            <div class="text-muted mb-1" style="font-size:0.85rem;">${texto}</div>
                            <div style="font-size:1.3rem; font-weight:600;"> ${fechaFormateada}</div>
                            <div style="font-size:1.3rem; font-weight:600;"> ${horario}</div>
                            <button type="button" class="btn btn-sm btn-secondary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="${celda.data('celda')}">
                                Editar
                            </button>
                        </div>
                    `);
                });
            } else if (tipoPartido === 'A' || tipoPartido === 'B' || tipoPartido === 'C') {
                // Si tiene tipo-partido, actualizar todas las celdas con el mismo tipo de partido
                // Buscar todas las celdas visibles e invisibles con el mismo tipo de partido
                const celdasConTipo = $('.seleccion-dia-horario[data-tipo-partido="' + tipoPartido + '"]');
                
                console.log('Encontradas ' + celdasConTipo.length + ' celdas con tipo-partido="' + tipoPartido + '"');
                
                celdasConTipo.each(function() {
                    const celda = $(this);
                    const celdaId = celda.data('celda');
                    
                    // Actualizar los datos usando tanto .data() como .attr() para asegurar que se guarden
                    celda.data('dia', dia);
                    celda.data('horario', horario);
                    celda.attr('data-dia', dia);
                    celda.attr('data-horario', horario);
                    
                    // Mantener el texto del tipo de partido
                    let texto = 'Partido';
                    if (tipoPartido === 'A') texto = 'Partido A';
                    else if (tipoPartido === 'B') texto = 'Partido B';
                    else if (tipoPartido === 'C') texto = 'Partido C';
                    
                    // Actualizar el HTML de todas las celdas, visibles o no
                    celda.html(`
                        <div>
                            <div class="text-muted mb-1" style="font-size:0.85rem;">${texto}</div>
                            <div style="font-size:1.3rem; font-weight:600;"> ${fechaFormateada}</div>
                            <div style="font-size:1.3rem; font-weight:600;"> ${horario}</div>
                            <button type="button" class="btn btn-sm btn-secondary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="${celdaId}">
                                Editar
                            </button>
                        </div>
                    `);
                    
                    console.log('Actualizada celda ' + celdaId + ' con tipo-partido="' + tipoPartido + '"');
                });
            } else {
                // Comportamiento normal para celdas sin tipo-resultado
                celdaActual.html(`
                    <div>
                        <div style="font-size:1.3rem; font-weight:600;"> ${fechaFormateada}</div>
                        <div style="font-size:1.3rem; font-weight:600;"> ${horario}</div>
                        <button type="button" class="btn btn-sm btn-secondary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="${celdaId}">
                            Editar
                        </button>
                    </div>
                `);
            }
            
            $('#modalDiaHorario').modal('hide');
            celdaActual = null;
        }
    });
    
    // Función para obtener datos de horario de una celda
    function getHorarioData(celdaId, filaIndex) {
        let celda = null;
        
        if (filaIndex !== undefined && filaIndex !== null) {
            // Obtener de una fila específica
            let filas = $('tbody tr');
            if (filaIndex >= 0 && filaIndex < filas.length) {
                let fila = filas.eq(filaIndex);
                if (fila.length) {
                    celda = fila.find('.seleccion-dia-horario[data-celda="' + celdaId + '"]').filter(function() {
                        return $(this).closest('td').is(':visible');
                    }).first();
                }
            }
        } else {
            // Obtener la primera celda visible
            celda = $('.seleccion-dia-horario[data-celda="' + celdaId + '"]').filter(function() {
                return $(this).closest('td').is(':visible');
            }).first();
        }
        
        if (!celda || celda.length === 0) {
            return { dia: '2000-01-01', horario: '00:00' };
        }
        
        let dia = celda.data('dia');
        let horario = celda.data('horario');
        return {
            dia: (dia && dia !== '' && dia !== 'null') ? dia : '2000-01-01',
            horario: (horario && horario !== '' && horario !== 'null') ? horario : '00:00'
        };
    }
    
    // Función para guardar los datos de la tabla
    function guardarTabla(options) {
        options = options || {};
        let torneoId = $('#torneo_id').val();
        let zona = $('#zona_actual').val() || 'A';
        
        // Obtener jugadores de cada pareja
        let pareja_1_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda1"]').attr('data-id') || '';
        let pareja_1_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda1"]').attr('data-id') || '';
        let pareja_2_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda2"]').attr('data-id') || '';
        let pareja_2_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda2"]').attr('data-id') || '';
        let pareja_3_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda3"]').attr('data-id') || '';
        let pareja_3_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda3"]').attr('data-id') || '';
        let pareja_4_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda4"]').attr('data-id') || '';
        let pareja_4_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda4"]').attr('data-id') || '';
        
        // Verificar si tiene 4 parejas
        let tieneCuatroParejas = !!(pareja_4_idJugadorArriba && pareja_4_idJugadorAbajo);
        
        // Preparar datos base
        let datosEnvio = {
            torneo_id: torneoId,
            zona: zona,
            config_cruces_puntuable_id: $('#config_cruces_puntuable').val() || null,
            tiene_cuatro_parejas: tieneCuatroParejas ? 1 : 0,
            tiene_cuatro_parejas_eliminatoria: 0, // Por ahora siempre 0
            pareja_1_idJugadorArriba: pareja_1_idJugadorArriba,
            pareja_1_idJugadorAbajo: pareja_1_idJugadorAbajo,
            pareja_2_idJugadorArriba: pareja_2_idJugadorArriba,
            pareja_2_idJugadorAbajo: pareja_2_idJugadorAbajo,
            pareja_3_idJugadorArriba: pareja_3_idJugadorArriba,
            pareja_3_idJugadorAbajo: pareja_3_idJugadorAbajo,
            _token: '{{ csrf_token() }}'
        };
        
        if (tieneCuatroParejas) {
            // Formato 4 parejas ELIMINATORIA según el layout:
            // Fila 1: img - partido A (celda 2) - perdedor (celda 3) - ganador (celda 10)
            // Fila 2: partido A (celda 4) - img - ganador (celda 6) - perdedor (celda 11)
            // Fila 3: perdedor (celda 7) - ganador (celda 8) - img - partido B (celda 15)
            // Fila 4: ganador (celda 10) - perdedor (celda 11) - partido B (celda 15) - img
            
            // Partido A: Pareja 1 vs Pareja 2
            let partidoA_P1 = getHorarioData(2, 0); // Celda 2 - Pareja 1 (fila 0)
            let partidoA_P2 = getHorarioData(4, 1); // Celda 4 - Pareja 2 (fila 1)
            
            // Partido B: Pareja 3 vs Pareja 4
            let partidoB_P3 = getHorarioData(15, 2); // Celda 15 - Pareja 3 (fila 2)
            let partidoB_P4 = getHorarioData(15, 3); // Celda 15 - Pareja 4 (fila 3)
            
            // Ganador: puede venir de celda 10 (fila 0 o 3), celda 6 (fila 1), o celda 8 (fila 2)
            let ganador1 = getHorarioData(10, 0); // Celda 10 - Ganador (fila 0)
            let ganador2 = getHorarioData(6, 1);  // Celda 6 - Ganador (fila 1)
            let ganador3 = getHorarioData(8, 2);  // Celda 8 - Ganador (fila 2)
            let ganador4 = getHorarioData(10, 3); // Celda 10 - Ganador (fila 3)
            // Usar el primero que tenga datos válidos
            let ganador = ganador1.dia !== '2000-01-01' ? ganador1 : 
                         (ganador2.dia !== '2000-01-01' ? ganador2 : 
                         (ganador3.dia !== '2000-01-01' ? ganador3 : ganador4));
            
            // Perdedor: puede venir de celda 3 (fila 0), celda 11 (fila 1 o 3), o celda 7 (fila 2)
            let perdedor1 = getHorarioData(3, 0);  // Celda 3 - Perdedor (fila 0)
            let perdedor2 = getHorarioData(11, 1); // Celda 11 - Perdedor (fila 1)
            let perdedor3 = getHorarioData(7, 2);  // Celda 7 - Perdedor (fila 2)
            let perdedor4 = getHorarioData(11, 3); // Celda 11 - Perdedor (fila 3)
            // Usar el primero que tenga datos válidos
            let perdedor = perdedor1.dia !== '2000-01-01' ? perdedor1 : 
                          (perdedor2.dia !== '2000-01-01' ? perdedor2 : 
                          (perdedor3.dia !== '2000-01-01' ? perdedor3 : perdedor4));
            
            // Configurar como formato eliminatoria
            datosEnvio.tiene_cuatro_parejas_eliminatoria = 1;
            
            // Pareja 1
            datosEnvio.pareja_1_partido_1_dia = partidoA_P1.dia; // Partido A
            datosEnvio.pareja_1_partido_1_horario = partidoA_P1.horario;
            datosEnvio.pareja_1_partido_2_dia = perdedor.dia; // Perdedor (celda 3)
            datosEnvio.pareja_1_partido_2_horario = perdedor.horario;
            
            // Pareja 2
            datosEnvio.pareja_2_partido_1_dia = partidoA_P2.dia; // Partido A
            datosEnvio.pareja_2_partido_1_horario = partidoA_P2.horario;
            datosEnvio.pareja_2_partido_2_dia = ganador.dia; // Ganador (celda 6)
            datosEnvio.pareja_2_partido_2_horario = ganador.horario;
            
            // Pareja 3
            datosEnvio.pareja_3_partido_1_dia = perdedor.dia; // Perdedor (celda 7)
            datosEnvio.pareja_3_partido_1_horario = perdedor.horario;
            datosEnvio.pareja_3_partido_2_dia = partidoB_P3.dia; // Partido B
            datosEnvio.pareja_3_partido_2_horario = partidoB_P3.horario;
            
            // Pareja 4
            datosEnvio.pareja_4_idJugadorArriba = pareja_4_idJugadorArriba;
            datosEnvio.pareja_4_idJugadorAbajo = pareja_4_idJugadorAbajo;
            datosEnvio.pareja_4_partido_1_dia = ganador.dia; // Ganador (celda 10)
            datosEnvio.pareja_4_partido_1_horario = ganador.horario;
            datosEnvio.pareja_4_partido_2_dia = partidoB_P4.dia; // Partido B
            datosEnvio.pareja_4_partido_2_horario = partidoB_P4.horario;
            
            // Final y consolación (no aplican en este formato)
            datosEnvio.final_dia = '';
            datosEnvio.final_horario = '';
            datosEnvio.consolacion_dia = '';
            datosEnvio.consolacion_horario = '';
        } else {
            // Formato 3 parejas: todos contra todos
            let horario1 = getHorarioData(2); // Pareja 1 vs Pareja 2
            let horario2 = getHorarioData(3); // Pareja 1 vs Pareja 3
            let horario3 = getHorarioData(4); // Pareja 2 vs Pareja 1 (mismo que horario1)
            let horario4 = getHorarioData(6); // Pareja 2 vs Pareja 3
            let horario5 = getHorarioData(7); // Pareja 3 vs Pareja 1 (mismo que horario2)
            let horario6 = getHorarioData(8); // Pareja 3 vs Pareja 2 (mismo que horario4)
            
            // Pareja 1
            datosEnvio.pareja_1_partido_1_dia = horario1.dia;
            datosEnvio.pareja_1_partido_1_horario = horario1.horario;
            datosEnvio.pareja_1_partido_2_dia = horario2.dia;
            datosEnvio.pareja_1_partido_2_horario = horario2.horario;
            
            // Pareja 2
            datosEnvio.pareja_2_partido_1_dia = horario3.dia;
            datosEnvio.pareja_2_partido_1_horario = horario3.horario;
            datosEnvio.pareja_2_partido_2_dia = horario4.dia;
            datosEnvio.pareja_2_partido_2_horario = horario4.horario;
            
            // Pareja 3
            datosEnvio.pareja_3_partido_1_dia = horario5.dia;
            datosEnvio.pareja_3_partido_1_horario = horario5.horario;
            datosEnvio.pareja_3_partido_2_dia = horario6.dia;
            datosEnvio.pareja_3_partido_2_horario = horario6.horario;
        }
        
        // Enviar datos al backend
        return $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("guardarfechaadmintorneo") }}',
            data: datosEnvio,
            success: function(response) {
                if (response.success) {
                    // No mostrar alert si se llama desde el botón +
                    if (!options.silent) {
                        alert('Zona guardada correctamente');
                    }
                } else {
                    if (!options.silent) {
                        alert('Error al guardar: ' + (response.message || 'Error desconocido'));
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al guardar:', error);
                alert('Error al guardar la zona. Por favor, intente nuevamente.');
            }
        });
    }
    
    // Event listener para el botón Guardar
    $(document).on('click', '#btn-guardar-torneo', function() {
        guardarTabla();
    });
    
    // Event listener para el botón Comenzar Torneo
    $(document).on('click', '#btn-comenzar-torneo', function() {
        let torneoId = $('#torneo_id').val();
        let configCrucesId = $('#config_cruces_puntuable').val() || null;
        if (!torneoId) {
            alert('Por favor, seleccione un torneo primero');
            return;
        }
        if (!configCrucesId) {
            alert('Seleccioná una configuración de cruces antes de comenzar el torneo.');
            return;
        }

        let btn = $(this);
        btn.prop('disabled', true).text('Comenzando...');

        $.ajax({
            type: 'POST',
            url: '{{ route("comenzartorneopuntuable") }}',
            data: {
                torneo_id: torneoId,
                config_cruces_puntuable_id: configCrucesId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                btn.prop('disabled', false).text('Comenzar Torneo');
                if (response && response.success) {
                    // Al comenzar el torneo, ir a la pantalla de resultados (fase de grupos)
                    window.location.href = '{{ route("admintorneoresultados") }}?torneo_id=' + torneoId;
                } else {
                    alert((response && response.message) ? response.message : 'Error al comenzar el torneo');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).text('Comenzar Torneo');
                let msg = 'Error al comenzar el torneo';
                try {
                    let r = JSON.parse(xhr.responseText || '{}');
                    if (r.error_detail) msg += ': ' + r.error_detail;
                    else if (r.message) msg = r.message;
                } catch (e) {}
                console.error('Error al comenzar torneo puntuable:', xhr.responseText || xhr.statusText);
                alert(msg);
            }
        });
    });
    
    // Event listener para el botón Agregar Pareja (+)
    $(document).on('click', '#btn-agregar-pareja', function() {
        // Verificar si hay jugadores seleccionados
        let pareja_1_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda1"]').attr('data-id') || '';
        let pareja_1_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda1"]').attr('data-id') || '';
        let pareja_2_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda2"]').attr('data-id') || '';
        let pareja_2_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda2"]').attr('data-id') || '';
        let pareja_3_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda3"]').attr('data-id') || '';
        let pareja_3_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda3"]').attr('data-id') || '';
        
        let tieneJugadores = !!(pareja_1_idJugadorArriba && pareja_1_idJugadorAbajo) || 
                            !!(pareja_2_idJugadorArriba && pareja_2_idJugadorAbajo) || 
                            !!(pareja_3_idJugadorArriba && pareja_3_idJugadorAbajo);
        
        // Si hay jugadores, guardar primero. Si no, simplemente construir la tabla con 4 parejas
        if (tieneJugadores) {
            // Guardar la información actual (solo jugadores, sin horarios)
            guardarTabla({ silent: true }).then(function() {
                // Recargar los datos desde la base de datos
                let torneoId = $('#torneo_id').val();
                let zona = $('#zona_actual').val() || 'A';
                
                $.ajax({
                    url: '{{ route("obtenerdatoszona") }}',
                    method: 'POST',
                    data: {
                        torneo_id: torneoId,
                        zona: zona,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        // Reconstruir la tabla completa con 4 parejas (esto limpiará todos los horarios)
                        armarTabla(4);
                        
                        // Esperar un momento para que el DOM se actualice
                        setTimeout(function() {
                            if (response.success && response.datos) {
                                // Cargar solo los jugadores, NO los horarios (porque cambió el formato)
                                let datos = response.datos;
                                let jugadoresInfo = datos.jugadores || {};
                                
                                // Cargar jugadores de cada pareja
                                if (datos.parejas && datos.parejas.length > 0) {
                                    // Pareja 1
                                    if (datos.parejas[0]) {
                                        let pareja = datos.parejas[0];
                                        cargarJugadoresEnCelda('celda1', pareja.jugador_1, pareja.jugador_2, jugadoresInfo);
                                    }
                                    
                                    // Pareja 2
                                    if (datos.parejas[1]) {
                                        let pareja = datos.parejas[1];
                                        cargarJugadoresEnCelda('celda2', pareja.jugador_1, pareja.jugador_2, jugadoresInfo);
                                    }
                                    
                                    // Pareja 3
                                    if (datos.parejas[2]) {
                                        let pareja = datos.parejas[2];
                                        cargarJugadoresEnCelda('celda3', pareja.jugador_1, pareja.jugador_2, jugadoresInfo);
                                    }
                                    
                                    // Pareja 4 (si existe)
                                    if (datos.parejas[3]) {
                                        let pareja = datos.parejas[3];
                                        cargarJugadoresEnCelda('celda4', pareja.jugador_1, pareja.jugador_2, jugadoresInfo);
                                    }
                                }
                                // NO cargar horarios porque el formato cambió
                            }
                        }, 100);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error al recargar datos:', error);
                        // En caso de error, construir tabla con 4 parejas
                        armarTabla(4);
                    }
                });
            }).catch(function(error) {
                console.error('Error al guardar antes de agregar pareja:', error);
                // Aún así, construir la tabla con 4 parejas
                armarTabla(4);
            });
        } else {
            // No hay jugadores, simplemente construir la tabla con 4 parejas
            armarTabla(4);
        }
    });
    
    // Event listener para el botón Nueva Zona
    $(document).on('click', '#btn-nueva-zona', function() {
        // Guardar la zona actual antes de crear una nueva
        guardarTabla({ silent: true }).then(function() {
            // Recargar todas las zonas desde la base de datos para asegurar que tenemos la lista actualizada
            let torneoId = $('#torneo_id').val();
            $.ajax({
                url: '{{ route("obtenertodaslaszonas") }}',
                method: 'POST',
                data: {
                    torneo_id: torneoId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success && response.zonas && response.zonas.length > 0) {
                        // Filtrar zonas para obtener solo letras simples (A, B, C, etc.)
                        // Excluir "ganador X" y "perdedor X" (aunque el backend ya debería filtrarlas)
                        zonas = response.zonas.filter(z => z.length === 1 && /^[A-Z]$/.test(z));
                        if (zonas.length === 0) {
                            zonas = ['A'];
                        }
                    } else {
                        zonas = ['A'];
                    }
                    
                    // Crear una nueva zona (B, C, D, etc.)
                    let ultimaZona = zonas[zonas.length - 1];
                    let nuevaZona = String.fromCharCode(ultimaZona.charCodeAt(0) + 1);
                    
                    // Asegurarse de que no esté duplicada
                    if (zonas.indexOf(nuevaZona) === -1) {
                        zonas.push(nuevaZona);
                    }
                    zonaIndex = zonas.indexOf(nuevaZona);
                    
                    // Actualizar la zona actual y cargar (con 3 parejas por defecto)
                    $('#zona_actual').val(nuevaZona);
                    $('#zona-label').text('Zona ' + nuevaZona);
                    
                    // Construir tabla vacía con 3 parejas
                    armarTabla(3);
                },
                error: function() {
                    // Si falla, usar lógica simple
                    // Filtrar zonas para obtener solo letras simples (A, B, C, etc.)
                    let zonasSimples = zonas.filter(z => z.length === 1 && /^[A-Z]$/.test(z));
                    let ultimaZona = zonasSimples.length > 0 ? zonasSimples[zonasSimples.length - 1] : 'A';
                    let nuevaZona = String.fromCharCode(ultimaZona.charCodeAt(0) + 1);
                    
                    // Asegurarse de que no esté duplicada
                    if (zonas.indexOf(nuevaZona) === -1) {
                        zonas.push(nuevaZona);
                    }
                    zonaIndex = zonas.indexOf(nuevaZona);
                    $('#zona_actual').val(nuevaZona);
                    $('#zona-label').text('Zona ' + nuevaZona);
                    armarTabla(3);
                }
            });
        }).catch(function(error) {
            console.error('Error al guardar antes de crear nueva zona:', error);
            // Aún así, crear la nueva zona
            // Filtrar zonas para obtener solo letras simples (A, B, C, etc.)
            let zonasSimples = zonas.filter(z => z.length === 1 && /^[A-Z]$/.test(z));
            let ultimaZona = zonasSimples.length > 0 ? zonasSimples[zonasSimples.length - 1] : 'A';
            let nuevaZona = String.fromCharCode(ultimaZona.charCodeAt(0) + 1);
            
            // Asegurarse de que no esté duplicada
            if (zonas.indexOf(nuevaZona) === -1) {
                zonas.push(nuevaZona);
            }
            zonaIndex = zonas.indexOf(nuevaZona);
            $('#zona_actual').val(nuevaZona);
            $('#zona-label').text('Zona ' + nuevaZona);
            armarTabla(3);
        });
    });
    
    // Event listener para el botón Atrás
    $(document).on('click', '#btn-zona-anterior', function() {
        if (zonaIndex > 0) {
            // Guardar la zona actual antes de cambiar
            guardarTabla({ silent: true }).then(function() {
                zonaIndex--;
                let zona = zonas[zonaIndex];
                $('#zona_actual').val(zona);
                
                // Cargar la zona anterior
                cargarZona();
            }).catch(function(error) {
                console.error('Error al guardar antes de retroceder:', error);
                // Continuar de todas formas
                zonaIndex--;
                let zona = zonas[zonaIndex];
                $('#zona_actual').val(zona);
                cargarZona();
            });
        }
    });
    
    // Event listener para el botón Siguiente
    $(document).on('click', '#btn-zona-siguiente', function() {
        if (zonaIndex < zonas.length - 1) {
            // Guardar la zona actual antes de cambiar
            guardarTabla({ silent: true }).then(function() {
                zonaIndex++;
                let zona = zonas[zonaIndex];
                $('#zona_actual').val(zona);
                
                // Cargar la zona siguiente
                cargarZona();
            }).catch(function(error) {
                console.error('Error al guardar antes de avanzar:', error);
                // Continuar de todas formas
                zonaIndex++;
                let zona = zonas[zonaIndex];
                $('#zona_actual').val(zona);
                cargarZona();
            });
        }
    });
</script>

@endsection