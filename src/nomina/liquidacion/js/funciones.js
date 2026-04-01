// Función para determinar la URL basada en el tipo de liquidación seleccionado
const TIPO_RETROACTIVA = 5;

function getUrlListado() {
    const tipo = ValueInput('filter_tipo');
    if (tipo == 2) {
        return 'lista_liquidacion.php';
    } else if (tipo == TIPO_RETROACTIVA) {
        return 'lista_retroactiva.php';
    } else if (tipo == 3) {
        return 'lista_prestaciones_sociales.php';
    } else if (tipo == 6 || tipo == 7 || tipo == 8 || tipo == 9) {
        return 'lista_cesantias.php';
    } else if (tipo == 4) {
        return 'lista_vacaciones.php';
    }
    return 'lista_liquidacion.php'; // URL por defecto
}

const tableLiqMesEmpleados = crearDataTable(
    '#tableLiqMesEmpleados',
    getUrlListado(),
    [
        { data: 'check' },
        { data: 'doc' },
        { data: 'nombre' },
        { data: 'observacion' },
        { data: 'laborado' },
        { data: 'incapacidad' },
        { data: 'licencia' },
        { data: 'vacacion' },
        { data: 'otro' },
        { data: 'pago' }
    ],
    [{
        text: 'Liquidar',
        className: 'btn btn-success btn-sm shadow',
        titleAttr: 'Liquidar nómina de empleados',
        action: function (e, dt, node, config) {
            const checkboxes = document.querySelectorAll('#bodyTableLiqMesEmpleados input[type="checkbox"]:checked');
            if (checkboxes.length === 0) {
                mjeError('Debe seleccionar al menos un empleado para liquidar.');
            } else {
                let valid = true;
                LimpiaInvalid();
                if (ValueInput('filter_tipo') == TIPO_RETROACTIVA && ValueInput('filter_retroactivo') == 0) {
                    solicitarRetroactivo(true);
                    return;
                }
                if (ValueInput('filter_tipo') == 2 || ValueInput('filter_tipo') == 3) {
                    checkboxes.forEach((checkbox) => {
                        var row = checkbox.closest('tr');
                        var lab = row.querySelector('input[name^="lab"]');
                        var pago = row.querySelector('select[name^="metodo"]');
                        var min = parseFloat(lab.getAttribute('min'));
                        var max = parseFloat(lab.getAttribute('max'));

                        if (lab && pago) {
                            const valLab = parseFloat(lab.value);
                            const valPag = parseFloat(pago.value);

                            if (valLab < min || valLab > max) {
                                lab.classList.add('bg-danger');
                                lab.focus();
                                mjeError('El valor de los días laborados debe estar entre ' + min + ' y ' + max + '.');
                                valid = false;
                            } else if (valPag === 0) {
                                pago.classList.add('bg-danger');
                                pago.focus();
                                mjeError('Debe seleccionar un método de pago.');
                                valid = false;
                            }
                        }
                        if (!valid) {
                            return false;
                        }
                    });
                }
                if (valid) {
                    mostrarOverlay();
                    var data = Serializa('formLiquidacion');
                    data.append('mes', ValueInput('filter_mes'));
                    data.append('tipo', ValueInput('filter_tipo'));
                    data.append('retroactivo', ValueInput('filter_retroactivo'));
                    data.append('action', 'add');
                    SendPost('../php/controladores/liquidacion.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            setTimeout(function () {
                                tableLiqMesEmpleados.ajax.url(getUrlListado()).load(null, false);
                            }, 500);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeAlert('', '', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
            }
        }
    }],
    {
        pageLength: -1,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [2], className: 'text-nowrap' },
            { "orderable": false, "targets": [0, 6] },
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
        d.filter_mes = ValueInput('filter_mes');
        d.filter_tipo = ValueInput('filter_tipo');
        d.filter_retroactivo = ValueInput('filter_retroactivo');
        d.filter_observacion = ValueInput('filter_observacion');
        d.filter_nodoc = ValueInput('filter_nodoc');
        d.filter_nombre = ValueInput('filter_nombre');
    },
    false
);

