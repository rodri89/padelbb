@extends('bahia_padel/admin/plantilla')

@section('title_header','Torneos')

@section('contenedor')

@php
    // Si $torneo es una colección, tomamos el primer elemento
    $torneo = is_iterable($torneo) && count($torneo) > 0 ? $torneo[0] : $torneo;
@endphp

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
    <br>
    <div class="row justify-content-center">        
            <div class="card shadow bg-white w-100 px-5 py-3 d-flex ">
                <div id="seccion_zonas">        
                    <div class="table-responsive">
                        <table class="table table-bordered text-center w-100">
                            <thead class="thead-light">
                                <tr id="tabla-header">
                                    <th id="zona-label">Zona A</th>
                                    <th class="columna-partido" data-tipo="normal" id="col-header-1">1</th>
                                    <th class="columna-partido" data-tipo="normal" id="col-header-2">2</th>
                                    <th class="columna-partido" data-tipo="normal" id="columna-partido-3">3</th>
                                    <th class="columna-partido" data-tipo="normal" id="columna-partido-4" style="display:none;">4</th>
                                </tr>
                            </thead>
                            <tbody>                                
                                <tr>
                                    <td style="width:1%; white-space:nowrap;">
                                        <div class="d-flex flex-row align-items-center justify-content-between" style="min-width:110px; max-width:280px;">
                                        
                                        <img src="{{ asset('images/jugador_img.png') }}" 
                                            class="rounded-circle img-jugador-seleccionable img-jugador-arriba" 
                                            style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                            data-celda="celda1" data-posicion="arriba">

                                            <div class="d-flex flex-column justify-content-between align-items-center mx-2" style="height:48px;">
                                                <div class="nombre-jugador-arriba" data-celda="celda1" style="font-size:1.2rem;">Seleccionar</div>
                                                <div class="nombre-jugador-abajo" data-celda="celda1" style="font-size:1.2rem;">Seleccionar</div>
                                            </div>
                                            <img src="{{ asset('images/jugador_img.png') }}" 
                                                class="rounded-circle img-jugador-seleccionable img-jugador-abajo" 
                                                style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                                data-celda="celda1" data-posicion="abajo">
                                        </div>
                                    </td>                         
                                    <td>
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
                                            <div class="text-muted mb-1" style="font-size:0.85rem; display:none;">Perdedor</div>
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="3">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>
                                    <td class="columna-partido-4" style="display:none;">
                                        <div class="seleccion-dia-horario" data-celda="10" data-tipo-resultado="ganadores">
                                            <div class="text-muted mb-1" style="font-size:0.85rem; display:none;">Ganador</div>
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="10">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr>
                                <td style="width:1%; white-space:nowrap;">
                                        <div class="d-flex flex-row align-items-center justify-content-between" style="min-width:110px; max-width:280px;">                                        
                                            <img src="{{ asset('images/jugador_img.png') }}" 
                                            class="rounded-circle img-jugador-seleccionable img-jugador-arriba" 
                                            style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                            data-celda="celda2" data-posicion="arriba">

                                            <div class="d-flex flex-column justify-content-between align-items-center mx-2" style="height:48px;">
                                                <div class="nombre-jugador-arriba" data-celda="celda2" style="font-size:1.2rem;">Seleccionar</div>
                                                <div class="nombre-jugador-abajo" data-celda="celda2" style="font-size:1.2rem;">Seleccionar</div>
                                            </div>
                                            <img src="{{ asset('images/jugador_img.png') }}" 
                                                class="rounded-circle img-jugador-seleccionable img-jugador-abajo" 
                                                style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                                data-celda="celda2" data-posicion="abajo">
                                        </div>
                                    </td>                        
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="4" data-tipo-partido="A">
                                            <div class="text-muted mb-1" style="font-size:0.85rem; display:none;">Partido A</div>
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="4">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>                            
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="5">
                                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                                        </div>
                                    </td>                            
                                    <td class="columna-partido-3">
                                        <div class="seleccion-dia-horario" data-celda="6" data-tipo-resultado="ganadores">
                                            <div class="text-muted mb-1" style="font-size:0.85rem; display:none;">Ganador</div>
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="6">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>
                                    <td class="columna-partido-4" style="display:none;">
                                        <div class="seleccion-dia-horario" data-celda="11" data-tipo-resultado="perdedores">
                                            <div class="text-muted mb-1" style="font-size:0.85rem; display:none;">Perdedor</div>
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="11">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                <td style="width:1%; white-space:nowrap;">
                                        <div class="d-flex flex-row align-items-center justify-content-between" style="min-width:110px; max-width:280px;">                                        
                                        <img src="{{ asset('images/jugador_img.png') }}" 
                                            class="rounded-circle img-jugador-seleccionable img-jugador-arriba" 
                                            style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                            data-celda="celda3" data-posicion="arriba">

                                            <div class="d-flex flex-column justify-content-between align-items-center mx-2" style="height:48px;">
                                                <div class="nombre-jugador-arriba" data-celda="celda3" style="font-size:1.2rem;">Seleccionar</div>
                                                <div class="nombre-jugador-abajo" data-celda="celda3" style="font-size:1.2rem;">Seleccionar</div>
                                            </div>
                                            <img src="{{ asset('images/jugador_img.png') }}" 
                                                class="rounded-circle img-jugador-seleccionable img-jugador-abajo" 
                                                style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                                data-celda="celda3" data-posicion="abajo">
                                        </div>
                                    </td>                         
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="7" data-tipo-resultado="perdedores">
                                            <div class="text-muted mb-1" style="font-size:0.85rem; display:none;">Perdedor</div>
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="7">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>                            
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="8" data-tipo-resultado="ganadores">
                                            <div class="text-muted mb-1" style="font-size:0.85rem; display:none;">Ganador</div>
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="8">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>                            
                                    <td class="columna-partido-3">
                                        <div class="seleccion-dia-horario" data-celda="9">
                                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                                        </div>
                                    </td>
                                    <td class="columna-partido-4" style="display:none;">
                                        <div class="seleccion-dia-horario" data-celda="15" data-tipo-partido="B">
                                            <div class="text-muted mb-1" style="font-size:0.85rem; display:none;">Partido B</div>
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="15">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Fila para agregar cuarta pareja -->
                                <tr id="fila-agregar-pareja" style="display:none;">
                                    <td style="width:1%; white-space:nowrap;">
                                        <div class="d-flex flex-row align-items-center justify-content-between" style="min-width:110px; max-width:280px;">                                        
                                        <img src="{{ asset('images/jugador_img.png') }}" 
                                            class="rounded-circle img-jugador-seleccionable img-jugador-arriba" 
                                            style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                            data-celda="celda4" data-posicion="arriba">

                                            <div class="d-flex flex-column justify-content-between align-items-center mx-2" style="height:48px;">
                                                <div class="nombre-jugador-arriba" data-celda="celda4" style="font-size:1.2rem;">Seleccionar</div>
                                                <div class="nombre-jugador-abajo" data-celda="celda4" style="font-size:1.2rem;">Seleccionar</div>
                                            </div>
                                            <img src="{{ asset('images/jugador_img.png') }}" 
                                                class="rounded-circle img-jugador-seleccionable img-jugador-abajo" 
                                                style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                                data-celda="celda4" data-posicion="abajo">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="10" data-tipo-resultado="ganadores">
                                            <div class="text-muted mb-1" style="font-size:0.85rem; display:none;">Ganador</div>
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="10">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="11" data-tipo-resultado="perdedores">
                                            <div class="text-muted mb-1" style="font-size:0.85rem; display:none;">Perdedor</div>
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="11">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>
                                    <td class="columna-partido-3">
                                        <div class="seleccion-dia-horario" data-celda="15" data-tipo-partido="B">
                                            <div class="text-muted mb-1" style="font-size:0.85rem; display:none;">Partido B</div>
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="15">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>
                                    <td class="columna-partido-4" style="display:none;">
                                        <div class="seleccion-dia-horario" data-celda="14">
                                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                                        </div>
                                    </td>
                                </tr>

                                <!-- Botón para agregar cuarta pareja -->
                                <tr id="fila-boton-agregar">
                                    <td colspan="4" class="text-center py-3">
                                        <button type="button" class="btn btn-success btn-lg" id="btn-agregar-pareja" style="font-size:2rem; width:60px; height:60px; border-radius:50%;">
                                            +
                                        </button>
                                    </td>
                                    <td class="columna-partido-4" style="display:none;"></td>
                                </tr>
                                
                            </tbody>
                        </table>
                    </div>

            </div>
        </div>
    </div>
    <!-- Botón Guardar, Nueva zona, Atrás y Siguiente debajo de la tabla -->
    <div class="row justify-content-center mt-4">
        <div class="col-md-8 text-center">
            <button type="button" class="btn btn-secondary btn-lg mr-2" id="btn-zona-anterior">
                Atrás
            </button>
            <button type="button" class="btn btn-secondary btn-lg mr-2" id="btn-nueva-zona">
                Nueva zona
            </button>
            <button type="button" class="btn btn-secondary btn-lg" id="btn-zona-siguiente">
                Siguiente
            </button>
            <div class="w-100 my-3"></div>
            <button type="button" class="btn btn-primary btn-lg mr-2" id="btn-guardar-torneo">
                Guardar
            </button>
            <button type="button" class="btn btn-success btn-lg" id="btn-comenzar-torneo">
                Comenzar Torneo
            </button>
        </div>
    </div>

</div>

    <!-- Acciones -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-6 text-center">
            
        </div>
    </div>

    @include('bahia_padel.modal.jugadores')

<!-- Modal Seleccionar Día y Horario -->
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
            <label for="dia">Día</label>
            <input type="date" class="form-control" id="dia" name="dia">
          </div>
          <div class="form-group">
            <label for="hora">Horario</label>
            <div class="d-flex">
                <select class="form-control mr-2" id="hora" name="hora" style="width:auto;">
                    @for ($h = 0; $h < 24; $h++)
                        <option value="{{ sprintf('%02d', $h) }}">{{ sprintf('%02d', $h) }}</option>
                    @endfor
                </select>
                <span class="align-self-center">:</span>
                <select class="form-control ml-2" id="minuto" name="minuto" style="width:auto;">
                    <option value="00">00</option>
                    <option value="15">15</option>
                    <option value="30">30</option>
                    <option value="45">45</option>
                </select>
            </div>
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

    <!-- Aquí puedes incluir secciones dinámicas según la acción seleccionada -->
