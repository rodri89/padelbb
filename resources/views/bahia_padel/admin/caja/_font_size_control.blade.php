@push('head')
<script>
(function () {
  var scale = 1;
  try {
    var v = parseFloat(localStorage.getItem('cajaFontScale'));
    if (!isNaN(v)) scale = v;
  } catch (e) {}
  scale = Math.min(1.35, Math.max(0.85, scale));
  document.documentElement.classList.add('caja-font-active');
  document.documentElement.style.setProperty('--caja-font-scale', String(scale));
})();
</script>
<style>
html.caja-font-active {
  font-size: calc(16px * var(--caja-font-scale, 1));
}
#modal-caja-font-size .modal-dialog {
  max-width: 420px;
}
@media (max-width: 576px) {
  #modal-caja-font-size .modal-dialog {
    margin: 0.5rem;
    max-width: calc(100% - 1rem);
  }
}
#modal-caja-font-size .caja-font-range {
  width: 100%;
  min-height: 44px;
  cursor: pointer;
  accent-color: #4e73df;
}
#modal-caja-font-size .caja-font-presets .btn {
  min-width: 4.5rem;
}
#modal-caja-font-size .caja-font-preview {
  border: 1px dashed #d1d3e2;
  border-radius: 0.35rem;
  padding: 0.75rem 1rem;
  background: #f8f9fc;
}
body.dark-mode #modal-caja-font-size .caja-font-preview {
  background: #2d2d3a;
  border-color: #4a4a5a;
  color: #e0e0e0;
}
</style>
@endpush

@push('topbar_nav')
<li class="nav-item">
  <a class="nav-link" href="#" onclick="event.preventDefault(); if (window.jQuery) { window.jQuery('#modal-caja-font-size').modal('show'); }" title="Tamaño de letra">
    <i class="fas fa-text-height"></i>
    <span class="sr-only">Tamaño de letra</span>
  </a>
</li>
@endpush

<div class="modal fade" id="modal-caja-font-size" tabindex="-1" role="dialog" aria-labelledby="modal-caja-font-size-titulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title" id="modal-caja-font-size-titulo">Tamaño de letra</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body py-3">
        <p class="small text-muted mb-3">Ajustá el tamaño del texto en Caja (incluye tickets y modales). El valor se guarda para la próxima vez.</p>
        <label for="caja-font-range" class="font-weight-bold mb-1 d-flex justify-content-between align-items-center">
          <span>Tamaño</span>
          <span id="caja-font-range-label" class="text-primary">100%</span>
        </label>
        <input type="range" class="caja-font-range mb-3" id="caja-font-range" min="85" max="135" step="5" value="100" aria-describedby="caja-font-range-label">
        <div class="d-flex flex-wrap caja-font-presets mb-3" style="gap:8px;">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-caja-font-preset="0.9">A−</button>
          <button type="button" class="btn btn-sm btn-outline-primary" data-caja-font-preset="1">Normal</button>
          <button type="button" class="btn btn-sm btn-outline-secondary" data-caja-font-preset="1.15">A+</button>
          <button type="button" class="btn btn-sm btn-link text-muted ml-auto px-0" id="caja-font-reset">Restablecer</button>
        </div>
        <div class="caja-font-preview" id="caja-font-preview">
          <strong>Vista previa</strong>
          <p class="mb-1 small">Ticket #12 · Cancha 1 · $4.500,00</p>
          <p class="mb-0">Así se verá el texto en la pantalla de caja.</p>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-sm btn-primary" data-dismiss="modal">Listo</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var MIN = 0.85;
  var MAX = 1.35;
  var DEFAULT = 1;

  function clampScale(scale) {
    return Math.min(MAX, Math.max(MIN, scale));
  }

  function readStoredScale() {
    try {
      var v = parseFloat(localStorage.getItem('cajaFontScale'));
      if (!isNaN(v)) return clampScale(v);
    } catch (e) {}
    return DEFAULT;
  }

  function saveScale(scale) {
    try {
      localStorage.setItem('cajaFontScale', String(scale));
    } catch (e) {}
  }

  function applyCajaFontScale(scale) {
    var s = clampScale(scale);
    document.documentElement.classList.add('caja-font-active');
    document.documentElement.style.setProperty('--caja-font-scale', String(s));
    return s;
  }

  function percentLabel(scale) {
    return Math.round(scale * 100) + '%';
  }

  function sliderValueFromScale(scale) {
    return String(Math.round(clampScale(scale) * 100));
  }

  window.initCajaFontSizeControl = function () {
    var range = document.getElementById('caja-font-range');
    var label = document.getElementById('caja-font-range-label');
    var resetBtn = document.getElementById('caja-font-reset');
    if (!range || range._cajaFontWired) return;
    range._cajaFontWired = true;

    var current = applyCajaFontScale(readStoredScale());
    range.value = sliderValueFromScale(current);
    if (label) label.textContent = percentLabel(current);

    function syncFromScale(scale) {
      var s = applyCajaFontScale(scale);
      saveScale(s);
      range.value = sliderValueFromScale(s);
      if (label) label.textContent = percentLabel(s);
    }

    range.addEventListener('input', function () {
      syncFromScale(parseInt(range.value, 10) / 100);
    });

    document.querySelectorAll('[data-caja-font-preset]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var preset = parseFloat(btn.getAttribute('data-caja-font-preset'));
        if (!isNaN(preset)) syncFromScale(preset);
      });
    });

    if (resetBtn) {
      resetBtn.addEventListener('click', function () {
        syncFromScale(DEFAULT);
      });
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.initCajaFontSizeControl);
  } else {
    window.initCajaFontSizeControl();
  }
})();
</script>
