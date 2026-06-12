@extends('bahia_padel/admin/plantilla')

@section('title_header', 'Menú — Editar producto')

@section('contenedor')
<div class="container-fluid body_admin">
    <div style="max-width: 600px;">
        <h3 class="mb-4 text-gray-800" style="font-weight: 600;">Editar producto</h3>

        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('admin.menu.update', $menuItem) }}" method="POST">
                    @csrf @method('PUT')

                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="name" value="{{ old('name', $menuItem->name) }}" required class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="description" rows="2" class="form-control">{{ old('description', $menuItem->description) }}</textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Precio</label>
                            <input type="number" step="0.01" name="price" value="{{ old('price', $menuItem->price) }}" required class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Categoría</label>
                            <input type="text" name="category" value="{{ old('category', $menuItem->category) }}" required class="form-control">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Orden</label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', $menuItem->sort_order) }}" class="form-control">
                        </div>
                        <div class="form-group col-md-6 d-flex align-items-center">
                            <div class="form-check mt-3">
                                <input type="checkbox" name="available" id="available" value="1" {{ old('available', $menuItem->available) ? 'checked' : '' }} class="form-check-input">
                                <label for="available" class="form-check-label">Disponible</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="{{ route('admin.menu.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
