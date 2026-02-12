const tableContratosEmpleado = crearDataTable(
    '#tableContratosEmpleado',
    'lista_contratos.php',
    [
        { data: 'id' },
        { data: 'inicia' },
        { data: 'termina' },
        { data: 'salario' },
        { data: 'estado' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nuevo contrato de empleado',
            action: function (e, dt, node, config) {
                if (document.querySelector('.ValNewReg')) {
                    mjeError('Error!', 'El empleado tiene contratos activos, primero debe liquidar todos los contratos.');
                    return false;
                }
                mostrarOverlay();
                VerFormulario('../php/controladores/contratos.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);


const tableSegSocial = crearDataTable(
    '#tableSegSocial',
    'lista_segsocial.php',
    [
        { data: 'id' },
        { data: 'tipo' },
        { data: 'nombre' },
        { data: 'nit' },
        { data: 'afiliacion' },
        { data: 'retiro' },
        { data: 'riesgo' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nueva novedad de Seguridad Social',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/seguridad_social.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [
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
        d.filter_tipo = ValueInput('filter_tipo');
        d.filter_nombre = ValueInput('filter_nombre');
        d.filter_nit = ValueInput('filter_nit');
        d.filter_afiliacion = ValueInput('filter_afiliacion');
        d.filter_retiro = ValueInput('filter_retiro');
        d.filter_riesgo = ValueInput('filter_riesgo');
        d.filter_id = ValueInput('id_empleado');
    },
    false,
);

const tableIncapacidades = crearDataTable(
    '#tableIncapacidades',
    'lista_incapacidades.php',
    [
        { data: 'id' },
        { data: 'tipo' },
        { data: 'inicia' },
        { data: 'termina' },
        { data: 'dias' },
        { data: 'categoria' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nueva incapacidad de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/incapacidades.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);

const tableVacaciones = crearDataTable(
    '#tableVacaciones',
    'lista_vacaciones.php',
    [
        { data: 'id' },
        { data: 'anticipo' },
        { data: 'inicia' },
        { data: 'termina' },
        { data: 'inactivo' },
        { data: 'habiles' },
        { data: 'corte' },
        { data: 'liquidar' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nueva vacación de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/vacaciones.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);

const tableLicenciaMoP = crearDataTable(
    '#tableLicenciaMoP',
    'lista_licencias_mop.php',
    [
        { data: 'id' },
        { data: 'inicia' },
        { data: 'termina' },
        { data: 'inactivo' },
        { data: 'habiles' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nueva licencia materna o paterna de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/licencias_mop.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);
const tableLicenciaLuto = crearDataTable(
    '#tableLicenciaLuto',
    'lista_licencias_luto.php',
    [
        { data: 'id' },
        { data: 'inicia' },
        { data: 'termina' },
        { data: 'inactivo' },
        { data: 'habiles' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nueva licencia por luto de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/licencias_luto.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);
const tableLicenciaNoRem = crearDataTable(
    '#tableLicenciaNoRem',
    'lista_licencias_norem.php',
    [
        { data: 'id' },
        { data: 'inicia' },
        { data: 'termina' },
        { data: 'inactivo' },
        { data: 'habiles' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nueva licencia por no remunerada de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/licencias_norem.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);
const tableIndemnizaVacacion = crearDataTable(
    '#tableIndemnizaVacacion',
    'lista_indemniza_vacacion.php',
    [
        { data: 'id' },
        { data: 'inicia' },
        { data: 'termina' },
        { data: 'dias' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nueva indemnización de vacaciones de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/indemniza_vacacion.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);
const tableBsp = crearDataTable(
    '#tableBsp',
    'lista_bsp.php',
    [
        { data: 'id' },
        { data: 'corte' },
        { data: 'valor' },
        { data: 'tipo' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nueva bonificación por servicios prestados de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/bsp.php', 'form', { id: 0, id_empleado: ValueInput('id_empleado') }, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-sm');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);

const tableLibranzas = crearDataTable(
    '#tableLibranzas',
    'lista_libranzas.php',
    [
        { data: 'id' },
        { data: 'entidad' },
        { data: 'total' },
        { data: 'val_mes' },
        { data: 'pagado' },
        { data: 'cuotas' },
        { data: 'inicia' },
        { data: 'termina' },
        { data: 'estado' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nueva libranza de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/libranzas.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-lg');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);

const tableEmbargos = crearDataTable(
    '#tableEmbargos',
    'lista_embargos.php',
    [
        { data: 'id' },
        { data: 'entidad' },
        { data: 'total' },
        { data: 'val_mes' },
        { data: 'pagado' },
        { data: 'inicia' },
        { data: 'termina' },
        { data: 'estado' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nuevo embargo de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/embargos.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);
const tableSindicatos = crearDataTable(
    '#tableSindicatos',
    'lista_sindicatos.php',
    [
        { data: 'id' },
        { data: 'sindicato' },
        { data: 'porcentaje' },
        { data: 'aportado' },
        { data: 'inicia' },
        { data: 'termina' },
        { data: 'sindicalizacion' },
        { data: 'estado' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nuevo sindicato de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/sindicatos.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);
const tableOtroDescuento = crearDataTable(
    '#tableOtroDescuento',
    'lista_otros_descuentos.php',
    [
        { data: 'id' },
        { data: 'tipo' },
        { data: 'concepto' },
        { data: 'valor' },
        { data: 'inicia' },
        { data: 'fin' },
        { data: 'aportado' },
        { data: 'estado' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nuevo descuento de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/otros_descuentos.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);

const tableCcosto = crearDataTable(
    '#tableCcosto',
    'lista_ccostos.php',
    [
        { data: 'id' },
        { data: 'nombre' },
        { data: 'fecha' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nuevo centro de costo de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/ccostos.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);

const tableIvivienda = crearDataTable(
    '#tableIvivienda',
    'lista_ivivienda.php',
    [
        { data: 'id' },
        { data: 'fecha' },
        { data: 'valor' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nuevo interes de vivienda de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/ivivienda.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-sm');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);

const tableViaticos = crearDataTable(
    '#tableViaticos',
    'lista_viaticos.php',
    [
        { data: 'id' },
        { data: 'fecha' },
        { data: 'no_resolucion' },
        { data: 'tipo' },
        { data: 'destino' },
        { data: 'objetivo' },
        { data: 'monto' },
        { data: 'estado' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nuevo viático de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/viaticos.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.id_empleado = document.querySelector('#id_empleado').value;
    },
);

tableIncapacidades.on('init', function () {
    BuscaDataTable(tableIncapacidades);
});
tableVacaciones.on('init', function () {
    BuscaDataTable(tableVacaciones);
});

tableLicenciaMoP.on('init', function () {
    BuscaDataTable(tableLicenciaMoP);
});

tableLicenciaLuto.on('init', function () {
    BuscaDataTable(tableLicenciaLuto);
});

tableLicenciaNoRem.on('init', function () {
    BuscaDataTable(tableLicenciaNoRem);
});

tableIndemnizaVacacion.on('init', function () {
    BuscaDataTable(tableIndemnizaVacacion);
});

tableBsp.on('init', function () {
    BuscaDataTable(tableBsp);
});

tableLibranzas.on('init', function () {
    BuscaDataTable(tableLibranzas);
});

tableEmbargos.on('init', function () {
    BuscaDataTable(tableEmbargos);
});

tableSindicatos.on('init', function () {
    BuscaDataTable(tableSindicatos);
});

tableOtroDescuento.on('init', function () {
    BuscaDataTable(tableOtroDescuento);
});

tableCcosto.on('init', function () {
    BuscaDataTable(tableCcosto);
});

tableIvivienda.on('init', function () {
    BuscaDataTable(tableIvivienda);
});

tableViaticos.on('init', function () {
    BuscaDataTable(tableViaticos);
});


if (document.querySelector('#tableContratosEmpleado')) {
    document.querySelector('#tableContratosEmpleado').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/contratos.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/contratos.php', id, tableContratosEmpleado);
        }

    });
}

if (document.querySelector('#tableSegSocial')) {
    document.querySelector('#tableSegSocial').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/seguridad_social.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/seguridad_social.php', id, tableSegSocial);
        }

    });
}

if (document.querySelector('#tableIncapacidades')) {
    document.querySelector('#tableIncapacidades').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/incapacidades.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/incapacidades.php', id, tableIncapacidades);
        }

    });
}
if (document.querySelector('#tableVacaciones')) {
    document.querySelector('#tableVacaciones').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');
        const btnImprimir = event.target.closest('.imprimir');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/vacaciones.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/vacaciones.php', id, tableVacaciones);
        }

        if (btnImprimir) {
            mostrarOverlay();
            const id = btnImprimir.dataset.id;
            VerFormulario('../php/controladores/vacaciones.php', 'imp', id, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-lg');
        }

    });
}

if (document.querySelector('#tableLicenciaMoP')) {
    document.querySelector('#tableLicenciaMoP').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/licencias_mop.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/licencias_mop.php', id, tableLicenciaMoP);
        }
    });
}

if (document.querySelector('#tableLicenciaLuto')) {
    document.querySelector('#tableLicenciaLuto').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/licencias_luto.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/licencias_luto.php', id, tableLicenciaLuto);
        }
    });
}
if (document.querySelector('#tableLicenciaNoRem')) {
    document.querySelector('#tableLicenciaNoRem').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/licencias_norem.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/licencias_norem.php', id, tableLicenciaNoRem);
        }
    });
}
if (document.querySelector('#tableIndemnizaVacacion')) {
    document.querySelector('#tableIndemnizaVacacion').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/indemniza_vacacion.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/indemniza_vacacion.php', id, tableIndemnizaVacacion);
        }
    });
}
if (document.querySelector('#tableBsp')) {
    document.querySelector('#tableBsp').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/bsp.php', 'form', { id: id, id_empleado: ValueInput('id_empleado') }, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-sm');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/bsp.php', id, tableBsp);
        }
    });
}
if (document.querySelector('#tableLibranzas')) {
    document.querySelector('#tableLibranzas').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/libranzas.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-lg');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/libranzas.php', id, tableLibranzas);
        }
    });
}
if (document.querySelector('#tableEmbargos')) {
    document.querySelector('#tableEmbargos').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/embargos.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/embargos.php', id, tableEmbargos);
        }
    });
}
if (document.querySelector('#tableSindicatos')) {
    document.querySelector('#tableSindicatos').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/sindicatos.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/sindicatos.php', id, tableSindicatos);
        }
    });
}
if (document.querySelector('#tableOtroDescuento')) {
    document.querySelector('#tableOtroDescuento').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/otros_descuentos.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/otros_descuentos.php', id, tableOtroDescuento);
        }
    });
}

