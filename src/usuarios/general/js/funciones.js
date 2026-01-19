let tablaAsistencial;
let tablaFinanciero;
let tablaOpciones;

const tablaUsersSystem = crearDataTable(
    '#tableUsersSystem',
    'lista_usuarios.php',
    [
        { data: 'id_usuario' },
        { data: 'no_doc' },
        { data: 'nombre' },
        { data: 'usuario' },
        { data: 'rol' },
        { data: 'estado' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nuevo usuario',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/users.php', 'form1', 0, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-xl');
            }
        }
    ],
    {
        pageLength: 25,
        order: [[0, 'desc']],
    }
);

// Esperamos que la tabla se inicialice completamente
tablaUsersSystem.on('init', function () {
    BuscaDataTable(tablaUsersSystem);
});
document.querySelector('#tableUsersSystem').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar = event.target.closest('.eliminar');
    const btnEstado = event.target.closest('.estado');
    const btnPermisos = event.target.closest('.permisos');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/users.php', 'form1', id, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-xl');
    }

    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/users.php', id, tablaUsersSystem);
    }

    if (btnEstado) {
        const id = btnEstado.dataset.id;
        CambiaEstado('../php/controladores/users.php', id, tablaUsersSystem);
    }

    if (btnPermisos) {
        const id = btnPermisos.dataset.id;
        mostrarOverlay();
        VerFormulario('../php/controladores/users.php', 'form3', id, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-lg');
    }
});

document.querySelector('#modalForms').addEventListener('click', function (event) {
    const btnEstado = event.target.closest('.estado');
    const btnOpciones = event.target.closest('.opciones');
    const btnGuardar = event.target.closest('#btnGuardaUser');

    if (btnEstado) {
        var data = new FormData();
        var id = btnEstado.dataset.id;
        data.append('action', 'modulo');
        data.append('id', id);
        data.append('id_user', ValueInput('id_user'));
        mostrarOverlay();
        SendPost('../php/controladores/users.php', data).then((response) => {
            if (tablaAsistencial) tablaAsistencial.ajax.reload(null, false);
            if (tablaFinanciero) tablaFinanciero.ajax.reload(null, false);
        }).finally(() => {
            ocultarOverlay();
        });
    }

    if (btnOpciones) {
        const id = btnOpciones.dataset.id;
        var id_user = ValueInput('id_user');
        mostrarOverlay();
        VerFormulario('../php/controladores/users.php', 'form4', { id: id, id_user: id_user }, 'modalModulos', 'bodyModulos', 'tamModalModulos', 'modal-xl');
    }

    if (btnGuardar) {
        LimpiaInvalid();
        if (ValueInput('sl_tipoDocumento') === '0') {
            MuestraError('sl_tipoDocumento', 'Seleccione un tipo de documento');
        } else if (ValueInput('txtCCuser') === '') {
            MuestraError('txtCCuser', 'Ingrese un número de documento');
        } else if (ValueInput('txtlogin') === '') {
            MuestraError('txtlogin', 'Ingrese un nombre de usuario');
        } else if (ValueInput('txtPassUser') === '') {
            MuestraError('txtPassUser', 'Ingrese una contraseña');
        } else if (ValueInput('txtNomb1user') === '') {
            MuestraError('txtNomb1user', 'Ingrese un nombre');
        } else if (ValueInput('txtApe1user') === '') {
            MuestraError('txtApe1user', 'Ingrese un apellido');
        } else if (ValueInput('slcRolUser') === '0') {
            MuestraError('slcRolUser', 'Seleccione un rol');
        } else {
            var data = Serializa('formUserSistema');
            data.append('action', data.get('id_usuario') == 0 ? 'add' : 'edit');
            if (ValueInput('hidPassUser') === ValueInput('txtPassUser')) {
                data.append('clave', ValueInput('txtPassUser'));
            } else {
                data.append('clave', hex_sha512(ValueInput('txtPassUser')));
            }
            mostrarOverlay();
            SendPost('../php/controladores/users.php', data).then((response) => {
                if (response.status === 'ok') {
                    tableUsersSystem.ajax.reload(null, false);
                    mje('Guardado correctamente!');
                    $('#modalForms').modal('hide');
                } else {
                    mjeError(response.msg);
                }

            }).finally(() => {
                ocultarOverlay();
            });
        }
    }
});


