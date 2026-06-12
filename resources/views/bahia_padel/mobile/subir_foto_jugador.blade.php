@extends('bahia_padel.mobile.plantilla_mobile')

@section('content')
<div class="container-fluid px-3">
    <!-- Header -->
    <div class="header-mobile text-center">
        <h1><i class="fas fa-camera"></i> Subir Foto de Jugador</h1>
        <p class="mb-0" style="opacity: 0.9; font-size: 14px;">Busca un jugador y sube su foto</p>
    </div>

    <!-- Buscador -->
    <div class="search-container">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
            <input type="text" 
                   class="form-control border-start-0" 
                   id="buscador-jugadores" 
                   placeholder="Buscar por nombre o apellido..."
                   autocomplete="off">
            <button class="btn btn-outline-secondary" type="button" id="btn-limpiar-busqueda" style="display: none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Lista de jugadores -->
    <div id="lista-jugadores">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-3 text-muted">Cargando jugadores...</p>
        </div>
    </div>

    <!-- Sección de subida de foto (fixed bottom) -->
    <div class="upload-section" id="upload-section" style="display: none;">
        <form method="POST" action="{{ route('subir.foto.jugador.publico') }}" enctype="multipart/form-data" id="form-subir-foto">
            @csrf
            <input type="hidden" name="id" id="input-jugador-id" value="">
            
            <div class="selected-jugador-info mb-3 p-3 bg-light rounded">
                <div class="d-flex align-items-center">
                    <img id="selected-jugador-foto" src="" class="jugador-foto-mobile me-3" alt="Foto jugador">
                    <div>
                        <h6 class="mb-0" id="selected-jugador-nombre"></h6>
                        <small class="text-muted">ID: <span id="selected-jugador-id"></span></small>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Seleccionar nueva foto:</label>
                <input type="file" 
                       id="input-foto" 
                       name="foto" 
                       accept="image/*" 
                       class="form-control"
                       required>
                <small class="text-muted">Formatos: JPG, PNG, GIF, WEBP, BMP. Máximo 100MB (se comprimirá automáticamente a 5MB si es necesario)</small>
            </div>
            
            <div id="preview-container" style="display: none;" class="text-center mb-3">
                <img id="preview-foto" src="" class="preview-foto" alt="Vista previa">
            </div>
            
            <button type="submit" 
                    class="btn btn-upload w-100" 
                    id="btn-subir-foto">
                <i class="fas fa-upload"></i> Subir Foto
            </button>
        </form>
    </div>
    
    <!-- Mensajes de sesión -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
</div>

<!-- Toast para mensajes -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fas fa-info-circle me-2"></i>
            <strong class="me-auto">Bahía Padel</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toast-message"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let jugadorSeleccionado = null;
let timeoutBusqueda = null;

$(document).ready(function() {
    // Cargar todos los jugadores al inicio
    cargarTodosJugadores();
    
    // Si hay un jugador seleccionado desde la URL (después de subir foto), seleccionarlo
    @if(isset($jugador_id_seleccionado) && $jugador_id_seleccionado)
        setTimeout(function() {
            seleccionarJugador({{ $jugador_id_seleccionado }});
        }, 1000); // Esperar a que se carguen los jugadores
    @endif
    
    // Buscador en tiempo real
    $('#buscador-jugadores').on('input', function() {
        const busqueda = $(this).val().trim();
        
        // Mostrar/ocultar botón limpiar
        if (busqueda.length > 0) {
            $('#btn-limpiar-busqueda').show();
        } else {
            $('#btn-limpiar-busqueda').hide();
        }
        
        // Debounce para evitar demasiadas peticiones
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(function() {
            buscarJugadores(busqueda);
        }, 300);
    });
    
    // Limpiar búsqueda
    $('#btn-limpiar-busqueda').on('click', function() {
        $('#buscador-jugadores').val('');
        $(this).hide();
        cargarTodosJugadores();
        ocultarSeccionUpload();
    });
    
    // Seleccionar jugador
    $(document).on('click', '.jugador-item', function() {
        const jugadorId = $(this).data('id');
        seleccionarJugador(jugadorId);
    });
    
    // Preview simple de imagen (opcional, solo para mostrar)
    $(document).on('change', '#input-foto', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validar tamaño básico
            const maxSize = 100 * 1024 * 1024; // 100MB
            if (file.size > maxSize) {
                alert('La imagen es demasiado grande. Máximo 100MB.');
                $(this).val('');
                return;
            }
            
            // Mostrar preview si es posible
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#preview-foto').attr('src', e.target.result);
                $('#preview-container').show();
            };
            reader.readAsDataURL(file);
        } else {
            $('#preview-container').hide();
        }
    });
    
    // Validar formulario antes de enviar
    $('#form-subir-foto').on('submit', function(e) {
        if (!$('#input-jugador-id').val()) {
            e.preventDefault();
            alert('Por favor selecciona un jugador primero.');
            return false;
        }
        
        if (!$('#input-foto')[0].files || !$('#input-foto')[0].files[0]) {
            e.preventDefault();
            alert('Por favor selecciona una imagen.');
            return false;
        }
        
        // Mostrar indicador de carga
        $('#btn-subir-foto').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Subiendo...');
    });
    
    // Después de que la página se recarga (por el redirect), recargar la lista de jugadores
    // Esto asegura que se vean las fotos actualizadas
    $(document).ready(function() {
        // Si hay un mensaje de éxito, recargar la lista de jugadores
        @if(session('success'))
            // Esperar un momento para que la página termine de cargar
            setTimeout(function() {
                cargarTodosJugadores();
            }, 500);
        @endif
    });
    
    // Función para mostrar mensajes
    window.mostrarMensaje = function(mensaje, tipo) {
        const toastElement = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        const toastHeader = document.querySelector('#toast .toast-header');
        
        toastMessage.textContent = mensaje;
        
        // Cambiar color según el tipo
        toastHeader.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'text-white');
        if (tipo === 'success') {
            toastHeader.classList.add('bg-success', 'text-white');
            toastHeader.querySelector('i').className = 'fas fa-check-circle me-2';
        } else if (tipo === 'error') {
            toastHeader.classList.add('bg-danger', 'text-white');
            toastHeader.querySelector('i').className = 'fas fa-exclamation-circle me-2';
        } else {
            toastHeader.classList.add('bg-warning', 'text-white');
            toastHeader.querySelector('i').className = 'fas fa-info-circle me-2';
        }
        
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
    };
});

