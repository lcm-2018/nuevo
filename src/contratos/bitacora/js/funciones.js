// ============================================================
// funciones.js — Gestión de Bitácora
// src/contratos/bitacora/js/funciones.js
// ============================================================

const tableBitacora = crearDataTable(
    '#tableBitacora',
    'lista_bitacora.php',
    [
        { data: 'id' },
        { data: 'fecha' },
        { data: 'usuario' },
        { data: 'tipo' },
        { data: 'descripcion' },
        { data: 'relacion' },
        { data: 'botones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Registrar nuevo hito en bitácora',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/bitacora.php', 'form', 0, 'modalFormsBit', 'bodyModalBit', 'tamModalFormsBit', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    }
);

tableBitacora.on('init', function () {
    BuscaDataTable(tableBitacora);
});

document.querySelector('#tableBitacora').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar   = event.target.closest('.eliminar');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/bitacora.php', 'form', id, 'modalFormsBit', 'bodyModalBit', 'tamModalFormsBit', '');
    }
    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/bitacora.php', id, tableBitacora);
    }
});

document.getElementById('modalFormsBit').addEventListener('click', function (event) {
    const boton = event.target.closest('button');
    if (!boton) return;

    if (boton.id === 'btnGuardaBitacora') {
        event.preventDefault();
        LimpiaInvalid();

        if (ValueInput('tipo_evento').trim() === '') {
            MuestraError('tipo_evento', 'El tipo de evento es requerido');
        } else if (ValueInput('descripcion').trim() === '') {
            MuestraError('descripcion', 'La descripción es requerida');
        } else {
            mostrarOverlay();
            const dataForm = Serializa('formGestBitacora');
            dataForm.append('action', dataForm.get('id') == '0' ? 'add' : 'edit');
            SendPost('../php/controladores/bitacora.php', dataForm).then((response) => {
                if (response.status === 'ok') {
                    mje('Guardado correctamente!');
                    tableBitacora.ajax.reload(null, false);
                    $('#modalFormsBit').modal('hide');
                } else {
                    mjeError('Error!', response.msg);
                }
            }).finally(() => { ocultarOverlay(); });
        }
    }
});
