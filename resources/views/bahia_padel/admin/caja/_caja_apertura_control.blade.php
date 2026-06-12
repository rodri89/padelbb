@php
    $fmtApertura = fn ($n) => '$' . number_format((float) $n, 2, ',', '.');
    $tieneApertura = $cajaApertura !== null;
    $montoAperturaFmt = $tieneApertura ? $fmtApertura($cajaApertura->monto_efectivo_inicial) : '';
    $mostrarIniciar = !$tieneApertura && $fechaCajaEsHoy;
    $mostrarMonto = $tieneApertura;
@endphp

@push('topbar_nav')
@if($mostrarIniciar || $mostrarMonto)
<li class="nav-item d-flex align-items-center" id="caja-apertura-nav-item">
  @if($mostrarIniciar)
    <button type="button" class="btn btn-sm btn-success ml-1 caja-apertura-trigger" data-caja-apertura-interactive="1" title="Registrar efectivo inicial de caja">
      Iniciar Caja
    </button>
  @elseif($mostrarMonto)
    <span
      class="nav-link py-1 px-2 {{ $puedeEditarAperturaCaja ? 'caja-apertura-editable caja-apertura-trigger' : '' }}"
      @if($puedeEditarAperturaCaja)
        data-caja-apertura-interactive="1"
        role="button"
        title="Efectivo inicial de caja (tocá para editar)"
      @else
        title="Efectivo inicial de caja"
      @endif
    >
      <i class="fas fa-cash-register mr-1"></i>
      <span id="caja-apertura-monto-label">{{ $montoAperturaFmt }}</span>
    </span>
  @endif
</li>
@endif
@endpush

<div class="modal fade" id="modal-caja-apertura" tabindex="-1" role="dialog" aria-labelledby="modal-caja-apertura-titulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title" id="modal-caja-apertura-titulo">Efectivo inicial de caja</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body py-3">
        <p class="small text-muted mb-3">Ingresá el efectivo con el que arranca la caja el <strong>{{ $fechaCajaLabel }}</strong>.</p>
        <div class="form-group mb-0">
          <label for="caja-apertura-monto-input" class="font-weight-bold">Monto en efectivo</label>
          <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
            <input
              type="text"
              inputmode="decimal"
              class="form-control"
              id="caja-apertura-monto-input"
              placeholder="0,00"
              value="{{ $tieneApertura ? number_format((float) $cajaApertura->monto_efectivo_inicial, 2, ',', '.') : '' }}"
              autocomplete="off">
          </div>
          <div class="invalid-feedback d-block" id="caja-apertura-error" style="display:none !important;"></div>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-sm btn-success" id="caja-apertura-guardar">Guardar</button>
      </div>
    </div>
  </div>
</div>

<style>
.caja-apertura-editable {
  cursor: pointer;
}
.caja-apertura-editable:hover {
  color: #4e73df !important;
}
#caja-apertura-nav-item .btn-success {
  white-space: nowrap;
}
</style>

<script>
(function () {
  var storeUrl = @json(route('admincaja.apertura.store'));
  var fechaCaja = @json($fechaCaja);
  var puedeEditar = @json($puedeEditarAperturaCaja);
  var csrfMeta = document.querySelector('meta[name="csrf-token"]');
  var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

  function parseMontoInput(raw) {
    if (raw == null) return NaN;
    var s = String(raw).trim().replace(/\$/g, '').replace(/\s/g, '');
    if (!s) return NaN;
    if (s.indexOf(',') !== -1) {
      s = s.replace(/\./g, '').replace(',', '.');
    }
    return parseFloat(s);
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function setError(msg) {
    var err = document.getElementById('caja-apertura-error');
    var inp = document.getElementById('caja-apertura-monto-input');
    if (!err) return;
    if (msg) {
      err.textContent = msg;
      err.style.display = 'block';
      if (inp) inp.classList.add('is-invalid');
    } else {
      err.textContent = '';
      err.style.display = 'none';
      if (inp) inp.classList.remove('is-invalid');
    }
  }

  function renderMontoNav(montoFmt) {
    var navItem = document.getElementById('caja-apertura-nav-item');
    if (!navItem) return;
    var editableClass = puedeEditar ? ' caja-apertura-editable' : '';
    var roleAttr = puedeEditar ? ' role="button"' : '';
    var title = puedeEditar
      ? 'Efectivo inicial de caja (tocá para editar)'
      : 'Efectivo inicial de caja';
    navItem.innerHTML =
      '<span class="nav-link py-1 px-2' + editableClass + (puedeEditar ? ' caja-apertura-trigger' : '') + '"' + roleAttr +
        (puedeEditar ? ' data-caja-apertura-interactive="1"' : '') +
        ' title="' + escapeHtml(title) + '">' +
        '<i class="fas fa-cash-register mr-1"></i>' +
        '<span id="caja-apertura-monto-label">' + escapeHtml(montoFmt) + '</span>' +
      '</span>';
    wireTriggers();
  }

  function openModal() {
    if (!window.jQuery) return;
    setError('');
    window.jQuery('#modal-caja-apertura').modal('show');
    var inp = document.getElementById('caja-apertura-monto-input');
    if (inp) {
      setTimeout(function () { inp.focus(); inp.select(); }, 300);
    }
  }

  function wireTriggers() {
    document.querySelectorAll('.caja-apertura-trigger[data-caja-apertura-interactive="1"]').forEach(function (trigger) {
      if (trigger._cajaAperturaWired) return;
      trigger._cajaAperturaWired = true;
      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        openModal();
      });
    });
  }

  function wireGuardar() {
    var btn = document.getElementById('caja-apertura-guardar');
    if (!btn || btn._cajaAperturaWired) return;
    btn._cajaAperturaWired = true;
    btn.addEventListener('click', function () {
      var inp = document.getElementById('caja-apertura-monto-input');
      var monto = parseMontoInput(inp ? inp.value : '');
      if (isNaN(monto) || monto < 0) {
        setError('Ingresá un monto válido mayor o igual a 0.');
        return;
      }
      setError('');
      btn.disabled = true;
      fetch(storeUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          _token: csrfToken,
          fecha: fechaCaja,
          monto_efectivo_inicial: monto
        })
      })
        .then(function (res) {
          return res.json().then(function (data) {
            return { ok: res.ok, status: res.status, data: data };
          });
        })
        .then(function (result) {
          btn.disabled = false;
          if (!result.ok) {
            var msg = 'No se pudo guardar.';
            if (result.data) {
              if (result.data.message) msg = result.data.message;
              else if (result.data.errors && result.data.errors.monto_efectivo_inicial) {
                msg = result.data.errors.monto_efectivo_inicial[0];
              }
            }
            setError(msg);
            return;
          }
          renderMontoNav(result.data.monto_fmt);
          if (window.jQuery) {
            window.jQuery('#modal-caja-apertura').modal('hide');
          }
        })
        .catch(function () {
          btn.disabled = false;
          setError('Error de conexión. Intentá de nuevo.');
        });
    });
  }

  function initCajaAperturaControl() {
    wireTriggers();
    wireGuardar();
    var inp = document.getElementById('caja-apertura-monto-input');
    if (inp && !inp._cajaAperturaWired) {
      inp._cajaAperturaWired = true;
      inp.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          var guardar = document.getElementById('caja-apertura-guardar');
          if (guardar) guardar.click();
        }
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCajaAperturaControl);
  } else {
    initCajaAperturaControl();
  }
})();
</script>
