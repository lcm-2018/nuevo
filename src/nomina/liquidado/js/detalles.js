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
        { data: 'val_rlaboral' },
        { data: 'valor_salud_patronal' },
        { data: 'valor_pension_patronal' },
        { data: 'valor_libranza' },
        { data: 'valor_embargo' },
        { data: 'valor_sind' },
        { data: 'val_retencion' },
        { data: 'valor_dcto' },
        { data: 'deducciones' },
        { data: 'neto' },
        { data: 'patronal' },
        { data: 'accion' },
        { data: 'nit_eps' },
        { data: 'nit_afp' },
        { data: 'nit_arl' },
    ],
    [
        {
            extend: 'excelHtml5',
            text: '<i class="fa fa-file-excel fa-lg"></i>',
            className: 'btn btn-outline-success',
            titleAttr: 'Exportar a Excel Empleados',
            exportOptions: {
                columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 35, 36, 37, 38, 39, 40, 41, 43, 44, 45, 46]
            }
        },
        {
            extend: 'excelHtml5',
            text: '<i class="fa fa-file-excel fa-lg"></i>',
            className: 'btn btn-outline-info',
            titleAttr: 'Exportar a Excel Patronal',
            exportOptions: {
                columns: [0, 1, 2, 3, 4, 29, 30, 31, 32, 33, 34, 42, 43, 44, 45, 46]
            }
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
        columnDefs: [
            {
                targets: [32, 33, 34, 42, 43, 44, 45, 46],
                visible: false
            }
        ]
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
    const btnAnular = event.target.closest('.anular');

    const id_nomina = ValueInput('id_nomina');
    const data = tableDetallesNomina.row(fila).data();

    if (fila && (esDobleClick || btnDetalles)) {
        event.preventDefault();
        VerLiquidacionEmpleado(data['id_empleado'], id_nomina);
    }
    if (btnAnular) {
        event.preventDefault();
        EliminaRegistro('../php/controladores/liquidado.php', { id: data['id_empleado'], id_nomina: id_nomina }, tableDetallesNomina, 'annul');
    }
});


document.getElementById('modalForms').addEventListener('click', function (event) {
    const boton = event.target.closest('button');
    if (!boton) return;
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
                    $('#modalForms').modal('hide');
                    VerLiquidacionEmpleado(ValueInput('id_empleado'), ValueInput('id_nomina'), 2);
                    tableDetallesNomina.ajax.reload(null, false);
                } else {
                    mjeError('Error!', response.msg);
                }
            }).finally(() => {
                ocultarOverlay();
            });
            break;
        case 'btnGuardarSalarios':
            mostrarOverlay();
            var data = Serializa('formSalariosLiq', 'formPrestacionesLiq');
            data = AppendData(data, 1);
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
        case 'btnImprimir':
            event.preventDefault();
            const id = boton.dataset.id;
            ImprimirReporte('../php/reportes/desprendible.php', { id: id });
            break;
        case 'btnEnviarCorreo':
            event.preventDefault();
            const idCorreo = boton.dataset.id;
            Swal.fire({
                title: '¿Enviar desprendible por correo?',
                text: 'Se enviará el desprendible de nómina al correo registrado del empleado.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, enviar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    mostrarOverlay();
                    var data = new FormData();
                    data.append('id', idCorreo);
                    SendPost('../php/reportes/enviar_desprendible.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje(response.msg);
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
            });
            break;
    }
});
function VerLiquidacionEmpleado(id_empleado, id_nomina, item = 1) {
    mostrarOverlay();
    VerFormulario('../php/controladores/liquidado.php', 'form', { id: id_empleado, id_nomina: id_nomina, item: item }, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-xl');
}

function AppendData(data, option) {
    data.append('action', 'edit');
    data.append('option', option);
    data.append('id_nomina', ValueInput('id_nomina'));
    data.append('id_empleado', ValueInput('id_empleado'));
    return data;
}

var btnCerrarDefinitiva = document.getElementById('btnCerrarNomina');
if (btnCerrarDefinitiva) {
    btnCerrarDefinitiva.addEventListener('click', function () {
        mostrarOverlay();
        var data = new FormData();
        data.append('action', 'estado');
        data.append('id', ValueInput('id_nomina'));
        data.append('estado', '2');
        SendPost('../php/controladores/liquidado.php', data).then((response) => {
            if (response.status === 'ok') {
                mje('Nómina cerrada definitivamente!');
                setTimeout(() => {
                    location.reload();
                }, 500);
            } else {
                mjeError('Error!', response.msg);
            }
        }).finally(() => {
            ocultarOverlay();
        });
    });
}