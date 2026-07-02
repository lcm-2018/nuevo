function cargarTablaConsultaBDatos() {
    // DESTRUIR SI EXISTE
    if ($.fn.DataTable.isDataTable('#tb_consulta_bdatos')) {
        $('#tb_consulta_bdatos').DataTable().destroy();
    }

    let tableConsultaBDatos = crearDataTable(
        '#tb_consulta_bdatos',
        'listar_consulta_bdatos.php',
        [
            { data: 'id_bdatos' },
            { data: 'nombre_entidad' },
            { data: 'nombre_bd' },
            { data: 'botones' }
        ],
        [],
        {
            processing: true,
            serverSide: true,
            searching: true,
            autoWidth: false,
            pageLength: 10,
            order: [[1, 'asc']],
            ajax: {
                url: 'listar_consulta_bdatos.php',
                type: 'POST',
                data: function (d) {
                    d.id_consulta = document.getElementById('id_consulta_bd').value;
                }
            },
            columnDefs: [
                {   className: 'text-wrap',  targets: [1] },
                {   orderable: false, targets: [3] }        
            ]
        }
    );
    
    // CUANDO TERMINA DE CARGAR
    tableConsultaBDatos.on('init', function () {
        BuscaDataTable(tableConsultaBDatos);
    });
}
