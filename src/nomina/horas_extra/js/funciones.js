const tableHorasExtra = crearDataTable(
    '#tableHorasExtra',
    'lista_horas_extra.php',
    [
        { data: 'id' },
        { data: 'doc' },
        { data: 'nombre' },
        { data: 'do', className: 'actualizar', },
        { data: 'no', className: 'actualizar', },
        { data: 'rno', className: 'actualizar', },
        { data: 'dd', className: 'actualizar', },
        { data: 'rdd', className: 'actualizar', },
        { data: 'ndf', className: 'actualizar', },
        { data: 'rndf', className: 'actualizar', },
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar hora extra de empleado',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFomulario('../php/controladores/horas_extra.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
            }
        }
    ],
    {
        pageLength: 10,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [2], className: 'text-nowrap' },
            { "orderable": false, "targets": [0, 6] }
        ],
        initComplete: function () {
            var api = this.api();
            $('#filterRow th', api.table().header()).on('click', function (e) {
                e.stopPropagation();
            });

            $('#filterRow th', api.table().header()).on('mousedown', function (e) {
                e.stopPropagation();
            });
            //eliminiar los elementos de #filterRow .dt-column-order
            $('#filterRow .dt-column-order').remove();
        }
    },
    function (d) {
        d.filter_mes = ValueInput('filter_mes');
        d.filter_tipo = ValueInput('filter_tipo');
        d.filter_nodoc = ValueInput('filter_nodoc');
        d.filter_nombre = ValueInput('filter_nombre');
    },
    false
);


tableHorasExtra.on('init', function () {
    BuscaDataTable(tableHorasExtra);
});

if (document.querySelector('#tableHorasExtra tbody')) {
    document.querySelector('#tableHorasExtra tbody').addEventListener('dblclick', function (event) {
        const btnActualizar = event.target.closest('.actualizar');

        if (btnActualizar) {
            // Si ya hay un input activo dentro de la celda, no hacer nada
            if (btnActualizar.querySelector('input')) return;

            const id = btnActualizar.dataset.id;
            const valor = btnActualizar.textContent.trim();

            // Crear input y reemplazar contenido
            const input = document.createElement('input');
            input.type = 'number';
            input.id = 'upHE';
            input.className = 'form-control form-control-sm text-end';
            input.value = valor;

            // Limpiar la celda y poner el input
            btnActualizar.innerHTML = '';
            btnActualizar.appendChild(input);
            input.focus();

            // Cuando pierda el foco
            input.addEventListener('blur', function () {
                const nuevoValor = input.value;
                btnActualizar.textContent = nuevoValor;
                if (nuevoValor !== valor && nuevoValor >= 0) {
                    mostrarOverlay();
                    const data = new FormData();
                    data.append('id', id);
                    data.append('valor', nuevoValor);
                    data.append('mes', ValueInput('filter_mes'));
                    data.append('tipo', ValueInput('filter_tipo'));
                    data.append('action', 'edit');
                    SendPost('../php/controladores/horas_extra.php', data).then((response) => {
                        if (response.msg === 'no') {
                            mjeError('Hora extra ya fue liquidada, no se puede editar');
                        }
                        tableHorasExtra.ajax.reload(null, false);
                    }).finally(() => {
                        ocultarOverlay();
                    });
                }
            });

            // Presionar Enter = terminar edición
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    input.blur();
                    tableHorasExtra.ajax.reload(null, false);
                }
            });
        }
    });
}

document.getElementById('modalForms').addEventListener('click', function (event) {
    const boton = event.target.closest('button');
    if (boton) {
        LimpiaInvalid();
        switch (boton.id) {
            case 'btnGuardaHorasExtra':
                if (ValueInput('txtBuscaEmpleado') === '' && ValueInput('fileHE') === '') {
                    MuestraError('txtBuscaEmpleado', 'Ingrese el empleado');
                } else if (Number(ValueInput('id_empleado')) <= 0 && ValueInput('fileHE') === '') {
                    MuestraError('id_empleado', 'Empleado no válido');
                } else if (ValueInput('datFecInicia') === '' && ValueInput('fileHE') === '') {
                    MuestraError('datFecInicia', 'Ingrese la fecha de inicio');
                } else if (ValueInput('datFecFin') === '' && ValueInput('fileHE') === '') {
                    MuestraError('datFecFin', 'Ingrese la fecha de terminación');
                } else if (ValueInput('datFecInicia') > ValueInput('datFecFin')) {
                    MuestraError('datFecInicia', 'La fecha de inicio no puede ser mayor a la fecha de terminación');
                } else if (Number(ValueInput('numCantidad')) <= 0 && ValueInput('fileHE') === '') {
                    MuestraError('numCantidad', 'Ingrese la cantidad');
                } else if (ValueInput('slcTipoHora') === '0' && ValueInput('fileHE') === '') {
                    MuestraError('slcTipoHora', 'Seleccione el tipo de hora extra');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formHorasExtra');

                    if (ValueInput('fileHE') !== '') {
                        const file = document.getElementById("fileHE").files[0];
                        if (!/\.(csv|txt)$/i.test(file.name)) {
                            MuestraError('fileHE', 'El archivo debe ser un CSV válido, separado por punto y coma (;)');
                            return false;
                        } else if (file.size > 2 * 1024 * 1024) { // 2MB
                            MuestraError('fileHE', 'El archivo no debe ser mayor a 2MB');
                            return false;
                        }
                        data.append('action', 'upload');
                    } else {
                        data.append('action', 'add');
                    }
                    data.append('mes', ValueInput('filter_mes'));
                    data.append('slcTipoLiq', ValueInput('filter_tipo'));
                    SendPost('../php/controladores/horas_extra.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableHorasExtra.ajax.reload(null, false);
                            $('#modalForms').modal('hide');
                        } else {
                            mjeError('Error!', response.msg);
                        }
                    }).finally(() => {
                        ocultarOverlay();
                    });

                }
                break;
            case 'btnFormCsvHorasExtra':
                window.location.href = '../php/controladores/horas_extra.php?action=csv';
        }
    }
});

function cargarHorasExtra(valor) {
    mostrarOverlay();
    tableHorasExtra.ajax.reload(null, false);
    ocultarOverlay();
}