const modalForms = document.getElementById('modalForms');
if (modalForms) {
    modalForms.addEventListener('shown.bs.modal', function () {
        const idUserEl = document.getElementById('id_user');
        if (document.getElementById('tableModulosAsistencial') && idUserEl) {
            const id_user = idUserEl.value;

            if ($.fn.DataTable.isDataTable('#tableModulosAsistencial')) {
                $('#tableModulosAsistencial').DataTable().destroy();
            }
            if ($.fn.DataTable.isDataTable('#tableModulosFinanciero')) {
                $('#tableModulosFinanciero').DataTable().destroy();
            }

            tablaAsistencial = $('#tableModulosAsistencial').DataTable({
                ajax: {
                    url: '../php/controladores/users.php',
                    type: 'POST',
                    data: { action: 'get_permisos_json', id: id_user },
                    dataSrc: 'data.asistencial'
                },
                columns: [
                    { data: 'id' },
                    { data: 'modulo' },
                    { data: 'estado' },
                    { data: 'accion' }
                ],
                paging: false,
                searching: false,
                info: false,
                ordering: false
            });

            tablaFinanciero = $('#tableModulosFinanciero').DataTable({
                ajax: {
                    url: '../php/controladores/users.php',
                    type: 'POST',
                    data: { action: 'get_permisos_json', id: id_user },
                    dataSrc: 'data.financiero'
                },
                columns: [
                    { data: 'id' },
                    { data: 'modulo' },
                    { data: 'estado' },
                    { data: 'accion' }
                ],
                paging: false,
                searching: false,
                info: false,
                ordering: false
            });
        }
    });
}
// validar si ya existe modalModulos

const modalModulos = document.getElementById('modalModulos');
if (modalModulos) {
    modalModulos.addEventListener('shown.bs.modal', function () {
        const idUserEl = document.getElementById('id_user_opciones');
        const idModuloEl = document.getElementById('id_modulo_opciones');

        if (document.getElementById('tableOpciones') && idUserEl && idModuloEl) {
            const id_user = idUserEl.value;
            const id_modulo = idModuloEl.value;

            if ($.fn.DataTable.isDataTable('#tableOpciones')) {
                $('#tableOpciones').DataTable().destroy();
            }

            tablaOpciones = $('#tableOpciones').DataTable({
                ajax: {
                    url: '../php/controladores/users.php',
                    type: 'POST',
                    data: { action: 'get_permisos_opciones_json', id: id_modulo, id_user: id_user },
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'id' },
                    { data: 'opcion' },
                    { data: 'consultar' },
                    { data: 'adicionar' },
                    { data: 'modificar' },
                    { data: 'eliminar' },
                    { data: 'anular' },
                    { data: 'imprimir' }
                ],
                columnDefs: [
                    { targets: [1], className: 'text-start' },
                ],
                paging: false,
                searching: false,
                info: false,
                ordering: false
            });
        }
    });

    modalModulos.addEventListener('click', function (event) {
        const btnEstado = event.target.closest('.estado');
        if (btnEstado) {
            var data = new FormData();
            var id = btnEstado.dataset.id;
            var id_user = document.getElementById('id_user_opciones').value;
            data.append('action', 'opcion');
            data.append('id', id);
            data.append('id_user', id_user);
            mostrarOverlay();
            SendPost('../php/controladores/users.php', data).then((response) => {
                if (tablaOpciones) tablaOpciones.ajax.reload(null, false);
            }).finally(() => {
                ocultarOverlay();
            });
        }
    });
}
