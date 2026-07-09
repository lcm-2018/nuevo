// ============================================================
// funciones.js — Auditoría
// src/contratos/auditoria/js/funciones.js
// ============================================================

const tableAuditoria = crearDataTable(
    '#tableAuditoria',
    'lista_auditoria.php',
    [
        { data: 'id' },
        { data: 'fecha' },
        { data: 'usuario' },
        { data: 'modulo' },
        { data: 'accion' },
        { data: 'id_reg' },
        { data: 'botones' }
    ],
    [], // Sin botones de agregar
    {
        pageLength: 10,
        order: [[0, 'desc']],
    }
);

tableAuditoria.on('init', function () {
    BuscaDataTable(tableAuditoria);
});

document.querySelector('#tableAuditoria').addEventListener('click', function (event) {
    const btnVer = event.target.closest('.ver');

    if (btnVer) {
        mostrarOverlay();
        const id = btnVer.dataset.id;
        VerFormulario('../php/controladores/auditoria.php', 'ver', id, 'modalFormsAud', 'bodyModalAud', 'tamModalFormsAud', 'modal-lg');
    }
});
