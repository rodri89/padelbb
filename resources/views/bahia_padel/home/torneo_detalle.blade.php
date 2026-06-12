@extends('bahia_padel.home.plantilla')

@section('title_header', ($torneo->nombre ?? 'Torneo') . ' - Bahía Pádel')

@section('contenedor')
<section class="page-header-img mb-4">
    <div class="page-header-img-inner">
        <img src="{{ asset('images/home/reglamento.webp') }}" alt="Torneo" class="img-fluid w-100">
        <div class="page-header-img-overlay"></div>
        <h1 class="page-header-title">{{ $torneo->nombre ?? 'Torneo' }}</h1>
    </div>
</section>

<section class="torneo-detalle-acciones py-4">
    <div class="torneo-detalle-inner">
        <p class="torneo-detalle-leyenda text-secondary mb-3">Elegí qué ver de este torneo:</p>
        <div class="torneo-detalle-buttons mb-3">
            <button type="button" id="btn-detalle-zonas" class="torneo-detalle-btn torneo-detalle-btn-zonas torneo-detalle-btn-active">Zonas</button>
            @if($tiene_cruces)
            <button type="button" id="btn-detalle-cruces" class="torneo-detalle-btn torneo-detalle-btn-cruces">Cruces</button>
            @endif
        </div>

        <div id="torneo-zonas-section" class="torneo-detalle-section">
            <div id="torneo-zonas-loading" class="text-secondary small mb-2">Cargando zonas…</div>
            <div id="torneo-zonas-contenido"></div>
        </div>

        <div id="torneo-cruces-section" class="torneo-detalle-section" style="display: none;">
            <div id="torneo-cruces-loading" class="text-secondary small mb-2" style="display: none;">Cargando cruces…</div>
            <div id="torneo-cruces-contenido"></div>
        </div>

        <a href="{{ route('home.torneos') }}" class="torneo-detalle-volver mt-4 d-inline-block text-secondary small">← Volver a Torneos</a>
    </div>
</section>

