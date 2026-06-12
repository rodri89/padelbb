@extends('padel/admin/plantilla')

@section('title_header','Fecha Actual')

@section('contenedor')

<input type="hidden" id="torneo_id" value="{{$torneo_id}}">
<input type="hidden" id="fecha_numero" value="{{$fecha_numero}}">

<div class="row">
	<div class="col-xl-8">
		<label style="font-size: 30px;">Fecha: {{$fecha_numero}}</label><br>
		<label style="font-size: 25px;">Partido</label><label style="font-size: 25px;" id="partido_actual">1</label>
		<div class="contenedor" id="contenedor">		   
		</div>	
		<div class="botones-container">
			<button class="primary_button" onclick="anteriorPartido()">Anterior</button>
			<button class="primary_button" onclick="siguientePartido();">Siguiente</button>
		</div>
	</div>

	<div class="col-xl-4">
		<label>Posiciones</label>
		<div class="contenedor" id="contenedor_posiciones">		   

		</div>	
		<div class="text-center" style="margin-bottom: 20px;">
			<button class="primary_button" onclick="finalizarFecha()">Finalizar</button>			
		</div>
	</div>
</div>

@include('modal.modal_puntuar_fecha')


<script type="text/javascript">
	let partidoActual = 1;
	let cantidadPartidos = 5;

	function removeSeccion(seccionNombre) {
		//var seccion = document.getElementById("seccion_1_parejas");
		var seccion = document.getElementById(seccionNombre);
		while (seccion.firstChild) {
			seccion.removeChild(seccion.firstChild);
		}
	}

	function finalizarFecha() {
		removeSeccion("modalPuntuarFecha_body");
		var torneoId = document.getElementById("torneo_id").value;
		var fechaNumero = document.getElementById("fecha_numero").value;
		$.ajax({
		       type:'POST',
		       dataType:'JSON',
		       url:'/calcular_posiciones',
		       data:{fechaNumero:fechaNumero, torneoId:torneoId,_token: '{{csrf_token()}}'},
		       success:function(data) {   			       		
					generarTablaFinalizarFecha(data.posicionesAux);
		       } 
    		});

		$("#modalPuntuarFecha").modal();     
	}	

