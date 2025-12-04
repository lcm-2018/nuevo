const tablaRoles = crearDataTable(
    '#tableRoles',
    'lista_roles.php',
    [
        { data: 'id_rol' },
        { data: 'rol' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nuevo usuario',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/roles.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 25,
        order: [[0, 'desc']],
    }
)

tablaRoles.on('init', function () {
    BuscaDataTable(tablaRoles);
});

document.querySelector('#tableRoles').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar = event.target.closest('.eliminar');
    const btnPermisos = event.target.closest('.permisos');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/roles.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
    }

    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/roles.php', id, tablaRoles);
    }

    if (btnPermisos) {
        const id = btnPermisos.dataset.id;
        mostrarOverlay();
        VerFormulario('../php/controladores/roles.php', 'form2', id, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-xl');
    }
});

document.querySelector('#modalForms').addEventListener('click', function (event) {
    const btnGuardaRol = event.target.closest('#btnGuardaRol');

    if (btnGuardaRol) {
        LimpiaInvalid();
        if (ValueInput('txtNombreRol') === '') {
            MuestraError('txtNombreRol', 'Ingresar nombre del rol');
        } else {
            var data = Serializa('formRolUsuario');
            data.append('action', data.get('id_rol') == 0 ? 'add' : 'edit');
            mostrarOverlay();
            SendPost('../php/controladores/roles.php', data).then((response) => {
                tablaRoles.ajax.reload(null, false);
                mje('Guardado correctamente!');
                $('#modalForms').modal('hide');
            }).finally(() => {
                ocultarOverlay();
            });
        }

    }
});


const modalForms = document.getElementById('modalForms');
if (modalForms) {
    modalForms.addEventListener('shown.bs.modal', function () {
        const id = ValueInput('id_rol');
        if (document.getElementById('tableOpciones') && id) {

            if ($.fn.DataTable.isDataTable('#tableOpciones')) {
                $('#tableOpciones').DataTable().destroy();
            }
            // agragar al body de esta tabla scroll en y
            tablaOpciones = $('#tableOpciones').DataTable({
                ajax: {
                    url: '../php/controladores/roles.php',
                    type: 'POST',
                    data: { action: 'json', id: id }
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
                    {
                        targets: [1],
                        className: 'text-start'
                    }
                ],
                paging: false,
                searching: false,
                info: false,
                ordering: false,
                scrollY: '400px',
                scrollCollapse: true,
                scroller: true
            });
        }
    });
    modalForms.addEventListener('click', function (event) {
        const btnEstado = event.target.closest('.estado');
        if (btnEstado) {
            var data = new FormData();
            var id = btnEstado.dataset.id;
            data.append('action', 'opcion');
            data.append('id', id);
            data.append('id_rol', ValueInput('id_rol'));
            mostrarOverlay();
            SendPost('../php/controladores/roles.php', data).then((response) => {
                if (tablaOpciones) tablaOpciones.ajax.reload(null, false);
            }).finally(() => {
                ocultarOverlay();
            });
        }
    });
}