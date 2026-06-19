@extends('bahia_padel/admin/plantilla')

@section('title_header','Turnos Fijos')

@section('contenedor')
<div class="container-fluid body_admin">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Nuevo turno fijo</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('adminturnosfijos.store') }}">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Cancha</label>
                                <select name="stock_cancha_id" class="form-control" required>
                                    <option value="">Elegir…</option>
                                    @foreach($canchas as $c)
                                        <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Día</label>
                                <select name="dia_semana" class="form-control" required>
                                    <option value="">Elegir…</option>
                                    @foreach($dias as $num => $nombre)
                                        <option value="{{ $num }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label>Hora</label>
                                <select name="hora" class="form-control" required>
                                    <option value="">Elegir…</option>
                                    @foreach($horarios as $h)
                                        <option value="{{ $h }}">{{ $h }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Nombre del grupo</label>
                                <input type="text" name="nombre_grupo" class="form-control" placeholder="Ej: Grupo de Carlos" maxlength="100" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar turno fijo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Turnos fijos cargados</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Cancha</th>
                                    <th>Día</th>
                                    <th>Hora</th>
                                    <th>Nombre del grupo</th>
                                    <th>Estado</th>
                                    <th style="width:180px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($turnos as $turno)
                                <tr>
                                    <td>{{ $turno->cancha->nombre }}</td>
                                    <td>{{ $dias[$turno->dia_semana] ?? '-' }}</td>
                                    <td>{{ substr($turno->hora, 0, 5) }}</td>
                                    <td>{{ $turno->nombre_grupo }}</td>
                                    <td>
                                        @if($turno->activo)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#modal-editar-{{ $turno->id }}">Editar</button>
                                        <form method="POST" action="{{ route('adminturnosfijos.destroy', $turno) }}" class="d-inline" onsubmit="return confirm('¿Eliminar este turno fijo?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No hay turnos fijos cargados.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($turnos as $turno)
<div class="modal fade" id="modal-editar-{{ $turno->id }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar turno fijo</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST" action="{{ route('adminturnosfijos.update', $turno) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label>Cancha</label>
                        <select name="stock_cancha_id" class="form-control" required>
                            @foreach($canchas as $c)
                                <option value="{{ $c->id }}" {{ $c->id == $turno->stock_cancha_id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Día</label>
                        <select name="dia_semana" class="form-control" required>
                            @foreach($dias as $num => $nombre)
                                <option value="{{ $num }}" {{ $num == $turno->dia_semana ? 'selected' : '' }}>{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Hora</label>
                        <select name="hora" class="form-control" required>
                            @foreach($horarios as $h)
                                <option value="{{ $h }}" {{ $h == substr($turno->hora, 0, 5) ? 'selected' : '' }}>{{ $h }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nombre del grupo</label>
                        <input type="text" name="nombre_grupo" class="form-control" value="{{ $turno->nombre_grupo }}" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="activo" value="1" class="form-check-input" id="activo-{{ $turno->id }}" {{ $turno->activo ? 'checked' : '' }}>
                            <label class="form-check-label" for="activo-{{ $turno->id }}">Activo</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
