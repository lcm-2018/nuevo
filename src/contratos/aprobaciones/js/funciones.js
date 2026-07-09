// ============================================================
// funciones.js — Gestión de Aprobaciones
// src/contratos/aprobaciones/js/funciones.js
// ============================================================

const tableAprobaciones = crearDataTable(
    '#tableAprobaciones',
    'lista_aprobaciones.php',
    [
        { data: 'id' },
        { data: 'fecha' },
        { data: 'aprobador' },
        { data: 'decision' },
        { data: 'proceso_ctt' },
        { data: 'observaciones' },
        { data: 'botones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Registrar nueva aprobación/rechazo',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/aprobaciones.php', 'form', 0, 'modalFormsApr', 'bodyModalApr', 'tamModalFormsApr', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    }
);

tableAprobaciones.on('init', function () {
    BuscaDataTable(tableAprobaciones);
});

document.querySelector('#tableAprobaciones').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar   = event.target.closest('.eliminar');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/aprobaciones.php', 'form', id, 'modalFormsApr', 'bodyModalApr', 'tamModalFormsApr', '');
    }
    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/aprobaciones.php', id, tableAprobaciones);
    }
});

document.getElementById('modalFormsApr').addEventListener('click', function (event) {
    const boton = event.target.closest('button');
    if (!boton) return;

    if (boton.id === 'btnGuardaAprobacion') {
        event.preventDefault();
        LimpiaInvalid();

        if (ValueInput('id_proceso') === '' && ValueInput('id_contrato') === '') {
            MuestraError('id_proceso', 'Debe seleccionar un Proceso o un Contrato');
            MuestraError('id_contrato', 'Debe seleccionar un Proceso o un Contrato');
        } else if (ValueInput('rol_aprobador').trim() === '') {
            MuestraError('rol_aprobador', 'El rol del aprobador es requerido');
        } else if (ValueInput('observaciones').trim() === '') {
            MuestraError('observaciones', 'Debe proporcionar una justificación u observaciones');
        } else {
            mostrarOverlay();
            const dataForm = Serializa('formGestAprobacion');
            dataForm.append('action', dataForm.get('id') == '0' ? 'add' : 'edit');
            SendPost('../php/controladores/aprobaciones.php', dataForm).then((response) => {
                if (response.status === 'ok') {
                    mje('Guardado correctamente!');
                    tableAprobaciones.ajax.reload(null, false);
                    $('#modalFormsApr').modal('hide');
                } else {
                    mjeError('Error!', response.msg);
                }
            }).finally(() => { ocultarOverlay(); });
        }
    }
});
