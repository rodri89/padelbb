<div class="modal fade" id="modalBuscarJugador" 
     tabindex="-1" role="dialog" 
     aria-labelledby="favoritesModalLabel">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">              
        <h4 class="modal-title"         
        id="modalTitleMensaje">Buscar Jugador</h4>
      </div>      
       <div class="modal-body">                        
         <div class="container">
          
          <div class="table-responsive" style="height:700px; overflow-y: scroll;">
          <table class="table table-striped" id="laravel_datatable"> 
               <thead class="navBackground text-white">
                  <tr>                    
                    <th class="editText">Foto</th>
                    <th class="editText">Apellido</th>
                    <th class="editText">Nombre</th>
                    <th class="editText">Posicion</th>                    
                    <th class="editText">Seleccionar</th>                                       
                  </tr>
               </thead>
            </table>
         </div>
      </div>

       </div>
                
      </div>
    </div>
  </div>

   <script>
   
   $(document).ready( function () {
    $('#laravel_datatable').DataTable({
           language: {
              "decimal": "",
              "emptyTable": "No hay información",
              "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
              "infoEmpty": "Mostrando 0 to 0 of 0 Entradas",
              "infoFiltered": "(Filtrado de _MAX_ total entradas)",
              "infoPostFix": "",
              "thousands": ",",
              "lengthMenu": "Mostrar _MENU_ Entradas",
              "loadingRecords": "Cargando...",
              "processing": "Procesando...",
              "search": "Buscar:",
              "zeroRecords": "Sin resultados encontrados",
              "paginate": {
                  "first": "Primero",
                  "last": "Ultimo",
                  "next": "Siguiente",
                  "previous": "Anterior"
              },
           },
           processing: false,
           serverSide: false,
           ajax: "{{ url('modal_buscar_jugador_list') }}",
           columns: [                    
                    { data: 'foto', name: 'foto' },
                    { data: 'nombre', name: 'nombre' },
                    { data: 'apellido', name: 'apellido' },
                    { data: 'posicion', name: 'posicion' },                                        
                    { data: 'action', name: 'action', orderable: false, searchable: false}                                       
                 ]
        });
     });          

  </script>

 
