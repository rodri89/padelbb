@extends('padel/admin/plantilla')

@section('title_header','Tabla General')

@section('contenedor')

<div class="text-center">

	<div class="row text-center" id="seccion_seleccionar_torneo">	
		<div class="col-xl-3"></div>
		<div class="col-xl-5">						       	        	       
	        <label>Seleccionar un torneo</label>								
	        	<select id="torneo" name="torneo" class="form-control" onchange="onChangeTorneo()">
		            @foreach($torneos as $t)                  				
		            	<option value="{{$t->id}}">{{$t->nombre}}</option>		            
		            @endforeach
		        </select>
		    <br>
		    <button class="primary_button" onclick="seleccionarTorneo()">Continuar</button>								    	       			
		</div>	
	</div>

	<div class="row text-center" id="seccion_tabla_general" hidden>			
		<div class="col-xl-12">						       	        	       
	        <h4>Tabla General</h4>								
	        <div id="tabla-container" class="table-responsive">
			    <!-- La tabla se generará aquí dinámicamente -->
			</div>		    
		</div>	

		<div hidden style="position: absolute; bottom: 100px; right: 100px;">
			<button class="rodri_button_aceptar_si" onclick="cargarNuevaFecha()">+</button>								    	       			
		</div>
		<div style="position: absolute; bottom: 150px; right: 100px;">
		<button class="rodri_button_aceptar_si" onclick="mostrarFechasPrevias()">::</button>								    	       			
		</div>
	</div>	

</div>

@include('modal.modal_listado_fechas')


<script type="text/javascript">
	    let torneoSeleccionado = 0;

	    function removeSeccion(seccionNombre) {
			//var seccion = document.getElementById("seccion_1_parejas");
			var seccion = document.getElementById(seccionNombre);
			while (seccion.firstChild) {
				seccion.removeChild(seccion.firstChild);
			}
		}

	    function mostrarFechasPrevias() {
	    	removeSeccion("listado_fechas_seccion");
	    	$.ajax({
		       type:'POST',
		       dataType:'JSON',
		       url:'/get_listado_fechas_previas',
		       data:{torneo_id:torneoSeleccionado ,_token: '{{csrf_token()}}'},
		       success:function(data) { 
		       		const rutas = data.rutas; // Obtener las rutas de la respuesta
				
		       		var seccion = document.getElementById("listado_fechas_seccion");
		       		seccion.style.display = "block";
		       		
		       		for (let i = 0; i < data.cantidadFechas; i++) {
				    // Crear el botón
				    const btn = document.createElement('button');

				    // Asignar texto al botón
				    btn.textContent = "Fecha " + (i + 1); // Texto del botón (ej: "Fecha 1", "Fecha 2", etc.)

				    // Asignar un evento onClick
				    btn.onclick = function (event) {
		                event.preventDefault(); // Evitar el comportamiento por defecto del botón

		                // Actualizar el valor del campo oculto fecha_id
		                document.getElementById('fecha_id').value = i + 1;
		                document.getElementById('torneo_id').value = torneoSeleccionado;

		                // Enviar el formulario
		                document.getElementById('comenzarFechaForm').submit();
		            };

				    // Aplicar estilos directamente
				    btn.style.backgroundColor = "#4CAF50"; // Color de fondo
				    btn.style.color = "white"; // Color del texto
				    btn.style.padding = "10px 20px"; // Padding
				    btn.style.border = "none"; // Sin borde
				    btn.style.borderRadius = "5px"; // Bordes redondeados
				    btn.style.cursor = "pointer"; // Cursor tipo pointer
				    btn.style.marginBottom = "10px"; // Margen inferior entre botones
				    btn.style.width = "100%"; // Ancho completo del contenedor

				    // Agregar el botón al contenedor
				    seccion.appendChild(btn);
				}

		       		$("#modalListadoFechas").modal();                                          
		       } 
    		}); 
	    }

	    function seleccionarTorneo() {
	    	torneoSeleccionado = document.getElementById("torneo").value;
	    	document.getElementById("seccion_seleccionar_torneo").hidden = true;
	    	document.getElementById("seccion_tabla_general").hidden = false;
	    	cargarTablaGeneral();
	    }

	    function cargarTablaGeneral() {
	    	
	    	$.ajax({
		       type:'POST',
		       dataType:'JSON',
		       url:'/get_tabla_general',
		       data:{torneo_id:torneoSeleccionado ,_token: '{{csrf_token()}}'},
		       success:function(data) {      
		       		mostrarTablaGeneral(data);                                          
		       } 
    		});    
	    }

			function mostrarTablaGeneral(data) {
			    const tablaContainer = document.getElementById('tabla-container');
			    
			    // Limpiar contenedor
			    tablaContainer.innerHTML = '';
			    
			    // Crear tabla
			    const tabla = document.createElement('table');
			    tabla.className = 'table table-striped table-hover';
			    
			    // Crear encabezado
			    const thead = document.createElement('thead');
			    thead.innerHTML = `
			        <tr>
			            <th>Pos.</th>
			            <th>Foto</th>
			            <th>Jugador</th>
			            ${data.total_fechas ? Array.from({length: data.total_fechas}, (_, i) => 
			                `<th>Fecha ${i+1}</th>`).join('') : ''}
			            <th>Total</th>
			        </tr>
			    `;
			    tabla.appendChild(thead);
			    
			    // Crear cuerpo
			    const tbody = document.createElement('tbody');
			    
			    data.data.forEach((jugador, index) => {
			        const row = document.createElement('tr');
			        
			        // Posición
			        const tdPos = document.createElement('td');
			        tdPos.textContent = index + 1;
			        row.appendChild(tdPos);
			        
			        // Foto
			        const tdFoto = document.createElement('td');
			        const img = document.createElement('img');
			        img.src = jugador.foto || 'img/placeholder.jpg';
			        img.className = 'img-thumbnail';
			        img.style.width = '50px';
			        img.alt = jugador.nombre;
			        tdFoto.appendChild(img);
			        row.appendChild(tdFoto);
			        
			        // Nombre
			        const tdNombre = document.createElement('td');
			        tdNombre.textContent = jugador.nombre;
			        row.appendChild(tdNombre);
			        
			        // Puntos por fecha
			        if (jugador.fechas) {
			            for (let i = 1; i <= data.total_fechas; i++) {
			                const tdPuntos = document.createElement('td');
			                tdPuntos.textContent = jugador.fechas[i] || 0;
			                row.appendChild(tdPuntos);
			            }
			        }
			        
			        // Total
			        const tdTotal = document.createElement('td');
			        tdTotal.textContent = jugador.total;
			        tdTotal.style.fontWeight = 'bold';
			        row.appendChild(tdTotal);
			        
			        tbody.appendChild(row);
			    });
			    
			    tabla.appendChild(tbody);
			    tablaContainer.appendChild(tabla);
			}


	    function registrarNuevaFecha() {
        	var id_torneo = document.getElementById('id_torneo').value;
        	var nombre = document.getElementById('nombre').value;
        	var tipo_torneo = document.getElementById('tipo_torneo').value;        	

        	$.ajax({
		       type:'POST',
		       dataType:'JSON',
		       url:'/registrar_torneo',
		       data:{id_torneo:id_torneo, nombre:nombre, tipo_torneo:tipo_torneo ,_token: '{{csrf_token()}}'},
		       success:function(data){      
		          if(data.torneo != null){
		          	document.getElementById('id_torneo').value = data.torneo.id           	
		            alert("Torneo registrado")
		          }                                                                     
		       } 
    		});    

        }
</script>

@endsection