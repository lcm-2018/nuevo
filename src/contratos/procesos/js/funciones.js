// ============================================================
// funciones.js — Gestión de Procesos de Contratación
// src/contratos/procesos/js/funciones.js
// ============================================================

const tableProcesos = crearDataTable(
    '#tableProcesos',
    'lista_procesos.php',
    [
        { data: 'id' },
        { data: 'codigo' },
        { data: 'objeto' },
        { data: 'tipo' },
        { data: 'modalidad' },
        { data: 'estado' },
        { data: 'botones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Crear nuevo proceso de contratación',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/procesos.php', 'form', 0, 'modalFormsProc', 'bodyModalProc', 'tamModalFormsProc', 'modal-lg');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    }
);

tableProcesos.on('init', function () {
    BuscaDataTable(tableProcesos);
});

document.querySelector('#tableProcesos').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar   = event.target.closest('.eliminar');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/procesos.php', 'form', id, 'modalFormsProc', 'bodyModalProc', 'tamModalFormsProc', 'modal-lg');
    }
    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/procesos.php', id, tableProcesos);
    }
});

document.getElementById('modalFormsProc').addEventListener('click', function (event) {
    const boton = event.target.closest('button');
    if (!boton) return;

    if (boton.id === 'btnGuardaProceso') {
        event.preventDefault();
        LimpiaInvalid();

        if (ValueInput('id_tipo_proceso') === '') {
            MuestraError('id_tipo_proceso', 'Seleccione un tipo de proceso');
        } else if (ValueInput('id_modalidad') === '') {
            MuestraError('id_modalidad', 'Seleccione una modalidad');
        } else if (ValueInput('objeto').trim() === '') {
            MuestraError('objeto', 'El objeto del proceso es requerido');
        } else {
            mostrarOverlay();
            const dataForm = Serializa('formGestProceso');
            dataForm.append('action', dataForm.get('id') == '0' ? 'add' : 'edit');
            SendPost('../php/controladores/procesos.php', dataForm).then((response) => {
                if (response.status === 'ok') {
                    mje('Guardado correctamente!');
                    tableProcesos.ajax.reload(null, false);
                    $('#modalFormsProc').modal('hide');
                } else {
                    mjeError('Error!', response.msg);
                }
            }).finally(() => { ocultarOverlay(); });
        }
    }
});
