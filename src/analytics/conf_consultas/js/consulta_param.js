function cargarTablaConsultaParam() {
    // DESTRUIR SI EXISTE
    if ($.fn.DataTable.isDataTable('#tb_consulta_param')) {
        $('#tb_consulta_param').DataTable().destroy();
    }

    let tableConsultaParam = crearDataTable(
        '#tb_consulta_param',
        'listar_consulta_param.php',
        [
            { data: 'id_parametro' },
            { data: 'parametro' },
            { data: 'etiqueta' },
            { data: 'tipo' },
            { data: 'nom_tipo' },
            { data: 'botones' }
        ],
        [{
        text: plus,
        className: 'btn btn-success btn-sm shadow',
        titleAttr: 'Nuevo registro',
        action: function () {
            mostrarOverlay();
            
            fetch('frm_reg_consulta_param.php', { method: 'POST' })
            .then(r => r.text())
            .then(html => {
                const tam = document.getElementById('divTamModalReg');
                tam.classList.remove('modal-sm', 'modal-xl', 'modal-xxl');
                tam.classList.add('modal-lg');

                document.getElementById('divFormsReg').innerHTML = html;
                const modal = new bootstrap.Modal(document.getElementById('divModalReg'));
                modal.show();
            })
            .finally(() => ocultarOverlay());
            }
        }],
        {
            processing: true,
            serverSide: false,
            paging: false,
            searching: false,
            lengthChange: false,
            autoWidth: false,
            order: [[1, 'asc']],
            ajax: {
                url: 'listar_consulta_param.php',
                type: 'POST',
                data: function (d) {
                    d.id_consulta = document.getElementById('id_consulta').value;
                }
            },
            columnDefs: [
                { visible: false, targets: [0, 3] },
                { className: 'text-nowrap', targets: [1,2,3,4] },
                { orderable: false, targets: [5] }       
            ]
        }
    );
    
    // CUANDO TERMINA DE CARGAR
    tableConsultaParam.on('init', function () {
        BuscaDataTable(tableConsultaParam);
    });
}
