@extends('bahia_padel.admin.plantilla')

@section('contenido')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Formulario Sponsor</h1>
        <a href="{{ route('sponsors.index') }}" class="btn btn-secondary btn-icon-split">
            <span class="icon text-white-50">
                <i class="fas fa-arrow-left"></i>
            </span>
            <span class="text">Volver al listado</span>
        </a>
    </div>

    <!-- Mensajes de Error de Validación -->
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ isset($sponsor) ? 'Editar Sponsor' : 'Nuevo Sponsor' }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ isset($sponsor) ? route('sponsors.update', $sponsor->id) : route('sponsors.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if(isset($sponsor))
                            @method('PUT')
                        @endif

                        <div class="form-group mb-3">
                            <label for="nombre">Nombre del Sponsor</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="{{ isset($sponsor) ? $sponsor->nombre : old('nombre') }}" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="orden">Orden de aparición</label>
                            <input type="number" class="form-control" id="orden" name="orden" value="{{ isset($sponsor) ? $sponsor->orden : old('orden', 0) }}">
                        </div>

                        <div class="form-group mb-4">
                            <label for="imagen">Imagen (Recomendado 1000x562 px)</label>
                            @if(isset($sponsor) && $sponsor->imagen)
                                <div class="mb-2">
                                    <img src="{{ asset('images/ads/' . $sponsor->imagen) }}" style="width: 200px; height: auto;" class="img-thumbnail" onerror="this.src='{{ asset('images/no-image.png') }}'">
                                </div>
                            @endif
                            <input type="file" class="form-control-file" id="imagen" name="imagen">
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="active" name="active" {{ (isset($sponsor) && $sponsor->active) || !isset($sponsor) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">Mostrar en TV</label>
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save mr-1"></i>
                            {{ isset($sponsor) ? 'Guardar Cambios' : 'Crear Sponsor' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
