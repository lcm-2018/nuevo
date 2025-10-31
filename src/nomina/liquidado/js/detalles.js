const tableDetallesNomina = crearDataTable(
    '#tableDetallesNomina',
    'lista_detalles.php',
    [
        { data: 'id_empleado' },
        { data: 'nombre' },
        { data: 'no_documento' },
        { data: 'sede' },
        { data: 'descripcion_carg' },
        { data: 'sal_base' },
        { data: 'dias_incapacidad' },
        { data: 'dias_licencias' },
        { data: 'dias_vacaciones' },
        { data: 'dias_otros' },
        { data: 'dias_lab' },
        { data: 'valor_incap' },
        { data: 'valor_licencias' },
        { data: 'valor_vacacion' },
        { data: 'valor_otros' },
        { data: 'valor_laborado' },
        { data: 'aux_tran' },
        { data: 'aux_alim' },
        { data: 'horas_ext' },
        { data: 'val_bsp' },
        { data: 'val_prima_vac' },
        { data: 'g_representa' },
        { data: 'val_bon_recrea' },
        { data: 'valor_ps' },
        { data: 'valor_pv' },
        { data: 'val_cesantias' },
        { data: 'val_icesantias' },
        { data: 'val_compensa' },
        { data: 'devengado' },
        { data: 'valor_salud' },
        { data: 'valor_pension' },
        { data: 'val_psolidaria' },
        { data: 'valor_libranza' },
        { data: 'valor_embargo' },
        { data: 'valor_sind' },
        { data: 'val_retencion' },
        { data: 'valor_dcto' },
        { data: 'deducciones' },
        { data: 'neto' },
        { data: 'accion' },
    ],
    [
        {
            extend: 'excelHtml5',
            text: '<i class="fa fa-file-excel fa-lg"></i>',
            className: 'btn btn-outline-success',
            titleAttr: 'Exportar a Excel',
        }
    ],
    {
        pageLength: -1,
        order: [[0, 'asc']],
        paging: false,
        fixedColumns: {
            left: 3,
        },
        scrollCollapse: true,
        scrollX: true,
        scrollY: '80vh',
    },
    function (d) {
        d.id_nomina = ValueInput('id_nomina');
    },
    true
);

tableDetallesNomina.on('init', function () {
    BuscaDataTable(tableDetallesNomina);
});


document.querySelector('#tableDetallesNomina').addEventListener('click', function (event) {
    const fila = event.target.closest('tr');
    const esDobleClick = event.detail === 2;
    const btnDetalles = event.target.closest('.detalles');

    if (fila && (esDobleClick || btnDetalles)) {
        const id_nomina = ValueInput('id_nomina');
        const data = tableDetallesNomina.row(fila).data();
        VerLiquidacionEmpleado(data['id_empleado'], id_nomina);
    }
});

function VerLiquidacionEmpleado(id_empleado, id_nomina) {
    mostrarOverlay();
    VerFormulario('../php/controladores/liquidado.php', 'form', { id: id_empleado, id_nomina: id_nomina }, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-xl');
    setTimeout(ocultarOverlay, 500);
}