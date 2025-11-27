const tableGeDocs = crearDataTable(
    '#tableGeDocs',
    'lista_docs.php',
    [
        { data: 'id' },
        { data: 'modulo' },
        { data: 'documento' },
        { data: 'version' },
        { data: 'fecha' },
        { data: 'control' },
        { data: 'estado' },
        { data: 'accion' }
    ],
    [
        {
            text: plus,
            className: 'btn btn-success btn-sm shadow',
            titleAttr: 'Agregar responsables de documento',
            action: function (e, dt, node, config) {
                mostrarOverlay();
                VerFormulario('../php/controladores/documentos.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
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
        d.filter_modulo = ValueInput('filter_modulo');
        d.filter_doc = ValueInput('filter_doc');
        d.filter_version = ValueInput('filter_version');
        d.filter_fecha = ValueInput('filter_fecha');
        d.filter_control = ValueInput('filter_control');
        d.filter_estado = ValueInput('filter_estado');
    },
    false
);

tableGeDocs.on('init', function () {
    BuscaDataTable(tableGeDocs);
});

document.getElementById('modalForms').addEventListener('click', function (event) {
    const boton = event.target.closest('button');
    if (boton) {
        LimpiaInvalid();
        switch (boton.id) {
            case 'btnGuardaDctoFte':
                if (ValueInput('slcModulo') === '0') {
                    MuestraError('slcModulo', 'Seleccione un módulo');
                } else if (ValueInput('slcDocFte') === '0') {
                    MuestraError('slcDocFte', 'Seleccione un documento fuente');
                } else if (ValueInput('datFecha') === '') {
                    MuestraError('datFecha', 'Ingrese una fecha válida');
                } else {
                    mostrarOverlay();
                    var data = Serializa('formDctoFte');
                    data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                    SendPost('../php/controladores/documentos.php', data).then((response) => {
                        if (response.status === 'ok') {
                            mje('Guardado correctamente!');
                            tableGeDocs.ajax.reload(null, false);
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

if (document.querySelector('#tableGeDocs')) {
    document.querySelector('#tableGeDocs').addEventListener('click', function (event) {
        const btnActualizar = event.target.closest('.editar');
        const btnEliminar = event.target.closest('.borrar');
        const btnEstado = event.target.closest('.estado');
        const btnDetalle = event.target.closest('.detalles');


        if (btnEstado) {
            const id = btnEstado.dataset.id;
            CambiaEstado('../php/controladores/documentos.php', id, tableGeDocs);
        }

        if (btnActualizar) {
            const id = btnActualizar.dataset.id;
            mostrarOverlay();
            VerFormulario('../php/controladores/documentos.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }

        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            EliminaRegistro('../php/controladores/documentos.php', id, tableGeDocs);
        }
        if (btnDetalle) {
            const id = btnDetalle.dataset.id;
            mostrarOverlay();
            VerFormulario('../php/controladores/detalles.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-xl');
        }
    });
}