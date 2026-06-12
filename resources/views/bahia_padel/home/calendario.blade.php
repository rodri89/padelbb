@extends('bahia_padel.home.plantilla')

@section('title_header', 'Calendario - Bahía Pádel')

@section('contenedor')
@php
    $fmtPremio = function ($v) {
        if ($v === null || $v === '') {
            return null;
        }

        return '$'.number_format((float) $v, 0, ',', '.');
    };
@endphp
<style>
  .calendario-eventos {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
    gap: 1.25rem;
  }
  @keyframes calendarioDegrade {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
  }
  .calendario-card {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 14px;
    padding: 1.25rem 1.35rem;
    background: linear-gradient(
      -45deg,
      #0f172a 0%,
      #1e293b 18%,
      #172554 35%,
      #312e81 52%,
      #1e1b4b 68%,
      #0f172a 85%,
      #020617 100%
    );
    background-size: 400% 400%;
    animation: calendarioDegrade 16s ease-in-out infinite;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.06);
  }
  .calendario-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 120% 80% at 20% 0%, rgba(255, 2, 100, 0.18), transparent 50%),
                radial-gradient(ellipse 100% 60% at 90% 100%, rgba(59, 130, 246, 0.12), transparent 45%);
    pointer-events: none;
    z-index: 0;
  }
  .calendario-card::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    opacity: 0.35;
    pointer-events: none;
    z-index: 0;
  }
  /* Damas: mismo movimiento y textura, paleta más rosa */
  .calendario-card--damas {
    border-color: rgba(255, 182, 193, 0.22);
    background: linear-gradient(
      -45deg,
      #1a0a14 0%,
      #2d1220 18%,
      #4a1a38 35%,
      #6b2250 52%,
      #4a1a42 68%,
      #261018 85%,
      #0f060c 100%
    );
    background-size: 400% 400%;
    animation: calendarioDegrade 16s ease-in-out infinite;
    box-shadow: 0 8px 32px rgba(80, 20, 50, 0.4), inset 0 1px 0 rgba(255, 200, 220, 0.08);
  }
  .calendario-card--damas::before {
    background: radial-gradient(ellipse 120% 80% at 18% 0%, rgba(255, 64, 130, 0.32), transparent 52%),
                radial-gradient(ellipse 100% 65% at 92% 100%, rgba(244, 114, 182, 0.2), transparent 48%),
                radial-gradient(ellipse 80% 50% at 50% 50%, rgba(192, 38, 120, 0.12), transparent 55%);
  }
  .calendario-card > * {
    position: relative;
    z-index: 1;
  }
  .calendario-card .calendario-titulo {
    font-size: 1.15rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    line-height: 1.3;
    color: #e2e8f0;
  }
  .calendario-card .label-fecha {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 0.25rem;
    color: rgba(226, 232, 240, 0.9);
  }
  .calendario-card .valor-fecha {
    font-size: 1.05rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #f8fafc;
  }
  .calendario-card .premios-lista {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(148, 163, 184, 0.25);
    font-size: 0.9rem;
    color: #e2e8f0;
  }
  .calendario-card .small.text-secondary {
    color: rgba(203, 213, 225, 0.85) !important;
  }
  .calendario-card .premios-lista > div {
    margin-bottom: 0.25rem;
  }
  .calendario-card .btn-inscribirme {
    margin-top: 1rem;
    display: inline-block;
    padding: 0.5rem 1.25rem;
    border-radius: 8px;
    font-weight: 600;
    background: #ff0264;
    color: #fff !important;
    border: none;
    text-decoration: none;
    transition: opacity 0.2s;
  }
  .calendario-card .btn-inscribirme:hover {
    opacity: 0.92;
    color: #fff !important;
  }
  .calendario-meses {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    align-items: center;
  }
  .calendario-meses a {
    display: inline-block;
    padding: 0.45rem 0.85rem;
    border-radius: 999px;
    font-size: 0.9rem;
    font-weight: 500;
    text-decoration: none;
    border: 1px solid rgba(148, 163, 184, 0.45);
    color: inherit;
    background: rgba(255, 255, 255, 0.04);
    transition: background 0.15s, border-color 0.15s, color 0.15s;
  }
  .calendario-meses a:hover {
    border-color: rgba(255, 2, 100, 0.45);
    color: inherit;
  }
  .calendario-meses a.calendario-mes-activo {
    background: #ff0264;
    border-color: #ff0264;
    color: #fff !important;
  }
  body.dark-mode .calendario-meses a {
    border-color: rgba(148, 163, 184, 0.35);
    background: rgba(45, 45, 45, 0.5);
  }
  body.dark-mode .calendario-meses a.calendario-mes-activo {
    background: #ff0264;
    border-color: #ff0264;
    color: #fff !important;
  }
