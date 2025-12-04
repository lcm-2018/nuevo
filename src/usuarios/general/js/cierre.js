const modalDefault = document.getElementById('modalDefault');
const cierrePeriodo = document.getElementById('cierrePeriodo');
const btnVigencia = document.getElementById('btnVigencia');
const fechaSesion = document.getElementById('fechaSesion');

if (cierrePeriodo) {
    cierrePeriodo.addEventListener('click', function () {
        mostrarOverlay();
        VerFormulario(ValueInput('host') + '/src/usuarios/general/php/controladores/cierre.php', 'form', 0, 'modalDefault', 'bodyDefault', 'tamDefault', 'modal-xl');
    });
}
if (fechaSesion) {
    fechaSesion.addEventListener('click', function () {
        mostrarOverlay();
        VerFormulario(ValueInput('host') + '/src/usuarios/general/php/controladores/cierre.php', 'form_fecha', 0, 'modalDefault', 'bodyDefault', 'tamDefault', 'modal-sm');
    });
}
if (btnVigencia) {
    btnVigencia.addEventListener('click', function () {
        mostrarOverlay();
        VerFormulario(ValueInput('host') + '/src/usuarios/general/php/controladores/cierre.php', 'form_vigencia', 0, 'modalDefault', 'bodyDefault', 'tamDefault', 'modal-sm');
    });
}

if (modalDefault) {
    modalDefault.addEventListener('submit', function (e) {
        e.preventDefault();
        if (e.target.id === 'formFechaSesion') {
            var data = new FormData(e.target);
            data.append('action', 'add_fecha');
            mostrarOverlay();
            SendPost(ValueInput('host') + '/src/usuarios/general/php/controladores/cierre.php', data).then((response) => {
                if (response.status === 'ok') {
                    $('#modalDefault').modal('hide');
                    mje('Fecha registrada correctamente');
                } else {
                    mjeError(response.msg);
                }
            }).finally(() => {
                ocultarOverlay();
            });
        } else if (e.target.id === 'formVigencia') {
            var data = new FormData(e.target);
            data.append('action', 'add_vigencia');
            mostrarOverlay();
            SendPost(ValueInput('host') + '/src/usuarios/general/php/controladores/cierre.php', data).then((response) => {
                if (response.status === 'ok') {
                    $('#modalDefault').modal('hide');
                    mje('Vigencia creada correctamente');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    mjeError(response.msg);
                }
            }).finally(() => {
                ocultarOverlay();
            });
        }
    });
    if (modalDefault) {
        modalDefault.addEventListener('shown.bs.modal', function () {
            if (document.getElementById('tableMesesCierre')) {
                if ($.fn.DataTable.isDataTable('#tableMesesCierre')) {
                    $('#tableMesesCierre').DataTable().destroy();
                }
                tablaOpciones = $('#tableMesesCierre').DataTable({
                    ajax: {
                        url: ValueInput('host') + '/src/usuarios/general/php/controladores/cierre.php',
                        type: 'POST',
                        data: { action: 'json' }
                    },
                    columns: [
                        { data: 'modulo' },
                        { data: 'enero' },
                        { data: 'febrero' },
                        { data: 'marzo' },
                        { data: 'abril' },
                        { data: 'mayo' },
                        { data: 'junio' },
                        { data: 'julio' },
                        { data: 'agosto' },
                        { data: 'septiembre' },
                        { data: 'octubre' },
                        { data: 'noviembre' },
                        { data: 'diciembre' }
                    ],
                    columnDefs: [
                        {
                            targets: [0],
                            className: 'text-start'
                        }
                    ],
                    paging: false,
                    searching: false,
                    info: false,
                    ordering: false
                });
            }
        });

        modalDefault.addEventListener('click', function (event) {
            const btnEstado = event.target.closest('.estado');
            if (btnEstado) {
                var data = new FormData();
                var id = btnEstado.dataset.id;
                data.append('action', 'opcion');
                data.append('id', id);
                mostrarOverlay();
                SendPost(ValueInput('host') + '/src/usuarios/general/php/controladores/cierre.php', data).then((response) => {
                    if (tablaOpciones) tablaOpciones.ajax.reload(null, false);
                }).finally(() => {
                    ocultarOverlay();
                });
            }
        });
    }
}