// ============================================================
// funciones.js — Gestión de Contratos
// src/contratos/contratos/js/funciones.js
// ============================================================

const tableContratos = crearDataTable(
    '#tableContratos',
    'lista_contratos.php',
    [
        { data: 'id' },
        { data: 'codigo' },
        { data: 'proceso' },
        { data: 'contratista' },
        { data: 'fechas' },
        { data: 'valor' },
        { data: 'estado' },
        { data: 'botones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Crear nuevo contrato',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/contratos.php', 'form', 0, 'modalFormsCtt2', 'bodyModalCtt2', 'tamModalFormsCtt2', 'modal-lg');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    }
);

tableContratos.on('init', function () {
    BuscaDataTable(tableContratos);
});

document.querySelector('#tableContratos').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar   = event.target.closest('.eliminar');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/contratos.php', 'form', id, 'modalFormsCtt2', 'bodyModalCtt2', 'tamModalFormsCtt2', 'modal-lg');
    }
    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/contratos.php', id, tableContratos);
    }
});

document.getElementById('modalFormsCtt2').addEventListener('click', function (event) {
    const boton = event.target.closest('button');
    if (!boton) return;

    if (boton.id === 'btnGuardaContrato') {
        event.preventDefault();
        LimpiaInvalid();

        if (ValueInput('id_proceso') === '') {
            MuestraError('id_proceso', 'Seleccione el proceso relacionado');
        } else if (ValueInput('id_tercero') === '') {
            MuestraError('id_tercero', 'Seleccione el contratista');
        } else if (ValueInput('objeto_contrato').trim() === '') {
            MuestraError('objeto_contrato', 'El objeto del contrato es requerido');
        } else if (ValueInput('fec_inicio') === '') {
            MuestraError('fec_inicio', 'La fecha de inicio es requerida');
        } else if (ValueInput('fec_fin') === '') {
            MuestraError('fec_fin', 'La fecha de fin es requerida');
        } else if (ValueInput('valor_total') === '' || parseFloat(ValueInput('valor_total')) <= 0) {
            MuestraError('valor_total', 'El valor total debe ser mayor a 0');
        } else {
            mostrarOverlay();
            const dataForm = Serializa('formGestContrato');
            dataForm.append('action', dataForm.get('id') == '0' ? 'add' : 'edit');
            SendPost('../php/controladores/contratos.php', dataForm).then((response) => {
                if (response.status === 'ok') {
                    mje('Guardado correctamente!');
                    tableContratos.ajax.reload(null, false);
                    $('#modalFormsCtt2').modal('hide');
                } else {
                    mjeError('Error!', response.msg);
                }
            }).finally(() => { ocultarOverlay(); });
        }
    }
});
