@extends('bahia_padel/admin/plantilla')

@section('title_header', 'Menú')

@section('contenedor')
<div class="container-fluid body_admin">
    <div style="max-width: 900px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0 text-gray-800" style="font-weight: 600;">Productos del Menú</h3>
            <a href="{{ route('admin.menu.create') }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Nuevo producto
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover m-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr>
                            <td class="font-weight-medium">{{ $item->name }}</td>
                            <td class="text-muted">{{ $item->category }}</td>
                            <td class="font-weight-bold text-success">${{ number_format($item->price, 0, ',', '.') }}</td>
                            <td>
                                @if($item->available)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-secondary">No disp.</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.menu.edit', $item) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <form action="{{ route('admin.menu.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este producto?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i> Borrar
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
