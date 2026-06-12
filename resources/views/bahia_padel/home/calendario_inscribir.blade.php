@extends('bahia_padel.home.plantilla')

@section('title_header', 'Inscripción - Calendario - Bahía Pádel')

@section('contenedor')
@php
    $tituloTorneo = $evento->nombre ?: ($evento->categoria.'ª categoría · '.$evento->tipo_label);
    $txtFechas = $evento->textoFechasTorneo();
    $valorInscr = ($evento->valor_inscripcion !== null && $evento->valor_inscripcion !== '')
        ? '$'.number_format((float) $evento->valor_inscripcion, 0, ',', '.')
        : null;
@endphp
<style>
  .inscribir-resumen {
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    background: rgba(255, 255, 255, 0.04);
  }
  body.dark-mode .inscribir-resumen {
    background: rgba(45, 45, 45, 0.5);
    border-color: rgba(148, 163, 184, 0.25);
  }
  .inscribir-resumen h2 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }
  .inscribir-form label {
    font-weight: 500;
    font-size: 0.9rem;
  }
  .inscribir-form .form-section-title {
    font-size: 1rem;
    font-weight: 600;
    margin-top: 1.25rem;
    margin-bottom: 0.75rem;
    padding-bottom: 0.35rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.35);
  }
  .disp-box {
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 12px;
    padding: 1rem 1.1rem;
    background: rgba(255, 255, 255, 0.04);
  }
  body.dark-mode .disp-box {
    background: rgba(45, 45, 45, 0.5);
    border-color: rgba(148, 163, 184, 0.25);
  }
  .disp-row {
    display: grid;
    grid-template-columns: 140px 1fr 1fr;
    gap: 0.75rem;
    align-items: end;
  }
  @media (max-width: 768px) {
    .disp-row { grid-template-columns: 1fr; }
  }
  .disp-day {
    font-weight: 600;
  }
  .disp-hint {
    font-size: 0.9rem;
    color: rgba(100, 116, 139, 0.95);
  }
  body.dark-mode .disp-hint { color: rgba(203, 213, 225, 0.85); }
  .jugador-buscador {
    position: relative;
  }
  .jugador-buscador-lista {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 6px);
    z-index: 20;
    background: #fff;
    color: #0f172a;
    border: 1px solid rgba(148, 163, 184, 0.45);
    border-radius: 10px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
    max-height: 260px;
    overflow: auto;
    display: none;
  }
  body.dark-mode .jugador-buscador-lista {
    background: #1f2937;
    color: #e5e7eb;
    border-color: rgba(148, 163, 184, 0.25);
  }
  .jugador-buscador-item {
    padding: 0.6rem 0.85rem;
    cursor: pointer;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
    color: inherit;
  }
  .jugador-buscador-item:last-child {
    border-bottom: none;
  }
  .jugador-buscador-item:hover {
    background: rgba(255, 2, 100, 0.08);
  }
  body.dark-mode .jugador-buscador-item:hover {
    background: rgba(255, 2, 100, 0.18);
  }
  .jugador-seleccion {
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 10px;
    padding: 0.75rem 0.9rem;
    background: rgba(255, 255, 255, 0.04);
  }
  body.dark-mode .jugador-seleccion {
    background: rgba(45, 45, 45, 0.5);
    border-color: rgba(148, 163, 184, 0.25);
  }
</style>

<section class="page-header-img mb-4">
    <div class="page-header-img-inner">
        <img src="{{ asset('images/home/reglamento.webp') }}" alt="Inscripción" class="img-fluid w-100">
        <div class="page-header-img-overlay"></div>
        <h1 class="page-header-title">Inscripción</h1>
    </div>
</section>

