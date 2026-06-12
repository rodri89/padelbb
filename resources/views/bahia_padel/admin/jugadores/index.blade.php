@extends('bahia_padel/admin/plantilla')

@section('title_header','Jugadores')

@section('contenedor')
<style>
    .jugador-card {
        border-radius: 12px;
        border: 1px solid #e3e6f0;
        transition: all 0.3s ease;
    }
    .jugador-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .jugador-foto {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border: 3px solid #dee2e6;
        cursor: pointer;
    }
    .jugador-foto:hover {
        border-color: #4e73df;
    }
    .btn-agregar-jugador {
        font-size: 1.5rem;
        padding: 1rem 2rem;
        font-weight: 600;
        border-radius: 12px;
        border-width: 3px;
    }
    .modal-content {
        color: #000 !important;
    }
    .modal-content label {
        color: #000 !important;
    }
    .modal-content input,
    .modal-content select,
    .modal-content textarea {
        color: #000 !important;
    }
    .modal-title {
        color: #000 !important;
    }
    .modal-header {
        color: #000 !important;
    }
    .modal-body {
        color: #000 !important;
    }
    .modal-footer {
        color: #000 !important;
    }
    .custom-file-label {
        color: #000 !important;
    }
</style>

<div class="container-fluid body_admin">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow bg-white p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">Gestión de Jugadores</h3>
                    <button type="button" class="btn btn-primary btn-agregar-jugador" data-toggle="modal" data-target="#modalNuevoJugador" onclick="limpiarFormulario()">
                        + Nuevo Jugador
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <input type="text" class="form-control" id="buscador-jugadores" placeholder="Buscar por nombre o apellido...">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="lista-jugadores">
        <!-- Los jugadores se cargarán aquí dinámicamente -->
        <div class="col-12 text-center">
            <p class="text-muted">Cargando jugadores...</p>
        </div>
    </div>
</div>

