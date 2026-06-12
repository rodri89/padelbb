@extends('bahia_padel/admin/plantilla')

@section('title_header', 'Panel de administración')

@section('contenedor')

<div class="row">
  <div class="col-12 mb-4">
    <p class="text-muted mb-0">Elegí una sección del menú lateral o usá los accesos rápidos.</p>
  </div>
</div>

<div class="row">
  <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
    <a href="admin_torneos" class="card shadow-sm h-100 text-decoration-none text-dark">
      <div class="card-body text-center py-4">
        <i class="fas fa-folder-open fa-2x text-primary mb-3"></i>
        <h5 class="card-title mb-0">Torneos</h5>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
    <a href="{{ route('admincargarresultados') }}" class="card shadow-sm h-100 text-decoration-none text-dark">
      <div class="card-body text-center py-4">
        <i class="fas fa-edit fa-2x text-primary mb-3"></i>
        <h5 class="card-title mb-0">Cargar resultados</h5>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
    <a href="admin_jugadores" class="card shadow-sm h-100 text-decoration-none text-dark">
      <div class="card-body text-center py-4">
        <i class="fas fa-address-card fa-2x text-primary mb-3"></i>
        <h5 class="card-title mb-0">Jugadores</h5>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
    <a href="{{ route('admincaja') }}" class="card shadow-sm h-100 text-decoration-none text-dark">
      <div class="card-body text-center py-4">
        <i class="fas fa-cash-register fa-2x text-primary mb-3"></i>
        <h5 class="card-title mb-0">Caja</h5>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
    <a href="{{ route('adminstock') }}" class="card shadow-sm h-100 text-decoration-none text-dark">
      <div class="card-body text-center py-4">
        <i class="fas fa-boxes fa-2x text-primary mb-3"></i>
        <h5 class="card-title mb-0">Stock</h5>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
    <a href="{{ route('adminranking') }}" class="card shadow-sm h-100 text-decoration-none text-dark">
      <div class="card-body text-center py-4">
        <i class="fas fa-trophy fa-2x text-primary mb-3"></i>
        <h5 class="card-title mb-0">Ranking</h5>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
    <a href="{{ route('admincalendario') }}" class="card shadow-sm h-100 text-decoration-none text-dark">
      <div class="card-body text-center py-4">
        <i class="fas fa-calendar-alt fa-2x text-primary mb-3"></i>
        <h5 class="card-title mb-0">Calendario</h5>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
    <a href="{{ route('adminconfig') }}" class="card shadow-sm h-100 text-decoration-none text-dark">
      <div class="card-body text-center py-4">
        <i class="fas fa-cog fa-2x text-primary mb-3"></i>
        <h5 class="card-title mb-0">Config</h5>
      </div>
    </a>
  </div>
</div>

@endsection