<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bahia Padel - TV</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        html, body {
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            background: linear-gradient(135deg, #0a0f1a 0%, #1a1f2e 100%);
            font-family: "Segoe UI", Arial, sans-serif;
            color: #e2e8f0;
        }
        
        .container {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2vw;
        }
        
        .logo {
            font-size: 8vh;
            font-weight: 300;
            color: #fff;
            margin-bottom: 4vh;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }
        
        .logo span {
            color: #3b82f6;
        }
        
        .mensaje {
            font-size: 3vh;
            font-weight: 300;
            color: rgba(255,255,255,0.7);
            margin-bottom: 2vh;
        }
        
        .fecha {
            font-size: 2.5vh;
            font-weight: 300;
            color: rgba(255,255,255,0.5);
            margin-bottom: 6vh;
        }
        
        .esperando {
            display: flex;
            align-items: center;
            gap: 1vw;
            color: rgba(255,255,255,0.4);
            font-size: 2vh;
        }
        
        .dot {
            width: 1vh;
            height: 1vh;
            border-radius: 50%;
            background: #3b82f6;
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        .dot:nth-child(2) { animation-delay: 0.2s; }
        .dot:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes pulse {
            0%, 100% { opacity: 0.3; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        
        .refresh-info {
            position: fixed;
            bottom: 3vh;
            font-size: 1.6vh;
            color: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="logo">Bahia <span>Padel</span></h1>
        <p class="mensaje">{{ $mensaje ?? 'No hay torneos programados' }}</p>
        <p class="fecha">{{ \Carbon\Carbon::parse($fecha ?? now())->format('d/m/Y') }}</p>
        <div class="esperando">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
            <span>Verificando torneos...</span>
        </div>
    </div>
    
    <p class="refresh-info">La página se actualizará automáticamente cuando haya torneos disponibles</p>
    
    <script>
        // Recargar cada 30 segundos para detectar nuevos torneos
        setTimeout(() => window.location.reload(), 30000);
    </script>
</body>
</html>
