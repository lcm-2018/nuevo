let tablaAsistencial;
let tablaFinanciero;
let tablaOpciones;
let sedeUsuarioActiva = null;

const getUserSedeRows = () => Array.from(document.querySelectorAll('#tb_sedes tbody .sede-row'));
const getUserBodegaRows = () => Array.from(document.querySelectorAll('#tb_bodegas tbody .bodega-row'));

const findUserSedeRow = (idSede) => document.querySelector(`#tb_sedes tbody .sede-row[data-sede="${idSede}"]`);

const isUserSedeChecked = (idSede) => {
    const row = findUserSedeRow(idSede);
    return !!row?.querySelector('.chk-sede')?.checked;
};

const getUserSedeNombre = (idSede) => {
    const row = findUserSedeRow(idSede);
    return row?.children[2]?.textContent.trim() || '';
};

const clearUserBodegasBySede = (idSede) => {
    getUserBodegaRows()
        .filter((row) => row.dataset.sede === String(idSede))
        .forEach((row) => {
            const checkbox = row.querySelector('.chk-bodega');
            if (checkbox) {
                checkbox.checked = false;
            }
        });
};

const updateUserLocationHeaderChecks = () => {
    const chkAllSedes = document.getElementById('chk_sel_filtro_sedes');
    const chkAllBodegas = document.getElementById('chk_sel_filtro_bodegas');
    const sedes = getUserSedeRows().map((row) => row.querySelector('.chk-sede')).filter(Boolean);
    const bodegasVisibles = getUserBodegaRows()
        .filter((row) => row.style.display !== 'none')
        .map((row) => row.querySelector('.chk-bodega'))
        .filter((checkbox) => checkbox && !checkbox.disabled);

    if (chkAllSedes) {
        chkAllSedes.checked = sedes.length > 0 && sedes.every((checkbox) => checkbox.checked);
    }

    if (chkAllBodegas) {
        chkAllBodegas.checked = bodegasVisibles.length > 0 && bodegasVisibles.every((checkbox) => checkbox.checked);
    }
};

const refreshUserBodegasTable = () => {
    const emptyRow = document.getElementById('rowEmptyBodegasUsuario');
    const label = document.getElementById('txtSedeBodegaActiva');
    const activeId = sedeUsuarioActiva ? String(sedeUsuarioActiva) : '';
    const sedeChecked = activeId !== '' && isUserSedeChecked(activeId);
    let visibles = 0;

    getUserBodegaRows().forEach((row) => {
        const visible = activeId !== '' && row.dataset.sede === activeId;
        row.style.display = visible ? '' : 'none';
        if (visible) {
            visibles++;
            const checkbox = row.querySelector('.chk-bodega');
            if (checkbox) {
                checkbox.disabled = !sedeChecked;
            }
        }
    });

    if (label) {
        label.textContent = activeId !== '' ? `(${getUserSedeNombre(activeId)})` : '';
    }

    if (emptyRow) {
        const messageCell = emptyRow.querySelector('td');
        emptyRow.style.display = visibles === 0 ? '' : 'none';
        if (messageCell) {
            if (activeId === '') {
                messageCell.textContent = 'Seleccione una sede para visualizar sus bodegas.';
            } else if (!sedeChecked) {
                messageCell.textContent = 'Marque la sede para habilitar sus bodegas.';
            } else {
                messageCell.textContent = 'Esta sede no tiene bodegas disponibles.';
            }
        }
    }

    updateUserLocationHeaderChecks();
};

const setUserActiveSede = (idSede) => {
    sedeUsuarioActiva = idSede ? String(idSede) : null;

    getUserSedeRows().forEach((row) => {
        row.classList.toggle('table-active', sedeUsuarioActiva !== null && row.dataset.sede === sedeUsuarioActiva);
    });

    refreshUserBodegasTable();
};

const initializeUserLocationSelector = () => {
    if (!document.getElementById('formUserSistema')) {
        return;
    }

    const firstChecked = getUserSedeRows().find((row) => row.querySelector('.chk-sede')?.checked);
    const firstRow = getUserSedeRows()[0];
    setUserActiveSede(firstChecked?.dataset.sede || firstRow?.dataset.sede || null);
};

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
    const sedeRow = event.target.closest('#tb_sedes tbody .sede-row');

    if (sedeRow) {
        setUserActiveSede(sedeRow.dataset.sede);
    }

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
                    tablaUsersSystem.ajax.reload(null, false);
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

document.querySelector('#modalForms').addEventListener('change', function (event) {
    const target = event.target;

    if (target.matches('.chk-sede')) {
        const row = target.closest('.sede-row');
        if (!row) {
            return;
        }

        setUserActiveSede(row.dataset.sede);
        if (!target.checked) {
            clearUserBodegasBySede(row.dataset.sede);
        }
        refreshUserBodegasTable();
        return;
    }

    if (target.matches('#chk_sel_filtro_sedes')) {
        getUserSedeRows().forEach((row) => {
            const checkbox = row.querySelector('.chk-sede');
            if (checkbox) {
                checkbox.checked = target.checked;
                if (!target.checked) {
                    clearUserBodegasBySede(row.dataset.sede);
                }
            }
        });

        if (target.checked) {
            const firstRow = getUserSedeRows()[0];
            if (firstRow) {
                setUserActiveSede(firstRow.dataset.sede);
            }
        } else {
            refreshUserBodegasTable();
        }
        return;
    }

    if (target.matches('.chk-bodega')) {
        const row = target.closest('.bodega-row');
        if (!row) {
            return;
        }

        if (target.checked && !isUserSedeChecked(row.dataset.sede)) {
            const sedeCheckbox = findUserSedeRow(row.dataset.sede)?.querySelector('.chk-sede');
            if (sedeCheckbox) {
                sedeCheckbox.checked = true;
            }
        }

        setUserActiveSede(row.dataset.sede);
        updateUserLocationHeaderChecks();
        return;
    }

    if (target.matches('#chk_sel_filtro_bodegas')) {
        if (!sedeUsuarioActiva) {
            target.checked = false;
            return;
        }

        if (target.checked && !isUserSedeChecked(sedeUsuarioActiva)) {
            const sedeCheckbox = findUserSedeRow(sedeUsuarioActiva)?.querySelector('.chk-sede');
            if (sedeCheckbox) {
                sedeCheckbox.checked = true;
            }
            refreshUserBodegasTable();
        }

        getUserBodegaRows()
            .filter((row) => row.style.display !== 'none')
            .forEach((row) => {
                const checkbox = row.querySelector('.chk-bodega');
                if (checkbox && !checkbox.disabled) {
                    checkbox.checked = target.checked;
                }
            });

        updateUserLocationHeaderChecks();
    }
});


const modalForms = document.getElementById('modalForms');
if (modalForms) {
    modalForms.addEventListener('shown.bs.modal', function () {
        initializeUserLocationSelector();

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

    modalForms.addEventListener('hidden.bs.modal', function () {
        sedeUsuarioActiva = null;
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