<section class="py-3 page-content-home">
    <div class="inscribir-resumen">
        <h2 class="mb-1">{{ $tituloTorneo }}</h2>
        @if($evento->nombre)
            <p class="text-secondary small mb-2">{{ $evento->categoria }}ª · {{ $evento->tipo_label }}</p>
        @endif
        @if($txtFechas !== '')
            <p class="mb-0"><strong>Fecha:</strong> {{ $txtFechas }}</p>
        @endif
        @if($valorInscr)
            <p class="mb-0 mt-2"><strong>Valor inscripción por jugador:</strong> {{ $valorInscr }}</p>
        @endif
    </div>

    <form method="post" action="{{ route('home.calendario.inscribir.guardar', $evento) }}" class="inscribir-form">
        @csrf

        <div class="form-section-title">Jugador 1</div>
        <input type="hidden" id="jugador1_id" name="jugador1_id" value="">
        <input type="hidden" id="jugador1_nombre" name="jugador1_nombre" value="{{ old('jugador1_nombre') }}">
        <input type="hidden" id="jugador1_apellido" name="jugador1_apellido" value="{{ old('jugador1_apellido') }}">
        <input type="hidden" id="jugador1_telefono" name="jugador1_telefono" value="{{ old('jugador1_telefono') }}">
        <div class="form-group jugador-buscador">
            <label for="jugador1_buscar">Buscar jugador</label>
            <input type="text" class="form-control" id="jugador1_buscar" placeholder="Escribí nombre o apellido…" autocomplete="off">
            <div class="jugador-buscador-lista" id="jugador1_lista"></div>
            @if($errors->has('jugador1_nombre') || $errors->has('jugador1_apellido') || $errors->has('jugador1_telefono'))
                <div class="text-danger small mt-2">Completá el Jugador 1.</div>
            @endif
        </div>
        <div class="jugador-seleccion mb-3" id="jugador1_seleccion" style="display:none;"></div>
        <button type="button" class="btn btn-outline-secondary btn-sm mb-2" id="jugador1_btn_nuevo">Crear nuevo jugador</button>
        <div id="jugador1_form_nuevo" style="display:none;">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="jugador1_nuevo_nombre">Nombre</label>
                    <input type="text" class="form-control" id="jugador1_nuevo_nombre" maxlength="120">
                </div>
                <div class="form-group col-md-4">
                    <label for="jugador1_nuevo_apellido">Apellido</label>
                    <input type="text" class="form-control" id="jugador1_nuevo_apellido" maxlength="120">
                </div>
                <div class="form-group col-md-4">
                    <label for="jugador1_nuevo_tel">Teléfono <span class="text-muted font-weight-normal">(opcional)</span></label>
                    <input type="text" class="form-control" id="jugador1_nuevo_tel" maxlength="40" inputmode="tel">
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="jugador1_crear">Crear y seleccionar</button>
            <button type="button" class="btn btn-link btn-sm text-secondary" id="jugador1_cancelar">Cancelar</button>
        </div>

        <div class="form-section-title">Jugador 2</div>
        <input type="hidden" id="jugador2_id" name="jugador2_id" value="">
        <input type="hidden" id="jugador2_nombre" name="jugador2_nombre" value="{{ old('jugador2_nombre') }}">
        <input type="hidden" id="jugador2_apellido" name="jugador2_apellido" value="{{ old('jugador2_apellido') }}">
        <input type="hidden" id="jugador2_telefono" name="jugador2_telefono" value="{{ old('jugador2_telefono') }}">
        <div class="form-group jugador-buscador">
            <label for="jugador2_buscar">Buscar jugador</label>
            <input type="text" class="form-control" id="jugador2_buscar" placeholder="Escribí nombre o apellido…" autocomplete="off">
            <div class="jugador-buscador-lista" id="jugador2_lista"></div>
            @if($errors->has('jugador2_nombre') || $errors->has('jugador2_apellido'))
                <div class="text-danger small mt-2">Completá el Jugador 2.</div>
            @endif
        </div>
        <div class="jugador-seleccion mb-3" id="jugador2_seleccion" style="display:none;"></div>
        <button type="button" class="btn btn-outline-secondary btn-sm mb-2" id="jugador2_btn_nuevo">Crear nuevo jugador</button>
        <div id="jugador2_form_nuevo" style="display:none;">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="jugador2_nuevo_nombre">Nombre</label>
                    <input type="text" class="form-control" id="jugador2_nuevo_nombre" maxlength="120">
                </div>
                <div class="form-group col-md-4">
                    <label for="jugador2_nuevo_apellido">Apellido</label>
                    <input type="text" class="form-control" id="jugador2_nuevo_apellido" maxlength="120">
                </div>
                <div class="form-group col-md-4">
                    <label for="jugador2_nuevo_tel">Teléfono <span class="text-muted font-weight-normal">(opcional)</span></label>
                    <input type="text" class="form-control" id="jugador2_nuevo_tel" maxlength="40" inputmode="tel">
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="jugador2_crear">Crear y seleccionar</button>
            <button type="button" class="btn btn-link btn-sm text-secondary" id="jugador2_cancelar">Cancelar</button>
        </div>

        <div class="form-group">
            <label>Disponibilidad horaria</label>
            <div class="disp-box">
                <div class="disp-hint mb-3">
                    - Si ponés <strong>Desde 18:00</strong> y <strong>Hasta 22:00</strong>: puede jugar entre esas horas.<br>
                    - Si ponés solo <strong>Desde 18:00</strong>: puede jugar a partir de esa hora.<br>
                    - Si dejás vacío: <strong>sin restricciones</strong>.
                </div>

                <input type="hidden" id="disponibilidad_horaria" name="disponibilidad_horaria" value="{{ old('disponibilidad_horaria') }}">

                <div class="disp-row mb-3">
                    <div class="disp-day">Viernes</div>
                    <div>
                        <label class="small mb-1" for="disp_viernes_desde">Desde</label>
                        <input type="time" class="form-control" id="disp_viernes_desde">
                    </div>
                    <div>
                        <label class="small mb-1" for="disp_viernes_hasta">Hasta</label>
                        <input type="time" class="form-control" id="disp_viernes_hasta">
                    </div>
                </div>

                <div class="disp-row">
                    <div class="disp-day">Sábado</div>
                    <div>
                        <label class="small mb-1" for="disp_sabado_desde">Desde</label>
                        <input type="time" class="form-control" id="disp_sabado_desde">
                    </div>
                    <div>
                        <label class="small mb-1" for="disp_sabado_hasta">Hasta</label>
                        <input type="time" class="form-control" id="disp_sabado_hasta">
                    </div>
                </div>

                <div class="small text-muted mt-3">
                    Si no tenés problema de horarios, dejalo vacío.
                </div>
            </div>
            @error('disponibilidad_horaria')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary px-4">Guardar</button>
        <a href="{{ route('home.calendario') }}" class="btn btn-link text-secondary">Volver al calendario</a>
    </form>
