@extends('bahia_padel.home.plantilla')

@section('title_header', 'Reglamento - Bahía Pádel')

@section('contenedor')
<section class="reglamento-header-img mb-4">
    <div class="reglamento-header-img-inner">
        <img src="{{ asset('images/home/reglamento.webp') }}" alt="Reglamento" class="img-fluid w-100">
        <div class="reglamento-header-img-overlay"></div>
        <h1 class="reglamento-header-title">Reglamento</h1>
    </div>
</section>
<section class="py-4 page-content-home reglamento-content">
    <div class="reglamento-block">
        <h2 class="reglamento-section-title">Modalidad de juego</h2>
        <ul class="reglamento-list">
            <li>Todos los partidos se disputarán al mejor de 3 sets desde la fase de zonas.</li>
            <li>El tiempo máximo de espera será de 15 minutos para todos los partidos, sin excepción.</li>
            <li>En caso de no presentarse alguno de los jugadores, se aplicará W.O.</li>
        </ul>
    </div>

    <div class="reglamento-block">
        <h2 class="reglamento-section-title">Entrada en calor</h2>
        <ul class="reglamento-list">
            <li>Cada partido contará con 5 minutos reglamentarios de calentamiento.</li>
        </ul>
    </div>

    <div class="reglamento-block">
        <h2 class="reglamento-section-title">Cambio de lado</h2>
        <ul class="reglamento-list">
            <li>El cambio de lado se realizará en los games impares, según reglamento oficial.</li>
        </ul>
    </div>

    <div class="reglamento-block">
        <h2 class="reglamento-section-title">Inscripción y pago</h2>
        <ul class="reglamento-list">
            <li>La inscripción podrá abonarse en efectivo o por transferencia.</li>
            <li>El pago deberá realizarse antes del primer partido (obligatorio).</li>
            <li>La pareja que no haya abonado será automáticamente descalificada.</li>
        </ul>
    </div>

    <div class="reglamento-block">
        <h2 class="reglamento-section-title">Conducta y disciplina</h2>
        <p class="reglamento-intro">Ante falta de respeto o daños en las instalaciones:</p>
        <ul class="reglamento-list">
            <li>Primer advertencia: Warning.</li>
            <li>Segundo warning: descalificación automática.</li>
            <li>El respeto hacia los rivales, organizadores e instalaciones es obligatorio.</li>
        </ul>
    </div>

    <div class="reglamento-block">
        <h2 class="reglamento-section-title">Control de categorías</h2>
        <p class="reglamento-intro">Jugadores considerados fuera de categoría por el complejo:</p>
        <ul class="reglamento-list">
            <li>Deberán abonar la inscripción.</li>
            <li>Serán automáticamente descalificados.</li>
            <li>Se solicita honestidad, especialmente a jugadores que provengan de otros complejos o ciudades.</li>
        </ul>
    </div>

    <div class="reglamento-block">
        <h2 class="reglamento-section-title">Carga de resultados</h2>
        <p class="reglamento-intro">Al finalizar cada partido:</p>
        <ul class="reglamento-list">
            <li>Un jugador de cada pareja deberá acercarse al mostrador.</li>
            <li>Informar el resultado y firmar la planilla.</li>
            <li>Sin excepción.</li>
        </ul>
    </div>

    <div class="reglamento-block">
        <h2 class="reglamento-section-title">Premios</h2>
        <p class="reglamento-intro">Los premios en dinero podrán entregarse en efectivo o por transferencia.</p>
        <ul class="reglamento-list">
            <li>1º Puesto: Trofeo + $$$</li>
            <li>2º Puesto: Trofeo + $$$</li>
            <li>3º Puesto: Inscripción gratuita</li>
            <li>4º Puesto: 20% de descuento en indumentaria o premio aportado por sponsors</li>
        </ul>
    </div>

    <div class="reglamento-block">
        <h2 class="reglamento-section-title">Master Final</h2>        
        <ul class="reglamento-list">
            <li>Clasifican los primeros 16 jugadores de cada categoría.</li>
            <li>Requisito: mínimo 5 torneos disputados en su categoría.</li>
            <li>El master final se jugará en Diciembre.</li>            
        </ul>
    </div>

    <div class="reglamento-block">
        <h2 class="reglamento-section-title">Inscripciones</h2>        
        <ul class="reglamento-list">
            <li>Se abren 1 semana antes de cada torneo.</li>
            <li>Cierran los miércoles a las 20:00hs, sin excepción.</li>            
        </ul>
    </div>

    <div class="reglamento-block">
        <h2 class="reglamento-section-title">Puntuación</h2>        
        <ul class="reglamento-list">
            <li>Campeón 100 puntos.</li>
            <li>Subcampeón 75 puntos.</li>
            <li>3er y 4to 50 puntos.</li>
            <li>4tos 25 puntos.</li>
            <li>8vos 15 puntos.</li>
            <li>16vos 10 puntos.</li>
            <li>Zona 5 puntos.</li>            
        </ul>
    </div>
</section>
@endsection
