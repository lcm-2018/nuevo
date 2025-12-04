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
    const btnBorrar = event.target.closest('.borrar');
    const btnAnular = event.target.closest('.anular');
    const btnImprimir = event.target.closest('.imprimir');

    if (btnDetalles) {
        event.preventDefault();
        const id = btnDetalles.dataset.id;
        SubmitPost('detalles.php', 'id_nomina', id);
    }
    if (btnBorrar) {
        event.preventDefault();
        const id = btnBorrar.dataset.id;
        EliminaRegistro('../php/controladores/liquidado.php', id, tablenNominasEmpleados);
    }
    if (btnAnular) {
        event.preventDefault();
        const id = btnAnular.dataset.id;
        EliminaRegistro('../php/controladores/liquidado.php', { id: id, estado: 0 }, tablenNominasEmpleados, 'estado');

    }
    if (btnImprimir) {
        event.preventDefault();
        const id = btnImprimir.dataset.id;
        const text = btnImprimir.getAttribute('text');
        var url = text === 'M' ? 'mensual' : 'patronal';
        var pdf = true;
        ImprimirReporte('../php/reportes/cdp_' + url + '.php', { id: id, pdf: pdf });

    }
});