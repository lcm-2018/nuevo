const tablenNominasEmpleados = crearDataTable(
    '#tablenNominasEmpleados',
    'lista_liquidado.php',
    [
        { data: 'id' },
        { data: 'descripcion' },
        { data: 'mes' },
        { data: 'tipo' },
        { data: 'estado' },
        { data: 'accion' },
    ],
    [],
    {
        pageLength: -1,
        scrollX: false,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [1, 2], className: 'text-wrap' },
            { "orderable": false, "targets": [0, 5] },
            { targets: [4], className: 'p-1' },
        ],
        initComplete: function () {
            var api = this.api();
            $('#filterRow th', api.table().header()).on('click', function (e) {
                e.stopPropagation();
            });

            $('#filterRow th', api.table().header()).on('mousedown', function (e) {
                e.stopPropagation();
            });
            $('#filterRow .dt-column-order').remove();
        }
    },
    function (d) {
        d.filter_descripcion = ValueInput('filter_descripcion');
        d.filter_mes = ValueInput('filter_mes');
        d.filter_tipo = ValueInput('filter_tipo');
        d.filter_estado = ValueInput('filter_estado');
    },
    false
);

tablenNominasEmpleados.on('init', function () {
    BuscaDataTable(tablenNominasEmpleados);
});

document.querySelector('#tablenNominasEmpleados').addEventListener('click', function (event) {
    const btnDetalles = event.target.closest('.detalles');
    const btnBorrar = event.target.closest('.borrar');
    const btnAnular = event.target.closest('.anular');
    const btnImprimir = event.target.closest('.imprimir');
    const btnDescargarPdf = event.target.closest('.descargar-pdf');
    const btnReportes = event.target.closest('.reportes');
    const btnEditar = event.target.closest('.editar');

    if (btnDetalles) {
        event.preventDefault();
        const id = btnDetalles.dataset.id;
        SubmitPost('detalles.php', 'id_nomina', id);
    }
    if (btnBorrar) {
        event.preventDefault();
        const id = btnBorrar.dataset.id;
        EliminaRegistro('../php/controladores/liquidado.php', id, tablenNominasEmpleados);
    }
    if (btnAnular) {
        event.preventDefault();
        const id = btnAnular.dataset.id;
        EliminaRegistro('../php/controladores/liquidado.php', { id: id, estado: 0 }, tablenNominasEmpleados, 'estado');

    }
    if (btnImprimir) {
        event.preventDefault();
        const id = btnImprimir.dataset.id;
        const text = btnImprimir.getAttribute('text');
        var url = '';
        if (text === 'M') {
            url = 'cdp_mensual';
        } else if (text === 'P') {
            url = 'cdp_patronal';
        } else if (text === 'N') {
            url = 'nomina_general';
        }
        var pdf = false;
        ImprimirReporte('../php/reportes/' + url + '.php', { id: id, pdf: pdf });

    }
    /*
    if (btnDescargarPdf) {
        event.preventDefault();
        const id = btnDescargarPdf.dataset.id;
        const text = btnDescargarPdf.getAttribute('text');
        var url = text === 'M' ? 'mensual' : 'patronal';
        var pdf = true;
        ImprimirReporte('../php/reportes/cdp_' + url + '.php', { id: id, pdf: pdf });
    }*/

    if (btnReportes) {
        event.preventDefault();
        const id = btnReportes.dataset.id;
        mostrarOverlay();
        VerFormulario('../php/controladores/reportes.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-lg');
    }
    if (btnEditar) {
        event.preventDefault();
        const id = btnEditar.dataset.id;
        mostrarOverlay();
        VerFormulario('../../liquidacion/php/controladores/liquidacion.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
    }
});

//evento click en el modal
document.querySelector('#modalForms').addEventListener('click', function (event) {
    const btnReportes = event.target.closest('.reportes');
    const btnGuardarNomina = event.target.closest('#btnGuardaNomina');
    if (btnReportes) {
        event.preventDefault();
        const id_nomina = document.getElementById('id_nomina').value; // Obtener id_nomina del input hidden
        const tipo_reporte = btnReportes.dataset.id; // 1=Libranzas, 2=Embargos, 3=Sindicatos
        const text = btnReportes.getAttribute('text'); // E=Excel, P=PDF

        // Determinar el archivo según el tipo de reporte
        let archivo = '';
        let params = { id: id_nomina, tipo: text };

        switch (tipo_reporte) {
            case '1':
                archivo = 'libranzas';
                break;
            case '2':
                archivo = 'embargos';
                break;
            case '3':
                archivo = 'sindicatos';
                break;
            case '4':
                archivo = 'conceptos';
                // Obtener el concepto seleccionado
                const id_concepto = document.getElementById('concepto').value;
                if (!id_concepto || id_concepto == '0') {
                    mjeAlert('Atención', 'Debe seleccionar un concepto', 'warning');
                    return;
                }
                params.id_concepto = id_concepto;
                break;
            case '5':
                // Envío masivo de desprendibles
                Swal.fire({
                    title: '¿Enviar desprendibles masivamente?',
                    html: `<p>Se enviarán los desprendibles de nómina a <strong>todos los empleados</strong> de esta liquidación que tengan correo electrónico registrado.</p>
                           <p class="text-warning"><i class="fas fa-exclamation-triangle"></i> Este proceso puede tardar varios minutos dependiendo del número de empleados.</p>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#16a085',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '<i class="fas fa-paper-plane"></i> Sí, enviar a todos',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        mostrarOverlay();
                        Swal.fire({
                            title: 'Enviando desprendibles...',
                            html: 'Por favor espere, este proceso puede tardar varios minutos.<br><br><i class="fas fa-spinner fa-spin fa-2x"></i>',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                var data = new FormData();
                                data.append('id_nomina', id_nomina);
                                SendPost('../php/reportes/enviar_masivo.php', data).then((response) => {
                                    Swal.close();
                                    if (response.status === 'ok') {
                                        let detalles = '';
                                        if (response.stats) {
                                            detalles = `<br><br><strong>Resumen:</strong><br>
                                                        ✅ Enviados: ${response.stats.enviados}<br>
                                                        📭 Sin correo: ${response.stats.sin_correo}<br>
                                                        ❌ Fallidos: ${response.stats.fallidos}`;
                                        }
                                        Swal.fire({
                                            title: '¡Proceso completado!',
                                            html: response.msg + detalles,
                                            icon: 'success'
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error',
                                            text: response.msg,
                                            icon: 'error'
                                        });
                                    }
                                }).catch((error) => {
                                    Swal.close();
                                    Swal.fire({
                                        title: 'Error',
                                        text: 'Ocurrió un error durante el envío: ' + error,
                                        icon: 'error'
                                    });
                                }).finally(() => {
                                    ocultarOverlay();
                                });
                            }
                        });
                    }
                });
                return; // Salir porque el envío masivo maneja su propia lógica
            default:
                console.error('Tipo de reporte no válido');
                return;
        }

        ImprimirReporte('../php/reportes/' + archivo + '.php', params);
    }

    if (btnGuardarNomina) {
        event.preventDefault();
        var data = new FormData();
        data.append('id_nomina', ValueInput('id_nomina'));
        data.append('descripcion', ValueInput('descripcion'));
        data.append('action', 'edit2');
        mostrarOverlay();
        SendPost('../../liquidacion/php/controladores/liquidacion.php', data).then((response) => {
            if (response.status === 'ok') {
                mje('Guardado correctamente!');
                tablenNominasEmpleados.ajax.reload(null, false);
                $('#modalForms').modal('hide');
            } else {
                mjeAlert('', '', response.msg);
            }
        }).finally(() => {
            ocultarOverlay();
        });
    }
});