</style>
<section class="page-header-img mb-4">
    <div class="page-header-img-inner">
        <img src="{{ asset('images/home/reglamento.webp') }}" alt="Calendario" class="img-fluid w-100">
        <div class="page-header-img-overlay"></div>
        <h1 class="page-header-title">Calendario</h1>
    </div>
</section>
<section class="py-3 page-content-home">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif
    @php
        $mesSel = $mesSeleccionado ?? (int) now()->format('n');
        if ($mesSel < 3 || $mesSel > 12) {
            $mesSel = 3;
        }
        $anioSel = $anioCalendario ?? (int) now()->year;
        $nombres = $nombresMes ?? [
            3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
    @endphp
    <nav class="calendario-meses" aria-label="Elegir mes">
        @for($m = 3; $m <= 12; $m++)
            <a href="{{ route('home.calendario', ['mes' => $m, 'anio' => $anioSel]) }}"
               class="@if($mesSel === $m) calendario-mes-activo @endif">{{ $nombres[$m] ?? $m }}</a>
        @endfor
    </nav>

    @if(!($tieneEventosTotales ?? false))
        <p class="text-secondary mb-0">No hay eventos programados aún.</p>
    @elseif(!isset($eventos) || $eventos->isEmpty())
        <p class="text-secondary mb-0">No hay eventos en {{ $nombres[$mesSel] ?? '' }} {{ $anioSel }}.</p>
    @else
        <div class="calendario-eventos">
            @foreach($eventos as $e)
                @if($e)
                <article class="calendario-card @if(strtolower($e->tipo ?? '') === 'femenino') calendario-card--damas @endif">
                    <div class="calendario-titulo">
                        {{ $e->nombre ?: ($e->categoria.'ª categoría · '.$e->tipo_label) }}
                    </div>
                    @if($e->nombre)
                        <p class="small text-secondary mb-2">{{ $e->categoria }}ª · {{ $e->tipo_label }}</p>
                    @endif

                    @php
                        $txtFechas = $e->textoFechasTorneo();
                    @endphp
                    @if($txtFechas !== '')
                        <div class="label-fecha">Fecha</div>
                        <div class="valor-fecha">{{ $txtFechas }}</div>
                    @endif

                    @php
                        $p1 = $fmtPremio($e->premio_1);
                        $p2 = $fmtPremio($e->premio_2);
                        $p3 = $fmtPremio($e->premio_3);
                        $p4 = $fmtPremio($e->premio_4);
                        $hayPremios = $p1 || $p2 || $p3 || $p4;
                        $valorInscr = $fmtPremio($e->valor_inscripcion);
                    @endphp
                    @if($hayPremios)
                        <div class="premios-lista">
                            @if($p1)<div><strong>1º premio:</strong> {{ $p1 }}</div>@endif
                            @if($p2)<div><strong>2º premio:</strong> {{ $p2 }}</div>@endif
                            @if($p3)<div><strong>3º premio:</strong> {{ $p3 }}</div>@endif
                            @if($p4)<div><strong>4º premio:</strong> {{ $p4 }}</div>@endif
                        </div>
                    @endif
                    @if($valorInscr)
                        <div class="premios-lista mt-2 pt-2" style="border-top: 1px solid rgba(148, 163, 184, 0.25);">
                            <div><strong>Valor inscripción por jugador:</strong> {{ $valorInscr }}</div>
                        </div>
                    @endif

                    @if($e->inscripcionAbiertaHoy())
                        <a href="{{ route('home.calendario.inscribir', $e) }}" class="btn-inscribirme">Inscribirme</a>
                    @endif
                </article>
                @endif
            @endforeach
        </div>
    @endif
</section>
@endsection
