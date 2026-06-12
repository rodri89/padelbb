@extends('bahia_padel/admin/plantilla')

@section('title_header','Armar Torneo Suma')

@section('contenedor')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="text-center mb-4">Armar Torneo Suma</h2>
            <input type="hidden" id="torneo_id" value="{{ $torneo->id ?? 0 }}">
            
            <div class="alert alert-info">
                <h4>Pantalla para Armar Torneo Suma</h4>
                <p>Esta pantalla está en desarrollo. Aquí se configurará el formato de torneo suma.</p>
                <p><strong>Torneo:</strong> {{ $torneo->nombre ?? 'N/A' }}</p>
            </div>
            
            <div class="text-center mt-4">
                <button type="button" class="btn btn-secondary btn-lg" onclick="window.history.back()">
                    Volver
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

