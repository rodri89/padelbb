@extends('padel/admin/plantilla')

@section('title_header','Admin Jugador')

@section('contenedor')

	
<div class="text-center">

	<div class="row text-center">	
		<div class="col-xl-1"></div>
		<div class="col-xl-5">		
			<br><br>
				<input type="hidden" id="id_jugador" name="id_jugador" value="0">
				<label style="width: 100px">Nombre</label>
				<input id="nombre" name="nombre" type="text"  class="form-control"/>
			
				<label style="width: 100px">Apellido</label>
				<input id="apellido" name="apellido" type="text" class="form-control" />

				<label hidden style="width: 100px">Telefono</label>
				<input hidden id="telefono" name="telefono" type="text" class="form-control" />		
			
				<label style="width: 100px">Posicion</label>								
	        	<select id="posicion" name="posicion" class="form-control">
	            <option value="0">Drive</option>
	            <option value="1">Reves</option>            
	            <option value="2">Drive | Reves</option>            
	        </select>					
			<br>		
		</div>

		<div class="col-xl-5">
			<h3>Foto</h3>
			    <form id="uploadForm">
	        		<input type="file" id="imageFile" name="image" accept="image/*">
	        		<button type="button" id="uploadImage" class="primary_button">Guardar Imagen</button>
    			</form>
    			<div id="response"></div>
		</div>
	</div>

	<button id="registrar_btn" class="primary_button" onclick="registrar()">Registrar</button>
	<button class="primary_button" onclick="buscar()">Buscar</button>

</div>

@include('modal.modal_jugadores')

 <script>

 		function buscar() {		
			$("#modalBuscarJugador").modal();  
		}		

		function seleccionarJugador(id) {		
			$('#modalBuscarJugador').modal('hide'); 

			$.ajax({
			       type:'POST',
			       dataType:'JSON',
			       url:'/get_jugador',
			       data:{id:id,_token: '{{csrf_token()}}'},
			       success:function(data) {   	
			       		document.getElementById('id_jugador').value = data.jugador.id;
		        		document.getElementById('nombre').value = data.jugador.nombre;
		        		document.getElementById('apellido').value = data.jugador.apellido;
		        		document.getElementById('telefono').value = data.jugador.telefono;
		        		document.getElementById('posicion').value = data.jugador.posicion;
		        		document.getElementById("registrar_btn").innerText = "Actualizar";
			       } 
	    		}); 
		}

		$(document).ready(function() {
		    // Configurar el token CSRF para todas las solicitudes AJAX
		    $.ajaxSetup({
		        headers: {
		            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		        }
		    });

		    $('#uploadImage').click(function() {
		        // Crear un objeto FormData
		        let formData = new FormData();
		        let imageFile = $('#imageFile')[0].files[0];

		        if (!imageFile) {
		            alert("Por favor selecciona una imagen.");
		            return;
		        }

		        var jugador_id = document.getElementById('id_jugador').value;
		        formData.append('image', imageFile);
		        formData.append('id_jugador', jugador_id);
		        
		        // Realizar la solicitud AJAX
		        $.ajax({
		            url: '/cargar_imagen_jugador', // Cambia esto por tu endpoint del servidor
		            type: 'POST',
		            data: formData,
		            contentType: false,
		            processData: false,
		            success: function(response) {		            	
		                $('#response').html(`<p>Imagen cargada con éxito</p>`);
		            },
		            error: function(xhr, status, error) {
		                $('#response').html(`<p>Error al cargar la imagen: ${xhr.responseText}</p>`);
		            }
		        });
		    });
		});

        function registrar() {
        	var id_jugador = document.getElementById('id_jugador').value;
        	var nombre = document.getElementById('nombre').value;
        	var apellido = document.getElementById('apellido').value;
        	var telefono = 1;//document.getElementById('telefono').value;
        	var posicion = document.getElementById('posicion').value;

        	$.ajax({
		       type:'POST',
		       dataType:'JSON',
		       url:'/registrar_jugador',
		       data:{id_jugador:id_jugador, nombre:nombre, apellido:apellido, telefono:telefono, posicion:posicion ,_token: '{{csrf_token()}}'},
		       success:function(data){      
		          if(data.jugador != null){
		          	document.getElementById('id_jugador').value = data.jugador.id           			            
		            location.reload();
		          }                                                                     
		       } 
    		});    

        }
    </script>

@endsection