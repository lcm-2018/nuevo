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
    const btnDescargarPdf = event.target.closest('.descargar-pdf');
    const btnReportes = event.target.closest('.reportes');

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
        var url = '';
        if (text === 'M') {
            url = 'cdp_mensual';
        } else if (text === 'P') {
            url = 'cdp_patronal';
        } else if (text === 'N') {
            url = 'nomina_general';
        }
        var pdf = false;
        ImprimirReporte('../php/reportes/' + url + '.php', { id: id, pdf: pdf });

    }
    /*
    if (btnDescargarPdf) {
        event.preventDefault();
        const id = btnDescargarPdf.dataset.id;
        const text = btnDescargarPdf.getAttribute('text');
        var url = text === 'M' ? 'mensual' : 'patronal';
        var pdf = true;
        ImprimirReporte('../php/reportes/cdp_' + url + '.php', { id: id, pdf: pdf });
    }*/

    if (btnReportes) {
        event.preventDefault();
        const id = btnReportes.dataset.id;
        mostrarOverlay();
        VerFormulario('../php/controladores/reportes.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
    }
});

//evento click en el modal
document.querySelector('#modalForms').addEventListener('click', function (event) {
    const btnReportes = event.target.closest('.reportes');
    if (btnReportes) {
        event.preventDefault();
        const id_nomina = document.getElementById('id_nomina').value; // Obtener id_nomina del input hidden
        const tipo_reporte = btnReportes.dataset.id; // 1=Libranzas, 2=Embargos, 3=Sindicatos
        const text = btnReportes.getAttribute('text'); // E=Excel, P=PDF

        // Determinar el archivo según el tipo de reporte
        let archivo = '';
        switch (tipo_reporte) {
            case '1':
                archivo = 'libranzas';
                break;
            case '2':
                archivo = 'embargos';
                break;
            case '3':
                archivo = 'sindicatos';
                break;
            default:
                console.error('Tipo de reporte no válido');
                return;
        }

        ImprimirReporte('../php/reportes/' + archivo + '.php', { id: id_nomina, tipo: text });
    }
});