
<script type="text/javascript" src="https://raw.githubusercontent.com/wilq32/jqueryrotate/master/jQueryRotate.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<style>
	.pop-outer {
		background-color: rgba(0, 0, 0, 0.5);
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;		
	}	

	.pop-inner {
		background-color: #fff;
		width: 100%;
		height: 100%;
		padding: 25px;
		margin-top: 60px;
	}

	.center {
	    margin-left: auto;
	    margin-right: auto;
	    display: block;
	}
</style>

<script>
	$(document).ready(function() {
		$(".open").click(function(){			
			$('.pop-outer').fadeIn('slow');
			});
		$(".close").click(function(){
			$('.pop-outer').fadeOut('slow');
			});
		});	
</script>

<div style="display: none;" class="pop-outer">	
	<div class="pop-inner" style="background-color: transparent;">		
		<button class="close" style="color:white">X</button>
		<div id="container" class="table-responsive" style="height:100%; overflow-y: scroll;">				
			<img id="img_src" src="" class="card-img-top center">
			<input hidden type="number" id="width_original" value="0">
			<input hidden type="number" id="height_original" value="0">
			<input hidden type="number" id="manipular_image_id">
			<input hidden type="text" id="mi_seccion">
			<input hidden type="text" id="mi_img_numero">
			<input hidden type="text" id="mi_img_tope">
			<input hidden type="text" id="mi_img_aux">							
			
			<div class="flotante_modal_bottom_img center">			
				<div id="panelAvanzarMI" class="margin_top_3px_cel">					
					<button type="button" onclick="previousImage()" 
					class="rodri_button_aceptar_si"><</button>  
					<label id="cantidad_fotos_modal" disabled class="input_width_50px sinBackground letrasblancas margin_left_20px">0/0</label>
					<button type="button" onclick="siguienteImage()" 
					class="rodri_button_aceptar_si">></button>
				</div>
				<div id="panelZoomMI" class="margin_top_3px_cel cel_hidden">
					<button type="button" onclick="zoomin()" 
					class="rodri_button_aceptar_si">+</button>
					<button type="button" onclick="zoomout()"
					class="rodri_button_cancelar_no">-</button>   					
				</div>
				<div id="panelExtraMI" class="margin_top_3px_cel">
					<button type="button" onclick="original()" 
					class="rodri_button_aceptar_volver">O</button>
					<button hidden type="button" onclick="girar()" 
					class="rodri_button_aceptar_volver">G</button>
				</div>
			</div>
		</div>
	</div>
</div> 