if (document.querySelector('#tableCcosto')) {
    document.querySelector('#tableCcosto').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/ccostos.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/ccostos.php', id, tableCcosto);
        }
    });
}

if (document.querySelector('#tableIvivienda')) {
    document.querySelector('#tableIvivienda').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/ivivienda.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-sm');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/ivivienda.php', id, tableIvivienda);
        }
    });
}

if (document.querySelector('#tableViaticos')) {
    document.querySelector('#tableViaticos').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');
        const btnDetalles = event.target.closest('.detalles');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFormulario('../php/controladores/viaticos.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/viaticos.php', id, tableViaticos);
        }

        if (btnDetalles) {
            mostrarOverlay();
            const id = btnDetalles.dataset.id;
            VerFormulario('../php/controladores/viaticos_novedades.php', 'form', id, 'modalForms1', 'bodyModal1', 'tamModalForms1', 'modal-lg');
        }
    });
}

document.getElementById('modalForms').addEventListener('click', function (event) {
    const boton = event.target;
    if (boton) {
        LimpiaInvalid();
        switch (boton.id) {
            case 'btnGuardaContratoEmpleado':
                if (ValueInput('datFecInicia') === '') {
                    MuestraError('datFecInicia', 'Ingrese la fecha de inicio del contrato');
                } else if (ValueInput('datFecFin') != '' && ValueInput('datFecInicia') > ValueInput('datFecFin')) {
                    MuestraError('datFecInicia', 'La fecha de inicio no puede ser mayor a la fecha de terminación');
                } else if (ValueInput('txtSalarioBasico') === '' || Number(CleanNumber(ValueInput('txtSalarioBasico'))) <= 0) {
                    MuestraError('txtSalarioBasico', 'Ingrese el salario del empleado');
                } else if (ValueInput('slcCargo') === '0') {
                    MuestraError('slcCargo', 'Seleccione un cargo');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formContratoEmpleado');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    SendPost('../php/controladores/contratos.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableContratosEmpleado.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });

                }
                break;
            case 'btnGuardarSegSocial':
                if (ValueInput('slcTipoSS') === '0') {
                    MuestraError('slcTipoSS', 'Seleccione un tipo de seguridad social');
                } else if (ValueInput('slcTipoSS') === '3' && ValueInput('slcRiesgoLaboral') === '0') {
                    MuestraError('slcRiesgoLaboral', 'Seleccione un riesgo laboral');
                } else if (ValueInput('slcTercero') === '0') {
                    MuestraError('slcTercero', 'Seleccione un tercero');
                } else if (ValueInput('datFecInicia') === '') {
                    MuestraError('datFecInicia', 'Ingrese la fecha de afiliación');
                } else if (ValueInput('datFecFin') !== '' && ValueInput('datFecInicia') > ValueInput('datFecFin')) {
                    MuestraError('datFecFin', 'La fecha de afiliación no puede ser mayor a la fecha de retiro');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formSegSocial');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    SendPost('../php/controladores/seguridad_social.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableSegSocial.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });

                }
                break;
            case 'btnGuardarIncapacidad':
                if (ValueInput('datFecInicia') === '') {
                    MuestraError('datFecInicia', 'Seleccione un tipo de incapacidad');
                } else if (ValueInput('datFecFin') === '') {
                    MuestraError('datFecFin', 'Seleccione una fecha de finalización');
                } else if (ValueInput('datFecInicia') > ValueInput('datFecFin')) {
                    MuestraError('datFecFin', 'La fecha de inicio no puede ser mayor a la fecha de finalización');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formIncapacidad');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    SendPost('../php/controladores/incapacidades.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableIncapacidades.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardarVacacion':
                if (ValueInput('datFecCorte') === '') {
                    MuestraError('datFecCorte', 'Ingrese la fecha de corte de las vacaciones');
                } else if (Number(ValueInput('diasLiquidar')) <= 0) {
                    MuestraError('diasLiquidar', 'Ingrese los días a liquidar');
                } else if (Number(ValueInput('diasHabiles')) <= 0) {
                    MuestraError('diasHabiles', 'Ingrese los días hábiles');
                } else if (ValueInput('datFecInicia') === '') {
                    MuestraError('datFecInicia', 'Ingrese la fecha de inicio de las vacaciones');
                } else if (ValueInput('datFecFin') === '') {
                    MuestraError('datFecFin', 'Ingrese la fecha de finalización de las vacaciones');
                } else if (ValueInput('datFecInicia') > ValueInput('datFecFin')) {
                    MuestraError('datFecFin', 'La fecha de inicio no puede ser mayor a la fecha de finalización');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formVacaciones');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    SendPost('../php/controladores/vacaciones.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableVacaciones.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardarLicenciaMoP':
                if (Number(ValueInput('diasHabiles')) <= 0) {
                    MuestraError('diasHabiles', 'Ingrese los días hábiles de la licencia');
                } else if (ValueInput('datFecInicia') === '') {
                    MuestraError('datFecInicia', 'Ingrese la fecha de inicio de la licencia');
                } else if (ValueInput('datFecFin') === '') {
                    MuestraError('datFecFin', 'Ingrese la fecha de finalización de la licencia');
                } else if (ValueInput('datFecInicia') > ValueInput('datFecFin')) {
                    MuestraError('datFecFin', 'La fecha de inicio no puede ser mayor a la fecha de finalización');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formLicenciasMoP');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    SendPost('../php/controladores/licencias_mop.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableLicenciaMoP.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardarLicenciaLuto':
                if (Number(ValueInput('diasHabiles')) <= 0) {
                    MuestraError('diasHabiles', 'Ingrese los días hábiles de la licencia');
                } else if (ValueInput('datFecInicia') === '') {
                    MuestraError('datFecInicia', 'Ingrese la fecha de inicio de la licencia');
                } else if (ValueInput('datFecFin') === '') {
                    MuestraError('datFecFin', 'Ingrese la fecha de finalización de la licencia');
                } else if (ValueInput('datFecInicia') > ValueInput('datFecFin')) {
                    MuestraError('datFecFin', 'La fecha de inicio no puede ser mayor a la fecha de finalización');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formLicenciasLuto');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    SendPost('../php/controladores/licencias_luto.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableLicenciaLuto.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardarLicenciaNoRem':
                if (Number(ValueInput('diasHabiles')) <= 0) {
                    MuestraError('diasHabiles', 'Ingrese los días hábiles de la licencia');
                } else if (ValueInput('datFecInicia') === '') {
                    MuestraError('datFecInicia', 'Ingrese la fecha de inicio de la licencia');
                } else if (ValueInput('datFecFin') === '') {
                    MuestraError('datFecFin', 'Ingrese la fecha de finalización de la licencia');
                } else if (ValueInput('datFecInicia') > ValueInput('datFecFin')) {
                    MuestraError('datFecFin', 'La fecha de inicio no puede ser mayor a la fecha de finalización');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formLicenciasNoRem');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    SendPost('../php/controladores/licencias_norem.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableLicenciaNoRem.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardarIndemnizaVacacion':
                if (ValueInput('datFecInicia') === '') {
                    MuestraError('datFecInicia', 'Ingrese la fecha de inicio de la indemnización');
                } else if (ValueInput('datFecFin') === '') {
                    MuestraError('datFecFin', 'Ingrese la fecha de finalización de la indemnización');
                } else if (ValueInput('datFecInicia') > ValueInput('datFecFin')) {
                    MuestraError('datFecFin', 'La fecha de inicio no puede ser mayor a la fecha de finalización');
                } else if (Number(ValueInput('diasInactivo')) <= 0) {
                    MuestraError('diasInactivo', 'Ingrese los días de indemnización');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formIndemnizaVacacion');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    SendPost('../php/controladores/indemniza_vacacion.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableIndemnizaVacacion.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardarBsp':
                if (ValueInput('datFecCorte') === '') {
                    MuestraError('datFecCorte', 'Ingrese la fecha de corte de la Bonificación');
                } else if (Number(ValueInput('numValor')) <= 0) {
                    MuestraError('numValor', 'Ingrese el valor de la Bonificación');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formBsp');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    data.append('tipo', 'M');
                    SendPost('../php/controladores/bsp.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableBsp.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardarLibranza':
                if (ValueInput('slcEntFinanciera') === '0') {
                    MuestraError('slcEntFinanciera', 'Seleccione una entidad financiera');
                } else if (Number(ValueInput('numTotLib')) <= 0) {
                    MuestraError('numTotLib', 'Ingrese el total de la libranza');
                } else if (Number(ValueInput('numCuotasLib')) <= 0) {
                    MuestraError('numCuotasLib', 'Ingrese el número de cuotas de la libranza');
                } else if (Number(ValueInput('numValMes')) <= 0) {
                    MuestraError('numValMes', 'Ingrese el valor mensual de la libranza');
                } else if (Number(ValueInput('numPorcentaje')) <= 0) {
                    MuestraError('numPorcentaje', 'Ingrese el porcentaje de la libranza');
                } else if (ValueInput('datFecInicia') === '') {
                    MuestraError('datFecInicia', 'Ingrese la fecha de inicio de la libranza');
                } else if (ValueInput('datFecFin') === '') {
                    MuestraError('datFecFin', 'Ingrese la fecha de finalización de la libranza');
                } else if (ValueInput('datFecInicia') > ValueInput('datFecFin')) {
                    MuestraError('datFecFin', 'La fecha de inicio no puede ser mayor a la fecha de finalización');
                } else if (ValueInput('txtDescripcion') === '') {
                    MuestraError('txtDescripcion', 'Ingrese una descripción para la libranza');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formLibranza');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    SendPost('../php/controladores/libranzas.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableLibranzas.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardarEmbargo':
                if (ValueInput('slcJuzgado') === '0') {
                    MuestraError('slcJuzgado', 'Seleccione un juzgado');
                } else if (ValueInput('slcTpEmbargo') === '0') {
                    MuestraError('slcTpEmbargo', 'Seleccione un tipo de embargo');
                } else if (Number(ValueInput('numTotLib')) <= 0) {
                    MuestraError('numTotLib', 'Ingrese el total del embargo');
                } else if (Number(ValueInput('numDctoMax')) <= 0) {
                    MuestraError('numDctoMax', 'Ingrese el descuento máximo del embargo');
                } else if (Number(ValueInput('numValMes')) <= 0) {
                    MuestraError('numValMes', 'Ingrese el valor mensual del embargo');
                } else if (Number(ValueInput('numPorcentaje')) <= 0) {
                    MuestraError('numPorcentaje', 'Ingrese el porcentaje del embargo');
                } else if (ValueInput('datFecInicia') === '') {
                    MuestraError('datFecInicia', 'Ingrese la fecha de inicio del embargo');
                } else if (ValueInput('datFecFin') === '') {
                    MuestraError('datFecFin', 'Ingrese la fecha de finalización del embargo');
                } else if (ValueInput('datFecInicia') > ValueInput('datFecFin')) {
                    MuestraError('datFecFin', 'La fecha de inicio no puede ser mayor a la fecha de finalización');
                } else if (Number(ValueInput('numValMes')) > Number(ValueInput('numDctoMax'))) {
                    MuestraError('numValMes', 'El valor mensual no puede ser mayor al descuento máximo del embargo');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formEmbargo');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    SendPost('../php/controladores/embargos.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableEmbargos.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardarSindicato':
                if (ValueInput('slcSindicato') === '0') {
                    MuestraError('slcSindicato', 'Seleccione un sindicato');
                } else if (Number(ValueInput('numValSind')) < 0) {
                    MuestraError('numValSind', 'Ingrese el valor de sindicalización');
                } else if (Number(ValueInput('numPorcentaje')) <= 0) {
                    MuestraError('numPorcentaje', 'Ingrese el porcentaje del embargo');
                } else if (ValueInput('datFecInicia') === '') {
                    MuestraError('datFecInicia', 'Ingrese la fecha de inicio del embargo');
                } else if (ValueInput('datFecFin') != '' && ValueInput('datFecInicia') > ValueInput('datFecFin')) {
                    MuestraError('datFecFin', 'La fecha de inicio no puede ser mayor a la fecha de finalización');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formSindicatos');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    SendPost('../php/controladores/sindicatos.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableSindicatos.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardarOtroDescuento':
                if (ValueInput('slcTipoDcto') === '0') {
                    MuestraError('slcTipoDcto', 'Seleccione un tipo de descuento');
                } else if (Number(ValueInput('numValor')) <= 0) {
                    MuestraError('numValor', 'Ingrese un valor para el descuento');
                } else if (ValueInput('datFecInicia') === '') {
                    MuestraError('datFecInicia', 'Ingrese la fecha de inicio del descuento');
                } else if (ValueInput('datFecFin') != '' && ValueInput('datFecInicia') > ValueInput('datFecFin')) {
                    MuestraError('datFecFin', 'La fecha de inicio no puede ser mayor a la fecha de finalización');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formOtroDescuento');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    SendPost('../php/controladores/otros_descuentos.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableOtroDescuento.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardarCcostos':
                if (ValueInput('slcCcosto') === '0') {
                    MuestraError('slcCcosto', 'Seleccione un centro de costo');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formCcostos');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    SendPost('../php/controladores/ccostos.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableCcosto.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardarIvivienda':
                if (Number(ValueInput('numValor')) <= 0) {
                    MuestraError('numValor', 'Ingrese un valor para de interés de vivienda');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formIvivienda');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    SendPost('../php/controladores/ivivienda.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableIvivienda.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardarViatico':
                if (ValueInput('datFecInicia') === '') {
                    MuestraError('datFecInicia', 'Ingrese la fecha del viático');
                } else if (ValueInput('txtNoResolucion') === '') {
                    MuestraError('txtNoResolucion', 'Ingrese el número de resolución');
                } else if (ValueInput('slcTipo') === '') {
                    MuestraError('slcTipo', 'Seleccione un tipo de viático');
                } else if (ValueInput('txtDestino') === '') {
                    MuestraError('txtDestino', 'Ingrese el destino del viático');
                } else if (ValueInput('txtObjetivo') === '') {
                    MuestraError('txtObjetivo', 'Ingrese el motivo del viático');
                } else if (Number(ValueInput('numValTotal')) <= 0) {
                    MuestraError('numValTotal', 'Ingrese un monto válido para el viático');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formViaticos');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    data.append('id_empleado', ValueInput('id_empleado'));
                    SendPost('../php/controladores/viaticos.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableViaticos.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
        }
    }
});

function DiasIncapacidad() {
    DiasRangoFechas(ValueInput('datFecInicia'), ValueInput('datFecFin'), 'canDias');
}

function DiasInactivo() {
    DiasRangoFechas(ValueInput('datFecInicia'), ValueInput('datFecFin'), 'diasInactivo');
}

function CambiaEstadoDeducido(id, estado, opcion) {
    mostrarOverlay();
    var data = new FormData();
    data.append('action', 'annul');
    data.append('id', id);
    data.append('estado', estado);
    SendPost('../php/controladores/' + opcion + '.php', data).then((response) => {
        if (response.status === 'ok') {
            var tablas = {
                libranzas: tableLibranzas,
                embargos: tableEmbargos,
                sindicatos: tableSindicatos,
                otros_descuentos: tableOtroDescuento
            };
            tablas[opcion].ajax.reload(null, false);
        } else {
            mjeError('Error!', response.msg);
        }
    }).finally(() => {
        ocultarOverlay();
    });
}

function CalcValorPorcentaje() {
    var total = Number(ValueInput('numTotLib'));
    var valMes = Number(ValueInput('numValMes'));

    var porcentaje = (valMes) / total;
    InputValue('numPorcentaje', porcentaje);
}

function CalcValorMes() {
    var total = Number(ValueInput('numTotLib'));
    var porcentaje = Number(ValueInput('numPorcentaje'));

    var valMes = (total * porcentaje);
    InputValue('numValMes', valMes);
}

function LiberaTotal(valor) {
    var input = document.getElementById('numTotLib');
    if (Number(valor) > 0) {
        input.disabled = false;
        input.readOnly = false;
    } else {
        input.disabled = true;
        input.readOnly = true;
    }
}

function CalcDctoMax() {
    var valor = Number(ValueInput('numTotLib'));
    var tipo = Number(ValueInput('slcTpEmbargo'));
    if (valor > 0 && tipo > 0) {
        var data = new FormData();
        data.append('action', 'dcto');
        data.append('valor', valor);
        data.append('tipo', tipo);
        data.append('id_empleado', ValueInput('id_empleado'));
        data.append('id', 0);
        mostrarOverlay();
        SendPost('../php/controladores/embargos.php', data).then((response) => {
            if (response.status === 'ok') {
                InputValue('numDctoMax', response.valor);
            } else {
                mjeError('Error!', response.msg);
            }
        }).finally(() => {
            ocultarOverlay();
        });
    } else {
        InputValue('numDctoMax', 0);
    }

}

function InputRiesgoLaboral(div, tipo) {
    var div = document.getElementById(div);
    if (tipo == 3) {
        div.classList.remove('d-none');
    } else {
        div.classList.add('d-none');
    }
}

// === VIÁTICOS NOVEDADES ===
let tableViaticoNovedades = null;

// Inicializar DataTable de novedades cuando el modal se muestra
$('#modalForms1').on('shown.bs.modal', function () {
    if (document.getElementById('tableViaticoNovedades') && !tableViaticoNovedades) {
        tableViaticoNovedades = crearDataTable(
            '#tableViaticoNovedades',
            'lista_viaticos_novedades.php',
            [
                { data: 'id' },
                { data: 'fecha' },
                { data: 'tipo_registro' },
                { data: 'observacion' },
                { data: 'acciones' }
            ],
            [],
            {
                pageLength: 10,
                order: [[0, 'desc']],
            },
            function (d) {
                d.id_viatico = document.getElementById('idViaticoNov').value;
            },
        );
    }
});

// Destruir DataTable de novedades cuando el modal se cierra
$('#modalForms1').on('hidden.bs.modal', function () {
    if (tableViaticoNovedades) {
        tableViaticoNovedades.destroy();
        tableViaticoNovedades = null;
    }
    document.getElementById('bodyModal1').innerHTML = '';
});

// Click handler para el modal de novedades (guardar, editar, eliminar)
document.getElementById('modalForms1').addEventListener('click', function (event) {
    const boton = event.target;
    LimpiaInvalid();

    // Editar novedad: llenar formulario con datos del registro
    const btnActualizarNov = event.target.closest('.actualizarNov');
    if (btnActualizarNov) {
        const id = btnActualizarNov.dataset.id;
        var formData = new FormData();
        formData.append('action', 'get');
        formData.append('id', id);
        SendPost('../php/controladores/viaticos_novedades.php', formData).then((response) => {
            if (response.status === 'ok') {
                document.getElementById('idNovedad').value = response.data.id_novedad;
                document.getElementById('datFechaNov').value = response.data.fecha;
                document.getElementById('slcTipoRegistro').value = response.data.tipo_registro;
                document.getElementById('txtObservacion').value = response.data.observacion;
                // Scroll al inicio del modal
                document.querySelector('#modalForms1 .modal-body').scrollTop = 0;
            } else {
                mjeError('Error!', response.msg);
            }
        });
        return;
    }

    // Eliminar novedad
    const btnEliminarNov = event.target.closest('.eliminarNov');
    if (btnEliminarNov) {
        const id = btnEliminarNov.dataset.id;
        const idViatico = document.getElementById('idViaticoNov').value;
        Swal.fire(Confir).then((result) => {
            if (result.isConfirmed) {
                mostrarOverlay();
                var formData = new FormData();
                formData.append('action', 'del');
                formData.append('id', id);
                formData.append('id_viatico', idViatico);
                SendPost('../php/controladores/viaticos_novedades.php', formData).then((response) => {
                    if (response.status === 'ok') {
                        mje('Eliminado correctamente!');
                        if (tableViaticoNovedades) tableViaticoNovedades.ajax.reload(null, false);
                        tableViaticos.ajax.reload(null, false);
                        // Reset formulario
                        document.getElementById('idNovedad').value = '0';
                        document.getElementById('formViaticoNovedad').reset();
                    } else {
                        mjeError('Error!', response.msg);
                    }
                }).finally(() => {
                    ocultarOverlay();
                });
            }
        });
        return;
    }

    // Guardar novedad
    if (boton && boton.id === 'btnGuardarNovedad') {
        if (ValueInput('datFechaNov') === '') {
            MuestraError('datFechaNov', 'Ingrese la fecha de la novedad');
        } else if (ValueInput('slcTipoRegistro') === '') {
            MuestraError('slcTipoRegistro', 'Seleccione un tipo de registro');
        } else if (ValueInput('txtObservacion') === '') {
            MuestraError('txtObservacion', 'Ingrese la observación de la novedad');
        } else {
            mostrarOverlay();
            var data = Serializa('formViaticoNovedad');
            data.append('action', data.get('id') == '0' ? 'add' : 'edit');
            SendPost('../php/controladores/viaticos_novedades.php', data).then((response) => {
                if (response.status === 'ok') {
                    mje('Guardado correctamente!');
                    if (tableViaticoNovedades) tableViaticoNovedades.ajax.reload(null, false);
                    tableViaticos.ajax.reload(null, false);
                    // Reset formulario para nueva novedad
                    document.getElementById('idNovedad').value = '0';
                    document.getElementById('formViaticoNovedad').reset();
                } else {
                    mjeError('Error!', response.msg);
                }
            }).finally(() => {
                ocultarOverlay();
            });
        }
    }

    // Soporte legalización
    const btnSoporteNov = event.target.closest('.soporteNov');
    if (btnSoporteNov) {
        const id = btnSoporteNov.dataset.id;
        console.log('Soporte legalización - ID novedad:', id);
        return;
    }

    // Gestión caducado
    const btnCaducadoNov = event.target.closest('.caducadoNov');
    if (btnCaducadoNov) {
        const id = btnCaducadoNov.dataset.id;
        console.log('Gestión caducado - ID novedad:', id);
        return;
    }
});

function imprimirReporteViatico(id) {
    ImprimirReporte('../../liquidado/php/reportes/viaticos.php', { id: id });
}