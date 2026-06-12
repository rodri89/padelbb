{{-- Tarjeta de partido para 16avos y octavos (misma estructura) --}}
@php
    $cruce = $cruce ?? [];
    $partido = $cruce['partido'] ?? null;
    $diaVal = $cruce['dia'] ?? null;
    $horarioVal = $cruce['horario'] ?? null;
    $diaStr = is_string($diaVal) ? trim($diaVal) : '';
    $horarioStr = is_string($horarioVal) ? trim(preg_replace('/^(\d{2}:\d{2})(:\d{2})?$/', '$1', $horarioVal ?? '')) : '';
    $esDefault = (strpos($diaStr, '2000-01-01') !== false || $diaStr === '2000-01-01') && (empty($horarioStr) || $horarioStr === '00:00');
    $diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    $diaDisplay = $esDefault ? 'N/A' : ($diaStr ? (preg_match('/^\d{4}-\d{2}-\d{2}$/', $diaStr) ? ($diasSemana[date('w', strtotime($diaStr))] ?? $diaStr) : $diaStr) : '—');
    $horarioDisplay = $esDefault ? 'N/A' : ($horarioStr ?: '—');
    $ref1 = $cruce['referencia_1'] ?? '';
    $ref2 = $cruce['referencia_2'] ?? '';
@endphp
<div class="match-card"
     data-cruce-id="{{ $cruce['id'] }}"
     data-ronda="{{ $cruce['ronda'] }}"
     data-partido-id="{{ $cruce['partido_id'] ?? '' }}"
     data-llave-ref1="{{ $ref1 }}"
     data-llave-ref2="{{ $ref2 }}"
     style="padding: 15px; margin-bottom: 20px;">
    <div class="small mb-2" style="color: #555;">
        <span class="d-inline-block mr-2"><strong>Día:</strong> {{ $diaDisplay }}</span>
        <span><strong>Horario:</strong> {{ $horarioDisplay }}</span>
    </div>
    <div class="small text-muted mb-2" style="font-weight: 600;">Llave: {{ $ref1 ?: '—' }} vs {{ $ref2 ?: '—' }}</div>
    <!-- Pareja 1 -->
    <div class="d-flex align-items-center mb-3" data-pareja="1" data-jugador-1="{{ $cruce['pareja_1']['jugador_1'] ?? '' }}" data-jugador-2="{{ $cruce['pareja_1']['jugador_2'] ?? '' }}">
        @if($esPlaceholder1 ?? false)
        <div class="d-flex align-items-center" style="min-height: 60px;">
            <span class="text-muted font-italic" style="font-size: 0.9rem;">{{ ($tiene16avos ?? false) ? 'Esperando ganador (de 16avos)' : 'Esperando clasificación' }}</span>
        </div>
        @else
        <div class="d-flex mr-3">
            <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
            <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
        </div>
        <div class="d-flex flex-column justify-content-center" style="height: 60px;">
            <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}</div>
            <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}</div>
        </div>
        @endif
    </div>
    <div class="mb-3">
        <div class="d-flex align-items-center gap-2">
            <div class="d-flex flex-column align-items-center">
                <label class="small mb-1" style="color: #000;">Set 1</label>
                <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="1" data-set="1" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja1_set1 ?? 0 }}" placeholder="0">
            </div>
            <div class="d-flex flex-column align-items-center">
                <label class="small mb-1" style="color: #000;">Set 2</label>
                <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="1" data-set="2" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja1_set2 ?? 0 }}" placeholder="0">
            </div>
            <div class="d-flex flex-column align-items-center">
                <label class="small mb-1" style="color: #000;">Set 3</label>
                <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="1" data-set="3" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja1_set3 ?? 0 }}" placeholder="0">
            </div>
        </div>
    </div>
    <div class="mb-3">
        <div class="d-flex align-items-center gap-2">
            <div class="d-flex flex-column align-items-center">
                <label class="small mb-1" style="color: #000;">Set 1</label>
                <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="2" data-set="1" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja2_set1 ?? 0 }}" placeholder="0">
            </div>
            <div class="d-flex flex-column align-items-center">
                <label class="small mb-1" style="color: #000;">Set 2</label>
                <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="2" data-set="2" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja2_set2 ?? 0 }}" placeholder="0">
            </div>
            <div class="d-flex flex-column align-items-center">
                <label class="small mb-1" style="color: #000;">Set 3</label>
                <input type="number" class="form-control resultado-cruce" data-cruce-id="{{ $cruce['id'] }}" data-pareja="2" data-set="3" data-ronda="{{ $cruce['ronda'] }}" min="0" max="99" value="{{ $pareja2_set3 ?? 0 }}" placeholder="0">
            </div>
        </div>
    </div>
    <!-- Pareja 2 -->
    <div class="d-flex align-items-center mb-3" data-pareja="2" data-jugador-1="{{ $cruce['pareja_2']['jugador_1'] ?? '' }}" data-jugador-2="{{ $cruce['pareja_2']['jugador_2'] ?? '' }}">
        @if($esPlaceholder2 ?? false)
        <div class="d-flex align-items-center" style="min-height: 60px;">
            <span class="text-muted font-italic" style="font-size: 0.9rem;">{{ ($tiene16avos ?? false) ? 'Esperando ganador (de 16avos)' : 'Esperando clasificación' }}</span>
        </div>
        @else
        <div class="d-flex mr-3">
            <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
            <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;" onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
        </div>
        <div class="d-flex flex-column justify-content-center" style="height: 60px;">
            <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}</div>
            <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}</div>
        </div>
        @endif
    </div>
    <div class="text-center mt-2">
        <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="{{ $cruce['id'] }}" data-ronda="{{ $cruce['ronda'] }}">Guardar</button>
    </div>
</div>
