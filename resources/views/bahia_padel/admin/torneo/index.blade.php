@extends('bahia_padel/admin/plantilla')

@section('title_header','Torneos')

@section('contenedor')
    <style>
        .seccion-oculta {
            display: none !important;
        }
        .seccion-visible {
            display: flex !important;
        }
        .btn-grande {
            font-size: 2rem;
            padding: 1.5rem 3rem;
            font-weight: 600;
            border-radius: 12px;
            min-width: 800px;
            background-color: transparent !important;
            border-width: 3px;
            display: block;
            width: 100%;
            margin-bottom: 1.5rem;
        }
        .btn-primary.btn-grande {
            border-color: #007bff;
            color: #007bff;
        }
        .btn-primary.btn-grande:hover {
            background-color: #007bff !important;
            color: white;
        }
        .btn-success.btn-grande {
            border-color: #28a745;
            color: #28a745;
        }
        .btn-success.btn-grande:hover {
            background-color: #28a745 !important;
            color: white;
        }
        .btn-info.btn-grande {
            border-color: #17a2b8;
            color: #17a2b8;
        }
        .btn-info.btn-grande:hover {
            background-color: #17a2b8 !important;
            color: white;
        }
        .btn-warning.btn-grande {
            border-color: #f59e0b;
            color: #f59e0b;
        }
        .btn-warning.btn-grande:hover {
            background-color: #f59e0b !important;
            color: #0b1320;
        }
        #seccion_seleccionar_torneo {
            scrollbar-width: thin;
            scrollbar-color: #4e73df #f8f9fc;
        }
        #seccion_seleccionar_torneo::-webkit-scrollbar {
            width: 8px;
        }
        #seccion_seleccionar_torneo::-webkit-scrollbar-track {
            background: #f8f9fc;
        }
        #seccion_seleccionar_torneo::-webkit-scrollbar-thumb {
            background-color: #4e73df;
            border-radius: 4px;
        }
        #seccion_seleccionar_torneo::-webkit-scrollbar-thumb:hover {
            background-color: #375a7f;
        }
        @media (max-width: 768px) {
            .btn-grande {
                min-width: auto;
                font-size: 1.5rem;
                padding: 1rem 2rem;
            }
            #seccion_seleccionar_torneo {
                max-height: calc(100vh - 150px);
            }
        }
    </style>

    <!-- Sección de botones principales -->
    <div id="seccion_botones" class="d-flex justify-content-center align-items-center seccion-visible" style="min-height: 60vh;">
        <div style="width: 100%; max-width: 800px;">
            <button type="button" class="btn btn-primary btn-grande" onclick="mostrarNuevoTorneo()">
                Nuevo Torneo
            </button>
            <button type="button" class="btn btn-success btn-grande" onclick="mostrarSeleccionarTorneo()">
                Seleccionar Torneo
            </button>
            <a href="{{ route('tvtorneoshoy') }}" target="_blank" class="btn btn-info btn-grande" style="border-color: #17a2b8; color: #17a2b8;">
                <i class="fas fa-tv"></i> TV Torneos de Hoy
            </a>
            <a href="{{ route('tvtorneospuntuableszonas') }}" target="_blank" class="btn btn-warning btn-grande" style="border-color: #f59e0b; color: #f59e0b;">
                <i class="fas fa-tv"></i> TV Zonas Puntuables (Hoy)
            </a>
            <a href="{{ route('admintvcontrol') }}" class="btn btn-grande" style="border-color: #6f42c1; color: #6f42c1; background: transparent;">
                <i class="fas fa-sliders-h"></i> Control de TV
            </a>
        </div>
    </div>

    <!-- Sección del formulario de nuevo torneo -->
    <div id="seccion_form_nuevo_torneo" class="d-flex justify-content-center align-items-start seccion-oculta" style="min-height: 60vh; padding: 20px 0;">    
        <div style="width: 100%; max-width: 1200px; margin: 0 auto;">
            @include('bahia_padel.admin.torneo.form_nuevo_torneo')
        </div>
    </div>

    <!-- Sección de selección de torneo -->
    <div id="seccion_seleccionar_torneo" class="d-flex justify-content-center align-items-start seccion-oculta" style="min-height: 60vh; padding: 20px 0; overflow-y: auto; max-height: calc(100vh - 200px);">    
        <div style="width: 100%; max-width: 1400px; margin: 0 auto; padding: 0 15px;">
            @include('bahia_padel.admin.torneo.seleccionar_torneo')
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script>
        function mostrarNuevoTorneo() {    
            console.log('Función mostrarNuevoTorneo ejecutada');
            
            // Ocultar sección de botones
            document.getElementById('seccion_botones').className = 'd-flex justify-content-center align-items-center seccion-oculta';
            // Mostrar formulario de nuevo torneo
            document.getElementById('seccion_form_nuevo_torneo').className = 'd-flex justify-content-center align-items-start seccion-visible';
            // Ocultar sección de seleccionar torneo
            document.getElementById('seccion_seleccionar_torneo').className = 'd-flex justify-content-center align-items-start seccion-oculta';
        }

        function mostrarSeleccionarTorneo() {    
            console.log('Función mostrarSeleccionarTorneo ejecutada');
            
            // Ocultar sección de botones
            document.getElementById('seccion_botones').className = 'd-flex justify-content-center align-items-center seccion-oculta';
            // Mostrar sección de seleccionar torneo
            document.getElementById('seccion_seleccionar_torneo').className = 'd-flex justify-content-center align-items-start seccion-visible';
            // Ocultar formulario de nuevo torneo
            document.getElementById('seccion_form_nuevo_torneo').className = 'd-flex justify-content-center align-items-start seccion-oculta';
        }
    </script>
@endsection