<!-- Modal para Nuevo/Editar Jugador -->
<div class="modal fade" id="modalNuevoJugador" tabindex="-1" role="dialog" aria-labelledby="modalNuevoJugadorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoJugadorLabel">Nuevo Jugador</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formNuevoJugador">
                <input type="hidden" id="jugador_id" name="jugador_id" value="0">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nombre">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="apellido">Apellido <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="apellido" name="apellido" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono" placeholder="Opcional">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="foto">Foto</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="foto" name="foto" accept="image/*">
                                    <label class="custom-file-label" for="foto">Seleccionar imagen</label>
                                </div>
                                <small class="form-text text-muted">Opcional. Si no se selecciona, se mantendrá la imagen actual.</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Vista Previa</label>
                                <div class="text-center">
                                    <img id="preview-foto" src="{{ asset('images/jugador_img.png') }}" 
                                         class="rounded-circle jugador-foto" 
                                         style="width:150px; height:150px; object-fit:cover; border:3px solid #dee2e6;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Jugador</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    let todosJugadores = [];

    $(document).ready(function() {
        cargarJugadores();

        $('#buscador-jugadores').on('keyup', function() {
            const busqueda = $(this).val().toLowerCase();
            filtrarJugadores(busqueda);
        });

        $('#foto').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#preview-foto').attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        });

        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
        });
    });
    
    function filtrarJugadores(busqueda) {
        if (!busqueda) {
            mostrarJugadores(todosJugadores);
            return;
        }
        
        const jugadoresFiltrados = todosJugadores.filter(function(jugador) {
            const nombreCompleto = (jugador.nombre + ' ' + jugador.apellido).toLowerCase();
            return nombreCompleto.includes(busqueda);
        });
        
        mostrarJugadores(jugadoresFiltrados);
    }
    
    function cargarJugadores() {
        $.ajax({
            type: 'GET',
            dataType: 'JSON',
            url: '{{ route("getjugadoreshome") }}',
            success: function(response) {
                if (response.jugadores && response.jugadores.length > 0) {
                    todosJugadores = response.jugadores;
                    mostrarJugadores(todosJugadores);
                } else {
                    todosJugadores = [];
                    $('#lista-jugadores').html(`
                        <div class="col-12 text-center">
                            <p class="text-muted">No hay jugadores registrados. Agrega el primero haciendo clic en "Nuevo Jugador".</p>
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar jugadores:', xhr, status, error);
                console.error('Response:', xhr.responseText);
                $('#lista-jugadores').html(`
                    <div class="col-12 text-center">
                        <p class="text-danger">Error al cargar los jugadores.</p>
                        <p class="text-muted small">Status: ${status} | Error: ${error}</p>
                        <p class="text-muted small">¿Estás autenticado? Verifica que hayas iniciado sesión.</p>
                    </div>
                `);
            }
        });
    }
    
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
    
    function mostrarJugadores(jugadores) {
        let html = '';
        jugadores.forEach(function(jugador) {
            if (jugador.activo == 1) {
                let foto = getFotoUrl(jugador.foto);
                
                html += `
                    <div class="col-md-3 mb-4">
                        <div class="card shadow jugador-card h-100">
                            <div class="card-body text-center">
                                <img src="${foto}" 
                                     class="rounded-circle jugador-foto mb-3" 
                                     alt="${jugador.nombre} ${jugador.apellido}">
                                <h5 class="card-title">${jugador.nombre} ${jugador.apellido}</h5>
                                ${jugador.telefono ? `<p class="text-muted mb-2"><i class="fas fa-phone"></i> ${jugador.telefono}</p>` : ''}
                                <div class="d-flex justify-content-center">
                                    <button type="button" class="btn btn-sm btn-primary btn-editar-jugador mr-2" data-id="${jugador.id}">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-eliminar-jugador" data-id="${jugador.id}">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        });
        
        $('#lista-jugadores').html(html);
    }
    
    function limpiarFormulario() {
        $('#jugador_id').val('0');
        $('#nombre').val('');
        $('#apellido').val('');
        $('#telefono').val('');
        $('#foto').val('');
        $('#preview-foto').attr('src', '{{ asset('images/jugador_img.png') }}');
        $('.custom-file-label').html('Seleccionar imagen');
        $('#modalNuevoJugadorLabel').text('Nuevo Jugador');
    }
    
    function cargarDatosJugador(jugador) {
        $('#jugador_id').val(jugador.id);
        $('#nombre').val(jugador.nombre);
        $('#apellido').val(jugador.apellido);
        $('#telefono').val(jugador.telefono || '');
        
        let foto = jugador.foto || '{{ asset('images/jugador_img.png') }}';
        if (!foto.startsWith('http') && !foto.startsWith('/')) {
            foto = '/' + foto;
        }
        $('#preview-foto').attr('src', foto);
        $('#modalNuevoJugadorLabel').text('Editar Jugador');
    }

    $('#formNuevoJugador').on('submit', function(e) {
        e.preventDefault();

        const jugadorId = $('#jugador_id').val();
        const esEdicion = jugadorId && jugadorId != '0';

        const formData = new FormData();
        if (esEdicion) {
            formData.append('id', jugadorId);
        }
        formData.append('nombre', $('#nombre').val());
        formData.append('apellido', $('#apellido').val());
        formData.append('telefono', $('#telefono').val() || '0');

        const fotoFile = $('#foto')[0].files[0];
        if (fotoFile) {
            formData.append('foto', fotoFile);
        }
        formData.append('_token', '{{ csrf_token() }}');

        const url = esEdicion ? '{{ route("admineditarjugador") }}' : '{{ route("admincrearjugador") }}';
        const mensajeExito = esEdicion ? 'Jugador actualizado correctamente' : 'Jugador creado correctamente';

        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(mensajeExito);
                    $('#modalNuevoJugador').modal('hide');
                    limpiarFormulario();
                    cargarJugadores();
                } else {
                    alert('Error: ' + (response.message || 'Error desconocido'));
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al guardar el jugador';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert(errorMsg);
            }
        });
    });
    
    // Editar jugador
    $(document).on('click', '.btn-editar-jugador', function() {
        const jugadorId = $(this).data('id');
        const jugador = todosJugadores.find(j => j.id == jugadorId);
        
        if (jugador) {
            cargarDatosJugador(jugador);
            $('#modalNuevoJugador').modal('show');
        }
    });
    
    // Eliminar jugador
    $(document).on('click', '.btn-eliminar-jugador', function() {
        if (!confirm('¿Está seguro de que desea eliminar este jugador?')) {
            return;
        }
        
        const jugadorId = $(this).data('id');
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '/admin_eliminar_jugador',
            data: {
                id: jugadorId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    alert('Jugador eliminado correctamente');
                    cargarJugadores();
                } else {
                    alert('Error al eliminar el jugador: ' + (response.message || 'Error desconocido'));
                }
            },
            error: function() {
                alert('Error al eliminar el jugador');
            }
        });
    });
</script>
@endsection
