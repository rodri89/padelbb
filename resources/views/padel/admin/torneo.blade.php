@extends('padel/admin/plantilla')

@section('title_header','Torneo')

@section('contenedor')

<div class="text-center">

	<div class="row text-center">	
		<div class="col-xl-3"></div>
		<div class="col-xl-5">		
			<br><br>
				<input type="hidden" id="id_torneo" name="id_torneo" value="0">
				<label style="width: 100px">Nombre</label>
				<input id="nombre" name="nombre" type="text"  class="form-control"/>
									
				<label style="width: 100px">Tipo Torneo</label>								
	        	<select id="tipo_torneo" name="tipo_torneo" class="form-control">
	            <option value="0">Suma en equipo</option>
	            <option value="1">Suma individual</option>            	            
	        </select>
			<br><br>
	        <button class="primary_button" onclick="registrar()">Registrar</button>						
			<br>		
		</div>	
	</div>	

</div>

<script type="text/javascript">
	    
	    function registrar() {
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