function cargarTodosJugadores() {
    buscarJugadores(''); // Cargar todos sin búsqueda
}

function buscarJugadores(busqueda) {
    // Si la búsqueda es muy corta (menos de 2 caracteres), mostrar todos
    // El servidor ya maneja esto, así que siempre hacemos la petición
    
    $('#lista-jugadores').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-3 text-muted">${busqueda ? 'Buscando jugadores...' : 'Cargando jugadores...'}</p>
        </div>
    `);
    
    $.ajax({
        type: 'POST',
        url: '{{ route("buscar.jugadores.publico") }}',
        data: {
            busqueda: busqueda || '',
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            console.log('Respuesta del servidor:', response); // Debug
            if (response.jugadores && response.jugadores.length > 0) {
                mostrarJugadores(response.jugadores);
            } else {
                $('#lista-jugadores').html(`
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <p>${busqueda ? 'No se encontraron jugadores' : 'No hay jugadores registrados'}</p>
                        <small class="text-muted">${busqueda ? 'Intenta con otro nombre' : 'Contacta al administrador'}</small>
                    </div>
                `);
            }
        },
        error: function(xhr) {
            console.error('Error al buscar jugadores:', xhr);
            console.error('Status:', xhr.status);
            console.error('Response:', xhr.responseText);
            mostrarMensaje('Error al buscar jugadores. Por favor intenta de nuevo.', 'error');
            $('#lista-jugadores').html(`
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error al buscar jugadores</p>
                    <small class="text-muted">Status: ${xhr.status}</small>
                </div>
            `);
        }
    });
}

function mostrarJugadores(jugadores) {
    let html = '';
    const baseUrl = '{{ url('/') }}';
    const timestamp = new Date().getTime(); // Para evitar caché
    
    jugadores.forEach(function(jugador) {
        // Construir ruta de foto correctamente
        let foto = jugador.foto || 'images/jugador_img.png';
        
        // Si la foto no empieza con http o /, agregar la base URL
        if (!foto.startsWith('http') && !foto.startsWith('/')) {
            foto = baseUrl + '/' + foto;
        } else if (foto.startsWith('/')) {
            foto = baseUrl + foto;
        }
        
        // Agregar timestamp para evitar caché
        foto += (foto.indexOf('?') > -1 ? '&' : '?') + 't=' + timestamp;
        
        html += `
            <div class="jugador-item" data-id="${jugador.id}">
                <div class="d-flex align-items-center">
                    <img src="${foto}" class="jugador-foto-mobile me-3" alt="${jugador.nombre} ${jugador.apellido}" onerror="this.src='${baseUrl}/images/jugador_img.png?t=' + ${timestamp}">
                    <div class="flex-grow-1">
                        <h6 class="mb-0">${jugador.nombre} ${jugador.apellido}</h6>
                        ${jugador.telefono ? `<small class="text-muted"><i class="fas fa-phone"></i> ${jugador.telefono}</small>` : ''}
                    </div>
                    <i class="fas fa-chevron-right text-muted"></i>
                </div>
            </div>
        `;
    });
    
    $('#lista-jugadores').html(html);
}

function seleccionarJugador(jugadorId) {
    // Obtener datos del jugador desde la lista
    const jugadorItem = $(`.jugador-item[data-id="${jugadorId}"]`);
    if (jugadorItem.length === 0) return;
    
    // Buscar el jugador en la lista actual
    let jugador = null;
    $('.jugador-item').each(function() {
        if ($(this).data('id') == jugadorId) {
            const img = $(this).find('img');
            const nombre = $(this).find('h6').text().trim();
            jugador = {
                id: jugadorId,
                nombre: nombre,
                foto: img.attr('src')
            };
            return false;
        }
    });
    
    if (!jugador) return;
    
    jugadorSeleccionado = jugador;
    
    // Marcar como seleccionado
    $('.jugador-item').removeClass('selected');
    jugadorItem.addClass('selected');
    
    // Establecer ID en el formulario
    $('#input-jugador-id').val(jugador.id);
    
    // Mostrar sección de upload
    $('#selected-jugador-id').text(jugador.id);
    $('#selected-jugador-nombre').text(jugador.nombre);
    $('#selected-jugador-foto').attr('src', jugador.foto);
    $('#upload-section').slideDown();
    
    // Scroll a la sección de upload
    $('html, body').animate({
        scrollTop: $(document).height()
    }, 300);
}

function ocultarSeccionUpload() {
    $('#upload-section').slideUp();
    jugadorSeleccionado = null;
    $('.jugador-item').removeClass('selected');
    $('#input-jugador-id').val('');
    $('#input-foto').val('');
    $('#preview-container').hide();
    $('#btn-subir-foto').prop('disabled', false).html('<i class="fas fa-upload"></i> Subir Foto');
}
</script>
@endsection

