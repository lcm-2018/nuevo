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


document.getElementById('modalForms').addEventListener('click', function (event) {
    const boton = event.target;
    if (boton) {
        event.preventDefault();
        LimpiaInvalid();
        switch (boton.id) {
            case 'btnGuardarDctos':
                mostrarOverlay();
                var data = Serializa('formDctosLiq');
                data = AppendData(data, 4);
                SendPost('../php/controladores/liquidado.php', data).then((response) => {
                    if (response.status === 'ok') {
                        mje('Guardado correctamente!');
                        tableDetallesNomina.ajax.reload(null, false);
                    } else {
                        mjeError('Error!', response.msg);
                    }
                }).finally(() => {
                    ocultarOverlay();
                });
                break;
            case 'btnGuardarParafiscales':
                mostrarOverlay();
                var data = Serializa('formParafiscalesLiq');
                data = AppendData(data, 3);
                SendPost('../php/controladores/liquidado.php', data).then((response) => {
                    if (response.status === 'ok') {
                        mje('Guardado correctamente!');
                        tableDetallesNomina.ajax.reload(null, false);
                    } else {
                        mjeError('Error!', response.msg);
                    }
                }).finally(() => {
                    ocultarOverlay();
                });
                break;
            case 'btnGuardarPretaciones':
                mostrarOverlay();
                var data = Serializa('formPrestacionesLiq');
                data = AppendData(data, 2);
                SendPost('../php/controladores/liquidado.php', data).then((response) => {
                    if (response.status === 'ok') {
                        mje('Guardado correctamente!');
                        tableDetallesNomina.ajax.reload(null, false);
                    } else {
                        mjeError('Error!', response.msg);
                    }
                }).finally(() => {
                    ocultarOverlay();
                });
                break;
        }
    }
});
function VerLiquidacionEmpleado(id_empleado, id_nomina) {
    mostrarOverlay();
    VerFormulario('../php/controladores/liquidado.php', 'form', { id: id_empleado, id_nomina: id_nomina }, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-xl');
}

function AppendData(data, option) {
    data.append('action', 'edit');
    data.append('option', option);
    data.append('id_nomina', ValueInput('id_nomina'));
    data.append('id_empleado', ValueInput('id_empleado'));
    return data;
}

var miModal = document.getElementById('modalForms');

miModal.addEventListener('click', function (event) {
    if (event.target.matches('input[type="date"]')) {
        event.preventDefault();
        try {
            event.target.showPicker();
            event.target.focus();
        } catch (error) {
            console.error("Navegador no soporta showPicker():", error);
        }
    }
});