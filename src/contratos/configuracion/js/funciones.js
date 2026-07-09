// ============================================================
// funciones.js — Configuración de Contratación Pública
// src/contratos/configuracion/js/funciones.js
// Patrón: igual que nomina/configuracion/js/funciones.js
// ============================================================

// ────────────────────────────────────────────────────────────
// 1. INICIALIZACIÓN DE DATATABLES
// ────────────────────────────────────────────────────────────

const tableTiposProc = crearDataTable(
    '#tableTiposProc',
    'lista_tipos_proceso.php',
    [
        { data: 'id' },
        { data: 'nombre' },
        { data: 'descripcion' },
        { data: 'activo' },
        { data: 'botones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar tipo de proceso',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/tipos_proceso.php', 'form', 0, 'modalFormsCtt', 'bodyModalCtt', 'tamModalFormsCtt', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    }
);

const tableModalidadesCtt = crearDataTable(
    '#tableModalidadesCtt',
    'lista_modalidades.php',
    [
        { data: 'id' },
        { data: 'modalidad' },
        { data: 'botones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar modalidad de contratación',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/modalidades.php', 'form', 0, 'modalFormsCtt', 'bodyModalCtt', 'tamModalFormsCtt', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'asc']],
    }
);

const tableEstadosCtt = crearDataTable(
    '#tableEstadosCtt',
    'lista_estados.php',
    [
        { data: 'id' },
        { data: 'nombre' },
        { data: 'permite_edicion' },
        { data: 'botones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar estado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/estados.php', 'form', 0, 'modalFormsCtt', 'bodyModalCtt', 'tamModalFormsCtt', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'asc']],
    }
);

// ────────────────────────────────────────────────────────────
// 2. INICIALIZACIÓN DE BÚSQUEDA AL CARGAR (on init)
// ────────────────────────────────────────────────────────────

tableTiposProc.on('init', function () {
    BuscaDataTable(tableTiposProc);
});
tableModalidadesCtt.on('init', function () {
    BuscaDataTable(tableModalidadesCtt);
});
tableEstadosCtt.on('init', function () {
    BuscaDataTable(tableEstadosCtt);
});

// ────────────────────────────────────────────────────────────
// 3. EVENTOS DE CLICK EN TABLAS (event delegation)
// ────────────────────────────────────────────────────────────

document.querySelector('#tableTiposProc').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar   = event.target.closest('.eliminar');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/tipos_proceso.php', 'form', id, 'modalFormsCtt', 'bodyModalCtt', 'tamModalFormsCtt', '');
    }
    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/tipos_proceso.php', id, tableTiposProc);
    }
});

document.querySelector('#tableModalidadesCtt').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar   = event.target.closest('.eliminar');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/modalidades.php', 'form', id, 'modalFormsCtt', 'bodyModalCtt', 'tamModalFormsCtt', '');
    }
    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/modalidades.php', id, tableModalidadesCtt);
    }
});

document.querySelector('#tableEstadosCtt').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar   = event.target.closest('.eliminar');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/estados.php', 'form', id, 'modalFormsCtt', 'bodyModalCtt', 'tamModalFormsCtt', '');
    }
    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/estados.php', id, tableEstadosCtt);
    }
});

// ────────────────────────────────────────────────────────────
// 4. LISTENER CENTRALIZADO DEL MODAL (un solo switch para todos)
// ────────────────────────────────────────────────────────────

document.getElementById('modalFormsCtt').addEventListener('click', function (event) {
    const boton = event.target.closest('button');
    if (!boton) return;

    event.preventDefault();
    LimpiaInvalid();

    switch (boton.id) {

        // ── Guardar Tipo de Proceso ──
        case 'btnGuardaTipoProceso':
            if (ValueInput('txtNombreTipo') === '') {
                MuestraError('txtNombreTipo', 'El nombre del tipo de proceso no puede estar vacío');
            } else {
                mostrarOverlay();
                const dataTipo = Serializa('formGestTipoProceso');
                dataTipo.append('action', dataTipo.get('id') == '0' ? 'add' : 'edit');
                SendPost('../php/controladores/tipos_proceso.php', dataTipo).then((response) => {
                    if (response.status === 'ok') {
                        mje('Guardado correctamente!');
                        tableTiposProc.ajax.reload(null, false);
                        $('#modalFormsCtt').modal('hide');
                    } else {
                        mjeError('Error!', response.msg);
                    }
                }).finally(() => { ocultarOverlay(); });
            }
            break;

        // ── Guardar Modalidad ──
        case 'btnGuardaModalidad':
            if (ValueInput('txtNombreModalidad') === '') {
                MuestraError('txtNombreModalidad', 'El nombre de la modalidad no puede estar vacío');
            } else {
                mostrarOverlay();
                const dataModal = Serializa('formGestModalidad');
                dataModal.append('action', dataModal.get('id') == '0' ? 'add' : 'edit');
                SendPost('../php/controladores/modalidades.php', dataModal).then((response) => {
                    if (response.status === 'ok') {
                        mje('Guardado correctamente!');
                        tableModalidadesCtt.ajax.reload(null, false);
                        $('#modalFormsCtt').modal('hide');
                    } else {
                        mjeError('Error!', response.msg);
                    }
                }).finally(() => { ocultarOverlay(); });
            }
            break;

        // ── Guardar Estado ──
        case 'btnGuardaEstado':
            if (ValueInput('txtNombreEstado') === '') {
                MuestraError('txtNombreEstado', 'El nombre del estado no puede estar vacío');
            } else if (ValueInput('numOrden') <= 0) {
                MuestraError('numOrden', 'El orden debe ser mayor a 0');
            } else {
                mostrarOverlay();
                const dataEst = Serializa('formGestEstado');
                dataEst.append('action', dataEst.get('id') == '0' ? 'add' : 'edit');
                SendPost('../php/controladores/estados.php', dataEst).then((response) => {
                    if (response.status === 'ok') {
                        mje('Guardado correctamente!');
                        tableEstadosCtt.ajax.reload(null, false);
                        $('#modalFormsCtt').modal('hide');
                    } else {
                        mjeError('Error!', response.msg);
                    }
                }).finally(() => { ocultarOverlay(); });
            }
            break;
    }
});
