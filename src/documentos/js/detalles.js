document.getElementById('modalForms').addEventListener('click', function (event) {
    const boton = event.target.closest('button');
    if (boton) {
        LimpiaInvalid();
        switch (boton.id) {
            case 'btnGuardaDetallesDoc':
                if (ValueInput('slcTipoControl') === '0') {
                    MuestraError('slcTipoControl', 'Seleccione un tipo de control');
                } else if (ValueInput('buscaTercero') === '') {
                    MuestraError('buscaTercero', 'Seleccione un responsable');
                } else if (ValueInput('id_tercero') === '0') {
                    MuestraError('buscaTercero', 'Seleccione un responsable');
                } else if (ValueInput('txtCargo') === '') {
                    MuestraError('txtCargo', 'Ingrese un cargo válido');
                } else if (ValueInput('datFechaIni') === '') {
                    MuestraError('datFechaIni', 'Ingrese una fecha de inicio válida');
                } else if (ValueInput('datFechaFin') === '') {
                    MuestraError('datFechaFin', 'Ingrese una fecha de fin válida');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formDetallesDoc');
                    data.append('action', data.get('detalle') == '0' ? 'add' : 'edit');
                    SendPost('../php/controladores/detalles.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            $('#modalForms').modal('hide');
                            mostrarOverlay();
                            VerFormulario('../php/controladores/detalles.php', 'form', data.get('id'), 'modalForms', 'bodyModal', 'tamModalForms', 'modal-xl');
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

function EditarDetalle(idD) {
    var id = ValueInput('id');
    mostrarOverlay();
    $('#modalForms').modal('hide');
    VerFormulario('../php/controladores/detalles.php', 'form', { id: id, idD: idD }, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-xl');
}

function EliminarDetalle(idD) {
    EliminaRegistro('../php/controladores/detalles.php', idD, null);
    $('#modalForms').modal('hide');
}

function EditarEstado(idD) {
    var id = ValueInput('id');
    mostrarOverlay();
    $('#modalForms').modal('hide');
    CambiaEstado('../php/controladores/detalles.php', idD, null);
    setTimeout(() => {
        VerFormulario('../php/controladores/detalles.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-xl');
    }, 500);
}