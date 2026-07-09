// ============================================================
// funciones.js — Gestión de Minutas
// src/contratos/minutas/js/funciones.js
// ============================================================

const tableMinutas = crearDataTable(
    '#tableMinutas',
    'lista_minutas.php',
    [
        { data: 'id' },
        { data: 'version' },
        { data: 'fecha' },
        { data: 'contrato' },
        { data: 'usuario' },
        { data: 'botones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Crear nueva minuta',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/minutas.php', 'form', 0, 'modalFormsMin', 'bodyModalMin', 'tamModalFormsMin', 'modal-xl');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    }
);

tableMinutas.on('init', function () {
    BuscaDataTable(tableMinutas);
});

document.querySelector('#tableMinutas').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar   = event.target.closest('.eliminar');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/minutas.php', 'form', id, 'modalFormsMin', 'bodyModalMin', 'tamModalFormsMin', 'modal-xl');
    }
    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/minutas.php', id, tableMinutas);
    }
});

document.getElementById('modalFormsMin').addEventListener('click', function (event) {
    const boton = event.target.closest('button');
    if (!boton) return;

    if (boton.id === 'btnGuardaMinuta') {
        event.preventDefault();
        LimpiaInvalid();

        if (ValueInput('id_contrato') === '') {
            MuestraError('id_contrato', 'Seleccione el contrato relacionado');
        } else if (ValueInput('contenido_html').trim() === '') {
            MuestraError('contenido_html', 'El contenido de la minuta no puede estar vacío');
        } else {
            mostrarOverlay();
            const dataForm = Serializa('formGestMinuta');
            dataForm.append('action', dataForm.get('id') == '0' ? 'add' : 'edit');
            SendPost('../php/controladores/minutas.php', dataForm).then((response) => {
                if (response.status === 'ok') {
                    mje('Guardado correctamente!');
                    tableMinutas.ajax.reload(null, false);
                    $('#modalFormsMin').modal('hide');
                } else {
                    mjeError('Error!', response.msg);
                }
            }).finally(() => { ocultarOverlay(); });
        }
    }
});
