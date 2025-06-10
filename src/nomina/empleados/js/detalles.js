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
        }
    }
});

function InputRiesgoLaboral(id, value) {
    if (Number(value) === 3) {
        ShowInputs(id);
    } else {
        HiddenInputs(id)
    }

}