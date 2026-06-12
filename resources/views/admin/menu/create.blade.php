@extends('bahia_padel/admin/plantilla')

@section('title_header', 'Menú — Nuevo producto')

@section('contenedor')
<div class="container-fluid body_admin">
    <div style="max-width: 600px;">
        <h3 class="mb-4 text-gray-800" style="font-weight: 600;">Nuevo producto</h3>

        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('admin.menu.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="description" rows="2" class="form-control">{{ old('description') }}</textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Precio</label>
                            <input type="number" step="0.01" name="price" value="{{ old('price') }}" required class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Categoría</label>
                            <input type="text" name="category" value="{{ old('category') }}" required placeholder="Ej: Bebidas" class="form-control">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Orden</label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control">
                        </div>
                        <div class="form-group col-md-6 d-flex align-items-center">
                            <div class="form-check mt-3">
                                <input type="checkbox" name="available" id="available" value="1" checked class="form-check-input">
                                <label for="available" class="form-check-label">Disponible</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="{{ route('admin.menu.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
