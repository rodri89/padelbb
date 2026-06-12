<div class="d-flex justify-content-center align-items-start body_admin">
    <form class="p-4 rounded shadow bg-white" style="min-width: 450px; max-width: 450px; width: 100%; position: relative;">
        <button type="button" class="close" aria-label="Cerrar" style="position: absolute; top: 16px; right: 24px; font-size: 2rem; z-index: 10; background: none; border: none;" onclick="volverAtrasNuevoTorneo()">
            &times;
        </button>

        <input type="hidden" id="id_torneo" name="id_torneo" value="0">

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input id="nombre" name="nombre" type="text" class="form-control form-control-lg" />
        </div>

        <div class="mb-3">
            <label for="tipo_torneo" class="form-label">Tipo de Torneo</label>
            <select id="tipo_torneo" name="tipo_torneo" class="form-control form-control-lg" required>                
                <option value="puntuable">Puntuable</option>               
                <option value="americano">Americano</option>               
                <option value="suma">Suma</option>               
            </select>
        </div>

        <div class="mb-3">
            <label for="tipo" class="form-label">Tipo</label>
            <select id="tipo" name="tipo" class="form-control form-control-lg">                
                <option value="masculino">Masculino</option>               
                <option value="femenino">Femenino</option>               
                <option value="mixto">Mixto</option>               
            </select>
        </div>

        <div class="mb-3">
            <label for="categoria" class="form-label">Categoría</label>
            <select id="categoria" name="categoria" class="form-control form-control-lg">
                @for ($i = 1; $i <= 10; $i++)
                    <option value="{{ $i }}">{{ $i }}</option>
                @endfor
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                <input id="fecha_inicio" name="fecha_inicio" type="date" class="form-control form-control-lg" />
            </div>
            <div class="col-md-6 mb-3">
                <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                <input id="fecha_fin" name="fecha_fin" type="date" class="form-control form-control-lg" />
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="premio1" class="form-label">1º Premio</label>
                <input id="premio1" name="premio1" type="text" class="form-control form-control-lg" />
            </div>
            <div class="col-md-6 mb-3">
                <label for="premio2" class="form-label">2º Premio</label>
                <input id="premio2" name="premio2" type="text" class="form-control form-control-lg" />
            </div>
        </div>
        
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <input id="descripcion" name="descripcion" type="text" class="form-control form-control-lg" />
        </div>

        <div class="text-center">
            <button type="button" class="btn btn-primary btn-lg px-5" onclick="registrar()">
                Registrar
            </button>
        </div>
    </form>
</div>

<script type="text/javascript">

    function registrar() {
        var id_torneo = document.getElementById('id_torneo').value;
        var nombre = document.getElementById('nombre').value;
        var tipo_torneo_formato = document.getElementById('tipo_torneo').value; // americano, puntuable, suma
        var tipo_torneo = 2; // Mantener para compatibilidad (es_torneo_individual)
        var categoria = document.getElementById('categoria').value;
        var fechaInicio = document.getElementById('fecha_inicio').value;
        var fechaFin = document.getElementById('fecha_fin').value;
        var premio1 = document.getElementById('premio1').value;
        var premio2 = document.getElementById('premio2').value;
        var descripcion = document.getElementById('descripcion').value;
        var tipo = document.getElementById('tipo').value;
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("registrartorneoadmin") }}',
            data: { id_torneo: id_torneo, nombre: nombre, tipo_torneo: tipo_torneo, tipo_torneo_formato: tipo_torneo_formato, categoria:categoria, fechaInicio:fechaInicio,
                fechaFin:fechaFin, premio1:premio1, premio2:premio2, descripcion:descripcion,tipo:tipo, _token: '{{csrf_token()}}' },
            success: function (data) {
                if (data.torneo != null) {
                    document.getElementById('id_torneo').value = data.torneo.id
                    showSnackbar("¡Torneo registrado exitosamente!");
                    location.reload();
                } else {
                    let errorMsg = 'Error: No se pudo guardar el torneo';
                    if (data.error) {
                        errorMsg = data.error;
                    }
                    alert(errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al guardar torneo:', xhr, status, error);
                console.error('Response:', xhr.responseText);
                let errorMsg = 'Error al guardar el torneo';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    } else if (xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                } else if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg = response.error || response.message || errorMsg;
                    } catch (e) {
                        errorMsg = xhr.responseText.substring(0, 100);
                    }
                }
                alert(errorMsg);
            }
        });
    }

    function volverAtrasNuevoTorneo() {        
        location.reload();
    }
</script>

<script>
function formatearMilesInput(input) {
    // Elimina todo lo que no sea dígito
    let valor = input.value.replace(/\D/g, '');
    // Formatea con puntos de miles
    valor = valor.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    input.value = valor;
}

document.addEventListener('DOMContentLoaded', function() {
    const premio1 = document.getElementById('premio1');
    const premio2 = document.getElementById('premio2');
    if (premio1) {
        premio1.addEventListener('input', function() {
            formatearMilesInput(this);
        });
    }
    if (premio2) {
        premio2.addEventListener('input', function() {
            formatearMilesInput(this);
        });
    }
    // Limpiar puntos antes de enviar el formulario
    const form = premio1 ? premio1.form : null;
    if (form) {
        form.addEventListener('submit', function(e) {
            if (premio1) premio1.value = premio1.value.replace(/\./g, '');
            if (premio2) premio2.value = premio2.value.replace(/\./g, '');
        });
    }
});
</script>