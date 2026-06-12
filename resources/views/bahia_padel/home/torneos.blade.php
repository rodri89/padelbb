@extends('bahia_padel.home.plantilla')

@section('title_header', 'Torneos - Bahía Pádel')

@section('contenedor')
<section class="page-header-img mb-4">
    <div class="page-header-img-inner">
        <img src="{{ asset('images/home/reglamento.webp') }}" alt="Torneos" class="img-fluid w-100">
        <div class="page-header-img-overlay"></div>
        <h1 class="page-header-title">Torneos</h1>
    </div>
</section>

<section class="torneos-filtros py-3">
    <div class="torneos-filtros-inner">
        <label class="torneos-label">Tipo de torneo</label>
        <select id="torneos-tipo" class="torneos-select">
            @foreach($tipos as $valor => $texto)
                <option value="{{ $valor }}" {{ $valor === 'todos' ? 'selected' : '' }}>{{ $texto }}</option>
            @endforeach
        </select>

        <label class="torneos-label">Año</label>
        <select id="torneos-anio" class="torneos-select">
            @foreach($anios as $a)
                <option value="{{ $a }}" {{ $a == $anioDefault ? 'selected' : '' }}>{{ $a }}</option>
            @endforeach
        </select>

        <label class="torneos-label">Meses</label>
        <div class="torneos-meses" id="torneos-meses">
            @foreach($meses as $num => $nombre)
                <button type="button" class="torneos-mes-chip" data-mes="{{ $num }}" aria-pressed="false">{{ $nombre }}</button>
            @endforeach
        </div>
        <p class="torneos-meses-leyenda text-secondary small mt-2 mb-0">Elegí un mes. Sin elegir = todos.</p>
    </div>
</section>

<section class="torneos-listado py-3">
    <div id="torneos-loading" class="torneos-loading text-center py-4" style="display: none;">
        <span class="text-secondary">Cargando torneos…</span>
    </div>
    <div id="torneos-lista" class="torneos-lista"></div>
    <div id="torneos-vacio" class="torneos-vacio text-center py-4 text-secondary" style="display: none;">
        No hay torneos con los filtros elegidos.
    </div>
</section>

<script>
(function() {
    var tipoSelect = document.getElementById('torneos-tipo');
    var anioSelect = document.getElementById('torneos-anio');
    var mesesContainer = document.getElementById('torneos-meses');
    var lista = document.getElementById('torneos-lista');
    var loading = document.getElementById('torneos-loading');
    var vacio = document.getElementById('torneos-vacio');
    var urlListado = '{{ route("home.torneos.listado") }}';
    var urlTorneoDetalle = '{{ url("/torneos") }}';

    function getMesesSeleccionados() {
        var chips = mesesContainer.querySelectorAll('.torneos-mes-chip[aria-pressed="true"]');
        if (chips.length === 0) return '';
        return Array.from(chips).map(function(c) { return c.getAttribute('data-mes'); }).join(',');
    }

    function cargarTorneos() {
        var anio = anioSelect.value;
        var tipo = tipoSelect.value;
        var meses = getMesesSeleccionados();

        loading.style.display = 'block';
        lista.style.display = 'none';
        vacio.style.display = 'none';
        lista.innerHTML = '';

        var params = new URLSearchParams({ anio: anio, tipo: tipo });
        if (meses) params.set('meses', meses);

        fetch(urlListado + '?' + params.toString(), {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            loading.style.display = 'none';
            if (data.success && data.torneos && data.torneos.length > 0) {
                lista.style.display = 'block';
                data.torneos.forEach(function(t) {
                    var card = document.createElement('a');
                    card.href = urlTorneoDetalle + '/' + t.id;
                    card.className = 'torneo-card-item torneo-card-item-clickable';
                    var tipoCat = (t.tipo && typeof t.tipo === 'string') ? (t.tipo.charAt(0).toUpperCase() + t.tipo.slice(1).toLowerCase()) : '';
                    var tipoLabel = tipoCat ? 'Puntuable · ' + tipoCat : 'Puntuable';
                    var fi = t.fecha_inicio ? formatFecha(t.fecha_inicio) : '';
                    var ff = t.fecha_fin ? formatFecha(t.fecha_fin) : '';
                    var fechas = fi && ff ? fi + ' – ' + ff : (fi || ff || '');
                    var contentHtml = '<div class="torneo-card-item-content">' +
                        '<div class="torneo-card-item-tipo">' + tipoLabel + '</div>' +
                        '<div class="torneo-card-item-nombre">' + (t.nombre || 'Sin nombre') + '</div>' +
                        (fechas ? '<div class="torneo-card-item-fechas">' + fechas + '</div>' : '') +
                        '</div>';
                    var ganadorHtml = '';
                    if (t.ganador && (t.ganador.foto1 || t.ganador.foto2)) {
                        ganadorHtml = '<div class="torneo-card-item-ganador" title="' + (t.ganador.nombre1 || '') + ' / ' + (t.ganador.nombre2 || '') + '">' +
                            '<span class="torneo-ganador-label">Ganadores</span>' +
                            '<div class="torneo-ganador-fotos">' +
                            (t.ganador.foto1 ? '<img src="' + t.ganador.foto1 + '" alt="" class="torneo-ganador-foto torneo-ganador-foto-1">' : '') +
                            (t.ganador.foto2 ? '<img src="' + t.ganador.foto2 + '" alt="" class="torneo-ganador-foto torneo-ganador-foto-2">' : '') +
                            '</div></div>';
                    }
                    card.innerHTML = contentHtml + ganadorHtml;
                    lista.appendChild(card);
                });
            } else {
                vacio.style.display = 'block';
            }
        })
        .catch(function() {
            loading.style.display = 'none';
            vacio.style.display = 'block';
            vacio.textContent = 'Error al cargar. Intentá de nuevo.';
        });
    }

    function formatFecha(str) {
        if (!str) return '';
        var d = new Date(str);
        if (isNaN(d.getTime())) return str;
        var dia = d.getDate();
        var mes = d.getMonth() + 1;
        return (dia < 10 ? '0' + dia : dia) + '/' + (mes < 10 ? '0' + mes : mes) + '/' + d.getFullYear();
    }

    mesesContainer.addEventListener('click', function(e) {
        var chip = e.target.closest('.torneos-mes-chip');
        if (!chip) return;
        var wasPressed = chip.getAttribute('aria-pressed') === 'true';
        // Un solo mes a la vez: desmarcar todos y marcar este solo si no estaba ya marcado
        mesesContainer.querySelectorAll('.torneos-mes-chip').forEach(function(c) {
            c.setAttribute('aria-pressed', 'false');
            c.classList.remove('active');
        });
        if (!wasPressed) {
            chip.setAttribute('aria-pressed', 'true');
            chip.classList.add('active');
        }
        cargarTorneos();
    });

    tipoSelect.addEventListener('change', cargarTorneos);
    anioSelect.addEventListener('change', cargarTorneos);

    cargarTorneos();
})();
</script>
@endsection
