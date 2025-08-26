$(function () {
    "use strict";

    // Example table (if you still need it)
    $('#example').DataTable();

    // Example2 table with your settings
    var table = $('#example2').DataTable({
        destroy: true,          // ensure no duplicate init
        ordering: false,        // disable sorting
        lengthChange: false,
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'pdf', 'print']
    });

    table.buttons().container()
        .appendTo('#example2_wrapper .col-md-6:eq(0)');
});
