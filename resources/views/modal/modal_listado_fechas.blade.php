<div class="modal fade" id="modalListadoFechas" 
     tabindex="-1" role="dialog" 
     aria-labelledby="favoritesModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">      	
        <h4 class="modal-title"         
        id="modalListadoFechas_titulo">Listado Fechas</h4>
      </div>      
       <div class="modal-body">
        <form id="comenzarFechaForm" action="/comenzar_fecha" method="post">
            <!-- Token CSRF necesario para peticiones POST en Laravel -->
            @csrf
            <input type="hidden" id="torneo_id" name="torneo" value="">
            <input type="hidden" id="fecha_id" name="fecha_id" value="">
            
            <div id="listado_fechas_seccion"></div>
        </form>   
       </div>

      <div class="modal-footer">                
           <button type="button" 
           class="btn btn-default rodri_button_cancelar" 
           data-dismiss="modal">Cancelar</button>        
      </div>
    </div>
  </div>
</div>