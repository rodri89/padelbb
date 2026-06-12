<div class="modal fade" id="modalMostrarMensaje" 
     tabindex="-1" role="dialog" 
     aria-labelledby="favoritesModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">      	
        <h4 class="modal-title"         
        id="modal_mensaje_titulo">Confirmar</h4>
      </div>      
       <div class="modal-body">
          <p id="modal_mensaje_texto"></p>       	
       </div>
      <div class="modal-footer">
        <button type="button" 
           class="btn btn-default rodri_button_aceptar" onclick="modalAceptar()" 
           data-dismiss="modal">Aceptar</button>        
           <button type="button" 
           class="btn btn-default rodri_button_cancelar" 
           data-dismiss="modal">Cancelar</button>        
      </div>
    </div>
  </div>
</div>