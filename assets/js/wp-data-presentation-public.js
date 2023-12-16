jQuery(document).ready(function($){
    
    $('#wpdp_table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copyHtml5',
            'excelHtml5',
            'csvHtml5',
            'pdfHtml5'
        ],
        "rowGroup": {
            "dataSrc": 0
        },
        "fixedColumns": {
            "leftColumns": 0
        }
    });
  
});