@extends('padel/admin/plantilla')

@section('title_header','Fecha')

@section('contenedor')

<input type="hidden" id="cantidad_jugadores" value="0">

<div class="row text-center">	
	<div class="col-xl-1">
		<label>Ingresar Fecha</label>
		<input type="text" id="num_fecha" value="{{$nuevaFecha}}" class="form-control">
	</div>
</div>

<div class="text-center" id="seccion_armar_fecha">
	<div class="row text-center">	
		<div class="col-xl-3">
			<label style="width: 100px">Torneo</label>								
	        	<select id="torneo" name="torneo" class="form-control" onchange="onChangeTorneo()">
		            @foreach($torneos as $t)                  				
		            	<option value="{{$t->id}}">{{$t->nombre}}</option>		            
		            @endforeach
		        </select>
		</div>
		<div hidden class="col-xl-3">
			<label style="width: 250px">Agregar Jugador</label>								
			<button class="primary_button" onclick="agregarJugador()">Agregar</button>
		</div>
		<div class="col-xl-3">			
			<label style="width: 250px">Clic aqui para armar parejas</label>				
			<button class="primary_button" onclick="obtenerFechasPrevias()">Armar New</button>						

			<div style="margin-top: 10px">
				<button class="primary_button" onclick="armarParejasAction(jugadoresIds, 0)">Armar Old</button>
			</div>
			<br>			
			<div style="margin-top: 1px">
				<input class="form-control" style="margin-left: 100px;width: 150px" type="text" id="cantidad_random" value="3">
				<small>Ingresar numero de randoms</small>
			</div>		
			
		</div>

		<div class="col-xl-3" id="armarFechaBtn">			
			<label style="width: 250px">Clic aqui para generar fecha</label>			
			<button class="primary_button" onclick="armarFecha()">Generar</button>
		</div>

		<div class="col-xl-3" id="comenzarFechaBtn" hidden>
		    <form id="comenzarFechaForm" action="/comenzar_fecha" method="post">
		        <!-- Token CSRF necesario para peticiones POST en Laravel -->
		        <input type="hidden" name="_token" value="{{ csrf_token() }}">
		        
		        <input type="hidden" id="torneo_id" name="torneo" value="1">  		        
		        
		        <label style="width: 250px">Clic aquí para comenzar fecha</label>			
		        <button type="submit" class="primary_button">Comenzar</button>		    
		    </form>
		</div>


	</div>
<br>
<br>

<h5>Click para agregar un nuevo jugador</h5><br>
<div class="row text-center" id="seccion_agregar_jugadores">	
    @foreach($jugadores as $j)
        <div class="flex-shrink-0 px-2" style="width: 100px;"> <!-- Ancho fijo -->
            <a onclick="seleccionarJugador({{ $j->id }})" 
               class="d-block text-decoration-none text-dark cursor-pointer"
               style="transition: all 0.3s ease;">
                <div class="p-2 hover-effect"> <!-- Añadida clase para efecto hover -->
                    <img src="{{ $j->foto ?? 'placeholder.jpg' }}" alt="{{ $j->nombre }}"
                         class="img-fluid rounded-circle mb-2 mx-auto" 
                         style="width: 60px; height: 60px; object-fit: cover; border: 2px solid #eee; transition: transform 0.3s ease;">
                    <h6 class="mb-0 text-truncate" style="font-size: 0.9rem;">{{ $j->nombre }}</h6>
                    <h6 class="mb-0 text-truncate" style="font-size: 0.9rem;">{{ $j->apellido }}</h6>
                </div>
            </a>
        </div>
    @endforeach
</div>
	<br>
	<div class="row text-center" id="seccion_jugadores">			
		<h3>Listado de jugadores: </h3><h3 id="cantidad_jugadores_agregados">0</h3>		
		<div class="col-xl-12" id="seccion_1_jugadores" 
         style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
    	</div>

		<div class="col-xl-12" id="seccion_2_jugadores" 
         style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
    	</div>

		<div class="col-xl-12" id="seccion_3_jugadores" 
         style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
    	</div>

    	<div class="col-xl-12" id="seccion_4_jugadores" 
         style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
    	</div>
	</div>

	<div class="row text-center" id="seccion_parejas" hidden >			
		<h3>Listado de jugadores</h3>		
		<div class="col-xl-12" id="seccion_1_parejas" 
         style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">         	
    	</div>

		<div class="col-xl-12" id="seccion_2_parejas" 
         style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
    	</div>

		<div class="col-xl-12" id="seccion_3_parejas" 
         style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
    	</div>

    	<div class="col-xl-12" id="seccion_4_parejas" 
         style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
    	</div>
	</div>


</div>

<div class="text-center" id="seccion_admin_fecha">
	