function generarTablaFinalizarFecha(jugadores) {
    var seccion = document.getElementById('modalPuntuarFecha_body');		

    const tabla = document.createElement('table');
    tabla.id = 'tabla_puntuar_fechas_id';
    tabla.classList.add('table'); // Agrega clases CSS para darle estilo a la tabla
    
    // Crear el encabezado de la tabla
    const thead = document.createElement('thead');
    const encabezado = document.createElement('tr');
    encabezado.innerHTML = `
        <th style="display: none;">ID</th> <!-- Columna oculta para el ID -->
        <th>Posición</th>
        <th>Imagen</th>
        <th>Nombre</th>
        <th>Puntos</th>
    `;
    thead.appendChild(encabezado);
    tabla.appendChild(thead);
    console.log(jugadores);
    // Crear el cuerpo de la tabla
    const tbody = document.createElement('tbody');
    let pos = 1;
    
    jugadores.forEach(jugador => {
        if (jugador.nombre !== "Libre") {
            const fila = document.createElement('tr');

            // Crear la celda oculta con el ID del jugador
            const celdaID = document.createElement('td');
            celdaID.textContent = jugador.jugador_id;
            celdaID.style.display = "none"; // Ocultar la celda
            
            // Crear la celda de posición
            const celdaPosicion = document.createElement('td');
            celdaPosicion.textContent = pos++;

            // Crear la celda de imagen
            const celdaImagen = document.createElement('td');
            const img = document.createElement('img');
            img.src = jugador.imagen;
            img.alt = jugador.nombre;
            img.classList.add('imagen-jugador-posiciones');
            celdaImagen.appendChild(img);

            // Crear la celda de nombre
            const celdaNombre = document.createElement('td');
            celdaNombre.textContent = `${jugador.nombre}, ${jugador.apellido}`;

            // Crear la celda con el input para los puntos
            const celdaPuntos = document.createElement('td');
            const inputPuntos = document.createElement('input');	        
            inputPuntos.type = 'number';
            inputPuntos.classList.add('input-text-2');
            celdaPuntos.appendChild(inputPuntos);

            // Agregar las celdas a la fila
            fila.appendChild(celdaID);
            fila.appendChild(celdaPosicion);
            fila.appendChild(celdaImagen);
            fila.appendChild(celdaNombre);
            fila.appendChild(celdaPuntos);

            tbody.appendChild(fila);
        }
    });

    tabla.appendChild(tbody);
    seccion.appendChild(tabla);
}


	function guardarPuntosFecha() {
	    var tabla = document.getElementById('tabla_puntuar_fechas_id');
	    var resultados = []; // Array para almacenar los datos

	    if (tabla) {
	        var filas = tabla.getElementsByTagName('tr'); // Obtiene todas las filas de la tabla

	        for (var i = 0; i < filas.length; i++) {
	            var fila = filas[i];
	            var celdas = fila.getElementsByTagName('td');

	            if (celdas.length >= 5) { // Verificar que haya al menos 5 celdas (ID, Posición, Imagen, Nombre, Puntos)
	                var jugador_id = celdas[0].textContent.trim(); // Celda 0 es la del ID oculto
	                var input = celdas[4].querySelector('input'); // Buscar el input en la celda 4
	                
	                if (input) {
	                    var puntos = input.value.trim(); // Obtener el valor del input
	                    resultados.push({ jugador_id, puntos }); // Guardar en el array
	                }
	            }
	        }

	        console.log("Resultados:", resultados); // Mostrar el array en consola
	    } else {
	        alert("Tabla no encontrada");
	    }

	    var torneoId = document.getElementById("torneo_id").value;
		var fechaNumero = document.getElementById("fecha_numero").value;
		$.ajax({
		       type:'POST',
		       dataType:'JSON',
		       url:'/guardar_puntos_fecha',
		       data:{fechaNumero:fechaNumero, torneoId:torneoId, resultados:resultados,_token: '{{csrf_token()}}'},
		       success:function(data) {   			       		     	
					alert('Los puntos han sido guardado');
		       } 
    		});
	    //return resultados; // Retornar el array si necesitas usarlo
	}


	function calcularPosiciones() {		
		var torneoId = document.getElementById("torneo_id").value;
		var fechaNumero = document.getElementById("fecha_numero").value;
		$.ajax({
		       type:'POST',
		       dataType:'JSON',
		       url:'/calcular_posiciones',
		       data:{fechaNumero:fechaNumero, torneoId:torneoId,_token: '{{csrf_token()}}'},
		       success:function(data) {   	
		       		removeSeccion("contenedor_posiciones");		       				       		
					generarTabla(data.posicionesAux);
		       } 
    		});
	}

	function generarTabla(jugadores) {
		
        // Crear la tabla
        const tabla = document.createElement('table');
        tabla.classList.add('table'); // Aquí puedes agregar clases CSS para darle estilo a la tabla

        // Crear la cabecera de la tabla
        const thead = document.createElement('thead');
        const encabezado = document.createElement('tr');
        encabezado.innerHTML = `
            <th>Posición</th>
            <th>Imagen</th>
            <th>Nombre</th>
            <th>Puntos</th>
            
        `;
        thead.appendChild(encabezado);
        tabla.appendChild(thead);

        // Crear el cuerpo de la tabla
        const tbody = document.createElement('tbody');
        let pos = 1;
        jugadores.forEach(jugador => {
        	if(jugador.nombre !== "Libre"){
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td>${pos++}</td>
                <td><img src="${jugador.imagen}" alt="${jugador.nombre}" class="imagen-jugador-posiciones"></td>
                <td>${jugador.nombre}, ${jugador.apellido}</td>
                <td>${jugador.puntos}</td>
                
            `;            
            tbody.appendChild(fila);       
            }
        });

        tabla.appendChild(tbody);

        // Insertar la tabla en el contenedor
        const contenedor = document.getElementById('contenedor_posiciones');
        contenedor.appendChild(tabla);
    }

	function removeSeccion(seccionNombre) {
		//var seccion = document.getElementById("seccion_1_parejas");
		var seccion = document.getElementById(seccionNombre);
		while (seccion.firstChild) {
			seccion.removeChild(seccion.firstChild);
		}
	}

	function anteriorPartido() {
		removeSeccion("contenedor");
		
		partidoActual--;
		if(partidoActual == 0)
			partidoActual = 1;
		document.getElementById("partido_actual").innerText = partidoActual;
		cargarFecha(partidoActual);
	}

	function siguientePartido() {
		removeSeccion("contenedor");
		
		partidoActual++;
		if(partidoActual == cantidadPartidos + 1)
			partidoActual = cantidadPartidos;
		document.getElementById("partido_actual").innerText = partidoActual;
		cargarFecha(partidoActual);
	}

	function cargarFecha(partidoNumero) {		
		var torneoId = document.getElementById("torneo_id").value;
		var fechaNumero = document.getElementById("fecha_numero").value;
		$.ajax({
		       type:'POST',
		       dataType:'JSON',
		       url:'/get_partido_fecha',
		       data:{fechaNumero:fechaNumero, partidoNumero:partidoNumero,torneoId:torneoId,_token: '{{csrf_token()}}'},
		       success:function(data) {   		
		       	for(var i = 0; i<data.partidos.length; i++){
		       		agregarPartido(data.partidos[i], data.jugadores);
		       	}
		       	cantidadPartidos = data.maxPartidoNumero;
		       } 
    		});
	}

	// Función para crear un jugador
function crearJugador(imagen, nombre) {
    const divJugador = document.createElement('div');
    divJugador.classList.add('jugador');
    if (!imagen || imagen.trim() === "") {
    	imagen = "images/jugador_img.png";
    } 
    const img = document.createElement('img');
    img.src = imagen;
    img.alt = nombre;
    img.classList.add('imagen-jugador');

    const p = document.createElement('p');
    p.classList.add('nombre-jugador');
    p.innerText = nombre;

    // Agregar la imagen y el nombre al div
    divJugador.appendChild(img);
    divJugador.appendChild(p);

    return divJugador;
}

function guardarResultado(jugador_id, puntos) {
	var torneoId = document.getElementById("torneo_id").value;
	var fechaNumero = document.getElementById("fecha_numero").value;
	var partidoActual = document.getElementById("partido_actual").innerText;
	
	$.ajax({
       type:'POST',
       dataType:'JSON',
       url:'/guardar_puntos',
       data:{jugador_id:jugador_id, partidoNumero:partidoActual,torneoId:torneoId, fechaNumero:fechaNumero, puntos:puntos,_token: '{{csrf_token()}}'},
       success:function(data) {   			       				       		
    		calcularPosiciones();   	
       } 
	});
}

function agregarPartido(partido, jugadores) {	
	const contenedor = document.querySelector('.contenedor');
	const div = document.createElement('div');
	div.classList.add('row');
	
	const jugador1 = jugadores.find(j => j.id === partido.jugador_id_1);
	const jugador2 = jugadores.find(j => j.id === partido.jugador_id_2);
	const jugador3 = jugadores.find(j => j.id === partido.jugador_id_3);
	const jugador4 = jugadores.find(j => j.id === partido.jugador_id_4);

	div.appendChild(crearJugador(jugador1.foto, jugador1.nombre));
	div.appendChild(crearJugador(jugador2.foto, jugador2.nombre));
	// Agregar los inputs de texto
	const input1 = document.createElement('input');
	input1.type = 'text';
	input1.placeholder = '0';
	input1.value = partido.resultado_games_jugador_1;
	input1.classList.add('input-text');
	input1.onchange = function() {				
	    guardarResultado(jugador1.id, input1.value);
	};	

	const input2 = document.createElement('input');
	input2.type = 'text';
	input2.placeholder = '0';
	input2.value = partido.resultado_games_jugador_3;
	input2.classList.add('input-text');
	input2.onchange = function() {
	    guardarResultado(jugador3.id, input2.value);
	};

	if(jugador1.nombre === "Libre" || jugador2.nombre === "Libre" || jugador3.nombre === "Libre" || jugador4.nombre === "Libre"){
		input1.disabled = true;
		input2.disabled = true;
	}

	// Agregar los inputs al contenedor
	div.appendChild(input1);
	div.appendChild(input2);

	div.appendChild(crearJugador(jugador3.foto, jugador3.nombre));
	div.appendChild(crearJugador(jugador4.foto, jugador4.nombre));
	contenedor.appendChild(div);
}

 window.onload=function() {   	
 	cargarFecha(partidoActual);
 	calcularPosiciones();
 }

</script>

<style type="text/css">
.botones-container {
    display: flex;
    justify-content: center; /* Centra los botones horizontalmente */
    gap: 10px; /* Espacio entre botones */
    margin-top: 20px; /* Espacio superior opcional */
}


	.contenedor {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: space-around;
    padding: 10px;
}

.jugador {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 150px; /* Puedes ajustar el tamaño del contenedor de los jugadores */
}

.imagen-jugador {
    width: 120px; /* Ajusta el tamaño de las imágenes de los jugadores */
    height: 120px;
    border-radius: 50%; /* Si deseas que la imagen sea circular */
}

.imagen-jugador-posiciones {
    width: 50px; /* Ajusta el tamaño de las imágenes de los jugadores */
    height: 50px;
    border-radius: 50%; /* Si deseas que la imagen sea circular */
}

.nombre-jugador {
    margin-top: 10px;
    font-weight: bold;
    text-align: center;
}

.input-text {
    width: 100px; /* Ancho de los campos de texto */
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 50px;
},

.input-text-2 {
    width: 100px; /* Ancho de los campos de texto */
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 30px;
},


</style>

@endsection