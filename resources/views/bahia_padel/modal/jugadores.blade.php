<!-- Modal Seleccionar Jugador -->
<div class="modal fade body_admin" id="modalSeleccionarJugador" tabindex="-1" role="dialog" aria-labelledby="modalSeleccionarJugadorLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSeleccionarJugadorLabel">Seleccionar Jugador</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Buscador -->
        <input type="text" class="form-control mb-3" id="buscador-jugador" placeholder="Buscar jugador por nombre o apellido...">

        <!-- Botón para mostrar el formulario de creación -->
        <button type="button" class="btn btn-success btn-block mb-3" id="btn-mostrar-form-crear-jugador">
            + Crear nuevo jugador
        </button>

        <!-- Formulario inline oculto -->
        <div id="form-crear-jugador" class="mb-3" style="display:none;">
            <div class="form-row">
                <div class="col">
                    <input type="text" class="form-control mb-2" id="nuevo-nombre" placeholder="Nombre" required>
                </div>
                <div class="col">
                    <input type="text" class="form-control mb-2" id="nuevo-apellido" placeholder="Apellido" required>
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-block" id="btn-crear-jugador-ajax">Crear</button>
            <button type="button" class="btn btn-secondary btn-block mt-2" id="btn-cancelar-crear-jugador">Cancelar</button>
        </div>
        <div class="list-group" id="lista-jugadores">
          @foreach($jugadores as $jugador)
            <button type="button" class="list-group-item list-group-item-action jugador-option"
                data-id="{{ $jugador->id }}"
                data-nombre="{{ $jugador->apellido }}"
                data-img="{{ asset(($jugador->foto ?? 'jugador_img.png')) }}">
                <img src="{{ asset($jugador->foto ?? 'images/jugador_img.png') }}" class="rounded-circle mr-2" style="width:40px; height:40px; object-fit:cover;">
                {{ $jugador->nombre }} {{ $jugador->apellido }}
            </button>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Filtrado en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('buscador-jugador');
    const lista = document.getElementById('lista-jugadores');
    if(input && lista) {
        input.addEventListener('keyup', function() {
            const filtro = input.value.toLowerCase();
            lista.querySelectorAll('.jugador-option').forEach(function(btn) {
                const nombre = btn.getAttribute('data-nombre').toLowerCase();
                btn.style.display = nombre.includes(filtro) ? '' : 'none';
            });
        });
    }

    // Mostrar/ocultar el formulario
    document.getElementById('btn-mostrar-form-crear-jugador').addEventListener('click', function() {
        document.getElementById('form-crear-jugador').style.display = 'block';
        this.style.display = 'none';
        document.getElementById('lista-jugadores').style.display = 'none'; // OCULTA LA LISTA
        document.getElementById('buscador-jugador').style.display = 'none'; // OCULTA EL BUSCADOR
    });

    // Crear jugador por AJAX
    document.getElementById('btn-crear-jugador-ajax').addEventListener('click', function() {
        var nombre = document.getElementById('nuevo-nombre').value.trim();
        var apellido = document.getElementById('nuevo-apellido').value.trim();
        if (!nombre || !apellido) {
            alert('Completa nombre y apellido');
            return;
        }
        // AJAX POST
        fetch("{{ route('admincrearjugador') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ nombre: nombre, apellido: apellido })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Agregar a la lista
                let lista = document.getElementById('lista-jugadores');
                let btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'list-group-item list-group-item-action jugador-option';
                btn.setAttribute('data-id', data.jugador.id);
                btn.setAttribute('data-nombre', data.jugador.nombre + ' ' + data.jugador.apellido);
                btn.setAttribute('data-img', '{{ asset('images/jugador_img.png') }}');
                btn.innerHTML = `<img src=\"{{ asset('images/jugador_img.png') }}\" class=\"rounded-circle mr-2\" style=\"width:40px; height:40px; object-fit:cover;\"> ${data.jugador.nombre} ${data.jugador.apellido}`;
                lista.prepend(btn);

                // Limpiar y ocultar el form
                document.getElementById('nuevo-nombre').value = '';
                document.getElementById('nuevo-apellido').value = '';
                document.getElementById('form-crear-jugador').style.display = 'none';
                document.getElementById('btn-mostrar-form-crear-jugador').style.display = 'block';
                document.getElementById('lista-jugadores').style.display = 'block'; // MUESTRA LA LISTA
                document.getElementById('buscador-jugador').style.display = 'block'; // MUESTRA EL BUSCADOR
            } else {
                alert('Error al crear jugador');
            }
        })
        .catch(() => alert('Error de red'));
    });

    // Cancelar creación de jugador
    document.getElementById('btn-cancelar-crear-jugador').addEventListener('click', function() {
        document.getElementById('form-crear-jugador').style.display = 'none';
        document.getElementById('btn-mostrar-form-crear-jugador').style.display = 'block';
        document.getElementById('lista-jugadores').style.display = 'block';
        document.getElementById('buscador-jugador').style.display = 'block';
    });
});
</script>