// Función para filtrar que actualiza la URL dinámicamente antes de recargar
function FiltraLiquidacion() {
    if (ValueInput('filter_tipo') == TIPO_RETROACTIVA && ValueInput('filter_retroactivo') == 0) {
        solicitarRetroactivo();
        return;
    }
    tableLiqMesEmpleados.ajax.url(getUrlListado()).load();
}
tableLiqMesEmpleados.on('draw', function () {
    const filas = document.querySelectorAll('#tableLiqMesEmpleados tbody tr');

    filas.forEach((fila, index) => {
        const celda = fila.cells[4];
        const celdaPago = fila.cells[9];
        if (celda) {
            const input = celda.querySelector('input');
            if (input) {
                input.style.backgroundColor = (index % 2 === 0) ? '#f2f2f2' : '#ffffff';
            }
        }
        if (celdaPago) {
            const selectPago = celdaPago.querySelector('select');
            if (selectPago) {
                selectPago.style.backgroundColor = (index % 2 === 0) ? '#f2f2f2' : '#ffffff';
            }
        }
    });
});

tableLiqMesEmpleados.on('init', function () {
    BuscaDataTable(tableLiqMesEmpleados);
});

function eventFilterTipo(value) {
    var mes = document.getElementById('filter_mes');
    if (value == TIPO_RETROACTIVA) {
        solicitarRetroactivo();
        return;
    } else if (value == 7 || value == 8 || value == 9) {
        mes.value = '12'
    } else if (value == 6) {
        mes.value = '06'
    } else {
        resetRetroactivoSeleccionado();
        mes.value = '0'
    }
    FiltraLiquidacion();
}

function resetRetroactivoSeleccionado() {
    const input = document.getElementById('filter_retroactivo');
    const info = document.getElementById('retroactivoInfo');
    const texto = document.getElementById('retroactivoInfoTexto');
    if (input) {
        input.value = 0;
    }
    if (texto) {
        texto.textContent = '';
    }
    if (info) {
        info.classList.add('d-none');
    }
}

function setRetroactivoSeleccionado(id, texto) {
    const input = document.getElementById('filter_retroactivo');
    const info = document.getElementById('retroactivoInfo');
    const detalle = document.getElementById('retroactivoInfoTexto');
    if (input) {
        input.value = id;
    }
    if (detalle) {
        detalle.textContent = texto;
    }
    if (info) {
        info.classList.remove('d-none');
    }
}

function solicitarRetroactivo(forzarRecarga = false) {
    const data = new FormData();
    data.append('action', 'retroactivos');
    SendPost('../php/controladores/liquidacion.php', data).then((response) => {
        if (response.status !== 'ok' || !Array.isArray(response.data) || response.data.length === 0) {
            resetRetroactivoSeleccionado();
            mjeAlert('', '', response.msg || 'No hay retroactivos activos para seleccionar.');
            return;
        }

        const options = response.data
            .map((item) => `<option value="${item.id_retroactivo}" data-detalle="${item.detalle}">${item.detalle}</option>`)
            .join('');

        document.getElementById('bodyModal').innerHTML = `
            <div class="shadow text-center rounded">
                <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                    <h5 style="color: white;" class="mb-0">SELECCIONAR RETROACTIVO</h5>
                </div>
                <div class="p-3">
                    <div class="mb-3">
                        <label for="slcRetroactivoNomina" class="form-label small text-muted">Retroactivo registrado</label>
                        <select id="slcRetroactivoNomina" class="form-select form-select-sm bg-input">
                            ${options}
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary btn-sm" id="btnSeleccionaRetroactivo">Usar retroactivo</button>
                    </div>
                </div>
            </div>
        `;

        $('#tamModalForms').removeClass('modal-sm modal-xl').addClass('modal-lg');
        $('#modalForms').modal('show');

        document.getElementById('btnSeleccionaRetroactivo').onclick = function () {
            const select = document.getElementById('slcRetroactivoNomina');
            const option = select.options[select.selectedIndex];
            setRetroactivoSeleccionado(select.value, option.getAttribute('data-detalle') || option.text);
            $('#modalForms').modal('hide');
            if (forzarRecarga || ValueInput('filter_tipo') == TIPO_RETROACTIVA) {
                tableLiqMesEmpleados.ajax.url(getUrlListado()).load();
            }
        };
    });
}
