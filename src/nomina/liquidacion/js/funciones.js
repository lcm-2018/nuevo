const tableLiqMesEmpleados = crearDataTable(
    '#tableLiqMesEmpleados',
    'lista_liquidacion.php',
    [
        { data: 'check' },
        { data: 'doc' },
        { data: 'nombre' },
        { data: 'observacion' },
        { data: 'laborado' },
        { data: 'incapacidad' },
        { data: 'licencia' },
        { data: 'vacacion' },
        { data: 'otro' }
    ],
    [],
    {
        pageLength: -1,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [2], className: 'text-nowrap' },
            { "orderable": false, "targets": [0, 6] }
        ],
        initComplete: function () {
            var api = this.api();
            $('#filterRow th', api.table().header()).on('click', function (e) {
                e.stopPropagation();
            });

            $('#filterRow th', api.table().header()).on('mousedown', function (e) {
                e.stopPropagation();
            });
            //eliminiar los elementos de #filterRow .dt-column-order
            $('#filterRow .dt-column-order').remove();
        }
    },
    function (d) {
        d.filter_mes = ValueInput('filter_mes');
        d.filter_tipo = ValueInput('filter_tipo');
        d.filter_observacion = ValueInput('filter_observacion');
        d.filter_nodoc = ValueInput('filter_nodoc');
        d.filter_nombre = ValueInput('filter_nombre');
    },
    false
);


tableLiqMesEmpleados.on('init', function () {
    BuscaDataTable(tableLiqMesEmpleados);
});

