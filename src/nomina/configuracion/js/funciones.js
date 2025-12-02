const tablaParamLiq = crearDataTable(
    '#tableParamLiq',
    'lista_parametros.php',
    [
        { data: 'id' },
        { data: 'concepto' },
        { data: 'valor' },
        { data: 'botones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nuevo parámetro',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/parametros.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 25,
        order: [[0, 'desc']],
    }
);

const tableCargosNom = crearDataTable(
    '#tableCargosNom',
    'lista_cargos.php',
    [
        { data: 'id' },
        { data: 'codigo' },
        { data: 'cargo' },
        { data: 'grado' },
        { data: 'perfil' },
        { data: 'nombramiento' },
        { data: 'botones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar nuevo cargo de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/cargos.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', opCaracterJS == 2 ? 'modal-lg' : '');
            }
        }
    ],
    {
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [
            {
                targets: opCaracterJS == 2 ? [] : [1, 3, 4, 5],
                visible: false
            }
        ],
    }
);

const tableTerceroNom = crearDataTable(
    '#tableTerceroNom',
    'lista_terceros.php',
    [
        { data: 'id' },
        { data: 'nombre' },
        { data: 'nit' },
        { data: 'direccion' },
        { data: 'telefono' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar tercero',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/terceros.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    },
    function (d) {
        d.tipo = ValueInput('tipoTerceroNom');
    }
);

const tableIncSalario = crearDataTable(
    '#tableIncSalario',
    'lista_incrementos.php',
    [
        { data: 'id' },
        { data: 'porcentaje' },
        { data: 'fecha' },
        { data: 'estado' },
        { data: 'acciones' },
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar Incremento Salarial',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/incrementos.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    }
);
const tableRubroPto = crearDataTable(
    '#tableRubroPto',
    'lista_rubros.php',
    [
        { data: 'id' },
        { data: 'tipo' },
        { data: 'cod_ra' },
        { data: 'nom_ra' },
        { data: 'cod_ro' },
        { data: 'nom_ro' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar Rubro Presupuestal',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/rubros.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-lg');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
    }
);

const tableCtaCtbNom = crearDataTable(
    '#tableCtaCtbNom',
    'lista_cuentas.php',
    [
        { data: 'id_causacion' },
        { data: 'ccosto' },
        { data: 'tipo' },
        { data: 'nom_tipo' },
        { data: 'cuenta' },
        { data: 'nom_cta' },
        { data: 'acciones' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar Cuenta Contable',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/cuentas.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-lg');
            }
        }
    ],
    {
        pageLength: 25,
        order: [[1, 'desc']],
    }
);
// Esperamos que la tabla se inicialice completamente
tablaParamLiq.on('init', function () {
    BuscaDataTable(tablaParamLiq);
});
tableCargosNom.on('init', function () {
    BuscaDataTable(tableCargosNom);
});
tableTerceroNom.on('init', function () {
    BuscaDataTable(tableTerceroNom);
});
tableIncSalario.on('init', function () {
    BuscaDataTable(tableIncSalario);
});
tableRubroPto.on('init', function () {
    BuscaDataTable(tableRubroPto);
});
tableCtaCtbNom.on('init', function () {
    BuscaDataTable(tableCtaCtbNom);
});
document.querySelector('#tableParamLiq').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar = event.target.closest('.eliminar');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/parametros.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
    }

    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/parametros.php', id, tablaParamLiq);
    }
});

document.querySelector('#tableCargosNom').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar = event.target.closest('.eliminar');

    if (btnActualizar) {
        event.preventDefault();
        event.stopPropagation();
        alert
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/cargos.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', opCaracterJS === 2 ? 'modal-lg' : '');
    }

    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/cargos.php', id, tableCargosNom);
    }
});

document.querySelector('#tableIncSalario').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar = event.target.closest('.eliminar');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/incrementos.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms');
    }

    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/incrementos.php', id, tableIncSalario);
    }
});

document.querySelector('#tableRubroPto').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar = event.target.closest('.eliminar');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/rubros.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-lg');
    }

    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/rubros.php', id, tableRubroPto);
    }
});

document.querySelector('#tableCtaCtbNom').addEventListener('click', function (event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar = event.target.closest('.eliminar');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/cuentas.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-lg');
    }

    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/cuentas.php', id, tableCtaCtbNom);
    }
});

