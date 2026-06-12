@php
    $pareja1 = $cruce['pareja1'] ?? [];
    $pareja2 = $cruce['pareja2'] ?? [];
    $resultado = $cruce['resultado'] ?? null;
    $esAmericano = $esAmericano ?? false; // Se pasa desde el padre
    
    $setsGanados1 = 0; $setsGanados2 = 0;
    $sets = [];
    $games1 = 0; $games2 = 0;
    
    if ($resultado) {
        if ($esAmericano) {
            // AMERICANO: Solo usar set_1 como marcador de games
            $games1 = $resultado['pareja_1_set_1'] ?? 0;
            $games2 = $resultado['pareja_2_set_1'] ?? 0;
        } else {
            // PUNTUABLE: Usar todos los sets
            for ($s = 1; $s <= 3; $s++) {
                $p1 = $resultado['pareja_1_set_' . $s] ?? null;
                $p2 = $resultado['pareja_2_set_' . $s] ?? null;
                if ($p1 !== null && $p2 !== null && ($p1 > 0 || $p2 > 0)) {
                    $sets[] = ['p1' => $p1, 'p2' => $p2];
                    if ($p1 > $p2) $setsGanados1++;
                    elseif ($p2 > $p1) $setsGanados2++;
                }
            }
        }
    }
    $tieneResultado = $esAmericano ? ($games1 > 0 || $games2 > 0) : count($sets) > 0;
    $ganador1 = $esAmericano ? ($games1 > $games2) : ($setsGanados1 > $setsGanados2);
    $ganador2 = $esAmericano ? ($games2 > $games1) : ($setsGanados2 > $setsGanados1);
@endphp
<div class="match-card">
    <div class="player-pair{{ $ganador1 ? ' winner' : '' }}">
        <div class="player-pair-content">
            <div class="player-names">{{ $pareja1['nombre'] ?? 'TBD' }}</div>
        </div>
        <div class="player-pair-input">
            @if($tieneResultado)
                @if($esAmericano)
                    {{-- AMERICANO: Solo mostrar games --}}
                    <span class="set-score{{ $games1 > $games2 ? ' won' : ' lost' }}">{{ $games1 }}</span>
                @else
                    {{-- PUNTUABLE: Mostrar cada set --}}
                    @foreach($sets as $set)
                        <span class="set-score{{ $set['p1'] > $set['p2'] ? ' won' : ' lost' }}">{{ $set['p1'] }}</span>
                    @endforeach
                @endif
            @else
                <span class="set-score">-</span>
            @endif
        </div>
    </div>
    <div class="player-pair{{ $ganador2 ? ' winner' : '' }}">
        <div class="player-pair-content">
            <div class="player-names">{{ $pareja2['nombre'] ?? 'TBD' }}</div>
        </div>
        <div class="player-pair-input">
            @if($tieneResultado)
                @if($esAmericano)
                    {{-- AMERICANO: Solo mostrar games --}}
                    <span class="set-score{{ $games2 > $games1 ? ' won' : ' lost' }}">{{ $games2 }}</span>
                @else
                    {{-- PUNTUABLE: Mostrar cada set --}}
                    @foreach($sets as $set)
                        <span class="set-score{{ $set['p2'] > $set['p1'] ? ' won' : ' lost' }}">{{ $set['p2'] }}</span>
                    @endforeach
                @endif
            @else
                <span class="set-score">-</span>
            @endif
        </div>
    </div>
</div>