</div>

@include('modal.modal_jugadores')

<style>
    .hover-effect:hover {
        transform: translateY(-5px);
    }
    .hover-effect:hover img {
        transform: scale(1.05);
        border-color: #007bff !important;
    }
</style>

<script type="text/javascript">

	let jugadoresIds = []; // Array vacío para almacenar los IDs
	let colorArray = ["#f0f0f0", "#ff5733", "#a2ff33", "#33fffc", "#8a33ff", "#ff3349", "#e9ff33", "#336bff"];
	let colorIndex = 0;	

	function agregarJugador() {		
		$("#modalBuscarJugador").modal();  
	}	

	function removeSeccion(seccionNombre) {
		//var seccion = document.getElementById("seccion_1_parejas");
		var seccion = document.getElementById(seccionNombre);
		while (seccion.firstChild) {
			seccion.removeChild(seccion.firstChild);
		}
	}

	function delay(ms) {
	    return new Promise(resolve => setTimeout(resolve, ms));
	}

	async function armarParejas() {
		if(jugadoresIds.length > 14) {
			jugadoresIds.pop();
			jugadoresIds.pop();
		}
		var cantRandom = document.getElementById("cantidad_random").value;

		for (let i = cantRandom; i > 0; i--) {
		    armarParejasAction(jugadoresIds, 0);
		    await delay(800);	    
		}

	    if(jugadoresIds.length == 14) {
	    	agregarLibres();
	    }
	}

	function agregarLibres() {
		$.ajax({
		       type:'POST',
		       dataType:'JSON',
		       url:'/get_libres',
		       data:{jugadoresIds:jugadoresIds,_token: '{{csrf_token()}}'},
		       success:function(data){   		       		
		       		jugadoresIds.push(data.jugadoresLibres[0].id);
		       		jugadoresIds.push(data.jugadoresLibres[1].id);
		       		agregarParejaAlListado(data.jugadoresLibres[0],data.jugadoresLibres[1], "seccion_4_parejas");	       	
		       } 
    		}); 
	}

	function armarParejasAction(nuevasParejas, option) {
		colorIndex = 0;	
		document.getElementById("seccion_jugadores").hidden = true;
		document.getElementById("seccion_parejas").hidden = false;	
		removeSeccion("seccion_1_parejas");
		removeSeccion("seccion_2_parejas");
		removeSeccion("seccion_3_parejas");
		removeSeccion("seccion_4_parejas");
		if(option == 1) {
			jugadoresIds = nuevasParejas;
		}
		if(option == 0) {
			var cantidad_random = document.getElementById("cantidad_random").value;
			if(cantidad_random != 0)
				mezclarArray(jugadoresIds);
		}
		
		mezclarArray(colorArray);
		console.log(jugadoresIds);
		$.ajax({
		       type:'POST',
		       dataType:'JSON',
		       url:'/get_jugadores',
		       data:{jugadoresIds:jugadoresIds,_token: '{{csrf_token()}}'},
		       success:function(data){   		       		
		       		agregarParejas(data.jugadores); 
		       		if(jugadoresIds.length == 14) {
				    	agregarLibres();
				    }     	
		       } 
    		}); 
	}

	function agregarParejas(jugadoresList) {
		
		let i = 0; // Inicializamos el contador
		while (i < jugadoresList.length) {		    		    
		    if(i < 4)
		       	agregarParejaAlListado(jugadoresList[i++],jugadoresList[i++] , "seccion_1_parejas");
	       	if(i > 3 && i < 8)
	       		agregarParejaAlListado(jugadoresList[i++],jugadoresList[i++], "seccion_2_parejas");
	       	if(i > 7 && i < 12)
	       		agregarParejaAlListado(jugadoresList[i++],jugadoresList[i++], "seccion_3_parejas");	       	
	       	if(i > 11 && i < 16)
	       		agregarParejaAlListado(jugadoresList[i++],jugadoresList[i++], "seccion_4_parejas");	       	
		}
	}

	function obtenerFechasPrevias() {
		var torneo_id = document.getElementById('torneo').value;
		$.ajax({
		       type:'POST',
		       dataType:'JSON',
		       url:'/get_fechas_previas_jugadores',
		       data:{torneo_id:torneo_id,_token: '{{csrf_token()}}'},
		       success:function(data){
		       		var nuevasParejas = generarNuevasParejas(jugadoresIds, data);
		       		console.log("nuevasParejas", nuevasParejas);
		       		jugadoresIds = nuevasParejas.flat();
		       		console.log("plano", jugadoresIds);
		       		armarParejasAction(jugadoresIds, 1);
		       }   			       
    		}); 
	}

	function generarNuevasParejas(jugadores, historial) {
	    const historialParejas = new Set();
	    console.log("generarNuevasParejas", jugadores);
	    // Construir historial de parejas existentes
	    historial.data.forEach(partido => {
	        const pareja1 = [partido.pareja_1[0], partido.pareja_1[1]].sort((a, b) => a - b).join("-");
	        const pareja2 = [partido.pareja_2[0], partido.pareja_2[1]].sort((a, b) => a - b).join("-");
	        historialParejas.add(pareja1);
	        historialParejas.add(pareja2);
	    });

	    const disponibles = [...jugadores]; // copiar el array
	    const nuevasParejas = [];

	    while (disponibles.length >= 2) {
	        let parejaFormada = false;

	        for (let i = 1; i < disponibles.length; i++) {
	            const j1 = disponibles[0];
	            const j2 = disponibles[i];
	            const parejaKey = [j1, j2].sort((a, b) => a - b).join("-");

	            if (!historialParejas.has(parejaKey)) {
	                nuevasParejas.push([j1, j2]);
	                historialParejas.add(parejaKey);
	                disponibles.splice(i, 1); // remover j2
	                disponibles.splice(0, 1); // remover j1
	                parejaFormada = true;
	                break;
	            }
	        }

	        if (!parejaFormada) {
	            // Si no se pudo evitar repetición, forzar pareja con menor repeticiones
	            const j1 = disponibles.shift();
	            const j2 = disponibles.shift();
	            if (j2 !== undefined) {
	                nuevasParejas.push([j1, j2]);
	            }
	        }
	    }

	    return nuevasParejas;
	}


	function mezclarArray(array) {
	    for (let i = array.length - 1; i > 0; i--) {
	        let j = Math.floor(Math.random() * (i + 1)); // Índice aleatorio
	        [array[i], array[j]] = [array[j], array[i]]; // Intercambio de posiciones
	    }
	}

	function seleccionarJugador(id) {		
		$('#modalBuscarJugador').modal('hide'); 

		$.ajax({
		       type:'POST',
		       dataType:'JSON',
		       url:'/get_jugador',
		       data:{id:id,_token: '{{csrf_token()}}'},
		       success:function(data){   	
		       	var cantJugadores = parseInt(document.getElementById("cantidad_jugadores").value, 10);
		       	if(cantJugadores < 4)
		       		agregarJugadorAlListado(data.jugador, "seccion_1_jugadores");
		       	if(cantJugadores > 3 && cantJugadores < 8)
		       		agregarJugadorAlListado(data.jugador, "seccion_2_jugadores");
		       	if(cantJugadores > 7 && cantJugadores < 12)
		       		agregarJugadorAlListado(data.jugador, "seccion_3_jugadores");
		       	if(cantJugadores > 11 && cantJugadores < 16)
		       		agregarJugadorAlListado(data.jugador, "seccion_4_jugadores");
				//if(cantJugadores == 12){
				//	alert("No se pueden agregar mas jugadores");
				//}		                    				
		       } 
    		}); 
	}

	

	function agregarParejaAlListado(jugador1, jugador2, seccion, color) {
	    var divSeccion = document.getElementById(seccion);

	    // Contenedor principal de la pareja
	    var divPareja = document.createElement("div");
		divPareja.style.display = "flex";
		divPareja.style.flexDirection = "row"; // Alineación en fila (horizontal)
		divPareja.style.alignItems = "center";
		divPareja.style.justifyContent = "center";
		divPareja.style.gap = "20px"; // Espacio entre jugadores
		divPareja.style.margin = "10px";
		divPareja.style.border = `2px solid ${colorArray[colorIndex++]}`; // Aplica el borde con color
		divPareja.style.padding = "15px";
		divPareja.style.borderRadius = "10px"; 
		divPareja.style.boxShadow = "2px 2px 10px rgba(0, 0, 0, 0.1)";


	    // Función para crear un jugador con imagen y nombre
	    function crearJugador(jugador) {
	        var divJugador = document.createElement("div");
	        divJugador.style.display = "flex";
	        divJugador.style.flexDirection = "column";
	        divJugador.style.alignItems = "center";
	        divJugador.style.textAlign = "center";

	        var img = document.createElement("img");
	        img.classList.add("icono_header");
	        img.style.width = "250px";  
	        img.style.height = "250px";
	        img.src = jugador.foto;

	        var title = document.createElement("label");
	        title.innerText = jugador.nombre + ", " + jugador.apellido;
	        title.style.marginTop = "5px"; 
	        title.style.fontWeight = "bold";

	        divJugador.appendChild(img);
	        divJugador.appendChild(title);

	        return divJugador;
	    }

	    // Agregar jugadores al divPareja
	    divPareja.appendChild(crearJugador(jugador1));
	    divPareja.appendChild(crearJugador(jugador2));

	    // Agregar al contenedor principal
	    divSeccion.appendChild(divPareja);
}


	function agregarJugadorAlListado(jugador, seccion) {

		if (jugadoresIds.includes(jugador.id)) {
			alert("El jugador ya ha sido agregado");
			return;
		}

		var divSeccion = document.getElementById(seccion);

	    var div = document.createElement("div");
	    div.style.display = "flex";
	    div.style.flexDirection = "column";
	    div.style.alignItems = "center";
	    div.style.textAlign = "center";
	    div.style.width = "250px"; // Ancho fijo para que se vean uniformes
	    div.style.margin = "10px";

	    var img = document.createElement("img");
	    img.classList.add("icono_header");
	    img.style.width = "250px";  
	    img.style.height = "250px";
	    img.src = jugador.foto;

	    var title = document.createElement("label");
	    title.innerText = jugador.nombre + "," + jugador.apellido;
	    title.style.marginTop = "5px"; // Espacio entre la imagen y el texto
	    title.style.fontWeight = "bold";

	    div.appendChild(img);
	    div.appendChild(title);

	    divSeccion.appendChild(div);
	    var cantJugadores = parseInt(document.getElementById("cantidad_jugadores").value, 10);
		cantJugadores = cantJugadores + 1;
	    document.getElementById("cantidad_jugadores").value = cantJugadores;

	    document.getElementById("cantidad_jugadores_agregados").innerText = cantJugadores;
	    
	    jugadoresIds.push(jugador.id);
	}

	function generarFechas(parejas) {
	    let totalParejas = parejas.length;
	    let totalFechas = totalParejas - 1; // Número de fechas necesarias
	    let partidosPorFecha = Math.floor(totalParejas / 2);
	    let fechas = [];

	    let parejasRotativas = [...parejas]; // Copia del array
	    let parejaFija = parejasRotativas.shift(); // La primera pareja se mantiene fija

	    for (let i = 0; i < totalFechas; i++) {
	        let fecha = [];
	        fecha.push([parejaFija, parejasRotativas[0]]);

	        for (let j = 1; j < partidosPorFecha; j++) {
	            fecha.push([parejasRotativas[j], parejasRotativas[totalParejas - 1 - j]]);
	        }

	        fechas.push(fecha);

	        // Rotar las parejas (excepto la fija)
	        parejasRotativas.push(parejasRotativas.shift());
	    }

	    return fechas;
	}

	function armarFecha() {
		let parejas = [];
		console.log(jugadoresIds.length);
		if(jugadoresIds.length == 12) {			
			parejas = ["A", "B", "C", "D", "E", "F"];			
		}
		if(jugadoresIds.length == 16) {
			parejas = ["A", "B", "C", "D", "E", "F", "G", "H"];
		}			
		let calendario = generarFechas(parejas);
		console.log("jugadoresIds", jugadoresIds);
		console.log("parejas", parejas);
		console.log("calendario",calendario);
		var numFecha = document.getElementById("num_fecha").value;
		var torneoId = document.getElementById("torneo").value;
		document.getElementById("armarFechaBtn").hidden = true;
		document.getElementById("comenzarFechaBtn").hidden = false;
		document.getElementById("torneo_id").value = torneoId;
		// console.log(calendario);
		$.ajax({
		       type:'POST',
		       dataType:'JSON',
		       url:'/generar_fecha',
		       data:{jugadoresIds:jugadoresIds, calendario:calendario, numFecha:numFecha,torneoId:torneoId,_token: '{{csrf_token()}}'},
		       success:function(data) {   			       				       		
					
		       } 
    		});
	}

	function onChangeTorneo() {
		var torneo = document.getElementById("torneo").value;
		$.ajax({
		       type:'POST',
		       dataType:'JSON',
		       url:'/on_change_torneo',
		       data:{torneo:torneo, _token: '{{csrf_token()}}'},
		       success:function(data) {   			       				       		
					document.getElementById("num_fecha").value = data.nuevaFecha;
					jugadoresIds = []; // Array vacío para almacenar los IDs
					colorIndex = 0;
					removeSeccion("seccion_1_parejas");
					removeSeccion("seccion_2_parejas");
					removeSeccion("seccion_3_parejas");
					removeSeccion("seccion_4_parejas");

					removeSeccion("seccion_1_jugadores");
					removeSeccion("seccion_2_jugadores");
					removeSeccion("seccion_3_jugadores");
					removeSeccion("seccion_4_jugadores");
					
					document.getElementById("seccion_jugadores").hidden = false;
					document.getElementById("seccion_parejas").hidden = true;	
		       } 
    		});
	}	

</script>

@endsection