@extends('bahia_padel.admin.plantilla')

@section('title_header', 'Control TV')

@section('contenedor')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tv text-primary"></i> Control de Pantallas TV
        </h1>
        <div>
            <a href="{{ route('tvdisplay') }}" target="_blank" class="btn btn-success">
                <i class="fas fa-external-link-alt"></i> Abrir TV Display
            </a>
            <a href="{{ route('admintorneos') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Torneos
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Panel de Torneos y Vistas -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-plus-circle"></i> Agregar Vistas
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Seleccioná un torneo y el tipo de vista para agregar a la rotación de TV.</p>
                    
                    <div class="form-group">
                        <label><strong>Torneo:</strong></label>
                        <select id="select-torneo" class="form-control">
                            <option value="">-- Seleccionar torneo --</option>
                            @foreach($torneos as $torneo)
                                <option value="{{ $torneo->id }}" data-nombre="{{ $torneo->nombre }}" data-categoria="{{ $torneo->categoria }}" data-formato="{{ $torneo->tipo_torneo_formato ?? 'americano' }}">
                                    {{ $torneo->nombre }} ({{ $torneo->categoria ?? 'Sin categoría' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><strong>Tipo de Vista:</strong></label>
                        <div class="row" id="tipos-vista-container">
                            @foreach($tiposVista as $tipo)
                                <div class="col-6 mb-2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input tipo-vista-check" 
                                               id="tipo-{{ $tipo['id'] }}" value="{{ $tipo['id'] }}" data-nombre="{{ $tipo['nombre'] }}">
                                        <label class="custom-control-label" for="tipo-{{ $tipo['id'] }}">
                                            <i class="fas {{ $tipo['icono'] }}"></i> {{ $tipo['nombre'] }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><strong>Tiempo en pantalla (segundos):</strong></label>
                        <input type="number" id="duracion-slide" class="form-control" value="60" min="10" max="300">
                        <small class="text-muted">Tiempo total para esta vista. Si tiene varias zonas, rotará todas dentro de este tiempo.</small>
                    </div>
                    
                    <button type="button" id="btn-agregar-vista" class="btn btn-primary btn-block">
                        <i class="fas fa-plus"></i> Agregar Vista(s) Seleccionada(s)
                    </button>
                </div>
            </div>
            
            <!-- Configuración Global -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-cog"></i> Configuración Global
                    </h6>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label><strong>Intervalo por defecto (segundos):</strong></label>
                        <input type="number" id="intervalo-default" class="form-control" 
                               value="{{ $config->intervalo_default ?? 15 }}" min="5" max="120">
                        <small class="text-muted">Se usa cuando no se especifica duración individual</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel de Slides Configurados -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-list-ol"></i> Vistas en Rotación
                    </h6>
                    <span class="badge badge-primary" id="contador-slides">0 vistas</span>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Arrastrá para reordenar. La TV mostrará estas vistas en orden.</p>
                    
                    <ul class="list-group" id="lista-slides">
                        <!-- Se llena dinámicamente -->
                    </ul>
                    
                    <div id="empty-state" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No hay vistas configuradas.<br>Agregá vistas usando el panel de la izquierda.</p>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="button" id="btn-guardar" class="btn btn-success btn-lg btn-block">
                        <i class="fas fa-save"></i> Guardar Configuración
                    </button>
                </div>
            </div>
            
            <!-- Preview -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-eye"></i> Vista Previa
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div id="preview-container" style="height: 200px; background: #1a1a2e; display: flex; align-items: center; justify-content: center; color: #666;">
                        <span>Seleccioná una vista para previsualizar</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos base que funcionan en modo claro y oscuro */
.card {
    background-color: var(--card-bg, #fff);
    color: var(--text-color, #333);
}

.card-header {
    background-color: var(--card-header-bg, #f8f9fc);
}

.form-control, .custom-control-label {
    color: var(--text-color, #333);
}

/* Labels y textos en modo claro */
label, .form-group label, strong {
    color: var(--text-color, #333) !important;
}

/* Checkboxes labels */
.custom-control-label {
    color: var(--text-color, #333) !important;
}

/* Lista de slides */
#lista-slides {
    min-height: 100px;
    max-height: 400px;
    overflow-y: auto;
}

#lista-slides .list-group-item {
    cursor: move;
    border-left: 4px solid #4e73df;
    transition: all 0.2s;
    background-color: var(--list-item-bg, #fff);
    color: var(--text-color, #333);
}

#lista-slides .list-group-item:hover {
    background: var(--list-item-hover, #f8f9fc);
}

#lista-slides .list-group-item.sortable-ghost {
    opacity: 0.4;
    background: #e3e6f0;
}

.slide-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.slide-info {
    flex: 1;
}

.slide-info strong {
    color: var(--text-color, #333) !important;
}

.slide-actions {
    display: flex;
    gap: 5px;
}

.slide-tipo {
    font-size: 0.75rem;
    color: var(--muted-color, #858796);
}

.slide-duracion {
    font-size: 0.8rem;
    color: #4e73df;
    font-weight: bold;
}

.tipo-zonas { border-left-color: #1cc88a !important; }
.tipo-cruces { border-left-color: #f6c23e !important; }
.tipo-rotacion { border-left-color: #36b9cc !important; }

#preview-container iframe {
    width: 100%;
    height: 100%;
    border: none;
}
</style>

<!-- Sortable.js para drag & drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
$(document).ready(function() {
    // Cargar slides actuales
    let slides = @json($config->slides ?? []);
    
    // Inicializar Sortable para drag & drop
    const listaSlidesEl = document.getElementById('lista-slides');
    if (listaSlidesEl) {
        new Sortable(listaSlidesEl, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function() {
                actualizarOrdenSlides();
            }
        });
    }
    
    renderizarSlides();
    
    // Agregar vista(s)
    $('#btn-agregar-vista').click(function() {
        const torneoId = $('#select-torneo').val();
        const torneoOption = $('#select-torneo option:selected');
        const torneoNombre = torneoOption.data('nombre');
        const duracion = parseInt($('#duracion-slide').val()) || 15;
        
        if (!torneoId) {
            alert('Seleccioná un torneo');
            return;
        }
        
        const tiposSeleccionados = $('.tipo-vista-check:checked');
        if (tiposSeleccionados.length === 0) {
            alert('Seleccioná al menos un tipo de vista');
            return;
        }
        
        tiposSeleccionados.each(function() {
            const tipoId = $(this).val();
            const tipoNombre = $(this).data('nombre');
            
            slides.push({
                torneo_id: parseInt(torneoId),
                torneo_nombre: torneoNombre,
                tipo: tipoId,
                tipo_nombre: tipoNombre,
                duracion: duracion
            });
        });
        
        // Limpiar selección
        $('.tipo-vista-check').prop('checked', false);
        
        renderizarSlides();
        mostrarSnackbar('Vista(s) agregada(s)');
    });
    
    // Guardar configuración
    $('#btn-guardar').click(function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
        
        $.ajax({
            url: '{{ route("tvconfig.guardar") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                slides: slides,
                intervalo_default: $('#intervalo-default').val()
            },
            success: function(response) {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Configuración');
                if (response.success) {
                    mostrarSnackbar('Configuración guardada correctamente');
                } else {
                    alert('Error al guardar');
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Configuración');
                alert('Error de conexión');
            }
        });
    });
    
    function renderizarSlides() {
        const lista = $('#lista-slides');
        const emptyState = $('#empty-state');
        const contador = $('#contador-slides');
        
        lista.empty();
        
        if (slides.length === 0) {
            emptyState.show();
            contador.text('0 vistas');
            return;
        }
        
        emptyState.hide();
        contador.text(slides.length + ' vista' + (slides.length !== 1 ? 's' : ''));
        
        slides.forEach((slide, index) => {
            const tipoClass = slide.tipo.includes('zonas') ? 'tipo-zonas' : 
                             (slide.tipo.includes('cruces') ? 'tipo-cruces' : 'tipo-rotacion');
            
            const item = $(`
                <li class="list-group-item ${tipoClass}" data-index="${index}">
                    <div class="slide-item">
                        <div class="slide-info">
                            <i class="fas fa-grip-vertical text-muted mr-2"></i>
                            <strong>${slide.torneo_nombre}</strong>
                            <br>
                            <span class="slide-tipo">${slide.tipo_nombre || slide.tipo}</span>
                            <span class="slide-duracion ml-2">${slide.duracion}s</span>
                        </div>
                        <div class="slide-actions">
                            <button class="btn btn-sm btn-outline-info btn-preview" data-index="${index}" title="Vista previa">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-eliminar" data-index="${index}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </li>
            `);
            
            lista.append(item);
        });
        
        // Eventos de botones
        $('.btn-eliminar').click(function() {
            const idx = $(this).data('index');
            slides.splice(idx, 1);
            renderizarSlides();
            mostrarSnackbar('Vista eliminada');
        });
        
        $('.btn-preview').click(function() {
            const idx = $(this).data('index');
            previsualizarSlide(slides[idx]);
        });
    }
    
    function actualizarOrdenSlides() {
        const nuevoOrden = [];
        $('#lista-slides .list-group-item').each(function() {
            const idx = $(this).data('index');
            nuevoOrden.push(slides[idx]);
        });
        slides = nuevoOrden;
        renderizarSlides();
    }
    
    function previsualizarSlide(slide) {
        let url = '';
        switch (slide.tipo) {
            case 'zonas_americano':
                url = '{{ route("tvtorneoamericano") }}?torneo_id=' + slide.torneo_id;
                break;
            case 'cruces_americano':
                url = '{{ route("tvtorneoamericanocruces") }}?torneo_id=' + slide.torneo_id;
                break;
            case 'zonas_puntuable':
                url = '{{ route("tvtorneospuntuableszonas") }}?torneos=' + slide.torneo_id;
                break;
            case 'cruces_puntuable':
                url = '{{ route("tvtorneoamericanocruces") }}?torneo_id=' + slide.torneo_id;
                break;
            case 'rotacion':
                url = '{{ route("tvtorneosrotacion") }}?torneos=' + slide.torneo_id;
                break;
        }
        
        $('#preview-container').html(`<iframe src="${url}" style="transform: scale(0.5); width: 200%; height: 200%; transform-origin: top left;"></iframe>`);
    }
    
    function mostrarSnackbar(mensaje) {
        $('#snackbar_text').text(mensaje);
        $('#snackbar').addClass('show');
        setTimeout(() => $('#snackbar').removeClass('show'), 3000);
    }
});
</script>
@endsection
