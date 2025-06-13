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
                VerFomulario('../php/controladores/contratos.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
                VerFomulario('../php/controladores/seguridad_social.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
                VerFomulario('../php/controladores/incapacidades.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
                VerFomulario('../php/controladores/vacaciones.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
                VerFomulario('../php/controladores/licencias_mop.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
                VerFomulario('../php/controladores/licencias_luto.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
                VerFomulario('../php/controladores/licencias_norem.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
                VerFomulario('../php/controladores/indemniza_vacacion.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
        { data: 'inicia' },
        { data: 'termina' },
        { data: 'dias' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nueva libranza de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFomulario('../php/controladores/libranzas.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
        { data: 'inicia' },
        { data: 'termina' },
        { data: 'dias' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nuevo embargo de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFomulario('../php/controladores/embargos.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
        { data: 'inicia' },
        { data: 'termina' },
        { data: 'dias' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nuevo sindicato de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFomulario('../php/controladores/sindicatos.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
            titleAttr: 'Agregar nuevo descuento de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFomulario('../php/controladores/otro_descuento.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
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

if (document.querySelector('#tableContratosEmpleado')) {
    document.querySelector('#tableContratosEmpleado').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.actualizar');
        const btnEliminar = event.target.closest('.eliminar');

        if (btnActualizar) {
            mostrarOverlay();
            const id = btnActualizar.dataset.id;
            VerFomulario('../php/controladores/contratos.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
            VerFomulario('../php/controladores/seguridad_social.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
            VerFomulario('../php/controladores/incapacidades.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
            VerFomulario('../php/controladores/vacaciones.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/vacaciones.php', id, tableVacaciones);
        }

        if (btnImprimir) {
            mostrarOverlay();
            const id = btnImprimir.dataset.id;
            VerFomulario('../php/controladores/vacaciones.php', 'imp', id, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-lg');
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
            VerFomulario('../php/controladores/licencias_mop.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
            VerFomulario('../php/controladores/licencias_luto.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
            VerFomulario('../php/controladores/licencias_norem.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
            VerFomulario('../php/controladores/indemniza_vacacion.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/indemniza_vacacion.php', id, tableIndemnizaVacacion);
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
            VerFomulario('../php/controladores/libranzas.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
            VerFomulario('../php/controladores/embargos.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
            VerFomulario('../php/controladores/sindicatos.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
            VerFomulario('../php/controladores/otro_descuento.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/otro_descuento.php', id, tableOtroDescuento);
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
            case 'btnGuardarLibranza':
                alert('Guardar libranza');
                break;
            case 'btnGuardarEmbargo':
                alert('Guardar embargo');
                break;
            case 'btnGuardarSindicato':
                alert('Guardar sindicato');
                break;
            case 'btnGuardarOtroDescuento':
                alert('Guardar otro descuento');
                break;
        }
    }
});

function DiasIncapacidad() {
    DiasRangoFechas(ValueInput('datFecInicia'), ValueInput('datFecFin'), 'canDias');
}

function DiasInactivo() {
    DiasRangoFechas(ValueInput('datFecInicia'), ValueInput('datFecFin'), 'diasInactivo');
}