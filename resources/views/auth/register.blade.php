
@extends('bahia_padel/admin/plantilla')

@section('contenedor')
<style>
    .card {
        color: #000 !important;
    }
    .card-header {
        color: #000 !important;
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
    .card-body {
        color: #000 !important;
    }
    .form-group label {
        color: #000 !important;
    }
    .form-control {
        color: #000 !important;
        background-color: #fff !important;
    }
    .form-control::placeholder {
        color: #6c757d !important;
    }
    .invalid-feedback {
        color: #dc3545 !important;
    }
</style>
<br><br><br><br><br><br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card" style="color: #000;">
                <div class="card-header" style="color: #000; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">{{ __('Register') }}</div>

                <div class="card-body" style="color: #000;">                    
                    <form method="POST" action="{{ route('registrar') }}"> 
                        @csrf

                        <div class="form-group row">
                            <label for="name" class="col-md-4 col-form-label text-md-right" style="color: #000;">{{ __('Nombre') }}</label>

                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus style="color: #000; background-color: #fff;">

                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="surname" class="col-md-4 col-form-label text-md-right" style="color: #000;">{{ __('Apellido') }}</label>
                            
                            <div class="col-md-6">
                                <input id="surname" type="text" class="form-control @error('surname') is-invalid @enderror" name="surname" value="{{ old('surname') }}" required autocomplete="surname" autofocus style="color: #000; background-color: #fff;">

                                @error('surname')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right" style="color: #000;">{{ __('Mail') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" style="color: #000; background-color: #fff;">

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password" class="col-md-4 col-form-label text-md-right" style="color: #000;">{{ __('Contraseña') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" style="color: #000; background-color: #fff;">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-right" style="color: #000;">{{ __('Confirmar Contraseña') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" style="color: #000; background-color: #fff;">
                            </div>
                        </div>

                         <div class="form-group row">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-right" style="color: #000;">{{ __('Tipo Usuario') }}</label>

                            <div class="col-md-6">
                                <input id="usuario_tipo" type="integer" class="form-control" name="usuario_tipo" required autocomplete="usuario_tipo" placeholder="1-Admin 2-Admin Padel 3-User Padel" style="color: #000; background-color: #fff;">
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Registrar') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<br><br><br><br><br><br>
@endsection
