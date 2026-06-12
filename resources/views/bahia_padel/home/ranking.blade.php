@extends('bahia_padel.home.plantilla')

@section('title_header', 'Ranking - Bahía Pádel')

@section('contenedor')
<section class="page-header-img mb-4">
    <div class="page-header-img-inner">
        <img src="{{ asset('images/home/reglamento.webp') }}" alt="Ranking" class="img-fluid w-100">
        <div class="page-header-img-overlay"></div>
        <h1 class="page-header-title">Ranking</h1>
    </div>
</section>

<section class="py-4 page-content-home">
    <style>
        .ranking-filtros-inner { max-width: 560px; margin: 0 auto 1.5rem; display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.75rem 1rem; }
        .ranking-filtros-inner label { font-size: 0.9rem; font-weight: 600; color: #e0e0e0; margin-bottom: 0.35rem; display: block; }
        .ranking-filtros-inner select { min-height: 44px; padding: 0.5rem 1rem; font-size: 1rem; color: #fff; background-color: #2d2d2d; border: 1px solid #4d4d4d; border-radius: 12px; min-width: 140px; }
        .ranking-filtros-inner .btn-ver { background: #ff0264; color: #fff; border: none; padding: 0.5rem 1.25rem; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .ranking-filtros-inner .btn-ver:hover { opacity: 0.9; color: #fff; }
        .ranking-table-wrap { max-width: 720px; margin: 0 auto; overflow-x: auto; }
        .ranking-table { width: 100%; border-collapse: collapse; color: #e0e0e0; }
        .ranking-table th, .ranking-table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #4d4d4d; }
        .ranking-table th { font-weight: 600; color: #fff; }
        .ranking-table tbody tr:hover { background: rgba(255, 2, 100, 0.08); }
        .ranking-foto { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; margin-right: 0.75rem; vertical-align: middle; }
        .ranking-msg-vacio { text-align: center; color: #b0b0b0; padding: 2rem 1rem; }
    </style>

    <form method="get" action="{{ route('home.ranking') }}" class="ranking-filtros-inner" id="form-ranking">
        <div>
            <label for="tipo">Tipo</label>
            <select name="tipo" id="tipo">
                @foreach($tipos as $valor => $etiqueta)
                    <option value="{{ $valor }}" {{ $valor === $tipo_seleccionado ? 'selected' : '' }}>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
        @if(!$categorias->isEmpty())
        <div>
            <label for="categoria">Categoría</label>
            <select name="categoria" id="categoria">
                @foreach($categorias as $cat)
                    <option value="{{ $cat }}" {{ (int)$cat === (int)$categoria_seleccionada ? 'selected' : '' }}>{{ $cat }}º Categoría</option>
                @endforeach
            </select>
        </div>
        @endif
        @if(!$temporadas->isEmpty())
        <div>
            <label for="temporada">Temporada</label>
            <select name="temporada" id="temporada">
                @foreach($temporadas as $temp)
                    <option value="{{ $temp }}" {{ (int)$temp === (int)$temporada_seleccionada ? 'selected' : '' }}>{{ $temp }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div>
            <button type="submit" class="btn-ver"><i class="fas fa-search"></i> Ver</button>
        </div>
    </form>

    @if($categorias->isEmpty() && $temporadas->isEmpty())
        <p class="ranking-msg-vacio">No hay datos de ranking para el tipo {{ $tipos[$tipo_seleccionado] ?? $tipo_seleccionado }}.</p>
    @elseif(!$ranking->isEmpty())
        <div class="ranking-table-wrap">
            <table class="ranking-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Pos.</th>
                        <th>Jugador</th>
                        <th class="text-right" style="width: 120px;">Puntos totales</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ranking as $pos => $fila)
                    <tr>
                        <td><strong>{{ $pos + 1 }}</strong></td>
                        <td>
                            <img src="{{ asset($fila->foto ?? 'images/jugador_img.png') }}" alt="" class="ranking-foto" onerror="this.src='{{ asset('images/jugador_img.png') }}';">
                            {{ $fila->nombre ?? '' }} {{ $fila->apellido ?? '' }}
                        </td>
                        <td class="text-right font-weight-bold">{{ number_format($fila->puntos_totales, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="ranking-msg-vacio">No hay datos de ranking para {{ $categoria_seleccionada }}º categoría en la temporada {{ $temporada_seleccionada }}.</p>
    @endif
</section>

<script>
(function() {
    var form = document.getElementById('form-ranking');
    if (!form) return;
    ['tipo', 'categoria', 'temporada'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', function() { form.submit(); });
    });
})();
</script>
@endsection
