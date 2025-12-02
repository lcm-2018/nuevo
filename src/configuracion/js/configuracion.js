const tableConfiguracion = crearDataTable(
    '#tableConfiguracion',
    'lista_configuracion.php', [
        { data: 'id_consulta' },
        { data: 'nom_consulta' },
        { data: 'des_consulta' },
        { data: 'botones' }
    ], [{
        text: plus,
        className: 'btn btn-success btn-sm shadow',
        titleAttr: 'Agregar nueva consulta',
        action: function(e, dt, node, config) {
            mostrarOverlay();
            VerFormulario('../php/controladores/configuracion.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', '');
        }
    }], {
        pageLength: 25,
        order: [
            [0, 'desc']
        ],
    }
);

// Esperamos que la tabla se inicialice completamente
tableConfiguracion.on('init', function() {
    BuscaDataTable(tableConfiguracion);
});

document.querySelector('#tableConfiguracion').addEventListener('click', function(event) {
    const btnActualizar = event.target.closest('.actualizar');
    const btnEliminar = event.target.closest('.eliminar');

    if (btnActualizar) {
        mostrarOverlay();
        const id = btnActualizar.dataset.id;
        VerFormulario('../php/controladores/configuracion.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', '');
    }

    if (btnEliminar) {
        const id = btnEliminar.dataset.id;
        EliminaRegistro('../php/controladores/configuracion.php', id, tableConfiguracion);
    }
});

document.getElementById('modalForms').addEventListener('click', function(event) {
    const boton = event.target.closest('button');
    if (!boton) return;

    event.preventDefault();
    LimpiaInvalid();
    switch (boton.id) {
        case 'btnGuardaConfiguracion':
            if (ValueInput('txt_id_consulta') === '0') {
                MuestraError('txt_id_consulta', 'Ingrese un dato');
            } else if (Number(ValueInput('txt_nom_consulta')) < 0) {
                MuestraError('txt_nom_consulta', 'Ingrese un dato');
            } else {
                mostrarOverlay();
                var data = Serializa('formConfiguracion');
                data.append('action', data.get('id') == '0' ? 'add' : 'edit');
                SendPost('../php/controladores/configuracion.php', data).then((response) => {
                    if (response.status === 'ok') {
                        mje('Guardado correctamente!');
                        tableConfiguracion.ajax.reload(null, false);
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
});