<script>
(function() {
    var zonasBtn = document.getElementById('btn-detalle-zonas');
    var crucesBtn = document.getElementById('btn-detalle-cruces');
    var zonasSection = document.getElementById('torneo-zonas-section');
    var crucesSection = document.getElementById('torneo-cruces-section');
    var zonasLoading = document.getElementById('torneo-zonas-loading');
    var zonasContenido = document.getElementById('torneo-zonas-contenido');
    var zonasUrl = '{{ route("home.torneo.zonas", ["id" => $torneo->id]) }}';
    var crucesUrl = '{{ route("home.torneo.cruces", ["id" => $torneo->id]) }}';
    var crucesLoading = document.getElementById('torneo-cruces-loading');
    var crucesContenido = document.getElementById('torneo-cruces-contenido');
    var crucesCargados = false;

    function activarTab(tab) {
        if (tab === 'zonas') {
            zonasSection.style.display = 'block';
            if (crucesSection) crucesSection.style.display = 'none';
            zonasBtn.classList.add('torneo-detalle-btn-active');
            if (crucesBtn) crucesBtn.classList.remove('torneo-detalle-btn-active');
        } else if (tab === 'cruces') {
            zonasSection.style.display = 'none';
            if (crucesSection) crucesSection.style.display = 'block';
            if (crucesBtn) crucesBtn.classList.add('torneo-detalle-btn-active');
            zonasBtn.classList.remove('torneo-detalle-btn-active');
        }
    }

    function buildPartidoCard(p) {
        var card = document.createElement('div');
        card.className = 'torneo-zona-partido-card';
        var header = document.createElement('div');
        header.className = 'torneo-zona-partido-header';
        header.innerHTML = '<i class="fas fa-circle torneo-zona-partido-icon"></i> Partido ' + (p.partido_numero || '');
        card.appendChild(header);

        var cuerpo = document.createElement('div');
        cuerpo.className = 'torneo-zona-partido-body';

        if (p.dia || p.horario) {
            var lineaHorario = document.createElement('div');
            lineaHorario.className = 'torneo-zona-partido-horario mb-2';
            lineaHorario.style.fontSize = '0.9rem';
            lineaHorario.style.color = '#555';
            var texto = [];
            if (p.dia) texto.push('Día: ' + String(p.dia).trim());
            if (p.horario) texto.push('Horario: ' + String(p.horario).trim());
            lineaHorario.textContent = texto.join(' · ');
            cuerpo.appendChild(lineaHorario);
        }

        function labelPareja(pareja, fallback) {
            var l = pareja && pareja.label ? String(pareja.label).trim() : '';
            if (!l || /^[\s\/]+$/.test(l)) l = (pareja && pareja.referencia) ? String(pareja.referencia).trim() : fallback;
            return l || fallback;
        }
        var label1 = labelPareja(p.pareja_1, 'Pareja 1');
        var label2 = labelPareja(p.pareja_2, 'Pareja 2');

        var fila1 = document.createElement('div');
        fila1.className = 'torneo-zona-partido-linea';
        fila1.innerHTML =
            '<div class="torneo-zona-pareja-info">' +
                '<div class="torneo-zona-jugadores">' +
                    (p.pareja_1 && p.pareja_1.jugador_1 ? '<img class="torneo-zona-player-img" src=\"' + p.pareja_1.jugador_1.foto + '\" alt=\"\">' : '') +
                    (p.pareja_1 && p.pareja_1.jugador_2 ? '<img class="torneo-zona-player-img" src=\"' + p.pareja_1.jugador_2.foto + '\" alt=\"\">' : '') +
                '</div>' +
                '<span class="torneo-zona-pareja-label">' + label1 + '</span>' +
            '</div>' +
            '<span class="torneo-zona-resultado">' +
                (p.resultado ? (p.resultado.p1_set1 + ' - ' + p.resultado.p1_set2 + ' - ' + p.resultado.p1_set3) : '') +
            '</span>';

        var fila2 = document.createElement('div');
        fila2.className = 'torneo-zona-partido-linea';
        fila2.innerHTML =
            '<div class="torneo-zona-pareja-info">' +
                '<div class="torneo-zona-jugadores">' +
                    (p.pareja_2 && p.pareja_2.jugador_1 ? '<img class="torneo-zona-player-img" src=\"' + p.pareja_2.jugador_1.foto + '\" alt=\"\">' : '') +
                    (p.pareja_2 && p.pareja_2.jugador_2 ? '<img class="torneo-zona-player-img" src=\"' + p.pareja_2.jugador_2.foto + '\" alt=\"\">' : '') +
                '</div>' +
                '<span class="torneo-zona-pareja-label">' + label2 + '</span>' +
            '</div>' +
            '<span class="torneo-zona-resultado">' +
                (p.resultado ? (p.resultado.p2_set1 + ' - ' + p.resultado.p2_set2 + ' - ' + p.resultado.p2_set3) : '') +
            '</span>';

        cuerpo.appendChild(fila1);
        cuerpo.appendChild(fila2);
        card.appendChild(cuerpo);

        return card;
    }

    function renderZonas(data) {
        zonasContenido.innerHTML = '';
        if (!data || !data.zonas || data.zonas.length === 0) {
            zonasContenido.innerHTML = '<p class="text-secondary small mb-0">Todavía no hay zonas cargadas para este torneo.</p>';
            return;
        }
        data.zonas.forEach(function(z) {
            var bloque = document.createElement('div');
            bloque.className = 'torneo-zona-bloque';
            var titulo = document.createElement('h3');
            titulo.className = 'torneo-zona-titulo';
            titulo.textContent = 'Zona ' + (z.zona || '');
            bloque.appendChild(titulo);

            if (z.partidos && z.partidos.length > 0) {
                var sub = document.createElement('h4');
                sub.className = 'torneo-zona-subtitulo';
                sub.textContent = 'Partidos';
                bloque.appendChild(sub);

                z.partidos.forEach(function(p) {
                    var card = buildPartidoCard(p);
                    bloque.appendChild(card);
                });
            }

            if (z.clasificacion && z.clasificacion.length > 0) {
                var sub2 = document.createElement('h4');
                sub2.className = 'torneo-zona-subtitulo mt-3';
                sub2.textContent = 'Clasificación';
                bloque.appendChild(sub2);

                var lista = document.createElement('ul');
                lista.className = 'torneo-zona-clasificacion';
                z.clasificacion.forEach(function(c) {
                    var li = document.createElement('li');
                    li.innerHTML = '<strong>' + (c.posicion || '') + 'º</strong> ' + (c.label || '');
                    lista.appendChild(li);
                });
                bloque.appendChild(lista);
            }

            zonasContenido.appendChild(bloque);
        });
    }

    function renderCruces(data) {
        crucesContenido.innerHTML = '';
        if (!data || !data.rondas || data.rondas.length === 0) {
            crucesContenido.innerHTML = '<p class="text-secondary small mb-0">Todavía no hay cruces cargados para este torneo.</p>';
            return;
        }
        data.rondas.forEach(function(r) {
            if (!r.partidos || r.partidos.length === 0) return;
            var bloque = document.createElement('div');
            bloque.className = 'torneo-zona-bloque';
            var titulo = document.createElement('h3');
            titulo.className = 'torneo-zona-titulo';
            titulo.textContent = (r.label || '').toUpperCase();
            bloque.appendChild(titulo);

            var sub = document.createElement('h4');
            sub.className = 'torneo-zona-subtitulo';
            sub.textContent = 'Partidos';
            bloque.appendChild(sub);

            r.partidos.forEach(function(p) {
                var card = buildPartidoCard(p);
                bloque.appendChild(card);
            });

            crucesContenido.appendChild(bloque);
        });
    }

    function cargarCruces() {
        crucesLoading.style.display = 'block';
        crucesContenido.innerHTML = '';
        fetch(crucesUrl, { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                crucesLoading.style.display = 'none';
                if (!data.success) {
                    crucesContenido.innerHTML = '<p class="text-secondary small mb-0">No se pudieron cargar los cruces.</p>';
                    return;
                }
                renderCruces(data);
                crucesCargados = true;
            })
            .catch(function() {
                crucesLoading.style.display = 'none';
                crucesContenido.innerHTML = '<p class="text-secondary small mb-0">Error al cargar los cruces.</p>';
            });
    }

    function cargarZonas() {
        zonasLoading.style.display = 'block';
        zonasContenido.innerHTML = '';
        fetch(zonasUrl, { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                zonasLoading.style.display = 'none';
                if (!data.success) {
                    zonasContenido.innerHTML = '<p class=\"text-secondary small mb-0\">No se pudieron cargar las zonas.</p>';
                    return;
                }
                renderZonas(data);
            })
            .catch(function() {
                zonasLoading.style.display = 'none';
                zonasContenido.innerHTML = '<p class=\"text-secondary small mb-0\">Error al cargar las zonas.</p>';
            });
    }

    zonasBtn.addEventListener('click', function(e) {
        e.preventDefault();
        activarTab('zonas');
    });
    if (crucesBtn) {
        crucesBtn.addEventListener('click', function(e) {
            e.preventDefault();
            activarTab('cruces');
            if (!crucesCargados) {
                cargarCruces();
            }
        });
    }

    // Inicial
    activarTab('zonas');
    cargarZonas();
})();
</script>
@endsection
