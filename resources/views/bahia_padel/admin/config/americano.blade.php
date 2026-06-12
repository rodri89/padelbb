@extends('bahia_padel/admin/plantilla')

@section('title_header','Configuración de Cruces Americanos')

@section('contenedor')

<style>
    /* Forzar colores visibles en todo el formulario */
    .form-group label,
    .form-check-label,
    h5, h6,
    .card-title,
    .card-text,
    p, span, strong, div {
        color: #000 !important;
    }
    
    /* Excepciones para badges y elementos con colores específicos */
    .text-muted {
        color: #6c757d !important;
    }
    .text-warning {
        color: #856404 !important;
    }
    .text-primary {
        color: #4e73df !important;
    }
    
    #form-config-cruces .form-control {
        background-color: #fff !important;
        color: #333 !important;
        border: 1px solid #ced4da !important;
        min-height: 38px;
    }
    #form-config-cruces .card {
        background-color: #fff;
    }
    #form-config-cruces .card-body {
        background-color: #fff;
    }
    .card {
        background-color: #fff !important;
    }
    .card-body {
        background-color: #fff !important;
    }
    .clasificacion-preview {
        background: #f8f9fc !important;
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
        padding: 1rem;
        margin-top: 1rem;
    }
    .clasificacion-preview h6 {
        margin-bottom: 0.5rem;
        color: #000 !important;
    }
    .clasificacion-preview strong {
        color: #000 !important;
    }
    .clasificacion-item {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        margin: 0.25rem;
        border-radius: 0.25rem;
        font-size: 0.85rem;
    }
    .clasificacion-item.primero { background: #d4edda !important; color: #155724 !important; }
    .clasificacion-item.segundo { background: #cce5ff !important; color: #004085 !important; }
    .clasificacion-item.tercero { background: #fff3cd !important; color: #856404 !important; }
    .clasificacion-item.cuarto { background: #f8d7da !important; color: #721c24 !important; }
    .ejemplo-config {
        font-size: 0.85rem;
        color: #6c757d !important;
        margin-top: 0.5rem;
    }
    .drag-handle {
        cursor: move;
        padding: 0.5rem;
        background: #e9ecef !important;
        border-radius: 0.25rem;
        margin-right: 0.5rem;
        color: #333 !important;
    }
    .sortable-item {
        padding: 0.5rem;
        margin: 0.25rem 0;
        background: #fff !important;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        display: flex;
        align-items: center;
        color: #000 !important;
    }
    .sortable-item span {
        color: #000 !important;
    }
    .sortable-item strong {
        color: #000 !important;
    }
    .sortable-item.dragging {
        opacity: 0.5;
    }
    .games-config {
        background: #e8f4fd !important;
        padding: 1rem;
        border-radius: 0.35rem;
        margin-top: 1rem;
    }
    .games-config label,
    .games-config .form-group label {
        color: #000 !important;
    }
    
    /* Tablas */
    .table th, .table td {
        color: #000 !important;
    }
    .table thead th {
        color: #000 !important;
        background-color: #f8f9fc !important;
    }
    
    /* Small text */
    small, .small {
        color: #6c757d !important;
    }
    
    /* Contenedor principal */
    #criterio-desempate-container {
        background: #f8f9fc;
        padding: 1rem;
        border-radius: 0.35rem;
    }
    
    /* Inputs dentro de llaves */
    .partido-llave label {
        color: #000 !important;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            {{-- Listado de configuraciones existentes --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Configuraciones de Cruces Americanos</h6>
                    <a href="{{ route('adminconfigamericano') }}?nueva=1" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Nueva configuración
                    </a>
                </div>
                <div class="card-body">
                    @if($configuraciones->isEmpty())
                        <p class="text-muted mb-0">No hay configuraciones guardadas. Crea una con el botón «Nueva configuración».</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Parejas</th>
                                        <th>Zonas</th>
                                        <th>Clasifican</th>
                                        <th>Rondas</th>
                                        <th class="text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($configuraciones as $c)
                                    <tr>
                                        <td><strong>{{ $c->nombre ?? 'Sin nombre' }}</strong></td>
                                        <td>{{ $c->cantidad_parejas }} parejas</td>
                                        <td>{{ $c->cantidad_zonas }} zonas de {{ $c->parejas_por_zona }}</td>
                                        <td class="text-muted small">
                                            @if($c->clasifican_primeros) {{ $c->clasifican_primeros }}° · @endif
                                            @if($c->clasifican_segundos) {{ $c->clasifican_segundos }} 2° · @endif
                                            @if($c->clasifican_terceros) {{ $c->clasifican_terceros }} 3° @endif
                                            @if($c->clasifican_cuartos) · {{ $c->clasifican_cuartos }} 4° @endif
                                        </td>
                                        <td class="text-muted small">
                                            @if($c->tiene_16avos_final) 16avos · @endif
                                            @if($c->tiene_8vos_final) 8vos · @endif
                                            @if($c->tiene_4tos_final) 4tos · @endif
                                            Semi · Final
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('adminconfigamericano') }}?editar={{ $c->id }}" class="btn btn-outline-primary btn-sm">Editar</a>
                                            <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-config" data-id="{{ $c->id }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Formulario de configuración --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        @if(isset($config) && isset($config['id']))
                            Editar configuración: {{ $config['nombre'] ?? $config['cantidad_parejas'] . ' parejas' }}
                        @else
                            Nueva configuración de cruces americanos
                        @endif
                    </h6>
                </div>
                <div class="card-body">
                    <form id="form-config-cruces">
                        @csrf
                        @if(isset($config) && !empty($config['id']))
                            <input type="hidden" name="config_id" id="config_id" value="{{ $config['id'] }}">
                        @else
                            <input type="hidden" name="config_id" id="config_id" value="">
                        @endif
                        
                        {{-- Sección 1: Datos básicos --}}
                        <h5 class="mb-3">1. Datos del Torneo</h5>
                        
                        <div class="form-group row">
                            <label for="nombre" class="col-sm-3 col-form-label">Nombre descriptivo:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                    value="{{ $config['nombre'] ?? '' }}" 
                                    placeholder="Ej: 8 parejas - 2 zonas de 4">
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="cantidad_parejas" class="col-sm-3 col-form-label">Cantidad de Parejas:</label>
                            <div class="col-sm-3">
                                <input type="number" class="form-control" id="cantidad_parejas" name="cantidad_parejas" 
                                    min="4" max="64" value="{{ $config['cantidad_parejas'] ?? 8 }}" required>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="cantidad_zonas" class="col-sm-3 col-form-label">Cantidad de Zonas:</label>
                            <div class="col-sm-3">
                                <input type="number" class="form-control" id="cantidad_zonas" name="cantidad_zonas" 
                                    min="1" max="16" value="{{ $config['cantidad_zonas'] ?? 2 }}" required>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="parejas_por_zona" class="col-sm-3 col-form-label">Parejas por Zona:</label>
                            <div class="col-sm-3">
                                <input type="number" class="form-control" id="parejas_por_zona" name="parejas_por_zona" 
                                    min="2" max="8" value="{{ $config['parejas_por_zona'] ?? 4 }}" required>
                            </div>
                        </div>
                        
                        <hr>
                        
                        {{-- Sección 2: Clasificación --}}
                        <h5 class="mb-3">2. Clasificación a Cruces</h5>
                        <p class="text-muted small">Define cuántos equipos clasifican de cada posición. El total debe coincidir con la cantidad necesaria para los cruces (8, 16, etc.).</p>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="clasifican_primeros">Primeros que clasifican:</label>
                                    <input type="number" class="form-control clasifican-input" id="clasifican_primeros" 
                                        name="clasifican_primeros" min="0" max="16" 
                                        value="{{ $config['clasifican_primeros'] ?? 0 }}">
                                    <small class="text-muted">Ej: 2 zonas = 2 primeros</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="clasifican_segundos">Segundos que clasifican:</label>
                                    <input type="number" class="form-control clasifican-input" id="clasifican_segundos" 
                                        name="clasifican_segundos" min="0" max="16" 
                                        value="{{ $config['clasifican_segundos'] ?? 0 }}">
                                    <small class="text-muted">Ej: 2 zonas = 2 segundos</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="clasifican_terceros">Mejores terceros:</label>
                                    <input type="number" class="form-control clasifican-input" id="clasifican_terceros" 
                                        name="clasifican_terceros" min="0" max="16" 
                                        value="{{ $config['clasifican_terceros'] ?? 0 }}">
                                    <small class="text-muted">Comparados entre todas las zonas</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="clasifican_cuartos">Mejores cuartos:</label>
                                    <input type="number" class="form-control clasifican-input" id="clasifican_cuartos" 
                                        name="clasifican_cuartos" min="0" max="16" 
                                        value="{{ $config['clasifican_cuartos'] ?? 0 }}">
                                    <small class="text-muted">Comparados entre todas las zonas</small>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Preview de clasificación --}}
                        <div class="clasificacion-preview">
                            <h6>Vista previa de clasificación:</h6>
                            <div id="clasificacion-preview-content">
                                <!-- Se llena dinámicamente -->
                            </div>
                            <div id="clasificacion-total" class="mt-2">
                                <strong>Total clasificados:</strong> <span id="total-clasificados">0</span> parejas
                                <span id="clasificacion-warning" class="text-warning ml-2" style="display:none;">
                                    (Debe ser potencia de 2: 4, 8, 16, etc.)
                                </span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        {{-- Sección 3: Criterio de desempate --}}
                        <h5 class="mb-3">3. Criterio de Desempate (para mejores 3ros/4tos)</h5>
                        <p class="text-muted small">Arrastra para ordenar la prioridad. Se usa para determinar los "mejores terceros" o "mejores cuartos" entre zonas.</p>
                        
                        <div id="criterio-desempate-container">
                            <div class="sortable-item" data-criterio="PG">
                                <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                                <span>1. <strong>Partidos Ganados (PG)</strong></span>
                            </div>
                            <div class="sortable-item" data-criterio="DIF_GAMES">
                                <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                                <span>2. <strong>Diferencia de Games (GF - GC)</strong></span>
                            </div>
                            <div class="sortable-item" data-criterio="GF">
                                <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                                <span>3. <strong>Games a Favor (GF)</strong></span>
                            </div>
                            <div class="sortable-item" data-criterio="ENFRENTAMIENTO">
                                <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                                <span>4. <strong>Enfrentamiento Directo</strong> (solo entre equipos de misma zona)</span>
                            </div>
                        </div>
                        <input type="hidden" id="criterio_desempate_orden" name="criterio_desempate_orden" 
                            value="{{ $config['criterio_desempate_orden'] ?? 'PG,DIF_GAMES,GF,ENFRENTAMIENTO' }}">
                        
                        <hr>
                        
                        {{-- Sección 4: Games por fase --}}
                        <h5 class="mb-3">4. Games por Partido</h5>
                        <p class="text-muted small">Configura cuántos games se juegan en cada fase (el primero en llegar gana).</p>
                        
                        <div class="games-config">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="games_fase_grupos">Fase de Grupos:</label>
                                        <input type="number" class="form-control" id="games_fase_grupos" 
                                            name="games_fase_grupos" min="1" max="15" 
                                            value="{{ $config['games_fase_grupos'] ?? 5 }}">
                                        <small class="text-muted">Ej: primer a 5 games</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="games_cruces">Cruces (8vos/4tos):</label>
                                        <input type="number" class="form-control" id="games_cruces" 
                                            name="games_cruces" min="1" max="15" 
                                            value="{{ $config['games_cruces'] ?? 5 }}">
                                        <small class="text-muted">Ej: primer a 5 games</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="games_semifinal">Semifinal:</label>
                                        <input type="number" class="form-control" id="games_semifinal" 
                                            name="games_semifinal" min="1" max="15" 
                                            value="{{ $config['games_semifinal'] ?? 5 }}">
                                        <small class="text-muted">Ej: primero a 5 games</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="games_final">Final:</label>
                                        <input type="number" class="form-control" id="games_final" 
                                            name="games_final" min="1" max="15" 
                                            value="{{ $config['games_final'] ?? 7 }}">
                                        <small class="text-muted">Ej: primero a 7 games</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        {{-- Sección 5: Rondas eliminatorias --}}
                        <h5 class="mb-3">5. Rondas Eliminatorias</h5>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Rondas activas:</label>
                            <div class="col-sm-9">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="tiene_16avos" name="tiene_16avos_final" value="1" 
                                        {{ isset($config) && $config['tiene_16avos_final'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tiene_16avos">
                                        Tiene 16avos de Final (32 clasificados)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="tiene_8vos" name="tiene_8vos_final" value="1" 
                                        {{ isset($config) && $config['tiene_8vos_final'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tiene_8vos">
                                        Tiene 8vos de Final (16 clasificados)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="tiene_4tos" name="tiene_4tos_final" value="1" 
                                        {{ isset($config) && $config['tiene_4tos_final'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tiene_4tos">
                                        Tiene 4tos de Final (8 clasificados)
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    Semifinal y Final siempre están incluidas.
                                </small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        {{-- Sección 6: Configuración de llaves --}}
                        <h5 class="mb-3">6. Configuración de Llaves</h5>
                        <p class="text-muted small">
                            Usa notación: <strong>Z1_P1</strong> = Zona 1, Posición 1 | <strong>Z2_P2</strong> = Zona 2, Posición 2 | <strong>M3_1</strong> = Mejor 3ro #1<br>
                            Para rondas posteriores: <strong>G1-4tos</strong> = Ganador partido 1 de cuartos | <strong>G1-semifinal</strong> = Ganador semifinal 1
                        </p>
                        
                        {{-- Llave 16avos --}}
                        <div id="llave-16avos-container" class="mb-4" style="display: none;">
                            <h6>16avos de Final</h6>
                            <div id="llave-16avos-content">
                                <!-- Generado dinámicamente -->
                            </div>
                        </div>
                        
                        {{-- Llave 8vos --}}
                        <div id="llave-8vos-container" class="mb-4" style="display: none;">
                            <h6>8vos de Final</h6>
                            <div id="llave-8vos-content">
                                <!-- Generado dinámicamente -->
                            </div>
                        </div>
                        
                        {{-- Llave 4tos --}}
                        <div id="llave-4tos-container" class="mb-4">
                            <h6>4tos de Final</h6>
                            <div id="llave-4tos-content">
                                @foreach([['Z1_P1','Z2_P4'],['Z2_P2','Z1_P3'],['Z2_P1','Z1_P4'],['Z1_P2','Z2_P3']] as $i => $par)
                                <div class="form-group row mb-2 partido-llave" data-ronda="4tos" data-partido="{{ $i+1 }}">
                                    <label class="col-sm-2 col-form-label">Partido {{ $i+1 }} (C{{ $i+1 }}):</label>
                                    <div class="col-sm-5">
                                        <input type="text" class="form-control pareja-1-input" name="llave_4tos[{{ $i }}][pareja_1]" 
                                            value="{{ isset($config['llave_4tos'][$i]) ? $config['llave_4tos'][$i]['pareja_1'] : $par[0] }}" 
                                            placeholder="Ej: Z1_P1">
                                    </div>
                                    <div class="col-sm-1 text-center align-self-center">VS</div>
                                    <div class="col-sm-4">
                                        <input type="text" class="form-control pareja-2-input" name="llave_4tos[{{ $i }}][pareja_2]" 
                                            value="{{ isset($config['llave_4tos'][$i]) ? $config['llave_4tos'][$i]['pareja_2'] : $par[1] }}" 
                                            placeholder="Ej: Z2_P4">
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        
                        {{-- Llave Semifinal --}}
                        <div id="llave-semifinal-container" class="mb-4">
                            <h6>Semifinal</h6>
                            <div id="llave-semifinal-content">
                                <div class="form-group row mb-2 partido-llave" data-ronda="semifinal" data-partido="1">
                                    <label class="col-sm-2 col-form-label">Partido 1 (S1):</label>
                                    <div class="col-sm-5">
                                        <input type="text" class="form-control pareja-1-input" name="llave_semifinal[0][pareja_1]" 
                                            value="{{ isset($config['llave_semifinal'][0]) ? $config['llave_semifinal'][0]['pareja_1'] : 'G1-4tos' }}" 
                                            placeholder="Ej: G1-4tos">
                                    </div>
                                    <div class="col-sm-1 text-center align-self-center">VS</div>
                                    <div class="col-sm-4">
                                        <input type="text" class="form-control pareja-2-input" name="llave_semifinal[0][pareja_2]" 
                                            value="{{ isset($config['llave_semifinal'][0]) ? $config['llave_semifinal'][0]['pareja_2'] : 'G2-4tos' }}" 
                                            placeholder="Ej: G2-4tos">
                                    </div>
                                </div>
                                <div class="form-group row mb-2 partido-llave" data-ronda="semifinal" data-partido="2">
                                    <label class="col-sm-2 col-form-label">Partido 2 (S2):</label>
                                    <div class="col-sm-5">
                                        <input type="text" class="form-control pareja-1-input" name="llave_semifinal[1][pareja_1]" 
                                            value="{{ isset($config['llave_semifinal'][1]) ? $config['llave_semifinal'][1]['pareja_1'] : 'G3-4tos' }}" 
                                            placeholder="Ej: G3-4tos">
                                    </div>
                                    <div class="col-sm-1 text-center align-self-center">VS</div>
                                    <div class="col-sm-4">
                                        <input type="text" class="form-control pareja-2-input" name="llave_semifinal[1][pareja_2]" 
                                            value="{{ isset($config['llave_semifinal'][1]) ? $config['llave_semifinal'][1]['pareja_2'] : 'G4-4tos' }}" 
                                            placeholder="Ej: G4-4tos">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Llave Final --}}
                        <div id="llave-final-container" class="mb-4">
                            <h6>Final</h6>
                            <div id="llave-final-content">
                                <div class="form-group row mb-2 partido-llave" data-ronda="final" data-partido="1">
                                    <label class="col-sm-2 col-form-label">Partido 1 (F1):</label>
                                    <div class="col-sm-5">
                                        <input type="text" class="form-control pareja-1-input" name="llave_final[0][pareja_1]" 
                                            value="{{ isset($config['llave_final'][0]) ? $config['llave_final'][0]['pareja_1'] : 'G1-semifinal' }}" 
                                            placeholder="Ej: G1-semifinal">
                                    </div>
                                    <div class="col-sm-1 text-center align-self-center">VS</div>
                                    <div class="col-sm-4">
                                        <input type="text" class="form-control pareja-2-input" name="llave_final[0][pareja_2]" 
                                            value="{{ isset($config['llave_final'][0]) ? $config['llave_final'][0]['pareja_2'] : 'G2-semifinal' }}" 
                                            placeholder="Ej: G2-semifinal">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        {{-- Notas --}}
                        <div class="form-group">
                            <label for="notas">Notas/Observaciones:</label>
                            <textarea class="form-control" id="notas" name="notas" rows="3" 
                                placeholder="Ej: Esta configuración se usa para torneos de fin de semana con 8 parejas...">{{ $config['notas'] ?? '' }}</textarea>
                        </div>
                        
                        {{-- Botones --}}
                        <div class="form-group row mt-4">
                            <div class="col-sm-12 text-right">
                                <button type="button" class="btn btn-secondary" id="btn-generar-llaves">
                                    <i class="fas fa-magic"></i> Generar Llaves Automáticamente
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Configuración
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            {{-- Ejemplos predefinidos --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ejemplos de Configuración</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">8 parejas (2 zonas de 4)</h6>
                                    <p class="card-text small">
                                        Clasifican: 2 primeros + 2 segundos = 4<br>
                                        Cruces: Semifinal + Final<br>
                                        1° Z1 vs 2° Z2, 1° Z2 vs 2° Z1
                                    </p>
                                    <button class="btn btn-sm btn-outline-primary btn-cargar-ejemplo" 
                                        data-parejas="8" data-zonas="2" data-porzona="4"
                                        data-primeros="2" data-segundos="2" data-terceros="0" data-cuartos="0">
                                        Cargar ejemplo
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">12 parejas (3 zonas de 4)</h6>
                                    <p class="card-text small">
                                        Clasifican: 3 primeros + 3 segundos + 2 mejores 3ros = 8<br>
                                        Cruces: Cuartos + Semifinal + Final
                                    </p>
                                    <button class="btn btn-sm btn-outline-primary btn-cargar-ejemplo"
                                        data-parejas="12" data-zonas="3" data-porzona="4"
                                        data-primeros="3" data-segundos="3" data-terceros="2" data-cuartos="0">
                                        Cargar ejemplo
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">16 parejas (4 zonas de 4)</h6>
                                    <p class="card-text small">
                                        Clasifican: 4 primeros + 4 segundos = 8<br>
                                        Cruces: Cuartos + Semifinal + Final<br>
                                        1° ZA vs 2° ZD, 1° ZB vs 2° ZC, etc.
                                    </p>
                                    <button class="btn btn-sm btn-outline-primary btn-cargar-ejemplo"
                                        data-parejas="16" data-zonas="4" data-porzona="4"
                                        data-primeros="4" data-segundos="4" data-terceros="0" data-cuartos="0">
                                        Cargar ejemplo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Actualizar preview de clasificación
    function actualizarPreviewClasificacion() {
        const zonas = parseInt($('#cantidad_zonas').val()) || 2;
        const primeros = parseInt($('#clasifican_primeros').val()) || 0;
        const segundos = parseInt($('#clasifican_segundos').val()) || 0;
        const terceros = parseInt($('#clasifican_terceros').val()) || 0;
        const cuartos = parseInt($('#clasifican_cuartos').val()) || 0;
        
        let html = '';
        
        // Mostrar primeros
        if (primeros > 0) {
            html += '<div class="mb-2"><strong>Primeros:</strong> ';
            for (let i = 1; i <= primeros && i <= zonas; i++) {
                html += `<span class="clasificacion-item primero">Z${i}_P1</span>`;
            }
            html += '</div>';
        }
        
        // Mostrar segundos
        if (segundos > 0) {
            html += '<div class="mb-2"><strong>Segundos:</strong> ';
            for (let i = 1; i <= segundos && i <= zonas; i++) {
                html += `<span class="clasificacion-item segundo">Z${i}_P2</span>`;
            }
            html += '</div>';
        }
        
        // Mostrar terceros
        if (terceros > 0) {
            html += '<div class="mb-2"><strong>Mejores terceros:</strong> ';
            for (let i = 1; i <= terceros; i++) {
                html += `<span class="clasificacion-item tercero">M3_${i}</span>`;
            }
            html += '</div>';
        }
        
        // Mostrar cuartos
        if (cuartos > 0) {
            html += '<div class="mb-2"><strong>Mejores cuartos:</strong> ';
            for (let i = 1; i <= cuartos; i++) {
                html += `<span class="clasificacion-item cuarto">M4_${i}</span>`;
            }
            html += '</div>';
        }
        
        if (!html) {
            html = '<p class="text-muted">Configura cuántos clasifican de cada posición</p>';
        }
        
        $('#clasificacion-preview-content').html(html);
        
        // Calcular total
        const total = primeros + segundos + terceros + cuartos;
        $('#total-clasificados').text(total);
        
        // Verificar si es potencia de 2
        const esPotenciaDe2 = total > 0 && (total & (total - 1)) === 0;
        if (!esPotenciaDe2 && total > 0) {
            $('#clasificacion-warning').show();
        } else {
            $('#clasificacion-warning').hide();
        }
        
        // Actualizar checkboxes de rondas según total
        if (total >= 32) {
            $('#tiene_16avos').prop('disabled', false);
        } else {
            $('#tiene_16avos').prop('checked', false).prop('disabled', true);
        }
        
        if (total >= 16) {
            $('#tiene_8vos').prop('disabled', false);
        } else {
            $('#tiene_8vos').prop('checked', false).prop('disabled', true);
        }
        
        if (total >= 8) {
            $('#tiene_4tos').prop('disabled', false);
        } else {
            $('#tiene_4tos').prop('checked', false).prop('disabled', true);
        }
    }
    
    // Eventos para actualizar preview
    $('.clasifican-input, #cantidad_zonas').on('change input', actualizarPreviewClasificacion);
    
    // Inicial
    actualizarPreviewClasificacion();
    
    // Mostrar/ocultar contenedores de llaves
    function actualizarVisibilidadLlaves() {
        if ($('#tiene_16avos').is(':checked')) {
            $('#llave-16avos-container').show();
        } else {
            $('#llave-16avos-container').hide();
        }
        
        if ($('#tiene_8vos').is(':checked')) {
            $('#llave-8vos-container').show();
        } else {
            $('#llave-8vos-container').hide();
        }
        
        if ($('#tiene_4tos').is(':checked')) {
            $('#llave-4tos-container').show();
        } else {
            $('#llave-4tos-container').hide();
        }
    }
    
    $('#tiene_16avos, #tiene_8vos, #tiene_4tos').on('change', function() {
        actualizarVisibilidadLlaves();
        // Al desactivar 16avos, regenerar llaves para eliminar refs DA*/G*-16avos
        if ($(this).attr('id') === 'tiene_16avos' && !$(this).is(':checked')) {
            generarLlavesAutomaticamente();
        }
    });
    actualizarVisibilidadLlaves();
    
    // Drag and drop para criterios de desempate
    let draggedItem = null;
    
    $('.sortable-item').on('dragstart', function(e) {
        draggedItem = this;
        $(this).addClass('dragging');
    });
    
    $('.sortable-item').on('dragend', function(e) {
        $(this).removeClass('dragging');
        actualizarOrdenCriterios();
    });
    
    $('.sortable-item').on('dragover', function(e) {
        e.preventDefault();
        const container = $('#criterio-desempate-container');
        const afterElement = getDragAfterElement(container[0], e.originalEvent.clientY);
        if (afterElement == null) {
            container.append(draggedItem);
        } else {
            $(afterElement).before(draggedItem);
        }
    });
    
    // Hacer los items arrastrables
    $('.sortable-item').attr('draggable', 'true');
    
    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.sortable-item:not(.dragging)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
    
    function actualizarOrdenCriterios() {
        const criterios = [];
        $('.sortable-item').each(function(index) {
            criterios.push($(this).data('criterio'));
            $(this).find('span:last').html(`${index + 1}. <strong>${$(this).find('span:last strong').text()}</strong>`);
        });
        $('#criterio_desempate_orden').val(criterios.join(','));
    }
    
    // Cargar orden existente si hay
    @if(isset($config) && isset($config['criterio_desempate_orden']))
        const ordenExistente = '{{ $config['criterio_desempate_orden'] }}'.split(',');
        const container = $('#criterio-desempate-container');
        ordenExistente.forEach(function(criterio, index) {
            const item = container.find(`[data-criterio="${criterio}"]`);
            if (item.length) {
                container.append(item);
            }
        });
        actualizarOrdenCriterios();
    @endif
    
    // Generar llaves automáticamente
    $('#btn-generar-llaves').on('click', function() {
        generarLlavesAutomaticamente();
    });
    
    function generarLlavesAutomaticamente() {
        const zonas = parseInt($('#cantidad_zonas').val()) || 2;
        const primeros = parseInt($('#clasifican_primeros').val()) || 0;
        const segundos = parseInt($('#clasifican_segundos').val()) || 0;
        const terceros = parseInt($('#clasifican_terceros').val()) || 0;
        const cuartos = parseInt($('#clasifican_cuartos').val()) || 0;
        const total = primeros + segundos + terceros + cuartos;
        
        const tiene16avos = $('#tiene_16avos').is(':checked');
        const tiene8vos = $('#tiene_8vos').is(':checked');
        const tiene4tos = $('#tiene_4tos').is(':checked');
        
        // Generar lista de clasificados
        const clasificados = [];
        
        // Primeros de cada zona
        for (let z = 1; z <= zonas && clasificados.length < primeros; z++) {
            clasificados.push(`Z${z}_P1`);
        }
        
        // Segundos de cada zona
        for (let z = 1; z <= zonas && clasificados.length < primeros + segundos; z++) {
            clasificados.push(`Z${z}_P2`);
        }
        
        // Mejores terceros
        for (let i = 1; i <= terceros; i++) {
            clasificados.push(`M3_${i}`);
        }
        
        // Mejores cuartos
        for (let i = 1; i <= cuartos; i++) {
            clasificados.push(`M4_${i}`);
        }
        
        // Determinar primera ronda
        let primeraRonda = 'semifinal';
        let partidosPrimeraRonda = 2;
        
        if (tiene16avos) {
            primeraRonda = '16avos';
            partidosPrimeraRonda = 16;
        } else if (tiene8vos) {
            primeraRonda = '8vos';
            partidosPrimeraRonda = 8;
        } else if (tiene4tos) {
            primeraRonda = '4tos';
            partidosPrimeraRonda = 4;
        }
        
        // Generar llaves de primera ronda con seeds típicos (1 vs último, 2 vs penúltimo, etc.)
        if (tiene4tos || tiene8vos || tiene16avos) {
            generarLlaveRonda(primeraRonda, partidosPrimeraRonda, clasificados);
        }
        
        // Generar llaves de rondas posteriores
        if (tiene16avos) {
            generarLlaveRondaPosterior('8vos', 8, '16avos');
        }
        
        if (tiene8vos && !tiene16avos) {
            // 8vos es primera ronda, ya generada
        } else if (tiene8vos && tiene16avos) {
            // 8vos viene después de 16avos
        }
        
        if (tiene4tos && (tiene8vos || tiene16avos)) {
            generarLlaveRondaPosterior('4tos', 4, tiene8vos ? '8vos' : '16avos');
        }
        
        generarLlaveRondaPosterior('semifinal', 2, tiene4tos ? '4tos' : (tiene8vos ? '8vos' : '16avos'));
        generarLlaveRondaPosterior('final', 1, 'semifinal');
    }
    
    function generarLlaveRonda(ronda, cantidadPartidos, clasificados) {
        const container = $(`#llave-${ronda}-content`);
        container.empty();
        
        // Generar enfrentamientos estilo torneo (1 vs 8, 4 vs 5, 2 vs 7, 3 vs 6 para 8 equipos)
        const partidos = [];
        const n = clasificados.length;
        
        if (n >= cantidadPartidos * 2) {
            // Formato típico de semillas
            for (let i = 0; i < cantidadPartidos; i++) {
                // Calcular índices de semillas opuestas
                let seed1, seed2;
                if (cantidadPartidos === 4) {
                    // 4 partidos: 1v8, 4v5, 2v7, 3v6
                    const orden = [[0, 7], [3, 4], [1, 6], [2, 5]];
                    seed1 = orden[i][0];
                    seed2 = orden[i][1];
                } else {
                    // Default: 1vs último, etc.
                    seed1 = i;
                    seed2 = n - 1 - i;
                }
                
                partidos.push({
                    pareja_1: clasificados[seed1] || `Clasificado${seed1 + 1}`,
                    pareja_2: clasificados[seed2] || `Clasificado${seed2 + 1}`
                });
            }
        }
        
        const codigoRonda = obtenerCodigoRonda(ronda);
        partidos.forEach(function(partido, index) {
            const partidoNum = index + 1;
            const html = `
                <div class="form-group row mb-2 partido-llave" data-ronda="${ronda}" data-partido="${partidoNum}">
                    <label class="col-sm-2 col-form-label">Partido ${partidoNum} (${codigoRonda}${partidoNum}):</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control pareja-1-input" name="llave_${ronda}[${index}][pareja_1]" 
                            value="${partido.pareja_1}" placeholder="Ej: Z1_P1">
                    </div>
                    <div class="col-sm-1 text-center align-self-center">VS</div>
                    <div class="col-sm-4">
                        <input type="text" class="form-control pareja-2-input" name="llave_${ronda}[${index}][pareja_2]" 
                            value="${partido.pareja_2}" placeholder="Ej: Z2_P4">
                    </div>
                </div>
            `;
            container.append(html);
        });
    }
    
    function generarLlaveRondaPosterior(ronda, cantidadPartidos, rondaAnterior) {
        const container = $(`#llave-${ronda}-content`);
        container.empty();
        
        const codigoRonda = obtenerCodigoRonda(ronda);
        
        for (let i = 0; i < cantidadPartidos; i++) {
            const partidoNum = i + 1;
            const pareja1 = `G${i * 2 + 1}-${rondaAnterior}`;
            const pareja2 = `G${i * 2 + 2}-${rondaAnterior}`;
            
            const html = `
                <div class="form-group row mb-2 partido-llave" data-ronda="${ronda}" data-partido="${partidoNum}">
                    <label class="col-sm-2 col-form-label">Partido ${partidoNum} (${codigoRonda}${partidoNum}):</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control pareja-1-input" name="llave_${ronda}[${i}][pareja_1]" 
                            value="${pareja1}" placeholder="Ej: G1-${rondaAnterior}">
                    </div>
                    <div class="col-sm-1 text-center align-self-center">VS</div>
                    <div class="col-sm-4">
                        <input type="text" class="form-control pareja-2-input" name="llave_${ronda}[${i}][pareja_2]" 
                            value="${pareja2}" placeholder="Ej: G2-${rondaAnterior}">
                    </div>
                </div>
            `;
            container.append(html);
        }
    }
    
    function obtenerCodigoRonda(ronda) {
        const codigos = {
            '16avos': '16',
            '8vos': 'O',
            '4tos': 'C',
            'semifinal': 'S',
            'final': 'F'
        };
        return codigos[ronda] || '';
    }

    function esReferencia16avos(ref) {
        if (!ref) return false;
        const raw = String(ref).trim().toUpperCase().replace(/\s+/g, '');
        return /^DA\d+$/.test(raw) || /^GANADOR_DA\d+$/.test(raw) || /^G\d+-16AVOS$/.test(raw);
    }

    function llaveContieneRefs16avos(llave) {
        if (!llave) return false;
        let data = llave;
        if (typeof data === 'string') {
            try { data = JSON.parse(data); } catch (e) { return false; }
        }
        if (!Array.isArray(data)) return false;
        return data.some(function(partido) {
            return esReferencia16avos(partido && partido.pareja_1) || esReferencia16avos(partido && partido.pareja_2);
        });
    }
    
    // Cargar ejemplo
    $('.btn-cargar-ejemplo').on('click', function() {
        const btn = $(this);
        $('#cantidad_parejas').val(btn.data('parejas'));
        $('#cantidad_zonas').val(btn.data('zonas'));
        $('#parejas_por_zona').val(btn.data('porzona'));
        $('#clasifican_primeros').val(btn.data('primeros'));
        $('#clasifican_segundos').val(btn.data('segundos'));
        $('#clasifican_terceros').val(btn.data('terceros'));
        $('#clasifican_cuartos').val(btn.data('cuartos'));
        
        // Actualizar checkboxes
        const total = btn.data('primeros') + btn.data('segundos') + btn.data('terceros') + btn.data('cuartos');
        $('#tiene_4tos').prop('checked', total >= 8);
        $('#tiene_8vos').prop('checked', total >= 16);
        $('#tiene_16avos').prop('checked', total >= 32);
        
        actualizarPreviewClasificacion();
        actualizarVisibilidadLlaves();
        generarLlavesAutomaticamente();
        
        // Generar nombre automático
        const nombre = `${btn.data('parejas')} parejas - ${btn.data('zonas')} zonas de ${btn.data('porzona')}`;
        $('#nombre').val(nombre);
    });
    
    // Guardar configuración
    $('#form-config-cruces').on('submit', function(e) {
        e.preventDefault();

        const tiene16avos = $('#tiene_16avos').is(':checked');
        const tiene8vos = $('#tiene_8vos').is(':checked');

        // Defensa para configs heredadas: no permitir refs a 16avos cuando la ronda está desactivada.
        if (!tiene16avos && tiene8vos) {
            const llave8Actual = obtenerLlave('8vos');
            if (llaveContieneRefs16avos(llave8Actual)) {
                generarLlavesAutomaticamente();
            }
        }
        
        const formData = {
            config_id: $('#config_id').val() || '',
            nombre: $('#nombre').val(),
            cantidad_parejas: $('#cantidad_parejas').val(),
            cantidad_zonas: $('#cantidad_zonas').val(),
            parejas_por_zona: $('#parejas_por_zona').val(),
            clasifican_primeros: $('#clasifican_primeros').val(),
            clasifican_segundos: $('#clasifican_segundos').val(),
            clasifican_terceros: $('#clasifican_terceros').val(),
            clasifican_cuartos: $('#clasifican_cuartos').val(),
            tiene_16avos_final: tiene16avos ? 1 : 0,
            tiene_8vos_final: tiene8vos ? 1 : 0,
            tiene_4tos_final: $('#tiene_4tos').is(':checked') ? 1 : 0,
            criterio_desempate_orden: $('#criterio_desempate_orden').val(),
            games_fase_grupos: $('#games_fase_grupos').val(),
            games_cruces: $('#games_cruces').val(),
            games_semifinal: $('#games_semifinal').val(),
            games_final: $('#games_final').val(),
            llave_16avos: tiene16avos ? obtenerLlave('16avos') : null,
            llave_8vos: obtenerLlave('8vos'),
            llave_4tos: obtenerLlave('4tos'),
            llave_semifinal: obtenerLlave('semifinal'),
            llave_final: obtenerLlave('final'),
            notas: $('#notas').val(),
            _token: '{{ csrf_token() }}'
        };
        
        $.ajax({
            type: 'POST',
            url: '{{ route("adminconfigamericanoguardar") }}',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Configuración guardada correctamente');
                    window.location.href = '{{ route("adminconfigamericano") }}';
                } else {
                    alert('Error al guardar: ' + (response.message || 'Error desconocido'));
                }
            },
            error: function(xhr) {
                alert('Error al guardar la configuración');
                console.error(xhr);
            }
        });
    });
    
    function obtenerLlave(ronda) {
        const partidos = [];
        $(`.partido-llave[data-ronda="${ronda}"]`).each(function() {
            const pareja1 = $(this).find('.pareja-1-input').val();
            const pareja2 = $(this).find('.pareja-2-input').val();
            if (pareja1 && pareja2) {
                partidos.push({
                    pareja_1: pareja1,
                    pareja_2: pareja2
                });
            }
        });
        return partidos.length > 0 ? JSON.stringify(partidos) : null;
    }
    
    // Eliminar configuración
    $('.btn-eliminar-config').on('click', function() {
        const configId = $(this).data('id');
        if (confirm('¿Está seguro de eliminar esta configuración?')) {
            $.ajax({
                type: 'POST',
                url: '{{ route("adminconfigamericanoeliminar") }}',
                data: {
                    config_id: configId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Configuración eliminada');
                        window.location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Error desconocido'));
                    }
                },
                error: function(xhr) {
                    alert('Error al eliminar');
                    console.error(xhr);
                }
            });
        }
    });
    
    // Cargar configuración existente si hay
    @if(isset($config) && $config !== null)
        // Ya se cargaron los valores via Blade, actualizar UI
        actualizarPreviewClasificacion();
        actualizarVisibilidadLlaves();
    @endif
});
</script>

@endsection
