// Call the dataTables jQuery plugin
$(document).ready(function() {
  $('.dataTable').DataTable(
  {
  	  "order": [],
  	  "pageLength": 50,
  	  dom: 'Bfrtip',
      buttons: [
            { 
            	extend: 'excel', 
              	className: 'btn btn-primary btn-sm',
              	text: "<i class='far fa-file-excel'></i> Exportar Datos",
              	titleAttr: 'Excel'
            }
        ]
  });

  $('.dataTable_no_excel').DataTable(
  {
      "order": [],
      "pageLength": 50,
      dom: 'Bfrtip',
      buttons: []
  });

    var groupColumn = 0;
    var table = $('#datatable_clases').DataTable({
        "columnDefs": [
            { "visible": false, "targets": groupColumn }
        ],
        "order": [[ groupColumn, 'asc' ]],
        "displayLength": 25,
        "drawCallback": function ( settings ) {
            var api = this.api();
            var rows = api.rows( {page:'current'} ).nodes();
            var last=null;
 
            api.column(groupColumn, {page:'current'} ).data().each( function ( group, i ) {
                if ( last !== group ) {
                    $(rows).eq( i ).before(
                        '<tr class="group"><td colspan="5">'+group+'</td></tr>'
                    );
 
                    last = group;
                }
            } );
        }
    } );
});


