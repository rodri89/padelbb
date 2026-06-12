<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bahia Padel - TV Display</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #0a0a0a;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .tv-container {
            position: relative;
            width: 100%;
            height: 100%;
        }
        
        .slide-frame {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            z-index: 1;
        }
        
        .slide-frame.active {
            opacity: 1;
            z-index: 10;
        }
        
        /* Indicador de progreso */
        .progress-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #4e73df, #1cc88a);
            z-index: 100;
            transition: width 0.1s linear;
        }
        
        /* Indicador de slide actual */
        .slide-indicator {
            position: fixed;
            bottom: 10px;
            right: 10px;
            display: flex;
            gap: 8px;
            z-index: 100;
            opacity: 0.7;
        }
        
        .slide-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transition: all 0.3s;
        }
        
        .slide-dot.active {
            background: #4e73df;
            transform: scale(1.3);
        }
        
        /* Loading */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #0a0a0a;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 1000;
        }
        
        .loading-overlay.hidden {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255,255,255,0.1);
            border-top-color: #4e73df;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loading-text {
            color: #888;
            margin-top: 20px;
            font-size: 1.2rem;
        }
        
        /* Info overlay (oculto por defecto, mostrar con tecla I) */
        .info-overlay {
            position: fixed;
            top: 10px;
            left: 10px;
            background: rgba(0,0,0,0.8);
            color: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            z-index: 100;
            display: none;
        }
        
        .info-overlay.visible {
            display: block;
        }
        
        .info-overlay h4 {
            margin-bottom: 10px;
            color: #4e73df;
        }
        
        .info-overlay p {
            margin: 5px 0;
            color: #ccc;
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loading">
        <div class="loading-spinner"></div>
        <div class="loading-text">Cargando vistas...</div>
    </div>
    
    <div class="tv-container" id="tv-container">
        <!-- Los iframes se cargan dinámicamente -->
    </div>
    
    <div class="progress-bar" id="progress-bar"></div>
    
    <div class="slide-indicator" id="slide-indicator">
        <!-- Los dots se generan dinámicamente -->
    </div>
    
    <div class="info-overlay" id="info-overlay">
        <h4><i class="fas fa-info-circle"></i> Info Display</h4>
        <p>Slide actual: <span id="info-current">-</span></p>
        <p>Total slides: <span id="info-total">{{ count($slides) }}</span></p>
        <p>Próximo cambio: <span id="info-countdown">-</span>s</p>
        <p style="font-size: 0.8rem; color: #666; margin-top: 10px;">Presioná 'I' para ocultar</p>
    </div>
    
    <script>
        const slides = @json($slides);
        const intervaloDefault = {{ $intervalo }};
        let configTimestamp = {{ $configTimestamp }};
        
        let currentSlide = 0;
        let slideTimer = null;
        let progressInterval = null;
        let progressValue = 0;
        
        // Inicializar
        $(document).ready(function() {
            if (slides.length === 0) {
                $('#loading .loading-text').text('No hay vistas configuradas');
                return;
            }
            
            crearIframes();
            iniciarRotacion();
            iniciarPollingConfig();
            
            // Tecla I para mostrar/ocultar info
            $(document).keypress(function(e) {
                if (e.key === 'i' || e.key === 'I') {
                    $('#info-overlay').toggleClass('visible');
                }
            });
        });
        
        function crearIframes() {
            const container = $('#tv-container');
            const indicator = $('#slide-indicator');
            
            slides.forEach((slide, index) => {
                // Crear iframe
                const iframe = $(`<iframe class="slide-frame" id="frame-${index}" data-index="${index}"></iframe>`);
                container.append(iframe);
                
                // Crear dot indicador
                const dot = $(`<div class="slide-dot" data-index="${index}"></div>`);
                indicator.append(dot);
            });
            
            // Cargar el primer iframe
            cargarSlide(0);
            
            // Precargar el siguiente
            if (slides.length > 1) {
                setTimeout(() => cargarSlide(1), 2000);
            }
        }
        
        function cargarSlide(index) {
            const iframe = $(`#frame-${index}`);
            if (iframe.attr('src')) return; // Ya cargado
            
            iframe.attr('src', slides[index].url);
            iframe.on('load', function() {
                if (index === 0) {
                    $('#loading').addClass('hidden');
                }
            });
        }
        
        function iniciarRotacion() {
            mostrarSlide(0);
        }
        
        function mostrarSlide(index) {
            currentSlide = index;
            const slide = slides[index];
            const duracion = (slide.duracion || intervaloDefault) * 1000;
            const esVistaZonas = String(slide.tipo || '').indexOf('zonas_') === 0;
            
            // Actualizar clases
            $('.slide-frame').removeClass('active');
            const iframe = $(`#frame-${index}`);
            iframe.addClass('active');
            
            // En vistas con rotación interna (zonas), recargar el iframe para que empiece desde el principio
            if (esVistaZonas && iframe.attr('src')) {
                // Forzar recarga añadiendo timestamp para evitar cache
                const baseUrl = slides[index].url.split('&_t=')[0];
                iframe.attr('src', baseUrl + '&_t=' + Date.now());
            }
            
            $('.slide-dot').removeClass('active');
            $(`.slide-dot[data-index="${index}"]`).addClass('active');
            
            // Actualizar info
            $('#info-current').text((index + 1) + ' - ' + (slide.torneo?.nombre || slide.torneo_nombre || 'Torneo'));

            // En vistas de zonas ya existe barra interna; ocultar la global para evitar duplicado
            if (esVistaZonas) {
                $('#progress-bar').hide().css('width', '0%');
            } else {
                $('#progress-bar').show();
            }
            
            // Precargar siguiente
            const nextIndex = (index + 1) % slides.length;
            cargarSlide(nextIndex);
            
            // Iniciar barra de progreso
            iniciarProgreso(duracion);
            
            // Timer para siguiente slide
            clearTimeout(slideTimer);
            slideTimer = setTimeout(() => {
                mostrarSlide(nextIndex);
            }, duracion);
        }
        
        function iniciarProgreso(duracion) {
            if ($('#progress-bar').is(':hidden')) {
                clearInterval(progressInterval);
                return;
            }

            progressValue = 0;
            const step = 100 / (duracion / 100);
            
            clearInterval(progressInterval);
            progressInterval = setInterval(() => {
                progressValue += step;
                $('#progress-bar').css('width', progressValue + '%');
                
                // Countdown para info
                const remaining = Math.ceil((100 - progressValue) / step / 10);
                $('#info-countdown').text(remaining);
                
                if (progressValue >= 100) {
                    clearInterval(progressInterval);
                }
            }, 100);
        }
        
        // Polling para detectar cambios en la configuración
        function iniciarPollingConfig() {
            setInterval(() => {
                $.get('{{ route("tvconfig.obtener") }}')
                    .done(function(data) {
                        if (data.updated_at && data.updated_at !== configTimestamp) {
                            // Configuración cambió, recargar página
                            console.log('Configuración actualizada, recargando...');
                            location.reload();
                        }
                    });
            }, 10000); // Cada 10 segundos
        }
    </script>
</body>
</html>
