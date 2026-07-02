function cargarTablaConsultaUser() {
    // DESTRUIR SI EXISTE
    if ($.fn.DataTable.isDataTable('#tb_consulta_user')) {
        $('#tb_consulta_user').DataTable().destroy();
    }

    let tableConsultaUser = crearDataTable(
        '#tb_consulta_user',
        'listar_consulta_user.php',
        [
            { data: 'id_usuario' },
            { data: 'num_documento' },
            { data: 'usuario' },
            { data: 'cargo' },
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
                url: 'listar_consulta_user.php',
                type: 'POST',
                data: function (d) {
                    d.id_consulta = document.getElementById('id_consulta_us').value;
                }
            },
            columnDefs: [
                {   className: 'text-start',  targets: [1] },
                {   className: 'text-wrap',  targets: [2] },
                {   orderable: false, targets: [4] }        
            ]
        }
    );
    
    // CUANDO TERMINA DE CARGAR
    tableConsultaUser.on('init', function () {
        BuscaDataTable(tableConsultaUser);
    });
}