<script type="text/javascript">
	
	function onClickVerMI(imagen, seccion, actual, tope, img_id, desdeHasta_id, img_numero_id, img_tope_id){		
		var modalImg = document.getElementById("img_src");				
		modalImg.src = imagen;
		original();

		if(desdeHasta_id != null){
			document.getElementById("mi_seccion").value = seccion;
			document.getElementById("mi_img_numero").value = actual;
			document.getElementById("cantidad_fotos_modal").innerHTML = actual+"/"+tope;
			document.getElementById("mi_img_aux").value = img_id+";"+desdeHasta_id+";"+img_numero_id+";"+img_tope_id;
		} else {
			document.getElementById("cantidad_fotos_modal").innerHTML = "1/1";
		}
				
		// document.getElementById("seccion_manipular_imagen").hidden = false;		
		setImageAuto();
		$('#modalImagen').modal("show");  
	}

	function setImageAuto() {
		// 633 - 666
		var GFG = document.getElementById("img_src"); 
        var currHeight = GFG.clientHeight;
        var currWidth = GFG.clientWidth; 
            GFG.style.height = (633) + "px";
            GFG.style.width = (666) + "px";
	}

	function original(){
		var GFG = document.getElementById("img_src");
		var originalHeight = document.getElementById("height_original").value;
		var originalWidth = document.getElementById("width_original").value;		
		GFG.style.height = (1033) + "px";
        GFG.style.width = (1066) + "px"; 
	}

	function zoomin() { 
        var GFG = document.getElementById("img_src"); 
        var currHeight = GFG.clientHeight;
        var currWidth = GFG.clientWidth; 
            GFG.style.height = (currHeight + 80) + "px";
            GFG.style.width = (currWidth + 80) + "px";             
    } 

    function zoomout() {     	
        var GFG = document.getElementById("img_src"); 
        var currHeight = GFG.clientHeight;
        var currWidth = GFG.clientWidth;
            GFG.style.height = (currHeight - 80) + "px";
            GFG.style.width = (currWidth - 80) + "px";                   
            alert(currHeight +" - "+currWidth);
    }

    function previousImage() {    
    	//original();
    	giro = 0;	
    	$("#container").attr("class", "table-responsive derecha360");
    	    	
    	manipularImagenesAnteriorSiguienteFoto(0);
    }

    function siguienteImage() {
    	//original();
    	manipularImagenesAnteriorSiguienteFoto(1);
    	giro = 0;
    	$("#container").attr("class", "table-responsive derecha360");    	
    }

    function manipularImagenesAnteriorSiguienteFoto(opcion) {		
		var auto_id = document.getElementById("auto_id").value;
    	
		var tope_aux = document.getElementById("auto_actual_cantidad_fotos").innerHTML;
		var tope = tope_aux.split("/");
		tope = tope[1];

		var numero_actual = document.getElementById("auto_img_numero").value;    
		if(opcion == 1){ // avanzo      
		  var numero = parseInt(numero_actual) + 1;
		  if(numero > parseInt(tope))
		    numero = tope;
		} else {      
		  var numero = parseInt(numero_actual) - 1; 
		  if(numero < 1)
		    numero = 1;
		}
		var seccion = "autos";
		
		$.ajax({
	       type:'POST',
	       dataType:'JSON',
	       url:'/cargar_imagen_auto_ant_sig',
	       data:{auto_id:auto_id, numero:numero, seccion:seccion, _token: '{{csrf_token()}}'},
	        success:function(data){              
	          if(data.response == 1){
	            // setImagen(data.imagen, "img_evolucion", "imagenes", data.numero, data.imagenes_cantidad, "evolucion_actual_cantidad_fotos", "ev_img_numero", "ev_img_tope");	            
	            manipularImagenesSetImagen(data.imagen, seccion, data.numero, data.imagenes_cantidad);
	          } 
	        }
		});
	}

	function manipularImagenesSetImagen(imagen, seccion, imagenesNumero, imagenesTope){   
		if(imagen != null){
			
		    if(imagen.url.localeCompare("") != 0){     

		        var imgModal = document.getElementById("img_src");
		        imgModal.src = imagen.url;	
		        
	            document.getElementById("cantidad_fotos_modal").innerHTML = imagenesNumero+"/"+imagenesTope;
	            document.getElementById("mi_img_numero").value = imagenesNumero;    		                
		        document.getElementById("mi_img_tope").value = imagenesTope;      

		        var aux = document.getElementById("mi_img_aux").value;// img_id+";"+desdeHasta_id+";"+img_numero_id+";"+img_tope_id;
		        var arrayAux = aux.split(';');
		        var img_id = arrayAux[0];
		        var desdeHasta_id = arrayAux[1];
		        var img_numero_id = arrayAux[2];
		        var img_tope_id = arrayAux[3];
		        
		        var img = document.getElementById(img_id);
		        img.src = imagen.url;
		        img.style = "cursor:pointer; width:150px;height:150px";
		        img.onclick = function() {                                                  
		              onClickVerMI(imagen.url, seccion, imagenesNumero, imagenesTope, img_id, desdeHasta_id, img_numero_id, img_tope_id); // debo cambiar el 2 y poner la seccion
		              document.getElementById("panelAvanzarMI").hidden = false;
		              //document.getElementById("panelCantidadMI").hidden = false;
		            }                 
		            document.getElementById(desdeHasta_id).innerHTML = imagenesNumero+"/"+imagenesTope;
		            document.getElementById(img_numero_id).value = imagenesNumero;    
		                
		          document.getElementById(img_tope_id).value = imagenesTope; 
		      }
		} else {
		    document.getElementById("img_src").value = "img/iconos/sin_imagen.jpg";
		}
	}

    var giro = 0;
    function girar(){
    	giro++;
	    if(giro == 1){    	    	  					
			$("#container").attr("class", "table-responsive derecha90");
		}
		if(giro == 2){    	    	  					
			$("#container").attr("class", "table-responsive derecha180");
		}
		if(giro == 3){    	    	  					
			$("#container").attr("class", "table-responsive derecha270");
		}
		if(giro == 4){    	    	  					
			$("#container").attr("class", "table-responsive derecha360");
		}
		if(giro == 4){
			giro = 0;
		}		
    }

</script>