document.getElementById('modalForms').addEventListener('click', function (event) {
    const boton = event.target.closest('button');
    if (!boton) return;

    // Sólo prevenir cuando se haga clic en botones de acción
    event.preventDefault();
    LimpiaInvalid();
    switch (boton.id) {
            case 'btnGuardaConcxVig':
                if (ValueInput('concepto') === '0') {
                    MuestraError('concepto', 'Seleccione un concepto');
                } else if (Number(ValueInput('valor')) < 0) {
                    MuestraError('valor', 'El valor no puede ser menor a 0');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formConcepXvig');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    SendPost('../php/controladores/parametros.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tablaParamLiq.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });

                }
                break;

            case 'btnGuardaCargo':
                if (ValueInput('txtNomCargo') === '') {
                    MuestraError('txtNomCargo', 'Ingrese el nombre del cargo');
                } else if (ValueInput('slcCodigo') === '0' && Number(opCaracterJS) === 2) {
                    MuestraError('slcCodigo', 'Seleccione un código');
                } else if (Number(ValueInput('numGrado')) <= 0 && Number(opCaracterJS) === 2) {
                    MuestraError('numGrado', 'El grado no puede ser menor a 0');
                } else if (ValueInput('slcNombramiento') === '0' && Number(opCaracterJS) === 2) {
                    MuestraError('slcNombramiento', 'Seleccione un nombramiento');
                } else if (ValueInput('txtPerfilSiho') === '' && Number(opCaracterJS) === 2) {
                    MuestraError('txtPerfilSiho', 'Ingrese el perfil SIHO');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formGestCargoNom');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    SendPost('../php/controladores/cargos.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableCargosNom.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardaTercero':
                if (ValueInput('buscaTercero') === '') {
                    MuestraError('buscaTercero', 'El nombre del tercero no puede estar vacío');
                } else if (ValueInput('id_tercero') === '0') {
                    MuestraError('id_tercero', 'Seleccione un tercero válido');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formGestTerceroNom');
                    data.append('action', 'add');
                    data.append('tipo', ValueInput('tipoTerceroNom'));
                    SendPost('../php/controladores/terceros.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableTerceroNom.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardaIncSalarial':
                if (Number(ValueInput('numPorcIncSal')) <= 0 || Number(ValueInput('numPorcIncSal')) > 100) {
                    MuestraError('numPorcIncSal', 'El porcentaje debe ser mayor a 0 y menor o igual a 100');
                } else if (ValueInput('datFechaInSal') === '') {
                    MuestraError('datFechaInSal', 'La fecha no puede estar vacía');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formGestIncSalarial');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    SendPost('../php/controladores/incrementos.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableIncSalario.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardaRubroPtoNom':
                if (ValueInput('slcTipo') === '0') {
                    MuestraError('slcTipo', 'Seleccione tipo de rubro');
                } else if (ValueInput('txtRubroAdmin') === '' || ValueInput('idRubroAdmin') === '0' || ValueInput('tp_dato_radm') === '0') {
                    MuestraError('txtRubroAdmin', 'Debe seleccionar un rubro administrativo que sea tipo detalle');
                } else if (ValueInput('txtRubroOpera') === '' || ValueInput('idRubroOpera') === '0' || ValueInput('tp_dato_rope') === '0') {
                    MuestraError('txtRubroOpera', 'Debe seleccionar un rubro operativo que sea tipo detalle');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formGestRubroNom');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    SendPost('../php/controladores/rubros.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableRubroPto.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;
            case 'btnGuardaCtaCtbNom':
                if (ValueInput('slcCcosto') === '0') {
                    MuestraError('slcCcosto', 'Seleccione un centro de costo');
                } else if (ValueInput('slcTipo') === '0') {
                    MuestraError('slcTipo', 'Seleccione un tipo de cuenta');
                } else if (ValueInput('buscaCuenta') === '' || ValueInput('idCtaCtb') === '0' || ValueInput('tipoCta') !== 'D') {
                    MuestraError('buscaCuenta', 'Seleccione una cuenta contable válida');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formGestCtaCtbNom');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    SendPost('../php/controladores/cuentas.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableCtaCtbNom.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
                break;

        }
    }
});

document.getElementById('btnsTerceros').addEventListener('click', function (event) {
    const boton = event.target;
    if (boton && boton.dataset.id) {
        document.getElementById('tipoTerceroNom').value = boton.dataset.id;
        tableTerceroNom.ajax.reload(null, false);
    }
});
