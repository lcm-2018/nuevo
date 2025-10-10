const tablenNominasEmpleados = crearDataTable(
    '#tablenNominasEmpleados',
    'lista_liquidado.php',
    [
        { data: 'id' },
        { data: 'descripcion' },
        { data: 'mes' },
        { data: 'tipo' },
        { data: 'estado' },
        { data: 'accion' },
    ],
    [],
    {
        pageLength: -1,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [2], className: 'text-nowrap' },
            { "orderable": false, "targets": [0, 5] },
            { targets: [4], className: 'p-1' },
        ],
        initComplete: function () {
            var api = this.api();
            $('#filterRow th', api.table().header()).on('click', function (e) {
                e.stopPropagation();
            });

            $('#filterRow th', api.table().header()).on('mousedown', function (e) {
                e.stopPropagation();
            });
            $('#filterRow .dt-column-order').remove();
        }
    },
    function (d) {
        d.filter_descripcion = ValueInput('filter_descripcion');
        d.filter_mes = ValueInput('filter_mes');
        d.filter_tipo = ValueInput('filter_tipo');
        d.filter_estado = ValueInput('filter_estado');
    },
    false
);

tablenNominasEmpleados.on('init', function () {
    BuscaDataTable(tablenNominasEmpleados);
});

document.querySelector('#tablenNominasEmpleados').addEventListener('click', function (event) {
    const btnDetalles = event.target.closest('.detalles');

    if (btnDetalles) {
        event.preventDefault();
        const id = btnDetalles.dataset.id;
        SubmitPost('detalles.php', 'id_nomina', id);
    }

});