</section>

<script>
(function() {
    // Usamos rutas internas del calendario para evitar problemas de prefijos (/Padel/public) y CSRF en la búsqueda.
    var path = window.location.pathname || '';
    var baseUrl = path.split('/calendario/')[0] || '{{ request()->getBaseUrl() }}' || '';
    var buscarUrl = baseUrl + '/calendario/buscar-jugadores';
    var crearUrl = baseUrl + '/calendario/crear-jugador';
    var csrf = (document.querySelector('meta[name=\"csrf-token\"]') || {}).content || '{{ csrf_token() }}';

    function escHtml(s) {
        return String(s === null || s === undefined ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function qs(id) { return document.getElementById(id); }

    function show(el) { if (el) el.style.display = 'block'; }
    function hide(el) { if (el) el.style.display = 'none'; }
    function setHtml(el, html) { if (el) el.innerHTML = html; }

    function postForm(url, dataObj) {
        var fd = new FormData();
        Object.keys(dataObj).forEach(function(k) { fd.append(k, dataObj[k]); });
        return fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: fd,
            credentials: 'same-origin'
        }).then(function(r) {
            return r.text().then(function(txt) {
                var j = null;
                try { j = txt ? JSON.parse(txt) : null; } catch (e) { j = null; }
                if (!r.ok) {
                    var msg = (j && j.message) ? j.message : ('Request failed (' + r.status + ')');
                    var err = new Error(msg);
                    err.payload = j;
                    err.raw = txt;
                    err.status = r.status;
                    err.url = url;
                    throw err;
                }
                return j || {};
            });
        });
    }

    function setJugador(prefix, j) {
        var idEl = qs(prefix + '_id');
        if (idEl) idEl.value = j.id ? String(j.id) : '';
        qs(prefix + '_nombre').value = j.nombre || '';
        qs(prefix + '_apellido').value = j.apellido || '';
        qs(prefix + '_telefono').value = j.telefono || '';

        var sel = qs(prefix + '_seleccion');
        setHtml(sel,
            '<div><strong>Seleccionado:</strong> ' + escHtml((j.nombre || '') + ' ' + (j.apellido || '')) + '</div>' +
            '<div class=\"small text-muted\">Tel: ' + escHtml(j.telefono || '—') + '</div>' +
            '<div class=\"mt-2\">' +
                '<button type=\"button\" class=\"btn btn-link btn-sm px-0 text-secondary mr-3\" id=\"' + prefix + '_limpiar\">Cambiar</button>' +
                '<button type=\"button\" class=\"btn btn-link btn-sm px-0 text-secondary\" id=\"' + prefix + '_editar\">Editar</button>' +
            '</div>'
        );
        show(sel);

        var buscar = qs(prefix + '_buscar');
        buscar.value = '';
        buscar.disabled = true;

        var lista = qs(prefix + '_lista');
        hide(lista);
        setHtml(lista, '');
        hide(qs(prefix + '_form_nuevo'));

        qs(prefix + '_limpiar').addEventListener('click', function() {
            var idEl = qs(prefix + '_id');
            if (idEl) idEl.value = '';
            qs(prefix + '_nombre').value = '';
            qs(prefix + '_apellido').value = '';
            qs(prefix + '_telefono').value = '';
            hide(sel);
            setHtml(sel, '');
            buscar.disabled = false;
            buscar.focus();
        });

        qs(prefix + '_editar').addEventListener('click', function() {
            var n = qs(prefix + '_nombre').value || '';
            var a = qs(prefix + '_apellido').value || '';
            var t = qs(prefix + '_telefono').value || '';
            setHtml(sel,
                '<div class=\"mb-2\"><strong>Editar jugador</strong></div>' +
                '<div class=\"form-row\">' +
                    '<div class=\"form-group col-md-4 mb-2\">' +
                        '<label class=\"small mb-1\">Nombre</label>' +
                        '<input type=\"text\" class=\"form-control form-control-sm\" id=\"' + prefix + '_edit_nombre\" value=\"' + escHtml(n) + '\">' +
                    '</div>' +
                    '<div class=\"form-group col-md-4 mb-2\">' +
                        '<label class=\"small mb-1\">Apellido</label>' +
                        '<input type=\"text\" class=\"form-control form-control-sm\" id=\"' + prefix + '_edit_apellido\" value=\"' + escHtml(a) + '\">' +
                    '</div>' +
                    '<div class=\"form-group col-md-4 mb-2\">' +
                        '<label class=\"small mb-1\">Teléfono</label>' +
                        '<input type=\"text\" class=\"form-control form-control-sm\" id=\"' + prefix + '_edit_telefono\" value=\"' + escHtml(t) + '\">' +
                    '</div>' +
                '</div>' +
                '<button type=\"button\" class=\"btn btn-primary btn-sm\" id=\"' + prefix + '_edit_guardar\">Guardar</button>' +
                '<button type=\"button\" class=\"btn btn-link btn-sm text-secondary\" id=\"' + prefix + '_edit_cancelar\">Cancelar</button>'
            );

            qs(prefix + '_edit_cancelar').addEventListener('click', function() {
                setJugador(prefix, { id: j.id, nombre: n, apellido: a, telefono: t });
            });
            qs(prefix + '_edit_guardar').addEventListener('click', function() {
                var nn = (qs(prefix + '_edit_nombre').value || '').trim();
                var aa = (qs(prefix + '_edit_apellido').value || '').trim();
                var tt = (qs(prefix + '_edit_telefono').value || '').trim();
                if (!nn || !aa) {
                    alert('Completá nombre y apellido.');
                    return;
                }
                setJugador(prefix, { id: j.id, nombre: nn, apellido: aa, telefono: tt });
            });
        });
    }

    function renderLista(prefix, jugadores) {
        var lista = qs(prefix + '_lista');
        if (!jugadores || jugadores.length === 0) {
            setHtml(lista, '<div class=\"jugador-buscador-item text-muted\">Sin resultados</div>');
            show(lista);
            return;
        }
        var html = jugadores.slice(0, 15).map(function(j) {
            var label = (j.nombre || '') + ' ' + (j.apellido || '');
            return '<div class=\"jugador-buscador-item\" data-id=\"' + escHtml(j.id) + '\" data-nombre=\"' + escHtml(j.nombre) + '\" data-apellido=\"' + escHtml(j.apellido) + '\" data-telefono=\"' + escHtml(j.telefono || '') + '\">' +
                '<strong>' + escHtml(label) + '</strong>' +
            '</div>';
        }).join('');
        setHtml(lista, html);
        show(lista);

        Array.prototype.forEach.call(lista.querySelectorAll('.jugador-buscador-item'), function(it) {
            it.addEventListener('click', function() {
                setJugador(prefix, {
                    id: it.getAttribute('data-id'),
                    nombre: it.getAttribute('data-nombre'),
                    apellido: it.getAttribute('data-apellido'),
                    telefono: it.getAttribute('data-telefono')
                });
            });
        });
    }

    function bindBuscador(prefix) {
        var t = null;
        var lastQuery = '';

        function buscarNow() {
            var q = qs(prefix + '_buscar').value.trim();
            lastQuery = q;
            if (t) window.clearTimeout(t);
            if (q.length < 1) {
                hide(qs(prefix + '_lista'));
                setHtml(qs(prefix + '_lista'), '');
                return;
            }
            var listaEl = qs(prefix + '_lista');
            setHtml(listaEl, '<div class=\"jugador-buscador-item text-muted\">Buscando…</div>');
            show(listaEl);
            t = window.setTimeout(function() {
                var fullUrl = buscarUrl + '?' + new URLSearchParams({ q: q }).toString();
                fetch(fullUrl, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                }).then(function(r) {
                    if (!r.ok) {
                        var err = new Error('Request failed (' + r.status + ')');
                        err.status = r.status;
                        err.url = fullUrl;
                        throw err;
                    }
                    return r.json();
                })
                    .then(function(r) {
                        if (qs(prefix + '_buscar').value.trim() !== lastQuery) return;
                        renderLista(prefix, (r && r.jugadores) ? r.jugadores : []);
                    })
                    .catch(function(err) {
                        var lista = qs(prefix + '_lista');
                        var msg = 'Error al buscar';
                        if (err && err.status) msg += ' (' + err.status + ')';
                        setHtml(lista, '<div class=\"jugador-buscador-item text-danger\">' + escHtml(msg) + '</div>' +
                            '<div class=\"jugador-buscador-item small text-muted\">' + escHtml((err && err.url) ? err.url : '') + '</div>'
                        );
                        show(lista);
                    });
            }, 120);
        }

        qs(prefix + '_buscar').addEventListener('input', buscarNow);
        qs(prefix + '_buscar').addEventListener('focus', function() {
            if (this.value.trim().length >= 1) buscarNow();
        });

        document.addEventListener('click', function(e) {
            var buscar = qs(prefix + '_buscar');
            var lista = qs(prefix + '_lista');
            if (!buscar.contains(e.target) && !lista.contains(e.target)) {
                hide(lista);
            }
        });

        qs(prefix + '_btn_nuevo').addEventListener('click', function() {
            var frm = qs(prefix + '_form_nuevo');
            frm.style.display = (frm.style.display === 'none' || frm.style.display === '') ? 'block' : 'none';
        });
        qs(prefix + '_cancelar').addEventListener('click', function() {
            hide(qs(prefix + '_form_nuevo'));
        });
        qs(prefix + '_crear').addEventListener('click', function() {
            var nombre = qs(prefix + '_nuevo_nombre').value.trim();
            var apellido = qs(prefix + '_nuevo_apellido').value.trim();
            var telefono = qs(prefix + '_nuevo_tel').value.trim();
            if (!nombre || !apellido) {
                alert('Completá nombre y apellido.');
                return;
            }
            var btn = this;
            btn.disabled = true;
            postForm(crearUrl, { _token: csrf, nombre: nombre, apellido: apellido, telefono: telefono })
                .then(function(r) {
                    if (!r || !r.success || !r.jugador) {
                        alert('No se pudo crear el jugador.');
                        return;
                    }
                    setJugador(prefix, r.jugador);
                })
                .catch(function(err) {
                    alert(err && err.message ? err.message : 'No se pudo crear el jugador.');
                })
                .finally(function() { btn.disabled = false; });
        });
    }

    bindBuscador('jugador1');
    bindBuscador('jugador2');

    function parseMaybeJson(s) {
        if (!s) return null;
        try { return JSON.parse(s); } catch (e) { return null; }
    }

    function buildDisponibilidad() {
        function norm(t) { return (t || '').trim(); }
        var vDesde = norm(qs('disp_viernes_desde').value);
        var vHasta = norm(qs('disp_viernes_hasta').value);
        var sDesde = norm(qs('disp_sabado_desde').value);
        var sHasta = norm(qs('disp_sabado_hasta').value);

        var data = {
            version: 1,
            viernes: { desde: vDesde || null, hasta: vHasta || null },
            sabado: { desde: sDesde || null, hasta: sHasta || null },
        };

        var sinRestricciones =
            !data.viernes.desde && !data.viernes.hasta &&
            !data.sabado.desde && !data.sabado.hasta;

        qs('disponibilidad_horaria').value = sinRestricciones ? '' : JSON.stringify(data);
    }

    // Inicializar desde old() si viene como JSON
    (function initDisp() {
        var oldVal = qs('disponibilidad_horaria').value || '';
        var parsed = parseMaybeJson(oldVal);
        if (!parsed) return;
        if (parsed.viernes) {
            if (parsed.viernes.desde) qs('disp_viernes_desde').value = parsed.viernes.desde;
            if (parsed.viernes.hasta) qs('disp_viernes_hasta').value = parsed.viernes.hasta;
        }
        if (parsed.sabado) {
            if (parsed.sabado.desde) qs('disp_sabado_desde').value = parsed.sabado.desde;
            if (parsed.sabado.hasta) qs('disp_sabado_hasta').value = parsed.sabado.hasta;
        }
        buildDisponibilidad();
    })();

    ['disp_viernes_desde','disp_viernes_hasta','disp_sabado_desde','disp_sabado_hasta'].forEach(function(id) {
        var el = qs(id);
        if (!el) return;
        el.addEventListener('input', buildDisponibilidad);
        el.addEventListener('change', buildDisponibilidad);
    });

    // Asegurar que el hidden se complete al enviar
    var form = document.querySelector('form.inscribir-form');
    if (form) {
        form.addEventListener('submit', function() {
            buildDisponibilidad();
        });
    }
})();
</script>
@endsection