<script>
    function formatearRangoFechas(fechaInicio, fechaFin) {
        const meses = [
            "enero", "febrero", "marzo", "abril", "mayo", "junio",
            "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"
        ];
        const [anioI, mesI, diaI] = fechaInicio.split("-");
        const [anioF, mesF, diaF] = fechaFin.split("-");
        // Si el mes es el mismo, muestra solo una vez el mes
        if (mesI === mesF) {
            return `${parseInt(diaI)} ${meses[parseInt(mesI)-1]} - ${parseInt(diaF)} ${meses[parseInt(mesF)-1]}`;
        } else {
            return `${parseInt(diaI)} ${meses[parseInt(mesI)-1]} - ${parseInt(diaF)} ${meses[parseInt(mesF)-1]}`;
        }
    }

    function getNombreDia(fechaStr) {
        const dias = ['DOMINGO', 'LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO'];
        // Parsear manualmente la fecha para evitar problemas de zona horaria
        const [anio, mes, dia] = fechaStr.split('-').map(Number);
        const fecha = new Date(anio, mes - 1, dia); // mes - 1 porque en JS los meses van de 0 a 11
        return dias[fecha.getDay()];
    }

    // --- Mapeo de celdas vinculadas ---
    const celdasVinculadas = {
        2: 4,
        4: 2,
        3: 7,
        7: 3,
        6: 8,
        8: 6
    };
    
    // Variable para controlar si hay 4 parejas
    let tieneCuatroParejas = false;
    
    // Variable para controlar si hay partido 3 en la zona actual
    let tienePartido3 = {};
    // Variable para controlar si hay 4 parejas (formato eliminatoria) en la zona actual
    let tieneCuatroParejasEliminatoria = {};
    
    // Botón para agregar cuarta pareja y configurar formato eliminatoria
    $('#btn-agregar-pareja').on('click', function() {
        let zonaActual = zonas[zonaIndex];
        
        // Si ya tiene 4 parejas en esta zona, no hacer nada
        if (tieneCuatroParejasEliminatoria[zonaActual]) {
            return;
        }
        
        // Marcar que esta zona tiene 4 parejas (formato eliminatoria)
        tieneCuatroParejasEliminatoria[zonaActual] = true;
        tieneCuatroParejas = true;
        
        // Guardar los datos de la zona actual antes de modificar
        datosZonas[zonaActual] = obtenerDatosZona();
        
        // Mostrar la fila de la cuarta pareja
        $('#fila-agregar-pareja').show();
        $('#fila-boton-agregar').hide();
        
        // Mostrar columna 4
        $('#columna-partido-4').show();
        $('.columna-partido-4').show();
        
        // Cambiar los headers de las columnas según el nuevo formato
        $('#col-header-1').text('Partido A');
        $('#col-header-2').text('Perdedor');
        $('#columna-partido-3').text('Ganador');
        $('#columna-partido-4').text('Partido B');
        
        // Configurar estructura según especificación:
        // Fila 1 (Pareja 1): parejas - img - partido A - perdedor - ganador
        // Fila 2 (Pareja 2): parejas - partido A - img - ganador - perdedor
        // Fila 3 (Pareja 3): parejas - perdedor - ganador - img - partido B
        // Fila 4 (Pareja 4): parejas - ganador - perdedor - partido B - img
        
        // Ocultar celdas que no se usan en formato normal
        $('.seleccion-dia-horario[data-celda="3"]').closest('td').hide(); // Pareja 1 partido 3 (normal)
        $('.seleccion-dia-horario[data-celda="6"]').closest('td').hide(); // Pareja 2 partido 3 (normal)
        $('.seleccion-dia-horario[data-celda="7"]').closest('td').hide(); // Pareja 3 partido 1 (normal)
        $('.seleccion-dia-horario[data-celda="8"]').closest('td').hide(); // Pareja 3 partido 2 (normal)
        
        // Fila 1 (Pareja 1): parejas - img - partido A - perdedor - ganador
        // Columna 1 (img): celda 1 - ya está visible
        // Columna 2 (partido A): celda 2 - mostrar y agregar label si no tiene
        let celda2Fila1 = $('tbody tr').eq(0).find('.seleccion-dia-horario[data-celda="2"]');
        celda2Fila1.closest('td').show();
        if (!celda2Fila1.find('.text-muted').length) {
            celda2Fila1.prepend('<div class="text-muted mb-1" style="font-size:0.85rem;">Partido A</div>');
        } else {
            celda2Fila1.find('.text-muted').text('Partido A').show();
        }
        celda2Fila1.attr('data-tipo-partido', 'A');
        
        // Columna 3 (perdedor): celda 3 - mostrar y configurar
        let celda3Fila1 = $('tbody tr').eq(0).find('.seleccion-dia-horario[data-celda="3"]');
        celda3Fila1.closest('td').show();
        celda3Fila1.attr('data-tipo-resultado', 'perdedores');
        if (!celda3Fila1.find('.text-muted').length) {
            celda3Fila1.prepend('<div class="text-muted mb-1" style="font-size:0.85rem;">Perdedor</div>');
        } else {
            celda3Fila1.find('.text-muted').text('Perdedor').show();
        }
        
        // Columna 4 (ganador): celda 10 - mostrar y configurar
        let celda10Fila1 = $('tbody tr').eq(0).find('.seleccion-dia-horario[data-celda="10"]');
        celda10Fila1.closest('td').show();
        celda10Fila1.attr('data-tipo-resultado', 'ganadores');
        if (!celda10Fila1.find('.text-muted').length) {
            celda10Fila1.prepend('<div class="text-muted mb-1" style="font-size:0.85rem;">Ganador</div>');
        } else {
            celda10Fila1.find('.text-muted').text('Ganador').show();
        }
        
        // Fila 2 (Pareja 2): parejas - partido A - img - ganador - perdedor
        // Columna 1 (partido A): celda 4 - mostrar y configurar
        let celda4Fila2 = $('tbody tr').eq(1).find('.seleccion-dia-horario[data-celda="4"]');
        celda4Fila2.closest('td').show();
        celda4Fila2.attr('data-tipo-partido', 'A');
        if (!celda4Fila2.find('.text-muted').length) {
            celda4Fila2.prepend('<div class="text-muted mb-1" style="font-size:0.85rem;">Partido A</div>');
        } else {
            celda4Fila2.find('.text-muted').text('Partido A').show();
        }
        
        // Columna 2 (img): celda 5 - ya está visible
        // Columna 3 (ganador): celda 6 - mostrar y configurar
        let celda6Fila2 = $('tbody tr').eq(1).find('.seleccion-dia-horario[data-celda="6"]');
        celda6Fila2.closest('td').show();
        celda6Fila2.attr('data-tipo-resultado', 'ganadores');
        if (!celda6Fila2.find('.text-muted').length) {
            celda6Fila2.prepend('<div class="text-muted mb-1" style="font-size:0.85rem;">Ganador</div>');
        } else {
            celda6Fila2.find('.text-muted').text('Ganador').show();
        }
        
        // Columna 4 (perdedor): celda 11 - mostrar y configurar
        let celda11Fila2 = $('tbody tr').eq(1).find('.seleccion-dia-horario[data-celda="11"]');
        celda11Fila2.closest('td').show();
        celda11Fila2.attr('data-tipo-resultado', 'perdedores');
        if (!celda11Fila2.find('.text-muted').length) {
            celda11Fila2.prepend('<div class="text-muted mb-1" style="font-size:0.85rem;">Perdedor</div>');
        } else {
            celda11Fila2.find('.text-muted').text('Perdedor').show();
        }
        
        // Fila 3 (Pareja 3): parejas - perdedor - ganador - img - partido B
        // Columna 1 (perdedor): celda 7 - mostrar y configurar
        let celda7Fila3 = $('tbody tr').eq(2).find('.seleccion-dia-horario[data-celda="7"]');
        celda7Fila3.closest('td').show();
        celda7Fila3.attr('data-tipo-resultado', 'perdedores');
        if (!celda7Fila3.find('.text-muted').length) {
            celda7Fila3.prepend('<div class="text-muted mb-1" style="font-size:0.85rem;">Perdedor</div>');
        } else {
            celda7Fila3.find('.text-muted').text('Perdedor').show();
        }
        
        // Columna 2 (ganador): celda 8 - mostrar y configurar
        let celda8Fila3 = $('tbody tr').eq(2).find('.seleccion-dia-horario[data-celda="8"]');
        celda8Fila3.closest('td').show();
        celda8Fila3.attr('data-tipo-resultado', 'ganadores');
        if (!celda8Fila3.find('.text-muted').length) {
            celda8Fila3.prepend('<div class="text-muted mb-1" style="font-size:0.85rem;">Ganador</div>');
        } else {
            celda8Fila3.find('.text-muted').text('Ganador').show();
        }
        
        // Columna 3 (img): celda 9 - ya está visible
        // Columna 4 (partido B): celda 15 - mostrar y configurar
        let celda15Fila3 = $('tbody tr').eq(2).find('.seleccion-dia-horario[data-celda="15"]');
        celda15Fila3.closest('td').show();
        celda15Fila3.attr('data-tipo-partido', 'B');
        if (!celda15Fila3.find('.text-muted').length) {
            celda15Fila3.prepend('<div class="text-muted mb-1" style="font-size:0.85rem;">Partido B</div>');
        } else {
            celda15Fila3.find('.text-muted').text('Partido B').show();
        }
        
        // Fila 4 (Pareja 4): parejas - ganador - perdedor - partido B - img
        // Columna 1 (ganador): celda 10 - mostrar y configurar
        let celda10Fila4 = $('tbody tr').eq(3).find('.seleccion-dia-horario[data-celda="10"]');
        celda10Fila4.closest('td').show();
        celda10Fila4.attr('data-tipo-resultado', 'ganadores');
        if (!celda10Fila4.find('.text-muted').length) {
            celda10Fila4.prepend('<div class="text-muted mb-1" style="font-size:0.85rem;">Ganador</div>');
        } else {
            celda10Fila4.find('.text-muted').text('Ganador').show();
        }
        
        // Columna 2 (perdedor): celda 11 - mostrar y configurar
        let celda11Fila4 = $('tbody tr').eq(3).find('.seleccion-dia-horario[data-celda="11"]');
        celda11Fila4.closest('td').show();
        celda11Fila4.attr('data-tipo-resultado', 'perdedores');
        if (!celda11Fila4.find('.text-muted').length) {
            celda11Fila4.prepend('<div class="text-muted mb-1" style="font-size:0.85rem;">Perdedor</div>');
        } else {
            celda11Fila4.find('.text-muted').text('Perdedor').show();
        }
        
        // Columna 3 (partido B): celda 15 - mostrar y configurar (si está en esta fila)
        let celda15Fila4 = $('tbody tr').eq(3).find('.seleccion-dia-horario[data-celda="15"]');
        if (celda15Fila4.length > 0) {
            celda15Fila4.closest('td').show();
            celda15Fila4.attr('data-tipo-partido', 'B');
            if (!celda15Fila4.find('.text-muted').length) {
                celda15Fila4.prepend('<div class="text-muted mb-1" style="font-size:0.85rem;">Partido B</div>');
            } else {
                celda15Fila4.find('.text-muted').text('Partido B').show();
            }
        }
        
        // Columna 4 (img): celda 14 - ya está visible
        
        // Guardar el estado de esta zona
        datosZonas[zonaActual] = obtenerDatosZona();
        
        // Guardar automáticamente en la base de datos
        guardarZonaEnBD(zonaActual, datosZonas[zonaActual]);
    });

    let celdaActual = null;

    // Al abrir el modal, guarda la celda que abrió el modal
    $(document).on('click', '.btn-abrir-modal', function() {
        celdaActual = $(this).closest('.seleccion-dia-horario');
        // Si ya hay valores, los pone en el modal
        const dia = celdaActual.data('dia') || '';
        const horario = celdaActual.data('horario') || '';
        $('#dia').val(dia);
        if(horario) {
            const [h, m] = horario.split(':');
            $('#hora').val(h);
            $('#minuto').val(m);
        } else {
            $('#hora').val('00');
            $('#minuto').val('00');
        }
    });

    // Al guardar en el modal
    $('#formDiaHorario').on('submit', function(e) {
        e.preventDefault();
        const dia = $('#dia').val();
        const hora = $('#hora').val();
        const minuto = $('#minuto').val();
        const horario = hora + ':' + minuto;
        if (celdaActual && dia && horario) {
            // Parsear fecha manualmente
            const [anio, mes, diaNum] = dia.split('-').map(Number);
            const nombreDia = getNombreDia(dia);
            const fechaFormateada = `${nombreDia}`;
            celdaActual.data('dia', dia);
            celdaActual.data('horario', horario);
            
            // Obtener el tipo de resultado (ganadores/perdedores) o tipo de partido (A/B) si existe
            const tipoResultado = celdaActual.data('tipo-resultado');
            const tipoPartido = celdaActual.data('tipo-partido');
            
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
            } else if (tipoPartido === 'A' || tipoPartido === 'B') {
                // Si tiene tipo-partido, actualizar todas las celdas con el mismo tipo de partido
                $('.seleccion-dia-horario[data-tipo-partido="' + tipoPartido + '"]').each(function() {
                    const celda = $(this);
                    celda.data('dia', dia);
                    celda.data('horario', horario);
                    
                    // Mantener el texto del tipo de partido
                    const texto = tipoPartido === 'A' ? 'Partido A' : 'Partido B';
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
            } else {
                // Comportamiento normal para celdas sin tipo-resultado
                const celdaId = celdaActual.data('celda');
                celdaActual.html(`
                    <div>
                        <div style="font-size:1.3rem; font-weight:600;"> ${fechaFormateada}</div>
                        <div style="font-size:1.3rem; font-weight:600;"> ${horario}</div>
                        <button type="button" class="btn btn-sm btn-secondary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="${celdaId}">
                            Editar
                        </button>
                    </div>
                `);

                // --- ACTUALIZA LA CELDA VINCULADA ---
                const vinculadaId = celdasVinculadas[celdaId];
                if (vinculadaId) {
                    const celdaVinculada = $('.seleccion-dia-horario[data-celda="' + vinculadaId + '"]');
                    celdaVinculada.data('dia', dia);
                    celdaVinculada.data('horario', horario);
                    celdaVinculada.html(`
                        <div>
                            <div style="font-size:1.3rem; font-weight:600;"> ${fechaFormateada}</div>
                            <div style="font-size:1.3rem; font-weight:600;"> ${horario}</div>
                            <button type="button" class="btn btn-sm btn-secondary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="${vinculadaId}">
                                Editar
                            </button>
                        </div>
                    `);
                }
            }
            
            // Guardar automáticamente la zona actual después de modificar un horario
            let zonaActual = zonas[zonaIndex];
            let datosZonaActual = obtenerDatosZona();
            datosZonas[zonaActual] = datosZonaActual;
            guardarZonaEnBD(zonaActual, datosZonaActual);
            
            $('#modalDiaHorario').modal('hide');
        }
    });

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
        const id = $(this).data('id'); // Obtén el ID del jugador seleccionado
        
        if (!celdaJugadorActual || !posicionJugadorActual) {
            console.error('Error: celdaJugadorActual o posicionJugadorActual no está definido');
            return;
        }
        
        try {
            // Busca el div central de la celda y actualiza la imagen y el nombre
            if (posicionJugadorActual === 'arriba') {
                celdaJugadorActual.find('.img-jugador-arriba').attr('src', img);
                celdaJugadorActual.find('.img-jugador-arriba').attr('data-id', id);
                celdaJugadorActual.find('.nombre-jugador-arriba').text(nombre);
            } else {
                celdaJugadorActual.find('.img-jugador-abajo').attr('src', img);
                celdaJugadorActual.find('.img-jugador-abajo').attr('data-id', id);
                celdaJugadorActual.find('.nombre-jugador-abajo').text(nombre);
            }
            
            // Cerrar el modal primero
            $('#modalSeleccionarJugador').modal('hide');
            
            // Guardar automáticamente la zona actual después de seleccionar un jugador (con delay para no bloquear)
            setTimeout(function() {
                try {
                    let zonaActual = zonas[zonaIndex];
                    let datosZonaActual = obtenerDatosZona();
                    datosZonas[zonaActual] = datosZonaActual;
                    guardarZonaEnBD(zonaActual, datosZonaActual);
                } catch (error) {
                    console.error('Error al guardar después de seleccionar jugador:', error);
                }
            }, 100);
        } catch (error) {
            console.error('Error al seleccionar jugador:', error);
            $('#modalSeleccionarJugador').modal('hide');
        }
    });

    // Cargar grupos guardados desde el backend
    let gruposGuardados = @json($grupos ?? []);
    
    // Agrupar grupos por zona
    let gruposPorZona = {};
    gruposGuardados.forEach(function(grupo) {
        if (!gruposPorZona[grupo.zona]) {
            gruposPorZona[grupo.zona] = [];
        }
        gruposPorZona[grupo.zona].push(grupo);
    });
    
    // Obtener todas las zonas únicas
    let zonasGuardadas = Object.keys(gruposPorZona).sort();
    let zonas = zonasGuardadas.length > 0 ? zonasGuardadas : ['A']; // Almacena las letras de las zonas creadas
    let datosZonas = {}; // Aquí se guardan los datos de cada zona
    let zonaIndex = 0; // Índice de la zona actual
    
    // Función para procesar grupos de una zona y preparar los datos
    function procesarGruposZona(grupos) {
        if (!grupos || grupos.length === 0) return null;
        
        // Primero ordenar todos los grupos por ID para mantener el orden de creación
        grupos.sort(function(a, b) {
            return a.id - b.id;
        });
        
        console.log('Grupos ordenados por ID:', grupos.map(g => ({ 
            id: g.id, 
            jugador_1: g.jugador_1, 
            jugador_2: g.jugador_2, 
            fecha: g.fecha, 
            horario: g.horario,
            partido_id: g.partido_id 
        })));
        
        // Los grupos se guardan en orden: pareja 1 (2 grupos), pareja 2 (2 grupos), pareja 3 (2 grupos)
        // Si hay grupos con partido_id, pueden estar duplicados (cada partido tiene 2 grupos)
        // Primero, agrupar por partido_id para identificar partidos únicos
        let gruposPorPartido = {};
        let gruposSinPartido = [];
        
        grupos.forEach(function(grupo) {
            if (grupo.partido_id && grupo.partido_id !== 0 && grupo.partido_id !== null) {
                // Grupo con partido_id (partido ya creado)
                if (!gruposPorPartido[grupo.partido_id]) {
                    gruposPorPartido[grupo.partido_id] = [];
                }
                gruposPorPartido[grupo.partido_id].push(grupo);
            } else {
                // Grupo sin partido_id (borrador)
                gruposSinPartido.push(grupo);
            }
        });
        
        // Si hay grupos con partido_id, procesarlos primero
        // Cada partido tiene 2 grupos (una para cada pareja), necesitamos identificar las parejas únicas
        let parejas = {};
        
        // Procesar grupos con partido_id: agrupar por pareja y partido_id
        Object.keys(gruposPorPartido).forEach(function(partidoId) {
            let gruposPartido = gruposPorPartido[partidoId];
            gruposPartido.forEach(function(grupo) {
                let key = grupo.jugador_1 + '_' + grupo.jugador_2;
                if (!parejas[key]) {
                    parejas[key] = [];
                }
                // Solo agregar si no existe ya un grupo con el mismo partido_id para esta pareja
                let existePartido = parejas[key].some(function(g) {
                    return g.partido_id === grupo.partido_id;
                });
                if (!existePartido) {
                    parejas[key].push(grupo);
                }
            });
        });
        
        // Procesar grupos sin partido_id (borradores)
        gruposSinPartido.forEach(function(grupo) {
            let key = grupo.jugador_1 + '_' + grupo.jugador_2;
            if (!parejas[key]) {
                parejas[key] = [];
            }
            parejas[key].push(grupo);
        });
        
        // Convertir a array y ordenar para mantener consistencia
        let parejasArray = Object.values(parejas);
        
        // Separar parejas normales de grupos con jugador_1 = 0 o jugador_2 = 0 (partidos "libres")
        let parejasNormales = parejasArray.filter(function(pareja) {
            return pareja.length > 0 && pareja[0].jugador_1 && pareja[0].jugador_2 && 
                   pareja[0].jugador_1 !== 0 && pareja[0].jugador_2 !== 0;
        });
        
        // Grupos con jugador_1 = 0 o jugador_2 = 0 (partidos "libres")
        let gruposLibres = [];
        grupos.forEach(function(grupo) {
            if ((grupo.jugador_1 === 0 || grupo.jugador_1 === null) || 
                (grupo.jugador_2 === 0 || grupo.jugador_2 === null)) {
                gruposLibres.push(grupo);
            }
        });
        
        // Ordenar grupos libres por ID para mantener el orden
        gruposLibres.sort(function(a, b) {
            return a.id - b.id;
        });
        
        console.log('Grupos libres (jugador 0) encontrados:', gruposLibres.length, gruposLibres.map(g => ({
            id: g.id,
            jugador_1: g.jugador_1,
            jugador_2: g.jugador_2,
            fecha: g.fecha,
            horario: g.horario
        })));
        
        if (parejasNormales.length === 0) {
            console.error('Error: No se encontraron parejas válidas');
            return null;
        }
        
        // Detectar si hay 4 parejas
        let tieneCuatroParejasEnDatos = parejasNormales.length >= 4;
        console.log('Parejas encontradas:', parejasNormales.length, '¿Tiene 4 parejas?', tieneCuatroParejasEnDatos);
        console.log('Grupos libres (jugador 0):', gruposLibres.length);
        
        // Usar parejas normales para el procesamiento
        parejasArray = parejasNormales;
        
        // Ordenar parejas por el ID del primer grupo para mantener el orden
        parejasArray.sort(function(a, b) {
            return a[0].id - b[0].id;
        });
        
        @php
            $jugadoresArray = [];
            foreach($jugadores as $j) {
                $foto = $j->foto ?? 'images/jugador_img.png';
                // Asegurar que la ruta sea correcta
                if (!str_starts_with($foto, 'http') && !str_starts_with($foto, '/')) {
                    $foto = asset($foto);
                } else if (str_starts_with($foto, 'images/')) {
                    $foto = asset($foto);
                }
                $jugadoresArray[] = [
                    'id' => $j->id,
                    'nombre' => $j->nombre ?? '',
                    'apellido' => $j->apellido ?? '',
                    'foto' => $foto
                ];
            }
        @endphp
        let jugadores = @json($jugadoresArray);
        
        let datos = {
            jugadores: {},
            horarios: {},
            horariosPorFila: {}, // Para formato 4 parejas: almacenar horarios por fila y celda
            tieneCuatroParejas: tieneCuatroParejasEnDatos,
            tieneCuatroParejasEliminatoria: false
        };
        
        // Procesar cada pareja (puede ser 3 o 4 parejas)
        parejasArray.forEach(function(parejaGrupos, index) {
            // Si hay más de 3 parejas, la cuarta pareja va en celda4
            let celda = index < 3 ? ('celda' + (index + 1)) : 'celda4';
            let primerGrupo = parejaGrupos[0];
            
            // Debug: mostrar información de la pareja
            console.log('Procesando pareja ' + (index + 1) + ':', {
                celda: celda,
                jugador_1: primerGrupo.jugador_1,
                jugador_2: primerGrupo.jugador_2,
                grupos: parejaGrupos.map(g => ({ 
                    id: g.id, 
                    partido_id: g.partido_id, 
                    fecha: g.fecha, 
                    horario: g.horario,
                    jugador_1: g.jugador_1,
                    jugador_2: g.jugador_2
                }))
            });
            
            // Preparar datos de jugadores
            let jugador1 = jugadores.find(j => j.id == primerGrupo.jugador_1);
            let jugador2 = jugadores.find(j => j.id == primerGrupo.jugador_2);
            
            // Asegurar que ambos jugadores tengan sus imágenes
            let img1 = '{{ asset('images/jugador_img.png') }}';
            let img2 = '{{ asset('images/jugador_img.png') }}';
            
            if (jugador1 && jugador1.foto) {
                img1 = jugador1.foto;
                // Si la ruta no es absoluta, hacerla absoluta
                if (!img1.startsWith('http') && !img1.startsWith('/')) {
                    img1 = '/' + img1;
                }
            }
            
            if (jugador2 && jugador2.foto) {
                img2 = jugador2.foto;
                // Si la ruta no es absoluta, hacerla absoluta
                if (!img2.startsWith('http') && !img2.startsWith('/')) {
                    img2 = '/' + img2;
                }
            }
            
            datos.jugadores[celda] = {
                arriba: {
                    id: primerGrupo.jugador_1,
                    nombre: jugador1 ? (jugador1.nombre + ' ' + jugador1.apellido) : '',
                    img: img1
                },
                abajo: {
                    id: primerGrupo.jugador_2,
                    nombre: jugador2 ? (jugador2.nombre + ' ' + jugador2.apellido) : '',
                    img: img2
                }
            };
            
            // Preparar datos de horarios
            // Ordenar los grupos de la pareja: primero por partido_id (si existe), luego por ID
            // Esto asegura que el primer grupo guardado sea el partido 1 y el segundo sea el partido 2
            parejaGrupos.sort(function(a, b) {
                // Si ambos tienen partido_id, ordenar por partido_id
                if (a.partido_id && b.partido_id && a.partido_id !== b.partido_id) {
                    return a.partido_id - b.partido_id;
                }
                // Si solo uno tiene partido_id, el que no tiene partido_id va primero (borrador)
                if (a.partido_id && !b.partido_id) return 1;
                if (!a.partido_id && b.partido_id) return -1;
                // Si ambos tienen el mismo partido_id o ninguno tiene, ordenar por ID
                return a.id - b.id;
            });
            
            // Mapear los grupos a las celdas correctas
            // El orden de guardado en el backend es:
            // Pareja 1: grupoA1 (partido 1) -> celda 2, grupoA2 (partido 2) -> celda 3
            // Pareja 2: grupoA3 (partido 1) -> celda 4, grupoA4 (partido 2) -> celda 6
            // Pareja 3: grupoA5 (partido 1) -> celda 7, grupoA6 (partido 2) -> celda 8
            
            if (index === 0) {
                // Pareja 1: img - partido A - partido 1 sin jugadores - partido 2 sin jugadores
                // Fila 1: Columna 1 (celda 1) = img, Columna 2 (celda 2) = partido A, Columna 3 (celda 3) = grupo libre 1, Columna 4 (celda 10) = grupo libre 2
                if (tieneCuatroParejasEnDatos) {
                    // Formato 4 parejas
                    if (parejaGrupos[0]) {
                        datos.horarios[2] = { dia: parejaGrupos[0].fecha, horario: parejaGrupos[0].horario };
                        datos.horariosPorFila['fila1_celda2'] = { dia: parejaGrupos[0].fecha, horario: parejaGrupos[0].horario };
                    }
                    // Asignar grupos libres: celda 3 (columna 3) y celda 10 (columna 4, fila 1)
                    if (gruposLibres.length > 0) {
                        datos.horarios[3] = { dia: gruposLibres[0].fecha, horario: gruposLibres[0].horario };
                        datos.horariosPorFila['fila1_celda3'] = { dia: gruposLibres[0].fecha, horario: gruposLibres[0].horario };
                    }
                    if (gruposLibres.length > 1) {
                        datos.horariosPorFila['fila1_celda10'] = { dia: gruposLibres[1].fecha, horario: gruposLibres[1].horario };
                    }
                } else {
                    // Formato normal: celdas 2 y 3
                    if (parejaGrupos[0]) {
                        datos.horarios[2] = { dia: parejaGrupos[0].fecha, horario: parejaGrupos[0].horario };
                    }
                    if (parejaGrupos[1]) {
                        datos.horarios[3] = { dia: parejaGrupos[1].fecha, horario: parejaGrupos[1].horario };
                    }
                }
            } else if (index === 1) {
                // Pareja 2: partido A - img - partido 1 sin jugadores - partido 2 sin jugadores
                // Fila 2: Columna 1 (celda 4) = partido A, Columna 2 (celda 5) = img, Columna 3 (celda 6) = grupo libre 3, Columna 4 (celda 11) = grupo libre 4
                if (tieneCuatroParejasEnDatos) {
                    // Formato 4 parejas
                    if (parejaGrupos[0]) {
                        datos.horarios[4] = { dia: parejaGrupos[0].fecha, horario: parejaGrupos[0].horario };
                        datos.horariosPorFila['fila2_celda4'] = { dia: parejaGrupos[0].fecha, horario: parejaGrupos[0].horario };
                    }
                    // Asignar grupos libres: celda 6 (columna 3) y celda 11 (columna 4, fila 2)
                    if (gruposLibres.length > 2) {
                        datos.horarios[6] = { dia: gruposLibres[2].fecha, horario: gruposLibres[2].horario };
                        datos.horariosPorFila['fila2_celda6'] = { dia: gruposLibres[2].fecha, horario: gruposLibres[2].horario };
                    }
                    if (gruposLibres.length > 3) {
                        datos.horariosPorFila['fila2_celda11'] = { dia: gruposLibres[3].fecha, horario: gruposLibres[3].horario };
                    }
                } else {
                    // Formato normal: celdas 4 y 6
                    if (parejaGrupos[0]) {
                        datos.horarios[4] = { dia: parejaGrupos[0].fecha, horario: parejaGrupos[0].horario };
                    }
                    if (parejaGrupos[1]) {
                        datos.horarios[6] = { dia: parejaGrupos[1].fecha, horario: parejaGrupos[1].horario };
                    }
                }
            } else if (index === 2) {
                // Pareja 3: partido 1 sin jugadores - partido 2 sin jugadores - img - partido B
                // Fila 3: Columna 1 (celda 7) = grupo libre 5, Columna 2 (celda 8) = grupo libre 6, Columna 3 (celda 9) = img, Columna 4 (celda 15) = partido B
                if (tieneCuatroParejasEnDatos) {
                    // Formato 4 parejas - NO hay partido de pareja 3 aquí, solo grupos libres y partido B
                    // Asignar grupos libres: celda 7 (columna 1) y celda 8 (columna 2)
                    if (gruposLibres.length > 4) {
                        datos.horarios[7] = { dia: gruposLibres[4].fecha, horario: gruposLibres[4].horario };
                        datos.horariosPorFila['fila3_celda7'] = { dia: gruposLibres[4].fecha, horario: gruposLibres[4].horario };
                    }
                    if (gruposLibres.length > 5) {
                        datos.horarios[8] = { dia: gruposLibres[5].fecha, horario: gruposLibres[5].horario };
                        datos.horariosPorFila['fila3_celda8'] = { dia: gruposLibres[5].fecha, horario: gruposLibres[5].horario };
                    }
                    // Partido B se asigna desde pareja 4, pero va en fila 3, columna 4
                } else {
                    // Formato normal: celdas 7 y 8
                    if (parejaGrupos[0]) {
                        datos.horarios[7] = { dia: parejaGrupos[0].fecha, horario: parejaGrupos[0].horario };
                    }
                    if (parejaGrupos[1]) {
                        datos.horarios[8] = { dia: parejaGrupos[1].fecha, horario: parejaGrupos[1].horario };
                    }
                }
            } else if (index === 3) {
                // Pareja 4: partido 1 sin jugadores - partido 2 sin jugadores - partido B - img
                // Fila 4: Columna 1 (celda 10) = grupo libre 7, Columna 2 (celda 11) = grupo libre 8, Columna 3 (celda 15) = partido B, Columna 4 (celda 14) = img
                // El partido B viene de parejaGrupos[0] si existe
                if (parejaGrupos[0]) {
                    datos.horariosPorFila['fila3_celda15'] = { dia: parejaGrupos[0].fecha, horario: parejaGrupos[0].horario }; // Partido B va en fila 3
                    datos.horariosPorFila['fila4_celda15'] = { dia: parejaGrupos[0].fecha, horario: parejaGrupos[0].horario }; // También en fila 4
                }
                // Asignar grupos libres: celda 10 (columna 1, fila 4) y celda 11 (columna 2, fila 4)
                if (gruposLibres.length > 6) {
                    datos.horariosPorFila['fila4_celda10'] = { dia: gruposLibres[6].fecha, horario: gruposLibres[6].horario };
                }
                if (gruposLibres.length > 7) {
                    datos.horariosPorFila['fila4_celda11'] = { dia: gruposLibres[7].fecha, horario: gruposLibres[7].horario };
                }
            }
        });
        
        // Detectar si hay formato eliminatorio
        // Formato eliminatorio: tiene horarios en celdas 12, 13, o 14 (además de 10, 11, 15)
        // Formato normal con 4 parejas: solo tiene horarios en celdas 10, 11, 15
        if (tieneCuatroParejasEnDatos && (datos.horarios[12] || datos.horarios[13] || 
            (datos.horarios[14] && datos.horarios[14].horario && datos.horarios[14].horario !== '00:00'))) {
            datos.tieneCuatroParejasEliminatoria = true;
        } else if (tieneCuatroParejasEnDatos) {
            // Formato normal con 4 parejas
            datos.tieneCuatroParejasEliminatoria = false;
        }
        
        return datos;
    }
    
    // Inicializar datos de zonas desde grupos guardados
    zonas.forEach(function(zona) {
        if (gruposPorZona[zona]) {
            // No cargar directamente en la vista aquí, solo preparar los datos
            let datos = procesarGruposZona(gruposPorZona[zona]);
            datosZonas[zona] = datos;
            
            // Verificar si esta zona tiene partido 3 (hay datos en celdas 3, 6, 9 o 12)
            if (datos && datos.horarios) {
                let tienePartido3EnDatos = !!(datos.horarios[3] || datos.horarios[6] || datos.horarios[9] || datos.horarios[12]);
                if (tienePartido3EnDatos) {
                    tienePartido3[zona] = true;
                } else {
                    tienePartido3[zona] = true; // Por defecto, siempre tiene partido 3
                }
            } else {
                tienePartido3[zona] = true; // Por defecto, siempre tiene partido 3
            }
            
            // Verificar si esta zona tiene 4 parejas y formato eliminatoria
            if (datos && datos.tieneCuatroParejas) {
                tieneCuatroParejasEliminatoria[zona] = datos.tieneCuatroParejasEliminatoria || false;
                // Si hay 4 parejas pero no es formato eliminatorio, aún así debe mostrarse la fila 4
                // La función procesarGruposZona ya debería haberla agregado si hay 4 parejas
                console.log('Zona', zona, 'tiene 4 parejas:', datos.tieneCuatroParejas, 'eliminatoria:', datos.tieneCuatroParejasEliminatoria);
            } else {
                tieneCuatroParejasEliminatoria[zona] = false;
            }
        } else {
            datosZonas[zona] = null;
            tieneCuatroParejasEliminatoria[zona] = false;
            tienePartido3[zona] = true; // Por defecto, siempre tiene partido 3
        }
    });

    function obtenerDatosZona() {
        try {
        function getJugadorData(celda, posicion) {
            let img = $(`.img-jugador-${posicion}[data-celda="${celda}"]`);
            let nombre = $(`.nombre-jugador-${posicion}[data-celda="${celda}"]`);
            return {
                id: img.attr('data-id') || null,
                nombre: nombre.text() || null,
                img: img.attr('src') || null
            };
        }
        
        // Función helper para obtener datos de una celda visible
        function getHorarioData(celdaId, filaIndex) {
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
                    let dia = celda.data('dia');
                    let horario = celda.data('horario');
                    return {
                        dia: dia || null,
                        horario: horario || null
                    };
                }
            } catch (error) {
                console.error('Error en getHorarioData para celda ' + celdaId + ', fila ' + filaIndex + ':', error);
            }
            return { dia: null, horario: null };
        }
        
        let datos = {
            jugadores: {
                celda1: {
                    arriba: getJugadorData('celda1', 'arriba'),
                    abajo: getJugadorData('celda1', 'abajo')
                },
                celda2: {
                    arriba: getJugadorData('celda2', 'arriba'),
                    abajo: getJugadorData('celda2', 'abajo')
                },
                celda3: {
                    arriba: getJugadorData('celda3', 'arriba'),
                    abajo: getJugadorData('celda3', 'abajo')
                }
            },
            horarios: {},
            horariosPorFila: {}, // Para zonas de 4 parejas
            tieneCuatroParejas: tieneCuatroParejas,
            tieneCuatroParejasEliminatoria: tieneCuatroParejasEliminatoria[zonas[zonaIndex]] || false
        };
        
        // Obtener horarios de celdas normales (1-9)
        for (let i = 1; i <= 9; i++) {
            let horarioData = getHorarioData(i);
            datos.horarios[i] = horarioData;
        }
        
        // Si hay 4 parejas, agregar datos de celda4 y horarios adicionales
        if (tieneCuatroParejas) {
            datos.jugadores.celda4 = {
                arriba: getJugadorData('celda4', 'arriba'),
                abajo: getJugadorData('celda4', 'abajo')
            };
            
            // Para zonas de 4 parejas, obtener datos por fila para evitar conflictos
            if (!tieneCuatroParejasEliminatoria[zonas[zonaIndex]]) {
                try {
                    // Formato normal de 4 parejas: obtener datos por fila
                    // Fila 1: celda 2, 3, 10
                    let horario2Fila1 = getHorarioData(2, 0);
                    let horario3Fila1 = getHorarioData(3, 0);
                    let horario10Fila1 = getHorarioData(10, 0);
                    datos.horariosPorFila['fila1_celda2'] = horario2Fila1;
                    datos.horariosPorFila['fila1_celda3'] = horario3Fila1;
                    datos.horariosPorFila['fila1_celda10'] = horario10Fila1;
                    datos.horarios[2] = horario2Fila1;
                    datos.horarios[3] = horario3Fila1;
                    
                    // Fila 2: celda 4, 6, 11
                    let horario4Fila2 = getHorarioData(4, 1);
                    let horario6Fila2 = getHorarioData(6, 1);
                    let horario11Fila2 = getHorarioData(11, 1);
                    datos.horariosPorFila['fila2_celda4'] = horario4Fila2;
                    datos.horariosPorFila['fila2_celda6'] = horario6Fila2;
                    datos.horariosPorFila['fila2_celda11'] = horario11Fila2;
                    datos.horarios[4] = horario4Fila2;
                    datos.horarios[6] = horario6Fila2;
                    
                    // Fila 3: celda 7, 8, 15
                    let horario7Fila3 = getHorarioData(7, 2);
                    let horario8Fila3 = getHorarioData(8, 2);
                    let horario15Fila3 = getHorarioData(15, 2);
                    datos.horariosPorFila['fila3_celda7'] = horario7Fila3;
                    datos.horariosPorFila['fila3_celda8'] = horario8Fila3;
                    datos.horariosPorFila['fila3_celda15'] = horario15Fila3;
                    datos.horarios[7] = horario7Fila3;
                    datos.horarios[8] = horario8Fila3;
                    
                    // Fila 4: celda 10, 11, 15
                    let horario10Fila4 = getHorarioData(10, 3);
                    let horario11Fila4 = getHorarioData(11, 3);
                    let horario15Fila4 = getHorarioData(15, 3);
                    datos.horariosPorFila['fila4_celda10'] = horario10Fila4;
                    datos.horariosPorFila['fila4_celda11'] = horario11Fila4;
                    datos.horariosPorFila['fila4_celda15'] = horario15Fila4;
                    
                    // También guardar en horarios generales (usar valores de fila 4 para celdas compartidas)
                    datos.horarios[10] = horario10Fila1.dia ? horario10Fila1 : horario10Fila4;
                    datos.horarios[11] = horario11Fila2.dia ? horario11Fila2 : horario11Fila4;
                    datos.horarios[15] = horario15Fila3.dia ? horario15Fila3 : horario15Fila4;
                } catch (error) {
                    console.error('Error al obtener datos de zona de 4 parejas:', error);
                    // Fallback: obtener datos normalmente
                    datos.horarios[10] = getHorarioData(10);
                    datos.horarios[11] = getHorarioData(11);
                    datos.horarios[15] = getHorarioData(15);
                }
            } else {
                // Formato eliminatoria: obtener datos normalmente
                datos.horarios[10] = getHorarioData(10);
                datos.horarios[11] = getHorarioData(11);
                datos.horarios[12] = getHorarioData(12);
                datos.horarios[13] = getHorarioData(13);
                datos.horarios[14] = getHorarioData(14);
                datos.horarios[15] = getHorarioData(15);
            }
        }
        
        return datos;
        } catch (error) {
            console.error('Error en obtenerDatosZona:', error);
            // Devolver estructura básica en caso de error
            return {
                jugadores: {
                    celda1: { arriba: null, abajo: null },
                    celda2: { arriba: null, abajo: null },
                    celda3: { arriba: null, abajo: null }
                },
                horarios: {},
                horariosPorFila: {},
                tieneCuatroParejas: false,
                tieneCuatroParejasEliminatoria: false
            };
        }
    }

    function restaurarDatosZona(datos) {
        // Declarar zonaActual una sola vez al inicio de la función
        let zonaActual = zonas[zonaIndex];
        
        if (!datos) {
            // Si no hay datos, inicializar tabla con formato por defecto (3 partidos, 3 parejas)
            // Limpiar jugadores
            $('.img-jugador-arriba, .img-jugador-abajo').attr('src', '{{ asset('images/jugador_img.png') }}').removeAttr('data-id');
            $('.nombre-jugador-arriba, .nombre-jugador-abajo').text('Seleccionar');
            
            // Limpiar celdas con botones (partidos 1 y 2 para las 3 parejas)
            $('.seleccion-dia-horario[data-celda="2"], .seleccion-dia-horario[data-celda="3"], .seleccion-dia-horario[data-celda="4"], .seleccion-dia-horario[data-celda="6"], .seleccion-dia-horario[data-celda="7"], .seleccion-dia-horario[data-celda="8"]').removeData('dia').removeData('horario').html(`
                <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="">
                    Seleccionar día/horario
                </button>
            `);
            
            // Las celdas 1, 5, 9 muestran una imagen (imágenes de las parejas)
            $('.seleccion-dia-horario[data-celda="1"], .seleccion-dia-horario[data-celda="5"], .seleccion-dia-horario[data-celda="9"]').removeData('dia').removeData('horario').html(`
                <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
            `);
            
            // Asegurar formato por defecto: 3 partidos, 3 parejas
            $('#fila-agregar-pareja').hide();
            $('#fila-boton-agregar').show();
            $('#columna-partido-4').hide();
            $('.columna-partido-4').hide();
            $('#columna-partido-3').show().text('3');
            $('.columna-partido-3').show();
            tieneCuatroParejas = false;
            tieneCuatroParejasEliminatoria[zonaActual] = false;
            $('.columna-partido[data-tipo="final"], .columna-partido[data-tipo="consolacion"]').hide();
            
            // Asegurar visibilidad correcta de celdas
            $('.seleccion-dia-horario[data-celda="1"], .seleccion-dia-horario[data-celda="2"], .seleccion-dia-horario[data-celda="3"]').closest('td').show();
            $('.seleccion-dia-horario[data-celda="4"], .seleccion-dia-horario[data-celda="5"], .seleccion-dia-horario[data-celda="6"]').closest('td').show();
            $('.seleccion-dia-horario[data-celda="7"], .seleccion-dia-horario[data-celda="8"], .seleccion-dia-horario[data-celda="9"]').closest('td').show();
            $('.seleccion-dia-horario[data-celda="10"], .seleccion-dia-horario[data-celda="11"], .seleccion-dia-horario[data-celda="12"], .seleccion-dia-horario[data-celda="13"], .seleccion-dia-horario[data-celda="14"], .seleccion-dia-horario[data-celda="15"]').closest('td').hide();
            
            return;
        }
        
        // PRIMERO: Limpiar todos los datos anteriores para evitar que queden datos de la zona anterior
        // Limpiar jugadores
        for (let celda of ['celda1', 'celda2', 'celda3', 'celda4']) {
            $(`.img-jugador-arriba[data-celda="${celda}"], .img-jugador-abajo[data-celda="${celda}"]`).attr('src', '{{ asset('images/jugador_img.png') }}').removeAttr('data-id');
            $(`.nombre-jugador-arriba[data-celda="${celda}"], .nombre-jugador-abajo[data-celda="${celda}"]`).text('Seleccionar');
        }
        
        // Limpiar todos los horarios (remover data y contenido)
        for (let i = 1; i <= 15; i++) {
            $('.seleccion-dia-horario[data-celda="' + i + '"]').removeData('dia').removeData('horario');
        }
        
        // NO cambiar la estructura de la tabla aquí - eso ya lo hace actualizarZona()
        // Solo verificar y actualizar los flags internos, pero NO mostrar/ocultar elementos
        // Verificar si hay datos en las celdas de partido 3 (3, 6, 9, 12)
        let tienePartido3EnDatos = false;
        if (datos.horarios) {
            tienePartido3EnDatos = !!(datos.horarios[3] || datos.horarios[6] || datos.horarios[9] || datos.horarios[12]);
        }
        
        // Actualizar flag interno, pero NO cambiar la estructura de la tabla
        if (tienePartido3EnDatos) {
            tienePartido3[zonaActual] = true;
        }
        
        // Verificar si hay formato eliminatoria (4 parejas con Final/Consolación)
        // Solo actualizar el flag, NO cambiar la estructura de la tabla (eso lo hace actualizarZona)
        if (datos.tieneCuatroParejasEliminatoria || (datos.tieneCuatroParejas && datos.jugadores.celda4 && (datos.horarios && (datos.horarios[10] || datos.horarios[11] || datos.horarios[12] || datos.horarios[13] || datos.horarios[15])))) {
            tieneCuatroParejasEliminatoria[zonaActual] = true;
            tieneCuatroParejas = true;
        } else if (datos.tieneCuatroParejas && datos.jugadores.celda4) {
            // Formato antiguo con 4 parejas pero sin eliminatoria
            tieneCuatroParejas = true;
            tieneCuatroParejasEliminatoria[zonaActual] = false;
        } else {
            tieneCuatroParejasEliminatoria[zonaActual] = false;
            tieneCuatroParejas = false;
            $('#columna-partido-4').hide();
            $('.columna-partido-4').hide();
            $('.columna-partido[data-tipo="final"], .columna-partido[data-tipo="consolacion"]').hide();
        }
        
        // Jugadores
        for (let celda of ['celda1', 'celda2', 'celda3', 'celda4']) {
            if (!datos.jugadores[celda]) continue;
            
            // Restaurar jugador arriba
            let jugadorArriba = datos.jugadores[celda].arriba;
            let imgElemArriba = $(`.img-jugador-arriba[data-celda="${celda}"]`);
            let nombreElemArriba = $(`.nombre-jugador-arriba[data-celda="${celda}"]`);
            
            if (jugadorArriba && jugadorArriba.id) {
                let imgSrcArriba = jugadorArriba.img && jugadorArriba.img !== '' ? jugadorArriba.img : '{{ asset('images/jugador_img.png') }}';
                if (!imgSrcArriba.startsWith('http') && !imgSrcArriba.startsWith('/')) {
                    imgSrcArriba = '/' + imgSrcArriba;
                }
                imgElemArriba.attr('src', imgSrcArriba).attr('data-id', jugadorArriba.id);
                nombreElemArriba.text(jugadorArriba.nombre || 'Seleccionado');
            } else {
                imgElemArriba.attr('src', '{{ asset('images/jugador_img.png') }}').removeAttr('data-id');
                nombreElemArriba.text('Seleccionar');
            }
            
            // Restaurar jugador abajo
            let jugadorAbajo = datos.jugadores[celda].abajo;
            let imgElemAbajo = $(`.img-jugador-abajo[data-celda="${celda}"]`);
            let nombreElemAbajo = $(`.nombre-jugador-abajo[data-celda="${celda}"]`);
            
            if (jugadorAbajo && jugadorAbajo.id) {
                let imgSrcAbajo = jugadorAbajo.img && jugadorAbajo.img !== '' ? jugadorAbajo.img : '{{ asset('images/jugador_img.png') }}';
                if (!imgSrcAbajo.startsWith('http') && !imgSrcAbajo.startsWith('/')) {
                    imgSrcAbajo = '/' + imgSrcAbajo;
                }
                imgElemAbajo.attr('src', imgSrcAbajo).attr('data-id', jugadorAbajo.id);
                nombreElemAbajo.text(jugadorAbajo.nombre || 'Seleccionado');
            } else {
                imgElemAbajo.attr('src', '{{ asset('images/jugador_img.png') }}').removeAttr('data-id');
                nombreElemAbajo.text('Seleccionar');
            }
        }
        // Horarios - Restaurar TODAS las celdas correctamente
        let celdasConHorario = [2, 3, 4, 6, 7, 8];
        let celdasConImagen = [1, 5, 9]; // Celdas que siempre muestran imagen
        if (tieneCuatroParejasEliminatoria[zonaActual]) {
            // Formato eliminatoria según estructura:
            // Columna 1: celda 1 (img Pareja 1), celda 4 (btn partido 1 Pareja 2), celda 11 (perdedores Pareja 3), celda 10 (ganadores Pareja 4)
            // Columna 2: celda 2 (btn partido 1 Pareja 1), celda 5 (img Pareja 2), celda 12 (ganadores Pareja 3), celda 13 (perdedores Pareja 4)
            // Columna 3: celda 11 (perdedores Pareja 1), celda 10 (ganadores Pareja 2), celda 9 (img Pareja 3), celda 15 (btn partido 2 Pareja 4)
            // Columna 4: celda 10 (ganadores Pareja 1), celda 11 (perdedores Pareja 2), celda 15 (btn partido 2 Pareja 3), celda 14 (img Pareja 4)
            celdasConHorario = [2, 4, 10, 11, 12, 13, 15];
            celdasConImagen = [1, 5, 9, 14]; // En formato eliminatoria, celda 14 también es imagen
        } else if (tieneCuatroParejas && !tieneCuatroParejasEliminatoria[zonaActual]) {
            // Formato con 4 parejas pero sin eliminatoria:
            // Fila 1: Columna 2 (celda 2) = partido pareja 1, Columna 3 (celda 3) = grupo libre, Columna 4 (celda 10) = grupo libre
            // Fila 2: Columna 1 (celda 4) = partido pareja 2, Columna 3 (celda 6) = grupo libre, Columna 4 (celda 11) = grupo libre
            // Fila 3: Columna 1 (celda 7) = partido pareja 3, Columna 2 (celda 8) = grupo libre, Columna 4 (celda 15) = grupo libre
            // Fila 4: Columna 1 (celda 10) = partido pareja 4, Columna 2 (celda 11) = grupo libre, Columna 3 (celda 15) = grupo libre, Columna 4 (celda 14) = imagen
            celdasConHorario = [2, 3, 4, 6, 7, 8, 10, 11, 15];
            celdasConImagen = [1, 5, 9, 14]; // Celda 14 es imagen de pareja 4
        } else if (tieneCuatroParejas) {
            celdasConHorario = [2, 4, 7, 10, 11, 12, 13, 14, 15];
        }
        
        // Función helper para mostrar horario en una celda
        function mostrarHorarioEnCelda(celda, dia, horario, celdaId) {
            if (!dia || !horario) return;
            
            const tipoResultado = celda.data('tipo-resultado');
            const [anio, mes, diaNum] = dia.split('-').map(Number);
            const nombreDia = getNombreDia(dia);
            const fechaFormateada = `${nombreDia}`;
            
            celda.data('dia', dia);
            celda.data('horario', horario);
            
            if (tipoResultado === 'ganadores' || tipoResultado === 'perdedores') {
                const texto = tipoResultado === 'ganadores' ? 'Ganadores' : 'Perdedores';
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
            } else {
                celda.html(`
                    <div>
                        <div style="font-size:1.3rem; font-weight:600;"> ${fechaFormateada}</div>
                        <div style="font-size:1.3rem; font-weight:600;"> ${horario}</div>
                        <button type="button" class="btn btn-sm btn-secondary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="${celdaId}">
                            Editar
                        </button>
                    </div>
                `);
            }
        }
        
        // Cuando hay 4 parejas, las celdas 10, 11, 15 aparecen en múltiples filas con diferentes valores
        // Procesar fila por fila usando horariosPorFila
        // Verificar si esta zona tiene 4 parejas (puede estar en datos o en el flag global)
        let tieneCuatroParejasEnEstaZona = tieneCuatroParejas || (datos && datos.tieneCuatroParejas);
        
        // Si hay horariosPorFila, usarlos (datos procesados desde BD)
        // Si no hay horariosPorFila pero hay datos en horarios, crear horariosPorFila a partir de horarios
        let tieneHorariosPorFila = datos.horariosPorFila && Object.keys(datos.horariosPorFila).length > 0;
        
        if (!tieneHorariosPorFila && datos.horarios && tieneCuatroParejasEnEstaZona) {
            // Crear horariosPorFila a partir de horarios para zonas guardadas sin horariosPorFila
            datos.horariosPorFila = {};
            // Mapear según la estructura de 4 parejas:
            // Fila 1: celda 2 (partido A), celda 3 (grupo libre), celda 10 (grupo libre en columna 4)
            if (datos.horarios[2]) datos.horariosPorFila['fila1_celda2'] = datos.horarios[2];
            if (datos.horarios[3]) datos.horariosPorFila['fila1_celda3'] = datos.horarios[3];
            if (datos.horarios[10]) datos.horariosPorFila['fila1_celda10'] = datos.horarios[10];
            // Fila 2: celda 4 (partido A), celda 6 (grupo libre), celda 11 (grupo libre en columna 4)
            if (datos.horarios[4]) datos.horariosPorFila['fila2_celda4'] = datos.horarios[4];
            if (datos.horarios[6]) datos.horariosPorFila['fila2_celda6'] = datos.horarios[6];
            if (datos.horarios[11]) datos.horariosPorFila['fila2_celda11'] = datos.horarios[11];
            // Fila 3: celda 7 (grupo libre), celda 8 (grupo libre), celda 15 (partido B en columna 4)
            if (datos.horarios[7]) datos.horariosPorFila['fila3_celda7'] = datos.horarios[7];
            if (datos.horarios[8]) datos.horariosPorFila['fila3_celda8'] = datos.horarios[8];
            if (datos.horarios[15]) datos.horariosPorFila['fila3_celda15'] = datos.horarios[15];
            // Fila 4: celda 10 (grupo libre en columna 1), celda 11 (grupo libre en columna 2), celda 15 (partido B en columna 3)
            // Nota: las celdas 10, 11, 15 pueden tener valores diferentes en diferentes filas
            // Por ahora, si hay datos en horarios[10], [11], [15], asumimos que son para la fila 4
            // (esto puede necesitar ajuste según cómo se guardaron los datos)
            if (datos.horarios[10] && !datos.horariosPorFila['fila1_celda10']) {
                datos.horariosPorFila['fila4_celda10'] = datos.horarios[10];
            }
            if (datos.horarios[11] && !datos.horariosPorFila['fila2_celda11']) {
                datos.horariosPorFila['fila4_celda11'] = datos.horarios[11];
            }
            if (datos.horarios[15] && !datos.horariosPorFila['fila3_celda15']) {
                datos.horariosPorFila['fila4_celda15'] = datos.horarios[15];
            }
            tieneHorariosPorFila = true;
        }
        
        if (tieneCuatroParejasEnEstaZona && !tieneCuatroParejasEliminatoria[zonaActual] && tieneHorariosPorFila) {
            let filas = $('tbody tr');
            
            // Fila 1 (pareja 1): celda 1 (img), celda 2 (partido A), celda 3 (grupo libre), celda 10 (grupo libre en columna 4)
            if (filas.length > 0) {
                let fila1 = filas.eq(0);
                if (datos.horariosPorFila['fila1_celda2']) {
                    // Buscar la celda 2 en la fila 1 (columna 2)
                    let celda2Fila1 = fila1.find('.seleccion-dia-horario[data-celda="2"]').first();
                    if (celda2Fila1.length && celda2Fila1.closest('td').is(':visible')) {
                        mostrarHorarioEnCelda(celda2Fila1, datos.horariosPorFila['fila1_celda2'].dia, datos.horariosPorFila['fila1_celda2'].horario, 2);
                    }
                }
                if (datos.horariosPorFila['fila1_celda3']) {
                    let celda3Fila1 = fila1.find('.seleccion-dia-horario[data-celda="3"]').first();
                    if (celda3Fila1.length && celda3Fila1.closest('td').is(':visible')) {
                        mostrarHorarioEnCelda(celda3Fila1, datos.horariosPorFila['fila1_celda3'].dia, datos.horariosPorFila['fila1_celda3'].horario, 3);
                    }
                }
                if (datos.horariosPorFila['fila1_celda10']) {
                    let celda10Fila1 = fila1.find('.seleccion-dia-horario[data-celda="10"]').first();
                    if (celda10Fila1.length && celda10Fila1.closest('td').is(':visible')) {
                        mostrarHorarioEnCelda(celda10Fila1, datos.horariosPorFila['fila1_celda10'].dia, datos.horariosPorFila['fila1_celda10'].horario, 10);
                    }
                }
            }
            
            // Fila 2 (pareja 2): celda 4 (partido A), celda 5 (img), celda 6 (grupo libre), celda 11 (grupo libre en columna 4)
            if (filas.length > 1) {
                let fila2 = filas.eq(1);
                if (datos.horariosPorFila['fila2_celda4']) {
                    let celda4Fila2 = fila2.find('.seleccion-dia-horario[data-celda="4"]').first();
                    if (celda4Fila2.length && celda4Fila2.closest('td').is(':visible')) {
                        mostrarHorarioEnCelda(celda4Fila2, datos.horariosPorFila['fila2_celda4'].dia, datos.horariosPorFila['fila2_celda4'].horario, 4);
                    }
                }
                if (datos.horariosPorFila['fila2_celda6']) {
                    let celda6Fila2 = fila2.find('.seleccion-dia-horario[data-celda="6"]').first();
                    if (celda6Fila2.length && celda6Fila2.closest('td').is(':visible')) {
                        mostrarHorarioEnCelda(celda6Fila2, datos.horariosPorFila['fila2_celda6'].dia, datos.horariosPorFila['fila2_celda6'].horario, 6);
                    }
                }
                if (datos.horariosPorFila['fila2_celda11']) {
                    let celda11Fila2 = fila2.find('.seleccion-dia-horario[data-celda="11"]').first();
                    if (celda11Fila2.length && celda11Fila2.closest('td').is(':visible')) {
                        mostrarHorarioEnCelda(celda11Fila2, datos.horariosPorFila['fila2_celda11'].dia, datos.horariosPorFila['fila2_celda11'].horario, 11);
                    }
                }
            }
            
            // Fila 3 (pareja 3): celda 7 (grupo libre), celda 8 (grupo libre), celda 9 (img), celda 15 (partido B en columna 4)
            if (filas.length > 2) {
                let fila3 = filas.eq(2);
                if (datos.horariosPorFila['fila3_celda7']) {
                    let celda7Fila3 = fila3.find('.seleccion-dia-horario[data-celda="7"]').first();
                    if (celda7Fila3.length && celda7Fila3.closest('td').is(':visible')) {
                        mostrarHorarioEnCelda(celda7Fila3, datos.horariosPorFila['fila3_celda7'].dia, datos.horariosPorFila['fila3_celda7'].horario, 7);
                    }
                }
                if (datos.horariosPorFila['fila3_celda8']) {
                    let celda8Fila3 = fila3.find('.seleccion-dia-horario[data-celda="8"]').first();
                    if (celda8Fila3.length && celda8Fila3.closest('td').is(':visible')) {
                        mostrarHorarioEnCelda(celda8Fila3, datos.horariosPorFila['fila3_celda8'].dia, datos.horariosPorFila['fila3_celda8'].horario, 8);
                    }
                }
                if (datos.horariosPorFila['fila3_celda15']) {
                    let celda15Fila3 = fila3.find('.seleccion-dia-horario[data-celda="15"]').first();
                    if (celda15Fila3.length && celda15Fila3.closest('td').is(':visible')) {
                        mostrarHorarioEnCelda(celda15Fila3, datos.horariosPorFila['fila3_celda15'].dia, datos.horariosPorFila['fila3_celda15'].horario, 15);
                    }
                }
            }
            
            // Fila 4 (pareja 4): celda 10 (grupo libre en columna 1), celda 11 (grupo libre en columna 2), celda 15 (partido B en columna 3), celda 14 (img en columna 4)
            if (filas.length > 3) {
                let fila4 = filas.eq(3);
                if (datos.horariosPorFila['fila4_celda10']) {
                    let celda10Fila4 = fila4.find('.seleccion-dia-horario[data-celda="10"]').first();
                    if (celda10Fila4.length && celda10Fila4.closest('td').is(':visible')) {
                        mostrarHorarioEnCelda(celda10Fila4, datos.horariosPorFila['fila4_celda10'].dia, datos.horariosPorFila['fila4_celda10'].horario, 10);
                    }
                }
                if (datos.horariosPorFila['fila4_celda11']) {
                    let celda11Fila4 = fila4.find('.seleccion-dia-horario[data-celda="11"]').first();
                    if (celda11Fila4.length && celda11Fila4.closest('td').is(':visible')) {
                        mostrarHorarioEnCelda(celda11Fila4, datos.horariosPorFila['fila4_celda11'].dia, datos.horariosPorFila['fila4_celda11'].horario, 11);
                    }
                }
                if (datos.horariosPorFila['fila4_celda15']) {
                    let celda15Fila4 = fila4.find('.seleccion-dia-horario[data-celda="15"]').first();
                    if (celda15Fila4.length && celda15Fila4.closest('td').is(':visible')) {
                        mostrarHorarioEnCelda(celda15Fila4, datos.horariosPorFila['fila4_celda15'].dia, datos.horariosPorFila['fila4_celda15'].horario, 15);
                    }
                }
            }
        }
        
        // Procesar celdas normales (no en formato 4 parejas o ya procesadas)
        for (let i = 1; i <= 15; i++) {
            // Si ya procesamos en formato 4 parejas, saltar celdas que ya fueron procesadas
            if (tieneCuatroParejas && !tieneCuatroParejasEliminatoria[zonaActual] && datos.horariosPorFila) {
                // Saltar celdas que ya fueron procesadas por fila
                let skipCeldas = [2, 3, 4, 6, 7, 8, 10, 11, 15];
                if (skipCeldas.includes(i)) {
                    continue;
                }
            }
            
            // Obtener TODAS las instancias de esta celda (puede haber múltiples en diferentes filas)
            let celdas = $('.seleccion-dia-horario[data-celda="' + i + '"]');
            let dia = datos.horarios[i]?.dia;
            let horario = datos.horarios[i]?.horario;
            
            // Procesar cada instancia de la celda
            celdas.each(function() {
                let celda = $(this);
                // Verificar si tiene tipo-resultado
                const tipoResultado = celda.data('tipo-resultado');
                
                // Solo procesar celdas que están visibles o que deberían estar visibles
                let celdaVisible = celda.closest('td').is(':visible');
            
            if (celdasConImagen.includes(i)) {
                // Celdas que siempre muestran imagen
                if (celdaVisible) {
                    celda.removeData('dia').removeData('horario').html(`
                        <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                    `);
                }
            } else if (celdasConHorario.includes(i)) {
                // Celdas que deben tener botones de horario
                if (celdaVisible) {
                    if (dia && horario) {
                        const [anio, mes, diaNum] = dia.split('-').map(Number);
                        const nombreDia = getNombreDia(dia);
                        const fechaFormateada = `${nombreDia}`;
                        celda.data('dia', dia);
                        celda.data('horario', horario);
                        
                        if (tipoResultado === 'ganadores' || tipoResultado === 'perdedores') {
                            const texto = tipoResultado === 'ganadores' ? 'Ganadores' : 'Perdedores';
                            celda.html(`
                                <div>
                                    <div class="text-muted mb-1" style="font-size:0.85rem;">${texto}</div>
                                    <div style="font-size:1.3rem; font-weight:600;"> ${fechaFormateada}</div>
                                    <div style="font-size:1.3rem; font-weight:600;"> ${horario}</div>
                                    <button type="button" class="btn btn-sm btn-secondary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="${i}">
                                        Editar
                                    </button>
                                </div>
                            `);
                        } else {
                            celda.html(`
                                <div>
                                    <div style="font-size:1.3rem; font-weight:600;"> ${fechaFormateada}</div>
                                    <div style="font-size:1.3rem; font-weight:600;"> ${horario}</div>
                                    <button type="button" class="btn btn-sm btn-secondary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="${i}">
                                        Editar
                                    </button>
                                </div>
                            `);
                        }
                    } else {
                        // No tiene horario, restaurar botón por defecto
                        celda.removeData('dia').removeData('horario');
                        if (tipoResultado === 'ganadores' || tipoResultado === 'perdedores') {
                            const texto = tipoResultado === 'ganadores' ? 'Ganadores' : 'Perdedores';
                            celda.html(`
                                <div>
                                    <div class="text-muted mb-1" style="font-size:0.85rem;">${texto}</div>
                                    <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="${i}">
                                        Seleccionar día/horario
                                    </button>
                                </div>
                            `);
                        } else {
                            celda.html(`
                                <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="${i}">
                                    Seleccionar día/horario
                                </button>
                            `);
                        }
                    }
                }
            } else {
                // Celdas que no se usan en este formato - limpiar si están visibles
                if (celdaVisible) {
                    celda.removeData('dia').removeData('horario').html('');
                }
            }
            }); // Cerrar el each
        }
        
        // Asegurar que todas las celdas visibles con tipo-resultado tengan su estructura correcta
        // Esto es importante para las celdas con tipo-resultado que pueden haber perdido su estructura
        $('.seleccion-dia-horario[data-tipo-resultado]').each(function() {
            let celda = $(this);
            let tipoResultado = celda.data('tipo-resultado');
            let celdaId = celda.data('celda');
            
            // Solo restaurar si la celda está visible y no tiene contenido o tiene contenido incorrecto
            if (celda.closest('td').is(':visible')) {
                let contenido = celda.html().trim();
                let tieneTextoGanadoresPerdedores = contenido.includes('Ganadores') || contenido.includes('Perdedores');
                
                if (contenido === '' || (!tieneTextoGanadoresPerdedores && (tipoResultado === 'ganadores' || tipoResultado === 'perdedores'))) {
                    let texto = tipoResultado === 'ganadores' ? 'Ganadores' : tipoResultado === 'perdedores' ? 'Perdedores' : '';
                    if (texto) {
                        // Verificar si tiene horario guardado
                        let dia = celda.data('dia');
                        let horario = celda.data('horario');
                        
                        if (dia && horario) {
                            const [anio, mes, diaNum] = dia.split('-').map(Number);
                            const nombreDia = getNombreDia(dia);
                            const fechaFormateada = `${nombreDia}`;
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
                        } else {
                            celda.html(`
                                <div>
                                    <div class="text-muted mb-1" style="font-size:0.85rem;">${texto}</div>
                                    <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="${celdaId}">
                                        Seleccionar día/horario
                                    </button>
                                </div>
                            `);
                        }
                    }
                }
            }
        });
    }

    function actualizarZona() {
        let zonaActual = zonas[zonaIndex];
        $('#zona-label').text('Zona ' + zonaActual);
        $('#zona_actual').val(zonaActual);
        
        // PRIMERO: Ocultar TODAS las celdas para limpiar el estado anterior
        for (let i = 1; i <= 15; i++) {
            $('.seleccion-dia-horario[data-celda="' + i + '"]').closest('td').hide();
        }
        
        // Limpiar todos los datos de jugadores y horarios
        $('.img-jugador-arriba, .img-jugador-abajo').attr('src', '{{ asset('images/jugador_img.png') }}').removeAttr('data-id');
        $('.nombre-jugador-arriba, .nombre-jugador-abajo').text('Seleccionar');
        for (let i = 1; i <= 15; i++) {
            $('.seleccion-dia-horario[data-celda="' + i + '"]').removeData('dia').removeData('horario');
        }
        
        // Ocultar fila 4 y columna 4 por defecto
        $('#fila-agregar-pareja').hide();
        $('#fila-boton-agregar').show();
        $('#columna-partido-4').hide();
        $('.columna-partido-4').hide();
        
        // Asegurar que la columna 3 esté visible por defecto
        $('#columna-partido-3').show().text('3');
        $('.columna-partido-3').show();
        
        // CONSULTAR LA BASE DE DATOS para obtener los datos reales de esta zona
        let torneoId = $('#torneo_id').val();
        if (!torneoId) {
            console.error('No hay torneo_id');
            return;
        }
        
        $.ajax({
            url: '{{ route("obtenerdatoszona") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                torneo_id: torneoId,
                zona: zonaActual
            },
            success: function(response) {
                if (response.success && response.datos) {
                    construirTablaDesdeBD(response.datos);
                } else {
                    // Si no hay datos, mostrar tabla vacía con formato por defecto (3 parejas)
                    construirTablaVacia();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al obtener datos de zona:', error);
                // En caso de error, mostrar tabla vacía
                construirTablaVacia();
            }
        });
    }
    
    function construirTablaDesdeBD(datos) {
        // Establecer flags globales
        tieneCuatroParejas = datos.tieneCuatroParejas || false;
        tieneCuatroParejasEliminatoria[datos.zona] = datos.tieneCuatroParejasEliminatoria || false;
        
        let numParejas = datos.parejas ? datos.parejas.length : 0;
        
        // Si tiene 4 parejas, mostrar fila 4 y columna 4
        if (numParejas >= 4 && !datos.tieneCuatroParejasEliminatoria) {
            $('#fila-agregar-pareja').show();
            $('#fila-boton-agregar').hide();
            $('#columna-partido-4').show().text('4');
            $('.columna-partido-4').show();
            
            // Construir tabla con 4 parejas (formato normal)
            construirTabla4Parejas(datos);
        } else if (datos.tieneCuatroParejasEliminatoria) {
            // Formato eliminatoria
            $('#fila-agregar-pareja').show();
            $('#fila-boton-agregar').hide();
            $('#columna-partido-4').show().text('4');
            $('.columna-partido-4').show();
            construirTablaEliminatoria(datos);
        } else {
            // Formato normal con 3 parejas
            $('#fila-agregar-pareja').hide();
            $('#fila-boton-agregar').show();
            $('#columna-partido-4').hide();
            $('.columna-partido-4').hide();
            construirTabla3Parejas(datos);
        }
    }
    
    function construirTabla3Parejas(datos) {
        // Mostrar celdas para 3 parejas
        $('tbody tr').eq(0).find('.seleccion-dia-horario[data-celda="1"]').closest('td').show();
        $('tbody tr').eq(0).find('.seleccion-dia-horario[data-celda="2"]').closest('td').show();
        $('tbody tr').eq(0).find('.seleccion-dia-horario[data-celda="3"]').closest('td').show();
        $('tbody tr').eq(1).find('.seleccion-dia-horario[data-celda="4"]').closest('td').show();
        $('tbody tr').eq(1).find('.seleccion-dia-horario[data-celda="5"]').closest('td').show();
        $('tbody tr').eq(1).find('.seleccion-dia-horario[data-celda="6"]').closest('td').show();
        $('tbody tr').eq(2).find('.seleccion-dia-horario[data-celda="7"]').closest('td').show();
        $('tbody tr').eq(2).find('.seleccion-dia-horario[data-celda="8"]').closest('td').show();
        $('tbody tr').eq(2).find('.seleccion-dia-horario[data-celda="9"]').closest('td').show();
        
        // Cargar datos de parejas
        if (datos.parejas && datos.parejas.length > 0) {
            datos.parejas.forEach(function(pareja, index) {
                if (index >= 3) return; // Solo procesar primeras 3 parejas
                
                let celda = 'celda' + (index + 1);
                cargarJugadoresEnCelda(celda, pareja.jugador_1, pareja.jugador_2, datos.jugadores);
                
                // Cargar horarios según el formato de 3 parejas
                // Pareja 1: grupos[0] -> celda 2, grupos[1] -> celda 3
                // Pareja 2: grupos[0] -> celda 4, grupos[1] -> celda 6
                // Pareja 3: grupos[0] -> celda 7, grupos[1] -> celda 8
                if (pareja.grupos && pareja.grupos.length > 0) {
                    if (index === 0) {
                        if (pareja.grupos[0]) cargarHorarioEnCelda(2, pareja.grupos[0].fecha, pareja.grupos[0].horario);
                        if (pareja.grupos[1]) cargarHorarioEnCelda(3, pareja.grupos[1].fecha, pareja.grupos[1].horario);
                    } else if (index === 1) {
                        if (pareja.grupos[0]) cargarHorarioEnCelda(4, pareja.grupos[0].fecha, pareja.grupos[0].horario);
                        if (pareja.grupos[1]) cargarHorarioEnCelda(6, pareja.grupos[1].fecha, pareja.grupos[1].horario);
                    } else if (index === 2) {
                        if (pareja.grupos[0]) cargarHorarioEnCelda(7, pareja.grupos[0].fecha, pareja.grupos[0].horario);
                        if (pareja.grupos[1]) cargarHorarioEnCelda(8, pareja.grupos[1].fecha, pareja.grupos[1].horario);
                    }
                }
            });
        }
    }
    
    function construirTabla4Parejas(datos) {
        // Mostrar celdas para 4 parejas (formato normal)
        // Fila 1: celda 1, 2, 3, 10
        $('tbody tr').eq(0).find('.seleccion-dia-horario[data-celda="1"]').closest('td').show();
        $('tbody tr').eq(0).find('.seleccion-dia-horario[data-celda="2"]').closest('td').show();
        $('tbody tr').eq(0).find('.seleccion-dia-horario[data-celda="3"]').closest('td').show();
        $('tbody tr').eq(0).find('.seleccion-dia-horario[data-celda="10"]').closest('td').show();
        
        // Fila 2: celda 4, 5, 6, 11
        $('tbody tr').eq(1).find('.seleccion-dia-horario[data-celda="4"]').closest('td').show();
        $('tbody tr').eq(1).find('.seleccion-dia-horario[data-celda="5"]').closest('td').show();
        $('tbody tr').eq(1).find('.seleccion-dia-horario[data-celda="6"]').closest('td').show();
        $('tbody tr').eq(1).find('.seleccion-dia-horario[data-celda="11"]').closest('td').show();
        
        // Fila 3: celda 7, 8, 9, 15
        $('tbody tr').eq(2).find('.seleccion-dia-horario[data-celda="7"]').closest('td').show();
        $('tbody tr').eq(2).find('.seleccion-dia-horario[data-celda="8"]').closest('td').show();
        $('tbody tr').eq(2).find('.seleccion-dia-horario[data-celda="9"]').closest('td').show();
        $('tbody tr').eq(2).find('.seleccion-dia-horario[data-celda="15"]').closest('td').show();
        
        // Fila 4: celda 10, 11, 15, 14
        $('#fila-agregar-pareja').find('.seleccion-dia-horario[data-celda="10"]').closest('td').show();
        $('#fila-agregar-pareja').find('.seleccion-dia-horario[data-celda="11"]').closest('td').show();
        $('#fila-agregar-pareja').find('.seleccion-dia-horario[data-celda="15"]').closest('td').show();
        $('#fila-agregar-pareja').find('.seleccion-dia-horario[data-celda="14"]').closest('td').show();
        
        // Cargar datos de parejas
        if (datos.parejas && datos.parejas.length > 0) {
            datos.parejas.forEach(function(pareja, index) {
                if (index >= 4) return;
                
                let celda = index < 3 ? ('celda' + (index + 1)) : 'celda4';
                cargarJugadoresEnCelda(celda, pareja.jugador_1, pareja.jugador_2, datos.jugadores);
                
                // Mapear grupos según el formato de 4 parejas
                // Cada pareja tiene su partido principal (el primer grupo guardado)
                if (pareja.grupos && pareja.grupos.length > 0) {
                    if (index === 0) {
                        // Pareja 1: partido A (primer grupo) -> celda 2
                        if (pareja.grupos[0]) cargarHorarioEnCelda(2, pareja.grupos[0].fecha, pareja.grupos[0].horario, 0);
                    } else if (index === 1) {
                        // Pareja 2: partido A (primer grupo) -> celda 4
                        if (pareja.grupos[0]) cargarHorarioEnCelda(4, pareja.grupos[0].fecha, pareja.grupos[0].horario, 1);
                    } else if (index === 3) {
                        // Pareja 4: partido B (primer grupo) -> celda 15 (fila 3, columna 4) y también en fila 4, columna 3
                        if (pareja.grupos[0]) {
                            cargarHorarioEnCelda(15, pareja.grupos[0].fecha, pareja.grupos[0].horario, 2);
                            cargarHorarioEnCelda(15, pareja.grupos[0].fecha, pareja.grupos[0].horario, 3);
                        }
                    }
                    // Pareja 3 no tiene partido propio, solo grupos libres
                }
            });
            
            // Cargar grupos libres (jugador_1 = 0 o jugador_2 = 0)
            // Formato según especificación:
            // Fila 1: img - partido A - partido 1 sin jugadores (gruposLibres[0]) - partido 2 sin jugadores (gruposLibres[1])
            // Fila 2: partido A - img - partido 1 sin jugadores (gruposLibres[2]) - partido 2 sin jugadores (gruposLibres[3])
            // Fila 3: partido 1 sin jugadores (gruposLibres[4]) - partido 2 sin jugadores (gruposLibres[5]) - img - partido B
            // Fila 4: partido 1 sin jugadores (gruposLibres[6]) - partido 2 sin jugadores (gruposLibres[7]) - partido B - img
            
            if (datos.gruposLibres && datos.gruposLibres.length > 0) {
                // Fila 1: columna 3 (celda 3) y columna 4 (celda 10)
                if (datos.gruposLibres.length > 0) {
                    cargarHorarioEnCelda(3, datos.gruposLibres[0].fecha, datos.gruposLibres[0].horario, 0);
                }
                if (datos.gruposLibres.length > 1) {
                    cargarHorarioEnCelda(10, datos.gruposLibres[1].fecha, datos.gruposLibres[1].horario, 0);
                }
                
                // Fila 2: columna 3 (celda 6) y columna 4 (celda 11)
                if (datos.gruposLibres.length > 2) {
                    cargarHorarioEnCelda(6, datos.gruposLibres[2].fecha, datos.gruposLibres[2].horario, 1);
                }
                if (datos.gruposLibres.length > 3) {
                    cargarHorarioEnCelda(11, datos.gruposLibres[3].fecha, datos.gruposLibres[3].horario, 1);
                }
                
                // Fila 3: columna 1 (celda 7) y columna 2 (celda 8)
                if (datos.gruposLibres.length > 4) {
                    cargarHorarioEnCelda(7, datos.gruposLibres[4].fecha, datos.gruposLibres[4].horario, 2);
                }
                if (datos.gruposLibres.length > 5) {
                    cargarHorarioEnCelda(8, datos.gruposLibres[5].fecha, datos.gruposLibres[5].horario, 2);
                }
                
                // Fila 4: columna 1 (celda 10) y columna 2 (celda 11)
                if (datos.gruposLibres.length > 6) {
                    cargarHorarioEnCelda(10, datos.gruposLibres[6].fecha, datos.gruposLibres[6].horario, 3);
                }
                if (datos.gruposLibres.length > 7) {
                    cargarHorarioEnCelda(11, datos.gruposLibres[7].fecha, datos.gruposLibres[7].horario, 3);
                }
            }
        }
    }
    
    function construirTablaEliminatoria(datos) {
        // Implementar si es necesario
        construirTablaVacia();
    }
    
    function construirTablaVacia() {
        // Mostrar solo formato de 3 parejas vacío
        $('tbody tr').eq(0).find('.seleccion-dia-horario[data-celda="1"]').closest('td').show();
        $('tbody tr').eq(0).find('.seleccion-dia-horario[data-celda="2"]').closest('td').show();
        $('tbody tr').eq(0).find('.seleccion-dia-horario[data-celda="3"]').closest('td').show();
        $('tbody tr').eq(1).find('.seleccion-dia-horario[data-celda="4"]').closest('td').show();
        $('tbody tr').eq(1).find('.seleccion-dia-horario[data-celda="5"]').closest('td').show();
        $('tbody tr').eq(1).find('.seleccion-dia-horario[data-celda="6"]').closest('td').show();
        $('tbody tr').eq(2).find('.seleccion-dia-horario[data-celda="7"]').closest('td').show();
        $('tbody tr').eq(2).find('.seleccion-dia-horario[data-celda="8"]').closest('td').show();
        $('tbody tr').eq(2).find('.seleccion-dia-horario[data-celda="9"]').closest('td').show();
    }
    
    function cargarJugadoresEnCelda(celda, jugador1Id, jugador2Id, jugadoresInfo) {
        if (jugador1Id && jugadoresInfo[jugador1Id]) {
            let jugador1 = jugadoresInfo[jugador1Id];
            $(`.img-jugador-arriba[data-celda="${celda}"]`).attr('src', jugador1.foto).attr('data-id', jugador1.id);
            $(`.nombre-jugador-arriba[data-celda="${celda}"]`).text(jugador1.nombre + ' ' + jugador1.apellido);
        }
        if (jugador2Id && jugadoresInfo[jugador2Id]) {
            let jugador2 = jugadoresInfo[jugador2Id];
            $(`.img-jugador-abajo[data-celda="${celda}"]`).attr('src', jugador2.foto).attr('data-id', jugador2.id);
            $(`.nombre-jugador-abajo[data-celda="${celda}"]`).text(jugador2.nombre + ' ' + jugador2.apellido);
        }
    }
    
    function cargarHorarioEnCelda(celdaId, fecha, horario, filaIndex) {
        if (!fecha || fecha === '2000-01-01' || !horario || horario === '00:00') {
            return; // No cargar fechas/horarios por defecto
        }
        
        let celda = null;
        if (filaIndex !== undefined && filaIndex !== null) {
            let fila = $('tbody tr').eq(filaIndex);
            celda = fila.find('.seleccion-dia-horario[data-celda="' + celdaId + '"]').filter(function() {
                return $(this).closest('td').is(':visible');
            }).first();
        } else {
            celda = $('.seleccion-dia-horario[data-celda="' + celdaId + '"]').filter(function() {
                return $(this).closest('td').is(':visible');
            }).first();
        }
        
        if (celda && celda.length) {
            mostrarHorarioEnCelda(celda, fecha, horario, celdaId);
        }
    }
    
    // Inicializar estado de partido 3 para todas las zonas existentes
    // Por defecto, todas las zonas tienen partido 3 visible
    zonas.forEach(function(zona) {
        if (!tienePartido3.hasOwnProperty(zona)) {
            tienePartido3[zona] = true; // Por defecto, todas las zonas tienen partido 3
        }
        // Asegurar que todas las zonas nuevas tengan formato por defecto (3 partidos, 3 parejas)
        if (!tieneCuatroParejasEliminatoria.hasOwnProperty(zona)) {
            tieneCuatroParejasEliminatoria[zona] = false;
        }
    });
    
    // Cargar la primera zona al iniciar
    $(document).ready(function() {
        // Asegurar formato por defecto antes de actualizar
        $('#columna-partido-3').show().text('3');
        $('.columna-partido-3').show();
        $('#columna-partido-4').hide();
        $('.columna-partido-4').hide();
        $('#fila-agregar-pareja').hide();
        $('#fila-boton-agregar').show();
        
        // Asegurar que todas las celdas del formato por defecto estén visibles
        $('.seleccion-dia-horario[data-celda="1"], .seleccion-dia-horario[data-celda="2"], .seleccion-dia-horario[data-celda="3"]').closest('td').show();
        $('.seleccion-dia-horario[data-celda="4"], .seleccion-dia-horario[data-celda="5"], .seleccion-dia-horario[data-celda="6"]').closest('td').show();
        $('.seleccion-dia-horario[data-celda="7"], .seleccion-dia-horario[data-celda="8"], .seleccion-dia-horario[data-celda="9"]').closest('td').show();
        
        // Ocultar celdas de formato eliminatoria
        $('.seleccion-dia-horario[data-celda="10"], .seleccion-dia-horario[data-celda="11"], .seleccion-dia-horario[data-celda="12"], .seleccion-dia-horario[data-celda="13"], .seleccion-dia-horario[data-celda="14"], .seleccion-dia-horario[data-celda="15"]').closest('td').hide();
        
        actualizarZona();
    });

    // Función para guardar una zona en la base de datos
    function guardarZonaEnBD(zona, datosZona) {
        if (!datosZona || !datosZona.jugadores) {
            return; // No hay datos para guardar
        }
        
        // Verificar que haya al menos un jugador seleccionado antes de guardar
        let tieneJugadores = false;
        for (let celda of ['celda1', 'celda2', 'celda3', 'celda4']) {
            if (datosZona.jugadores[celda] && 
                (datosZona.jugadores[celda].arriba?.id || datosZona.jugadores[celda].abajo?.id)) {
                tieneJugadores = true;
                break;
            }
        }
        
        if (!tieneJugadores) {
            return; // No hay jugadores seleccionados, no guardar
        }
        
        var torneo_idElement = document.getElementById("torneo_id");
        if (!torneo_idElement || !torneo_idElement.value) {
            return; // No hay torneo seleccionado
        }
        var torneo_id = torneo_idElement.value;
        
        // Preparar datos de jugadores (convertir null a 0 o string vacío)
        var pareja_1_idJugadorArriba = datosZona.jugadores.celda1?.arriba?.id || 0;
        var pareja_1_idJugadorAbajo = datosZona.jugadores.celda1?.abajo?.id || 0;
        var pareja_2_idJugadorArriba = datosZona.jugadores.celda2?.arriba?.id || 0;
        var pareja_2_idJugadorAbajo = datosZona.jugadores.celda2?.abajo?.id || 0;
        var pareja_3_idJugadorArriba = datosZona.jugadores.celda3?.arriba?.id || 0;
        var pareja_3_idJugadorAbajo = datosZona.jugadores.celda3?.abajo?.id || 0;
        var pareja_4_idJugadorArriba = datosZona.jugadores.celda4?.arriba?.id || 0;
        var pareja_4_idJugadorAbajo = datosZona.jugadores.celda4?.abajo?.id || 0;
        
        // Verificar si tiene 4 parejas
        var tieneCuatroParejasLocal = datosZona.tieneCuatroParejasEliminatoria || (pareja_4_idJugadorArriba && pareja_4_idJugadorAbajo);
        
        // Función helper para obtener fecha/horario con valores por defecto
        function getFechaSegura(valor) {
            return valor && valor !== 'null' && valor !== null && valor !== '' ? valor : '2000-01-01';
        }
        function getHorarioSeguro(valor) {
            return valor && valor !== 'null' && valor !== null && valor !== '' ? valor : '00:00';
        }
        
        // Preparar datos de horarios según el formato
        var datosEnvio = {
            torneo_id: torneo_id,
            zona: zona,
            tiene_cuatro_parejas: tieneCuatroParejasLocal ? 1 : 0,
            pareja_1_idJugadorArriba: pareja_1_idJugadorArriba,
            pareja_1_idJugadorAbajo: pareja_1_idJugadorAbajo,
            pareja_2_idJugadorArriba: pareja_2_idJugadorArriba,
            pareja_2_idJugadorAbajo: pareja_2_idJugadorAbajo,
            pareja_3_idJugadorArriba: pareja_3_idJugadorArriba,
            pareja_3_idJugadorAbajo: pareja_3_idJugadorAbajo,
            _token: '{{csrf_token()}}'
        };
        
        // Asegurar que horarios existe
        let horarios = datosZona.horarios || {};
        
        if (tieneCuatroParejasEliminatoria && tieneCuatroParejasEliminatoria[zona]) {
            // ESTRUCTURA CON 4 PAREJAS ELIMINATORIA: Partido A, Perdedor, Ganador, Partido B
            // Partido A: Pareja 1 (celda 2) vs Pareja 2 (celda 4)
            datosEnvio.pareja_1_partido_1_dia = getFechaSegura(horarios[2]?.dia);
            datosEnvio.pareja_1_partido_1_horario = getHorarioSeguro(horarios[2]?.horario);
            datosEnvio.pareja_2_partido_1_dia = getFechaSegura(horarios[4]?.dia);
            datosEnvio.pareja_2_partido_1_horario = getHorarioSeguro(horarios[4]?.horario);
            // Partido B: Pareja 3 (celda 15) vs Pareja 4 (celda 15)
            datosEnvio.pareja_3_partido_2_dia = getFechaSegura(horarios[15]?.dia);
            datosEnvio.pareja_3_partido_2_horario = getHorarioSeguro(horarios[15]?.horario);
            datosEnvio.pareja_4_idJugadorArriba = pareja_4_idJugadorArriba;
            datosEnvio.pareja_4_idJugadorAbajo = pareja_4_idJugadorAbajo;
            datosEnvio.pareja_4_partido_2_dia = getFechaSegura(horarios[15]?.dia);
            datosEnvio.pareja_4_partido_2_horario = getHorarioSeguro(horarios[15]?.horario);
            // Ganador: (celda 10)
            datosEnvio.pareja_4_partido_1_dia = getFechaSegura(horarios[10]?.dia);
            datosEnvio.pareja_4_partido_1_horario = getHorarioSeguro(horarios[10]?.horario);
            // Perdedor: (celda 7, 11, etc - buscar en todas las celdas de perdedor)
            datosEnvio.pareja_3_partido_1_dia = getFechaSegura(horarios[7]?.dia || horarios[11]?.dia || horarios[3]?.dia);
            datosEnvio.pareja_3_partido_1_horario = getHorarioSeguro(horarios[7]?.horario || horarios[11]?.horario || horarios[3]?.horario);
        } else if (tieneCuatroParejasLocal) {
            // Formato eliminatoria anterior: usar celdas 2, 4, 10, 11, 12, 13, 15
            // Partido 1 (Semifinal 1): Pareja 1 (celda 2) vs Pareja 2 (celda 4)
            datosEnvio.pareja_1_partido_1_dia = getFechaSegura(horarios[2]?.dia);
            datosEnvio.pareja_1_partido_1_horario = getHorarioSeguro(horarios[2]?.horario);
            datosEnvio.pareja_2_partido_1_dia = getFechaSegura(horarios[4]?.dia);
            datosEnvio.pareja_2_partido_1_horario = getHorarioSeguro(horarios[4]?.horario);
            // Partido 2 (Semifinal 2): Pareja 3 (celda 12) vs Pareja 4 (celda 13)
            datosEnvio.pareja_3_partido_1_dia = getFechaSegura(horarios[12]?.dia);
            datosEnvio.pareja_3_partido_1_horario = getHorarioSeguro(horarios[12]?.horario);
            datosEnvio.pareja_4_idJugadorArriba = pareja_4_idJugadorArriba;
            datosEnvio.pareja_4_idJugadorAbajo = pareja_4_idJugadorAbajo;
            datosEnvio.pareja_4_partido_1_dia = getFechaSegura(horarios[13]?.dia);
            datosEnvio.pareja_4_partido_1_horario = getHorarioSeguro(horarios[13]?.horario);
            // Final: Ganadores (celda 10)
            datosEnvio.final_dia = getFechaSegura(horarios[10]?.dia);
            datosEnvio.final_horario = getHorarioSeguro(horarios[10]?.horario);
            // Consolación: Perdedores (celda 11)
            datosEnvio.consolacion_dia = getFechaSegura(horarios[11]?.dia);
            datosEnvio.consolacion_horario = getHorarioSeguro(horarios[11]?.horario);
        } else {
            // Formato normal: usar celdas 2, 3, 4, 6, 7, 8
            // Pareja 1: partido 1 (celda 2), partido 2 (celda 3)
            datosEnvio.pareja_1_partido_1_dia = getFechaSegura(horarios[2]?.dia);
            datosEnvio.pareja_1_partido_1_horario = getHorarioSeguro(horarios[2]?.horario);
            datosEnvio.pareja_1_partido_2_dia = getFechaSegura(horarios[3]?.dia);
            datosEnvio.pareja_1_partido_2_horario = getHorarioSeguro(horarios[3]?.horario);
            // Pareja 2: partido 1 (celda 4), partido 2 (celda 6)
            datosEnvio.pareja_2_partido_1_dia = getFechaSegura(horarios[4]?.dia);
            datosEnvio.pareja_2_partido_1_horario = getHorarioSeguro(horarios[4]?.horario);
            datosEnvio.pareja_2_partido_2_dia = getFechaSegura(horarios[6]?.dia);
            datosEnvio.pareja_2_partido_2_horario = getHorarioSeguro(horarios[6]?.horario);
            // Pareja 3: partido 1 (celda 7), partido 2 (celda 8)
            datosEnvio.pareja_3_partido_1_dia = getFechaSegura(horarios[7]?.dia);
            datosEnvio.pareja_3_partido_1_horario = getHorarioSeguro(horarios[7]?.horario);
            datosEnvio.pareja_3_partido_2_dia = getFechaSegura(horarios[8]?.dia);
            datosEnvio.pareja_3_partido_2_horario = getHorarioSeguro(horarios[8]?.horario);
        }
        
        // Limpiar el objeto datosEnvio: convertir null/undefined a valores por defecto
        let datosEnvioLimpio = {};
        for (let key in datosEnvio) {
            if (datosEnvio.hasOwnProperty(key)) {
                let valor = datosEnvio[key];
                // Si es un campo de fecha, usar getFechaSegura
                if (key.includes('_dia') || key === 'final_dia' || key === 'consolacion_dia') {
                    datosEnvioLimpio[key] = getFechaSegura(valor);
                }
                // Si es un campo de horario, usar getHorarioSeguro
                else if (key.includes('_horario') || key === 'final_horario' || key === 'consolacion_horario') {
                    datosEnvioLimpio[key] = getHorarioSeguro(valor);
                }
                // Para otros campos, convertir null/undefined a string vacío o mantener el valor
                else if (valor === null || valor === undefined) {
                    datosEnvioLimpio[key] = '';
                } else if (typeof valor === 'object' && valor !== null) {
                    // Si es un objeto, convertirlo a string (no debería pasar, pero por seguridad)
                    datosEnvioLimpio[key] = JSON.stringify(valor);
                } else {
                    datosEnvioLimpio[key] = valor;
                }
            }
        }
        
        // Debug: mostrar datos que se envían
        console.log('Guardando zona ' + zona + ':', {
            'pareja_1_partido_2': datosEnvioLimpio.pareja_1_partido_2_dia + ' ' + datosEnvioLimpio.pareja_1_partido_2_horario,
            'pareja_2_partido_2': datosEnvioLimpio.pareja_2_partido_2_dia + ' ' + datosEnvioLimpio.pareja_2_partido_2_horario,
            'pareja_3_partido_2': datosEnvioLimpio.pareja_3_partido_2_dia + ' ' + datosEnvioLimpio.pareja_3_partido_2_horario
        });
        
        // Guardar en la base de datos (sin mostrar alertas)
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("guardarfechaadmintorneo") }}',
            data: datosEnvioLimpio,
            success: function(data) {
                console.log('Zona ' + zona + ' guardada correctamente');
            },
            error: function(xhr, status, error) {
                console.error('Error al guardar zona ' + zona + ':', error);
                console.error('Respuesta del servidor:', xhr.responseText);
            }
        });
    }

    $('#btn-nueva-zona').on('click', function() {
        // Guarda los datos de la zona actual antes de cambiar
        let datosGuardados = obtenerDatosZona();
        console.log('Guardando datos de zona', zonas[zonaIndex], ':', datosGuardados);
        datosZonas[zonas[zonaIndex]] = datosGuardados;
        
        // Guardar la zona actual en la base de datos antes de crear una nueva
        guardarZonaEnBD(zonas[zonaIndex], datosGuardados);
        
        // Agrega una nueva zona solo si no existe ya
        let nuevaZona = String.fromCharCode(zonas[zonas.length - 1].charCodeAt(0) + 1);
        zonas.push(nuevaZona);
        datosZonas[nuevaZona] = null;
        tienePartido3[nuevaZona] = true; // Nueva zona tiene partido 3 por defecto
        tieneCuatroParejasEliminatoria[nuevaZona] = false; // Nueva zona no tiene formato eliminatoria inicialmente
        zonaIndex = zonas.length - 1;
        actualizarZona();
    });

    $('#btn-zona-anterior').on('click', function() {
        if (zonaIndex > 0) {
            try {
                // Guardar la zona actual antes de cambiar
                let datosGuardados = obtenerDatosZona();
                if (datosGuardados) {
                    datosZonas[zonas[zonaIndex]] = datosGuardados;
                    guardarZonaEnBD(zonas[zonaIndex], datosGuardados);
                }
            } catch (error) {
                console.error('Error al guardar datos de zona antes de retroceder:', error);
            }
            
            zonaIndex--;
            actualizarZona();
        }
    });

    $('#btn-zona-siguiente').on('click', function() {
        if (zonaIndex < zonas.length - 1) {
            try {
                // Guardar la zona actual antes de cambiar
                let datosGuardados = obtenerDatosZona();
                if (datosGuardados) {
                    datosZonas[zonas[zonaIndex]] = datosGuardados;
                    guardarZonaEnBD(zonas[zonaIndex], datosGuardados);
                }
            } catch (error) {
                console.error('Error al guardar datos de zona antes de avanzar:', error);
            }
            
            zonaIndex++;
            actualizarZona();
        }
    });

    // Al guardar, también guarda los datos de la zona actual
    $('#btn-guardar-torneo').on('click', function() {
        datosZonas[zonas[zonaIndex]] = obtenerDatosZona();        
        var zona = zonas[zonaIndex];
        var torneo_id = document.getElementById("torneo_id").value;
        
        // Verificar si tiene 4 parejas y si es formato eliminatoria
        var tieneCuatroParejas = (pareja_4_idJugadorArriba && pareja_4_idJugadorAbajo) || 
                                 (tieneCuatroParejasEliminatoria && tieneCuatroParejasEliminatoria[zona]);
        
        // PAREJA 1
        var pareja_1_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda1"]').attr('data-id');
        var pareja_1_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda1"]').attr('data-id');
        
        // PAREJA 2
        var pareja_2_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda2"]').attr('data-id');
        var pareja_2_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda2"]').attr('data-id');
        
        // PAREJA 3
        var pareja_3_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda3"]').attr('data-id');
        var pareja_3_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda3"]').attr('data-id');
        
        // PAREJA 4 (si existe)
        var pareja_4_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda4"]').attr('data-id');
        var pareja_4_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda4"]').attr('data-id');
        
        // Verificar si tiene 4 parejas y si es formato eliminatoria
        var tieneCuatroParejas = !!(pareja_4_idJugadorArriba && pareja_4_idJugadorAbajo) || 
                                 !!(tieneCuatroParejasEliminatoria && tieneCuatroParejasEliminatoria[zona]);
        
        var pareja_1_partido_1, pareja_1_partido_1_dia, pareja_1_partido_1_horario;
        var pareja_1_partido_2, pareja_1_partido_2_dia, pareja_1_partido_2_horario;
        var pareja_2_partido_1, pareja_2_partido_1_dia, pareja_2_partido_1_horario;
        var pareja_2_partido_2, pareja_2_partido_2_dia, pareja_2_partido_2_horario;
        var pareja_3_partido_1, pareja_3_partido_1_dia, pareja_3_partido_1_horario;
        var pareja_3_partido_2, pareja_3_partido_2_dia, pareja_3_partido_2_horario;
        var pareja_4_partido_1, pareja_4_partido_1_dia, pareja_4_partido_1_horario;
        var pareja_4_partido_2, pareja_4_partido_2_dia, pareja_4_partido_2_horario;
        var final_dia, final_horario, consolacion_dia, consolacion_horario;
        
        if (tieneCuatroParejasEliminatoria && tieneCuatroParejasEliminatoria[zona]) {
            // ESTRUCTURA CON 4 PAREJAS ELIMINATORIA: Partido A, Perdedor, Ganador, Partido B
            // Fila 1 (Pareja 1): parejas - img - partido A (celda 2) - perdedor (celda 3) - ganador (celda 10)
            pareja_1_partido_1 = $('.seleccion-dia-horario[data-celda="2"]');
            pareja_1_partido_1_dia = pareja_1_partido_1.data('dia') || '';
            pareja_1_partido_1_horario = pareja_1_partido_1.data('horario') || '';
            pareja_1_partido_2_dia = null;
            pareja_1_partido_2_horario = null;
            
            // Fila 2 (Pareja 2): parejas - partido A (celda 4) - img - ganador (celda 6) - perdedor (celda 11)
            pareja_2_partido_1 = $('.seleccion-dia-horario[data-celda="4"]');
            pareja_2_partido_1_dia = pareja_2_partido_1.data('dia') || '';
            pareja_2_partido_1_horario = pareja_2_partido_1.data('horario') || '';
            pareja_2_partido_2_dia = null;
            pareja_2_partido_2_horario = null;
            
            // Fila 3 (Pareja 3): parejas - perdedor (celda 7) - ganador (celda 8) - img - partido B (celda 15)
            pareja_3_partido_1 = $('.seleccion-dia-horario[data-celda="7"]');
            pareja_3_partido_1_dia = pareja_3_partido_1.data('dia') || '';
            pareja_3_partido_1_horario = pareja_3_partido_1.data('horario') || '';
            pareja_3_partido_2 = $('.seleccion-dia-horario[data-celda="15"]');
            pareja_3_partido_2_dia = pareja_3_partido_2.data('dia') || '';
            pareja_3_partido_2_horario = pareja_3_partido_2.data('horario') || '';
            
            // Fila 4 (Pareja 4): parejas - ganador (celda 10) - perdedor (celda 11) - partido B (celda 15) - img
            pareja_4_partido_1 = $('.seleccion-dia-horario[data-celda="10"]');
            pareja_4_partido_1_dia = pareja_4_partido_1.data('dia') || '';
            pareja_4_partido_1_horario = pareja_4_partido_1.data('horario') || '';
            pareja_4_partido_2 = $('.seleccion-dia-horario[data-celda="15"]');
            pareja_4_partido_2_dia = pareja_4_partido_2.data('dia') || '';
            pareja_4_partido_2_horario = pareja_4_partido_2.data('horario') || '';
            
            // Final y Consolación (no aplican en este formato)
            final_dia = null;
            final_horario = null;
            consolacion_dia = null;
            consolacion_horario = null;
        } else if (tieneCuatroParejas) {
            // ESTRUCTURA CON 4 PAREJAS: SEMIFINALES Y FINAL (formato anterior)
            // Semifinal 1: Pareja 1 vs Pareja 2
            pareja_1_partido_1 = $('.seleccion-dia-horario[data-celda="2"]');
            pareja_1_partido_1_dia = pareja_1_partido_1.data('dia') || '';
            pareja_1_partido_1_horario = pareja_1_partido_1.data('horario') || '';
            pareja_1_partido_2_dia = null;
            pareja_1_partido_2_horario = null;
            
            pareja_2_partido_1 = $('.seleccion-dia-horario[data-celda="4"]');
            pareja_2_partido_1_dia = pareja_2_partido_1.data('dia') || '';
            pareja_2_partido_1_horario = pareja_2_partido_1.data('horario') || '';
            pareja_2_partido_2_dia = null;
            pareja_2_partido_2_horario = null;
            
            // Semifinal 2: Pareja 3 vs Pareja 4
            pareja_3_partido_1 = $('.seleccion-dia-horario[data-celda="7"]');
            pareja_3_partido_1_dia = pareja_3_partido_1.data('dia') || '';
            pareja_3_partido_1_horario = pareja_3_partido_1.data('horario') || '';
            pareja_3_partido_2_dia = null;
            pareja_3_partido_2_horario = null;
            
            pareja_4_partido_1 = $('.seleccion-dia-horario[data-celda="13"]');
            pareja_4_partido_1_dia = pareja_4_partido_1.data('dia') || '';
            pareja_4_partido_1_horario = pareja_4_partido_1.data('horario') || '';
            pareja_4_partido_2_dia = null;
            pareja_4_partido_2_horario = null;
            
            // Final y Consolación
            final_dia = $('.seleccion-dia-horario[data-celda="10"]').data('dia') || '';
            final_horario = $('.seleccion-dia-horario[data-celda="10"]').data('horario') || '';
            consolacion_dia = $('.seleccion-dia-horario[data-celda="11"]').data('dia') || '';
            consolacion_horario = $('.seleccion-dia-horario[data-celda="11"]').data('horario') || '';
        } else {
            // ESTRUCTURA CON 3 PAREJAS: TODOS CONTRA TODOS
            pareja_1_partido_1 = $('.seleccion-dia-horario[data-celda="2"]');
            pareja_1_partido_1_dia = pareja_1_partido_1.data('dia');
            pareja_1_partido_1_horario = pareja_1_partido_1.data('horario');
            pareja_1_partido_2 = $('.seleccion-dia-horario[data-celda="3"]');
            pareja_1_partido_2_dia = pareja_1_partido_2.data('dia');
            pareja_1_partido_2_horario = pareja_1_partido_2.data('horario');
            
            pareja_2_partido_1 = $('.seleccion-dia-horario[data-celda="4"]');
            pareja_2_partido_1_dia = pareja_2_partido_1.data('dia');
            pareja_2_partido_1_horario = pareja_2_partido_1.data('horario');
            pareja_2_partido_2 = $('.seleccion-dia-horario[data-celda="6"]');
            pareja_2_partido_2_dia = pareja_2_partido_2.data('dia');
            pareja_2_partido_2_horario = pareja_2_partido_2.data('horario');
            
            pareja_3_partido_1 = $('.seleccion-dia-horario[data-celda="7"]');
            pareja_3_partido_1_dia = pareja_3_partido_1.data('dia');
            pareja_3_partido_1_horario = pareja_3_partido_1.data('horario');
            pareja_3_partido_2 = $('.seleccion-dia-horario[data-celda="8"]');
            pareja_3_partido_2_dia = pareja_3_partido_2.data('dia');
            pareja_3_partido_2_horario = pareja_3_partido_2.data('horario');
            
            pareja_4_partido_1_dia = null;
            pareja_4_partido_1_horario = null;
            final_dia = null;
            final_horario = null;
            consolacion_dia = null;
            consolacion_horario = null;
        }
                
        // Preparar datos
        var datosEnvio = {
            torneo_id: torneo_id,
            zona: zona,
            tiene_cuatro_parejas: tieneCuatroParejas ? 1 : 0,
            tiene_cuatro_parejas_eliminatoria: (tieneCuatroParejasEliminatoria && tieneCuatroParejasEliminatoria[zona]) ? 1 : 0,
            pareja_1_idJugadorArriba: pareja_1_idJugadorArriba,
            pareja_1_idJugadorAbajo: pareja_1_idJugadorAbajo,
            pareja_1_partido_1_dia: pareja_1_partido_1_dia,
            pareja_1_partido_1_horario: pareja_1_partido_1_horario,
            pareja_1_partido_2_dia: pareja_1_partido_2_dia,
            pareja_1_partido_2_horario: pareja_1_partido_2_horario,
            pareja_2_idJugadorArriba: pareja_2_idJugadorArriba,
            pareja_2_idJugadorAbajo: pareja_2_idJugadorAbajo,
            pareja_2_partido_1_dia: pareja_2_partido_1_dia,
            pareja_2_partido_1_horario: pareja_2_partido_1_horario,
            pareja_2_partido_2_dia: pareja_2_partido_2_dia,
            pareja_2_partido_2_horario: pareja_2_partido_2_horario,
            pareja_3_idJugadorArriba: pareja_3_idJugadorArriba,
            pareja_3_idJugadorAbajo: pareja_3_idJugadorAbajo,
            pareja_3_partido_1_dia: pareja_3_partido_1_dia,
            pareja_3_partido_1_horario: pareja_3_partido_1_horario,
            pareja_3_partido_2_dia: pareja_3_partido_2_dia,
            pareja_3_partido_2_horario: pareja_3_partido_2_horario,
            _token: '{{csrf_token()}}'
        };
        
        // Agregar datos de pareja 4 si existe
        if (tieneCuatroParejas && pareja_4_idJugadorArriba && pareja_4_idJugadorAbajo) {
            datosEnvio.pareja_4_idJugadorArriba = pareja_4_idJugadorArriba;
            datosEnvio.pareja_4_idJugadorAbajo = pareja_4_idJugadorAbajo;
            datosEnvio.pareja_4_partido_1_dia = pareja_4_partido_1_dia;
            datosEnvio.pareja_4_partido_1_horario = pareja_4_partido_1_horario;
            datosEnvio.pareja_4_partido_2_dia = pareja_4_partido_2_dia;
            datosEnvio.pareja_4_partido_2_horario = pareja_4_partido_2_horario;
            datosEnvio.final_dia = final_dia;
            datosEnvio.final_horario = final_horario;
            datosEnvio.consolacion_dia = consolacion_dia;
            datosEnvio.consolacion_horario = consolacion_horario;
        }
        
        // Enviar datos
        $.ajax({
	       type: 'POST',
	       dataType: 'JSON',
	       url: '{{ route("guardarfechaadmintorneo") }}',
	       data: datosEnvio,
	       	success: function(data) {
                alert('Torneo guardado correctamente');
	        }
	    });
    });

    // Botón Comenzar Torneo
    $('#btn-comenzar-torneo').on('click', function() {
        var torneo_id = document.getElementById("torneo_id").value;
        if (!torneo_id) {
            alert('Por favor, seleccione un torneo primero');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).text('Comenzando...');

        $.ajax({
            type: 'POST',
            url: '{{ route("comenzartorneopuntuable") }}',
            data: {
                torneo_id: torneo_id,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                btn.prop('disabled', false).text('Comenzar Torneo');
                if (response && response.success) {
                    // Redirigir a la pantalla de resultados (fase de grupos)
                    window.location.href = '{{ route("admintorneoresultados") }}?torneo_id=' + torneo_id;
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
</script>
@endsection