// Función para determinar la URL basada en el tipo de liquidación seleccionado
function getUrlListado() {
    const tipo = ValueInput('filter_tipo');
    if (tipo == 2) {
        return 'lista_liquidacion.php';
    } else if (tipo == 3) {
        return 'lista_prestaciones_sociales.php';
    } else if (tipo == 6 || tipo == 7 || tipo == 8 || tipo == 9) {
        return 'lista_cesantias.php';
    } else if (tipo == 4) {
        return 'lista_vacaciones.php';
    }
    return 'lista_liquidacion.php'; // URL por defecto
}

const tableLiqMesEmpleados = crearDataTable(
    '#tableLiqMesEmpleados',
    getUrlListado(),
    [
        { data: 'check' },
        { data: 'doc' },
        { data: 'nombre' },
        { data: 'observacion' },
        { data: 'laborado' },
        { data: 'incapacidad' },
        { data: 'licencia' },
        { data: 'vacacion' },
        { data: 'otro' },
        { data: 'pago' }
    ],
    [{
        text: 'Liquidar',
        className: 'btn btn-success btn-sm shadow',
        titleAttr: 'Liquidar nómina de empleados',
        action: function (e, dt, node, config) {
            const checkboxes = document.querySelectorAll('#bodyTableLiqMesEmpleados input[type="checkbox"]:checked');
            if (checkboxes.length === 0) {
                mjeError('Debe seleccionar al menos un empleado para liquidar.');
            } else {
                let valid = true;
                LimpiaInvalid();
                if (ValueInput('filter_tipo') == 2 || ValueInput('filter_tipo') == 3) {
                    checkboxes.forEach((checkbox) => {
                        var row = checkbox.closest('tr');
                        var lab = row.querySelector('input[name^="lab"]');
                        var pago = row.querySelector('select[name^="metodo"]');
                        var min = parseFloat(lab.getAttribute('min'));
                        var max = parseFloat(lab.getAttribute('max'));

                        if (lab && pago) {
                            const valLab = parseFloat(lab.value);
                            const valPag = parseFloat(pago.value);

                            if (valLab < min || valLab > max) {
                                lab.classList.add('bg-danger');
                                lab.focus();
                                mjeError('El valor de los días laborados debe estar entre ' + min + ' y ' + max + '.');
                                valid = false;
                            } else if (valPag === 0) {
                                pago.classList.add('bg-danger');
                                pago.focus();
                                mjeError('Debe seleccionar un método de pago.');
                                valid = false;
                            }
                        }
                        if (!valid) {
                            return false;
                        }
                    });
                }
                if (valid) {
                    mostrarOverlay();
                    var data = Serializa('formLiquidacion');
                    data.append('mes', ValueInput('filter_mes'));
                    data.append('tipo', ValueInput('filter_tipo'));
                    data.append('action', 'add');
                    SendPost('../php/controladores/liquidacion.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            setTimeout(function () {
                                tableLiqMesEmpleados.ajax.url(getUrlListado()).load(null, false);
                            }, 500);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeAlert('', '', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
            }
        }
    }],
    {
        pageLength: -1,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [2], className: 'text-nowrap' },
            { "orderable": false, "targets": [0, 6] },
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
        d.filter_mes = ValueInput('filter_mes');
        d.filter_tipo = ValueInput('filter_tipo');
        d.filter_observacion = ValueInput('filter_observacion');
        d.filter_nodoc = ValueInput('filter_nodoc');
        d.filter_nombre = ValueInput('filter_nombre');
    },
    false
);

// Función para filtrar que actualiza la URL dinámicamente antes de recargar
function FiltraLiquidacion() {
    tableLiqMesEmpleados.ajax.url(getUrlListado()).load();
}
tableLiqMesEmpleados.on('draw', function () {
    const filas = document.querySelectorAll('#tableLiqMesEmpleados tbody tr');

    filas.forEach((fila, index) => {
        const celda = fila.cells[4];
        const celdaPago = fila.cells[9];
        if (celda) {
            const input = celda.querySelector('input');
            if (input) {
                input.style.backgroundColor = (index % 2 === 0) ? '#f2f2f2' : '#ffffff';
            }
        }
        if (celdaPago) {
            const selectPago = celdaPago.querySelector('select');
            if (selectPago) {
                selectPago.style.backgroundColor = (index % 2 === 0) ? '#f2f2f2' : '#ffffff';
            }
        }
    });
});

tableLiqMesEmpleados.on('init', function () {
    BuscaDataTable(tableLiqMesEmpleados);
});

function eventFilterTipo(value) {
    var mes = document.getElementById('filter_mes');
    if (value == 7 || value == 8 || value == 9) {
        mes.value = '12'
    } else if (value == 6) {
        mes.value = '06'
    } else {
        mes.value = '0'
    }
    FiltraLiquidacion();
}