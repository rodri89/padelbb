@extends('bahia_padel/admin/plantilla')

@section('title_header','Cruces Eliminatorios')

@section('contenedor')

<div class="container body_admin">
    <div class="row justify-content-center mb-4">
        <div class="col-12">
            <div class="card shadow bg-white px-5 py-3" style="border-radius: 12px; border: 1px solid #e3e6f0;">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('admintorneoresultados') }}?torneo_id={{ $torneo->id }}" class="btn btn-secondary">
                        ← Volver a Resultados
                    </a>
                    <div class="text-center flex-grow-1">
                        <h2 class="mb-0" style="color:#4e73df; font-weight:700;">{{ $torneo->categoria ?? '-' }}º Categoría - Cruces Eliminatorios</h2>
                        <div class="text-muted">{{ $torneo->nombre ?? 'Torneo' }}</div>
                    </div>
                    <div style="width: 150px;"></div> <!-- Spacer para centrar -->
                </div>
                <input type="hidden" id="torneo_id" value="{{ $torneo->id ?? 0 }}">
            </div>
        </div>
    </div>

    @php
        $jugadoresMap = [];
        foreach($jugadores as $j) {
            $jugadoresMap[$j->id] = $j;
        }
        
        // Separar cruces por ronda
        $crucesCuartos = [];
        $crucesSemifinales = [];
        $crucesFinal = [];
        
        foreach($cruces as $cruce) {
            if ($cruce['ronda'] == 'cuartos') {
                $crucesCuartos[] = $cruce;
            } else if ($cruce['ronda'] == 'semifinales') {
                $crucesSemifinales[] = $cruce;
            } else if ($cruce['ronda'] == 'final') {
                $crucesFinal[] = $cruce;
            }
        }
    @endphp

    <!-- Cuartos de Final -->
    @if(count($crucesCuartos) > 0)
    <div class="row justify-content-center mb-5">
        <div class="col-12">
            <div class="card shadow bg-white px-4 py-3" style="border-radius: 12px; border: 1px solid #e3e6f0;">
                <h3 class="text-center mb-4" style="color:#4e73df; font-weight:700;">Cuartos de Final</h3>
                <div class="row">
                    @foreach($crucesCuartos as $index => $cruce)
                    @php
                        $jugador1_p1 = $jugadoresMap[$cruce['pareja_1']['jugador_1']] ?? null;
                        $jugador2_p1 = $jugadoresMap[$cruce['pareja_1']['jugador_2']] ?? null;
                        $jugador1_p2 = $jugadoresMap[$cruce['pareja_2']['jugador_1']] ?? null;
                        $jugador2_p2 = $jugadoresMap[$cruce['pareja_2']['jugador_2']] ?? null;
                        
                        // Obtener partido si existe
                        $partido = $cruce['partido'] ?? null;
                    @endphp
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border" style="height: 100%;">
                            <div class="card-body">
                                <h5 class="card-title text-center mb-3">Cuarto {{ $loop->iteration }}</h5>
                                
                                <!-- Pareja 1 -->
                                <div class="d-flex justify-content-around align-items-center mb-3">
                                    <div class="text-center pareja-container" style="position: relative; padding: 10px; border-radius: 8px;">
                                        @if($jugador1_p1)
                                        <div class="mb-2">
                                            <img src="{{ asset($jugador1_p1->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle" 
                                                style="width:60px; height:60px; object-fit:cover; border: 2px solid #4e73df;">
                                            <div style="font-size:0.7rem; font-weight:600; margin-top:5px;">
                                                {{ $jugador1_p1->nombre ?? '' }} {{ $jugador1_p1->apellido ?? '' }}
                                            </div>
                                        </div>
                                        @endif
                                        @if($jugador2_p1)
                                        <div>
                                            <img src="{{ asset($jugador2_p1->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle" 
                                                style="width:60px; height:60px; object-fit:cover; border: 2px solid #4e73df;">
                                            <div style="font-size:0.7rem; font-weight:600; margin-top:5px;">
                                                {{ $jugador2_p1->nombre ?? '' }} {{ $jugador2_p1->apellido ?? '' }}
                                            </div>
                                        </div>
                                        @endif
                                        @if(isset($cruce['pareja_1']['zona']) && isset($cruce['pareja_1']['posicion']))
                                        <div class="badge badge-info mt-2">{{ $cruce['pareja_1']['posicion'] }}{{ $cruce['pareja_1']['zona'] }}</div>
                                        @endif
                                    </div>
                                    
                                    <div class="mx-3">
                                        <h4 style="color:#dc3545; font-weight:bold;">VS</h4>
                                    </div>
                                    
                                    <!-- Pareja 2 -->
                                    <div class="text-center pareja-container" style="position: relative; padding: 10px; border-radius: 8px;">
                                        @if($jugador1_p2)
                                        <div class="mb-2">
                                            <img src="{{ asset($jugador1_p2->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle" 
                                                style="width:60px; height:60px; object-fit:cover; border: 2px solid #1a8917;">
                                            <div style="font-size:0.7rem; font-weight:600; margin-top:5px;">
                                                {{ $jugador1_p2->nombre ?? '' }} {{ $jugador1_p2->apellido ?? '' }}
                                            </div>
                                        </div>
                                        @endif
                                        @if($jugador2_p2)
                                        <div>
                                            <img src="{{ asset($jugador2_p2->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle" 
                                                style="width:60px; height:60px; object-fit:cover; border: 2px solid #1a8917;">
                                            <div style="font-size:0.7rem; font-weight:600; margin-top:5px;">
                                                {{ $jugador2_p2->nombre ?? '' }} {{ $jugador2_p2->apellido ?? '' }}
                                            </div>
                                        </div>
                                        @endif
                                        @if(isset($cruce['pareja_2']['zona']) && isset($cruce['pareja_2']['posicion']))
                                        <div class="badge badge-info mt-2">{{ $cruce['pareja_2']['posicion'] }}{{ $cruce['pareja_2']['zona'] }}</div>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Resultados -->
                                <div class="resultado-partido" data-partido-id="{{ $cruce['partido_id'] ?? '' }}" data-cruce-id="{{ $cruce['id'] ?? $index }}">
                                    <!-- Set 1 -->
                                    <div class="mb-2">
                                        <label style="font-size:0.8rem; font-weight:600;">Set 1</label>
                                        <div class="d-flex justify-content-center align-items-center">
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_1_set_1" 
                                                value="{{ $partido->pareja_1_set_1 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-2">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_2_set_1" 
                                                value="{{ $partido->pareja_2_set_1 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                        <div class="d-flex justify-content-center align-items-center mt-1">
                                            <small style="font-size:0.7rem;">TB:</small>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm ml-1" 
                                                style="width:50px;"
                                                name="pareja_1_set_1_tie_break" 
                                                value="{{ $partido->pareja_1_set_1_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-1">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:50px;"
                                                name="pareja_2_set_1_tie_break" 
                                                value="{{ $partido->pareja_2_set_1_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Set 2 -->
                                    <div class="mb-2">
                                        <label style="font-size:0.8rem; font-weight:600;">Set 2</label>
                                        <div class="d-flex justify-content-center align-items-center">
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_1_set_2" 
                                                value="{{ $partido->pareja_1_set_2 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-2">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_2_set_2" 
                                                value="{{ $partido->pareja_2_set_2 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                        <div class="d-flex justify-content-center align-items-center mt-1">
                                            <small style="font-size:0.7rem;">TB:</small>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm ml-1" 
                                                style="width:50px;"
                                                name="pareja_1_set_2_tie_break" 
                                                value="{{ $partido->pareja_1_set_2_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-1">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:50px;"
                                                name="pareja_2_set_2_tie_break" 
                                                value="{{ $partido->pareja_2_set_2_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Set 3 -->
                                    <div class="mb-2">
                                        <label style="font-size:0.8rem; font-weight:600;">Set 3</label>
                                        <div class="d-flex justify-content-center align-items-center">
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_1_set_3" 
                                                value="{{ $partido->pareja_1_set_3 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-2">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_2_set_3" 
                                                value="{{ $partido->pareja_2_set_3 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                        <div class="d-flex justify-content-center align-items-center mt-1">
                                            <small style="font-size:0.7rem;">TB:</small>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm ml-1" 
                                                style="width:50px;"
                                                name="pareja_1_set_3_tie_break" 
                                                value="{{ $partido->pareja_1_set_3_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-1">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:50px;"
                                                name="pareja_2_set_3_tie_break" 
                                                value="{{ $partido->pareja_2_set_3_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Super TB -->
                                    <div class="mb-2">
                                        <label style="font-size:0.8rem; font-weight:600;">Super TB</label>
                                        <div class="d-flex justify-content-center align-items-center">
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_1_set_super_tie_break" 
                                                value="{{ $partido->pareja_1_set_super_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-2">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_2_set_super_tie_break" 
                                                value="{{ $partido->pareja_2_set_super_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Botón Guardar -->
                                    <div class="text-center mt-3">
                                        <button type="button" class="btn btn-sm btn-primary guardar-resultado-cruce" 
                                            data-partido-id="{{ $cruce['partido_id'] ?? '' }}"
                                            data-cruce-id="{{ $cruce['id'] ?? $index }}"
                                            data-ronda="cuartos">
                                            Guardar Resultado
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Semifinales -->
    @if(count($crucesSemifinales) > 0)
    <div class="row justify-content-center mb-5">
        <div class="col-12">
            <div class="card shadow bg-white px-4 py-3" style="border-radius: 12px; border: 1px solid #e3e6f0;">
                <h3 class="text-center mb-4" style="color:#4e73df; font-weight:700;">Semifinales</h3>
                <div class="row">
                    @foreach($crucesSemifinales as $index => $cruce)
                    @php
                        $jugador1_p1 = $jugadoresMap[$cruce['pareja_1']['jugador_1']] ?? null;
                        $jugador2_p1 = $jugadoresMap[$cruce['pareja_1']['jugador_2']] ?? null;
                        $jugador1_p2 = $jugadoresMap[$cruce['pareja_2']['jugador_1']] ?? null;
                        $jugador2_p2 = $jugadoresMap[$cruce['pareja_2']['jugador_2']] ?? null;
                        
                        $partido = $cruce['partido'] ?? null;
                    @endphp
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border" style="height: 100%;">
                            <div class="card-body">
                                <h5 class="card-title text-center mb-3">Semifinal {{ $loop->iteration }}</h5>
                                
                                <!-- Mismo formato que cuartos -->
                                <div class="d-flex justify-content-around align-items-center mb-3">
                                    <div class="text-center pareja-container" style="position: relative; padding: 10px; border-radius: 8px;">
                                        @if($jugador1_p1)
                                        <div class="mb-2">
                                            <img src="{{ asset($jugador1_p1->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle" 
                                                style="width:60px; height:60px; object-fit:cover; border: 2px solid #4e73df;">
                                            <div style="font-size:0.7rem; font-weight:600; margin-top:5px;">
                                                {{ $jugador1_p1->nombre ?? '' }} {{ $jugador1_p1->apellido ?? '' }}
                                            </div>
                                        </div>
                                        @endif
                                        @if($jugador2_p1)
                                        <div>
                                            <img src="{{ asset($jugador2_p1->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle" 
                                                style="width:60px; height:60px; object-fit:cover; border: 2px solid #4e73df;">
                                            <div style="font-size:0.7rem; font-weight:600; margin-top:5px;">
                                                {{ $jugador2_p1->nombre ?? '' }} {{ $jugador2_p1->apellido ?? '' }}
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div class="mx-3">
                                        <h4 style="color:#dc3545; font-weight:bold;">VS</h4>
                                    </div>
                                    
                                    <div class="text-center pareja-container" style="position: relative; padding: 10px; border-radius: 8px;">
                                        @if($jugador1_p2)
                                        <div class="mb-2">
                                            <img src="{{ asset($jugador1_p2->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle" 
                                                style="width:60px; height:60px; object-fit:cover; border: 2px solid #1a8917;">
                                            <div style="font-size:0.7rem; font-weight:600; margin-top:5px;">
                                                {{ $jugador1_p2->nombre ?? '' }} {{ $jugador1_p2->apellido ?? '' }}
                                            </div>
                                        </div>
                                        @endif
                                        @if($jugador2_p2)
                                        <div>
                                            <img src="{{ asset($jugador2_p2->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle" 
                                                style="width:60px; height:60px; object-fit:cover; border: 2px solid #1a8917;">
                                            <div style="font-size:0.7rem; font-weight:600; margin-top:5px;">
                                                {{ $jugador2_p2->nombre ?? '' }} {{ $jugador2_p2->apellido ?? '' }}
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Resultados (mismo formato que cuartos) -->
                                <div class="resultado-partido" data-partido-id="{{ $cruce['partido_id'] ?? '' }}" data-cruce-id="{{ $cruce['id'] ?? $index }}">
                                    <!-- Set 1 -->
                                    <div class="mb-2">
                                        <label style="font-size:0.8rem; font-weight:600;">Set 1</label>
                                        <div class="d-flex justify-content-center align-items-center">
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_1_set_1" 
                                                value="{{ $partido->pareja_1_set_1 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-2">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_2_set_1" 
                                                value="{{ $partido->pareja_2_set_1 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                        <div class="d-flex justify-content-center align-items-center mt-1">
                                            <small style="font-size:0.7rem;">TB:</small>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm ml-1" 
                                                style="width:50px;"
                                                name="pareja_1_set_1_tie_break" 
                                                value="{{ $partido->pareja_1_set_1_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-1">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:50px;"
                                                name="pareja_2_set_1_tie_break" 
                                                value="{{ $partido->pareja_2_set_1_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Set 2 -->
                                    <div class="mb-2">
                                        <label style="font-size:0.8rem; font-weight:600;">Set 2</label>
                                        <div class="d-flex justify-content-center align-items-center">
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_1_set_2" 
                                                value="{{ $partido->pareja_1_set_2 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-2">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_2_set_2" 
                                                value="{{ $partido->pareja_2_set_2 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                        <div class="d-flex justify-content-center align-items-center mt-1">
                                            <small style="font-size:0.7rem;">TB:</small>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm ml-1" 
                                                style="width:50px;"
                                                name="pareja_1_set_2_tie_break" 
                                                value="{{ $partido->pareja_1_set_2_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-1">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:50px;"
                                                name="pareja_2_set_2_tie_break" 
                                                value="{{ $partido->pareja_2_set_2_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Set 3 -->
                                    <div class="mb-2">
                                        <label style="font-size:0.8rem; font-weight:600;">Set 3</label>
                                        <div class="d-flex justify-content-center align-items-center">
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_1_set_3" 
                                                value="{{ $partido->pareja_1_set_3 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-2">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_2_set_3" 
                                                value="{{ $partido->pareja_2_set_3 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                        <div class="d-flex justify-content-center align-items-center mt-1">
                                            <small style="font-size:0.7rem;">TB:</small>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm ml-1" 
                                                style="width:50px;"
                                                name="pareja_1_set_3_tie_break" 
                                                value="{{ $partido->pareja_1_set_3_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-1">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:50px;"
                                                name="pareja_2_set_3_tie_break" 
                                                value="{{ $partido->pareja_2_set_3_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Super TB -->
                                    <div class="mb-2">
                                        <label style="font-size:0.8rem; font-weight:600;">Super TB</label>
                                        <div class="d-flex justify-content-center align-items-center">
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_1_set_super_tie_break" 
                                                value="{{ $partido->pareja_1_set_super_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-2">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_2_set_super_tie_break" 
                                                value="{{ $partido->pareja_2_set_super_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Botón Guardar -->
                                    <div class="text-center mt-3">
                                        <button type="button" class="btn btn-sm btn-primary guardar-resultado-cruce" 
                                            data-partido-id="{{ $cruce['partido_id'] ?? '' }}"
                                            data-cruce-id="{{ $cruce['id'] ?? $index }}"
                                            data-ronda="semifinales">
                                            Guardar Resultado
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Final -->
    @if(count($crucesFinal) > 0)
    <div class="row justify-content-center mb-5">
        <div class="col-12">
            <div class="card shadow bg-white px-4 py-3" style="border-radius: 12px; border: 1px solid #e3e6f0;">
                <h3 class="text-center mb-4" style="color:#4e73df; font-weight:700;">Final</h3>
                <div class="row justify-content-center">
                    @foreach($crucesFinal as $index => $cruce)
                    @php
                        $jugador1_p1 = $jugadoresMap[$cruce['pareja_1']['jugador_1']] ?? null;
                        $jugador2_p1 = $jugadoresMap[$cruce['pareja_1']['jugador_2']] ?? null;
                        $jugador1_p2 = $jugadoresMap[$cruce['pareja_2']['jugador_1']] ?? null;
                        $jugador2_p2 = $jugadoresMap[$cruce['pareja_2']['jugador_2']] ?? null;
                        
                        $partido = $cruce['partido'] ?? null;
                    @endphp
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border" style="height: 100%;">
                            <div class="card-body">
                                <h5 class="card-title text-center mb-3">Final</h5>
                                
                                <!-- Mismo formato que cuartos y semifinales -->
                                <div class="d-flex justify-content-around align-items-center mb-3">
                                    <div class="text-center pareja-container" style="position: relative; padding: 10px; border-radius: 8px;">
                                        @if($jugador1_p1)
                                        <div class="mb-2">
                                            <img src="{{ asset($jugador1_p1->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle" 
                                                style="width:60px; height:60px; object-fit:cover; border: 2px solid #4e73df;">
                                            <div style="font-size:0.7rem; font-weight:600; margin-top:5px;">
                                                {{ $jugador1_p1->nombre ?? '' }} {{ $jugador1_p1->apellido ?? '' }}
                                            </div>
                                        </div>
                                        @endif
                                        @if($jugador2_p1)
                                        <div>
                                            <img src="{{ asset($jugador2_p1->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle" 
                                                style="width:60px; height:60px; object-fit:cover; border: 2px solid #4e73df;">
                                            <div style="font-size:0.7rem; font-weight:600; margin-top:5px;">
                                                {{ $jugador2_p1->nombre ?? '' }} {{ $jugador2_p1->apellido ?? '' }}
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div class="mx-3">
                                        <h4 style="color:#dc3545; font-weight:bold;">VS</h4>
                                    </div>
                                    
                                    <div class="text-center pareja-container" style="position: relative; padding: 10px; border-radius: 8px;">
                                        @if($jugador1_p2)
                                        <div class="mb-2">
                                            <img src="{{ asset($jugador1_p2->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle" 
                                                style="width:60px; height:60px; object-fit:cover; border: 2px solid #1a8917;">
                                            <div style="font-size:0.7rem; font-weight:600; margin-top:5px;">
                                                {{ $jugador1_p2->nombre ?? '' }} {{ $jugador1_p2->apellido ?? '' }}
                                            </div>
                                        </div>
                                        @endif
                                        @if($jugador2_p2)
                                        <div>
                                            <img src="{{ asset($jugador2_p2->foto ?? 'images/jugador_img.png') }}" 
                                                class="rounded-circle" 
                                                style="width:60px; height:60px; object-fit:cover; border: 2px solid #1a8917;">
                                            <div style="font-size:0.7rem; font-weight:600; margin-top:5px;">
                                                {{ $jugador2_p2->nombre ?? '' }} {{ $jugador2_p2->apellido ?? '' }}
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Resultados (mismo formato) -->
                                <div class="resultado-partido" data-partido-id="{{ $cruce['partido_id'] ?? '' }}" data-cruce-id="{{ $cruce['id'] ?? $index }}">
                                    <!-- Set 1 -->
                                    <div class="mb-2">
                                        <label style="font-size:0.8rem; font-weight:600;">Set 1</label>
                                        <div class="d-flex justify-content-center align-items-center">
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_1_set_1" 
                                                value="{{ $partido->pareja_1_set_1 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-2">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_2_set_1" 
                                                value="{{ $partido->pareja_2_set_1 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                        <div class="d-flex justify-content-center align-items-center mt-1">
                                            <small style="font-size:0.7rem;">TB:</small>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm ml-1" 
                                                style="width:50px;"
                                                name="pareja_1_set_1_tie_break" 
                                                value="{{ $partido->pareja_1_set_1_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-1">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:50px;"
                                                name="pareja_2_set_1_tie_break" 
                                                value="{{ $partido->pareja_2_set_1_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Set 2 -->
                                    <div class="mb-2">
                                        <label style="font-size:0.8rem; font-weight:600;">Set 2</label>
                                        <div class="d-flex justify-content-center align-items-center">
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_1_set_2" 
                                                value="{{ $partido->pareja_1_set_2 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-2">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_2_set_2" 
                                                value="{{ $partido->pareja_2_set_2 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                        <div class="d-flex justify-content-center align-items-center mt-1">
                                            <small style="font-size:0.7rem;">TB:</small>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm ml-1" 
                                                style="width:50px;"
                                                name="pareja_1_set_2_tie_break" 
                                                value="{{ $partido->pareja_1_set_2_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-1">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:50px;"
                                                name="pareja_2_set_2_tie_break" 
                                                value="{{ $partido->pareja_2_set_2_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Set 3 -->
                                    <div class="mb-2">
                                        <label style="font-size:0.8rem; font-weight:600;">Set 3</label>
                                        <div class="d-flex justify-content-center align-items-center">
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_1_set_3" 
                                                value="{{ $partido->pareja_1_set_3 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-2">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_2_set_3" 
                                                value="{{ $partido->pareja_2_set_3 ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                        <div class="d-flex justify-content-center align-items-center mt-1">
                                            <small style="font-size:0.7rem;">TB:</small>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm ml-1" 
                                                style="width:50px;"
                                                name="pareja_1_set_3_tie_break" 
                                                value="{{ $partido->pareja_1_set_3_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-1">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:50px;"
                                                name="pareja_2_set_3_tie_break" 
                                                value="{{ $partido->pareja_2_set_3_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Super TB -->
                                    <div class="mb-2">
                                        <label style="font-size:0.8rem; font-weight:600;">Super TB</label>
                                        <div class="d-flex justify-content-center align-items-center">
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_1_set_super_tie_break" 
                                                value="{{ $partido->pareja_1_set_super_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                            <span class="mx-2">-</span>
                                            <input type="number" min="0" max="99" 
                                                class="form-control form-control-sm" 
                                                style="width:60px;"
                                                name="pareja_2_set_super_tie_break" 
                                                value="{{ $partido->pareja_2_set_super_tie_break ?? 0 }}"
                                                data-partido-id="{{ $cruce['partido_id'] ?? '' }}">
                                        </div>
                                    </div>
                                    
                                    <!-- Botón Guardar -->
                                    <div class="text-center mt-3">
                                        <button type="button" class="btn btn-sm btn-primary guardar-resultado-cruce" 
                                            data-partido-id="{{ $cruce['partido_id'] ?? '' }}"
                                            data-cruce-id="{{ $cruce['id'] ?? $index }}"
                                            data-ronda="final">
                                            Guardar Resultado
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
$(document).ready(function() {
    // Guardar resultado de cruce (similar a resultados_torneo.blade.php)
    $(document).on('click', '.guardar-resultado-cruce', function() {
        var partidoId = $(this).data('partido-id');
        var cruceId = $(this).data('cruce-id');
        var ronda = $(this).data('ronda');
        var resultadoPartido = $(this).closest('.resultado-partido');
        
        if (!partidoId) {
            alert('Error: No se encontró el partido. Por favor, recarga la página.');
            return;
        }
        
        var datos = {
            partido_id: partidoId,
            pareja_1_set_1: resultadoPartido.find('input[name="pareja_1_set_1"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_1_tie_break: resultadoPartido.find('input[name="pareja_1_set_1_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_1: resultadoPartido.find('input[name="pareja_2_set_1"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_1_tie_break: resultadoPartido.find('input[name="pareja_2_set_1_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_2: resultadoPartido.find('input[name="pareja_1_set_2"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_2_tie_break: resultadoPartido.find('input[name="pareja_1_set_2_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_2: resultadoPartido.find('input[name="pareja_2_set_2"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_2_tie_break: resultadoPartido.find('input[name="pareja_2_set_2_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_3: resultadoPartido.find('input[name="pareja_1_set_3"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_3_tie_break: resultadoPartido.find('input[name="pareja_1_set_3_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_3: resultadoPartido.find('input[name="pareja_2_set_3"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_3_tie_break: resultadoPartido.find('input[name="pareja_2_set_3_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_super_tie_break: resultadoPartido.find('input[name="pareja_1_set_super_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_super_tie_break: resultadoPartido.find('input[name="pareja_2_set_super_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            _token: '{{csrf_token()}}'
        };
        
        var btn = $(this);
        btn.prop('disabled', true).text('Guardando...');
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("guardarresultadopartido") }}',
            data: datos,
            success: function(data) {
                if (data.success) {
                    btn.removeClass('btn-primary').addClass('btn-success').text('✓ Guardado');
                    
                    // Determinar ganador y aplicar estilo verde
                    determinarGanador(partidoId, resultadoPartido);
                    
                    // Si es cuartos o semifinales, recargar la página para mostrar las siguientes rondas
                    if (ronda === 'cuartos' || ronda === 'semifinales') {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                    
                    setTimeout(function() {
                        btn.removeClass('btn-success').addClass('btn-primary').text('Guardar Resultado');
                    }, 2000);
                } else {
                    alert('Error al guardar el resultado');
                    btn.prop('disabled', false).text('Guardar Resultado');
                }
            },
            error: function() {
                alert('Error al guardar el resultado');
                btn.prop('disabled', false).text('Guardar Resultado');
            }
        });
    });
    
    // Función para determinar el ganador (similar a resultados_torneo.blade.php)
    function determinarGanador(partidoId, resultadoPartido) {
        var set1_p1 = parseInt(resultadoPartido.find('input[name="pareja_1_set_1"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set1_p2 = parseInt(resultadoPartido.find('input[name="pareja_2_set_1"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set2_p1 = parseInt(resultadoPartido.find('input[name="pareja_1_set_2"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set2_p2 = parseInt(resultadoPartido.find('input[name="pareja_2_set_2"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set3_p1 = parseInt(resultadoPartido.find('input[name="pareja_1_set_3"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set3_p2 = parseInt(resultadoPartido.find('input[name="pareja_2_set_3"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var superTB_p1 = parseInt(resultadoPartido.find('input[name="pareja_1_set_super_tie_break"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var superTB_p2 = parseInt(resultadoPartido.find('input[name="pareja_2_set_super_tie_break"][data-partido-id="' + partidoId + '"]').val()) || 0;
        
        // Remover estilos anteriores
        resultadoPartido.find('.pareja-container')
            .removeClass('ganador').css('background-color', '').css('border', '');
        
        // Si hay super tie break, ese determina el ganador
        if (superTB_p1 > 0 || superTB_p2 > 0) {
            if (superTB_p1 > superTB_p2) {
                resultadoPartido.find('.pareja-container').first()
                    .addClass('ganador')
                    .css('background-color', '#d4edda')
                    .css('border', '3px solid #28a745');
            } else if (superTB_p2 > superTB_p1) {
                resultadoPartido.find('.pareja-container').last()
                    .addClass('ganador')
                    .css('background-color', '#d4edda')
                    .css('border', '3px solid #28a745');
            }
            return;
        }
        
        // Contar sets ganados
        var setsGanadosP1 = 0;
        var setsGanadosP2 = 0;
        
        if (set1_p1 > set1_p2) setsGanadosP1++;
        else if (set1_p2 > set1_p1) setsGanadosP2++;
        
        if (set2_p1 > set2_p2) setsGanadosP1++;
        else if (set2_p2 > set2_p1) setsGanadosP2++;
        
        if (set3_p1 > set3_p2) setsGanadosP1++;
        else if (set3_p2 > set3_p1) setsGanadosP2++;
        
        // Aplicar estilo verde al ganador
        if (setsGanadosP1 > setsGanadosP2) {
            resultadoPartido.find('.pareja-container').first()
                .addClass('ganador')
                .css('background-color', '#d4edda')
                .css('border', '3px solid #28a745');
        } else if (setsGanadosP2 > setsGanadosP1) {
            resultadoPartido.find('.pareja-container').last()
                .addClass('ganador')
                .css('background-color', '#d4edda')
                .css('border', '3px solid #28a745');
        }
    }
    
    // Aplicar ganador al cargar la página si ya hay resultados
    $('.resultado-partido').each(function() {
        var partidoId = $(this).data('partido-id');
        if (partidoId) {
            var resultadoPartido = $(this);
            determinarGanador(partidoId, resultadoPartido);
        }
    });
});
</script>

@endsection

