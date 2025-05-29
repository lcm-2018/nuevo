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
            tableContratosEmpleado.ajax.reload(null, false);
        } else {
            mjeError('Error!', response.msg);
        }
    }).finally(() => {
        ocultarOverlay();
    });
}