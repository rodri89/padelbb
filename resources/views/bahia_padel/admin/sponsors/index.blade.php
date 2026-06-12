@extends('bahia_padel.admin.plantilla')

@section('contenido')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Administrar Sponsors</h1>
        <a href="{{ route('sponsors.create') }}" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50">
                <i class="fas fa-plus"></i>
            </span>
            <span class="text">Nuevo Sponsor</span>
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Listado de Sponsors</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sponsors as $sponsor)
                        <tr>
                            <td>{{ $sponsor->orden }}</td>
                            <td style="width: 150px;">
                                @if($sponsor->imagen)
                                    <img src="{{ asset('images/ads/' . $sponsor->imagen) }}" style="width: 100px; height: auto;" class="img-thumbnail" onerror="this.src='{{ asset('images/no-image.png') }}'">
                                @else
                                    <span class="badge badge-secondary">Sin imagen</span>
                                @endif
                            </td>
                            <td>{{ $sponsor->nombre }}</td>
                            <td>
                                @if($sponsor->active)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-danger">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('sponsors.edit', $sponsor->id) }}" class="btn btn-info btn-circle btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('sponsors.destroy', $sponsor->id) }}" method="POST" style="display:inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-circle btn-sm" onclick="return confirm('¿Está seguro de eliminar este sponsor?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
