const tableEmpleados = crearDataTable(
    '#tableEmpleados',
    'lista_empleados.php',
    [
        { data: 'id' },
        { data: 'nodoc' },
        { data: 'nombre' },
        { data: 'correo' },
        { data: 'tel' },
        { data: 'estado' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nuevo empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFomulario('../php/controladores/empleados.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-fullscreen');
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
        d.filter_Nodoc = ValueInput('filter_Nodoc');
        d.filter_Nombre = ValueInput('filter_Nombre');
        d.filter_Correo = ValueInput('filter_Correo');
        d.filter_Tel = ValueInput('filter_Tel');
        d.filter_Status = ValueInput('filter_Status');
    },
    false,
);

document.querySelector('#tableEmpleados').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar = event.target.closest('.eliminar');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFomulario('../php/controladores/parametros.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
    }

    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/parametros.php', id, tableEmpleados);
    }
});

document.getElementById('modalForms').addEventListener('click', function (event) {
    const boton = event.target;
    if (boton) {
        LimpiaInvalid();
        switch (boton.id) {
            case 'btnGuardaEmpleado':
                if (ValueInput('slcSedeEmp') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcSedeEmp', 'Seleccione una sede');
                } else if (ValueInput('slcTipoEmp') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcTipoEmp', 'Seleccione un tipo de empleado');
                } else if (ValueInput('slcSubTipoEmp') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcSubTipoEmp', 'Seleccione un subtipo de empleado');
                } else if (ValueInput('slcTipoContratoEmp') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcTipoContratoEmp', 'Seleccione un tipo de contrato');
                } else if (ValueInput('slcTipoDocEmp') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcTipoDocEmp', 'Seleccione un tipo de documento');
                } else if (ValueInput('txtCCempleado') === '') {
                    ActivarTab('formEmpleado');
                    MuestraError('txtCCempleado', 'Ingrese el número de documento');
                } else if (ValueInput('slcPaisExp') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcPaisExp', 'Seleccione un país de expedición');
                } else if (ValueInput('slcDptoExp') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcDptoExp', 'Seleccione un departamento de expedición');
                } else if (ValueInput('slcMunicipioExp') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcMunicipioExp', 'Seleccione un municipio de expedición');
                } else if (ValueInput('datFecExp') === '') {
                    ActivarTab('formEmpleado');
                    MuestraError('datFecExp', 'Ingrese la fecha de expedición');
                } else if (ValueInput('slcPaisNac') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcPaisNac', 'Seleccione un país de nacimiento');
                } else if (ValueInput('slcDptoNac') === '0') {
                    MuestraError('slcDptoNac', 'Seleccione un departamento de nacimiento');
                } else if (ValueInput('slcMunicipioNac') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcMunicipioNac', 'Seleccione un municipio de nacimiento');
                } else if (ValueInput('datFecNac') === '') {
                    ActivarTab('formEmpleado');
                    MuestraError('datFecNac', 'Ingrese la fecha de nacimiento');
                } else if (ValueInput('txtNomb1Emp') === '') {
                    ActivarTab('formEmpleado');
                    MuestraError('txtNomb1Emp', 'Ingrese el primer nombre');
                } else if (ValueInput('txtApe1Emp') === '') {
                    MuestraError('txtApe1Emp', 'Ingrese el primer apellido');
                } else if (ValueInput('slcPaisEmp') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcPaisEmp', 'Seleccione un país de residencia');
                } else if (ValueInput('slcDptoEmp') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcDptoEmp', 'Seleccione un departamento de residencia');
                } else if (ValueInput('slcMunicipioEmp') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcMunicipioEmp', 'Seleccione un municipio de residencia');
                } else if (ValueInput('txtDireccion') === '') {
                    ActivarTab('formEmpleado');
                    MuestraError('txtDireccion', 'Ingrese la dirección de residencia');
                } else if (ValueInput('txtTelEmp') === '') {
                    ActivarTab('formEmpleado');
                    MuestraError('txtTelEmp', 'Ingrese el número de teléfono');
                } else if (ValueInput('mailEmp') === '') {
                    ActivarTab('formEmpleado');
                    MuestraError('mailEmp', 'Ingrese el correo electrónico');
                } else if (ValueInput('slcCargoEmp') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcCargoEmp', 'Seleccione un cargo');
                } else if (ValueInput('slcBancoEmp') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcBancoEmp', 'Seleccione un banco');
                } else if (ValueInput('txtCuentaBanc') === '') {
                    ActivarTab('formEmpleado');
                    MuestraError('txtCuentaBanc', 'Ingrese el número de cuenta bancaria');
                } else if (ValueInput('slcCCostoEmp') === '0') {
                    ActivarTab('formEmpleado');
                    MuestraError('slcCCostoEmp', 'Seleccione un centro de costo');
                } else if (ValueInput('slcEps') === '0') {
                    ActivarTab('formSalud');
                    MuestraError('slcEps', 'Seleccione una EPS');
                } else if (ValueInput('datFecAfilEps') === '') {
                    ActivarTab('formSalud');
                    MuestraError('datFecAfilEps', 'Ingrese la fecha de afiliación a la EPS');
                } else if (ValueInput('slcAfp') === '0') {
                    ActivarTab('formPension');
                    MuestraError('slcAfp', 'Seleccione un Fondo de pensiones');
                } else if (ValueInput('datFecAfilAfp') === '') {
                    ActivarTab('formPension');
                    MuestraError('datFecAfilAfp', 'Ingrese la fecha de afiliación al Fondo de pensiones');
                } else if (ValueInput('slcArl') === '0') {
                    ActivarTab('formRiesgos');
                    MuestraError('slcArl', 'Seleccione una ARL');
                } else if (ValueInput('datFecAfilArl') === '') {
                    ActivarTab('formRiesgos');
                    MuestraError('datFecAfilArl', 'Ingrese la fecha de afiliación a la ARL');
                } else if (ValueInput('slcRiesLab') === '0') {
                    ActivarTab('formRiesgos');
                    MuestraError('slcRiesLab', 'Seleccione un riesgo laboral');
                } else if (ValueInput('slcFc') === '0') {
                    ActivarTab('formCesantias');
                    MuestraError('slcFc', 'Seleccione un fondo de cesantías');
                } else if (ValueInput('datFecAfilFc') === '') {
                    ActivarTab('formCesantias');
                    MuestraError('datFecAfilFc', 'Ingrese la fecha de afiliación al fondo de cesantías');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formGestEmpleado', 'formGestSaludEmpleado', 'formGestPensionEmpleado', 'formGestRiesgoEmpleado', 'formGestCesantiaEmpleado');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    SendPost('../php/controladores/empleados.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableEmpleados.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });

                }
                break;
        }
    }
});

function CambiaEstadoEmpleado(id, estado) {
    mostrarOverlay();
    var data = new FormData();
    data.append('action', 'annul');
    data.append('id', id);
    data.append('estado', estado);
    SendPost('../php/controladores/empleados.php', data).then((response) => {
        if (response.status === 'ok') {
            tableEmpleados.ajax.reload(null, false);
        } else {
            mjeError('Error!', response.msg);
        }
    }).finally(() => {
        ocultarOverlay();
    });
}