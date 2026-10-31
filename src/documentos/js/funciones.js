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
                VerFormulario('../php/controladores/documentos.php', 'form', 0, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-xl');
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
                    if (typeof tinymce !== 'undefined' && tinymce.get('head_custom_html')) {
                        tinymce.get('head_custom_html').save();
                    }
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
            VerFormulario('../php/controladores/documentos.php', 'form', id, 'modalForms', 'bodyModal', 'tamModalForms', 'modal-xl');
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

// ==========================================
// ==========================================
// EDITOR HTML LIBRE CON VISTA PREVIA EN VIVO
// ==========================================

// Plantillas base de HTML que el usuario puede cargar con 1 clic
const PLANTILLAS_ENCABEZADO = {
    clasica: `<table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
    <tbody>
        <tr>
            <td style="width: 25%; text-align: left; vertical-align: middle;">
                {LOGO}
            </td>
            <td style="width: 75%; text-align: center; vertical-align: middle;">
                <div style="font-size: 11px; font-weight: bold; text-transform: uppercase; color: #444;">REPÚBLICA DE COLOMBIA</div>
                <div style="font-size: 14px; font-weight: bold; text-transform: uppercase; color: #000;">{NOMBRE_EMPRESA}</div>
                <div style="font-size: 11px; font-weight: bold; color: #333;">NIT {NIT}</div>
                <div style="font-size: 10px; font-style: italic; color: #666;">Sistema de Gestión Presupuestal y Financiera</div>
            </td>
        </tr>
    </tbody>
</table>`,

    calidad: `<table style="width: 100%; border-collapse: collapse; margin-bottom: 5px; border: 1px solid #333;">
    <tbody>
        <tr>
            <td style="width: 20%; text-align: center; vertical-align: middle; border-right: 1px solid #333; padding: 5px;">
                {LOGO}
            </td>
            <td style="width: 60%; text-align: center; vertical-align: middle; border-right: 1px solid #333; padding: 5px;">
                <div style="font-size: 10px; font-weight: bold; text-transform: uppercase; color: #555;">REPÚBLICA DE COLOMBIA</div>
                <div style="font-size: 13px; font-weight: bold; text-transform: uppercase; color: #000;">{NOMBRE_EMPRESA}</div>
                <div style="font-size: 10px; font-weight: bold; color: #333;">NIT {NIT}</div>
                <div style="font-size: 11px; font-weight: bold; color: #000; margin-top: 2px;">GESTIÓN FINANCIERA Y PRESUPUESTAL</div>
            </td>
            <td style="width: 20%; vertical-align: middle; padding: 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
                    <tbody>
                        <tr style="border-bottom: 1px solid #333;">
                            <td style="background-color: #f2f2f2; font-weight: bold; padding: 2px 4px; border-right: 1px solid #333; width: 45%;">CÓDIGO:</td>
                            <td style="padding: 2px 4px; text-align: center;">{CODIGO}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #333;">
                            <td style="background-color: #f2f2f2; font-weight: bold; padding: 2px 4px; border-right: 1px solid #333;">VERSIÓN:</td>
                            <td style="padding: 2px 4px; text-align: center;">{VERSION_DOC}</td>
                        </tr>
                        <tr>
                            <td style="background-color: #f2f2f2; font-weight: bold; padding: 2px 4px; border-right: 1px solid #333;">FECHA:</td>
                            <td style="padding: 2px 4px; text-align: center;">{FECHA_DOC}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>`,

    centrado: `<div style="text-align: center; margin-bottom: 5px;">
    <div style="margin-bottom: 4px;">{LOGO}</div>
    <div style="font-size: 11px; font-weight: bold; text-transform: uppercase; color: #444;">REPÚBLICA DE COLOMBIA</div>
    <div style="font-size: 14px; font-weight: bold; text-transform: uppercase; color: #000;">{NOMBRE_EMPRESA}</div>
    <div style="font-size: 11px; font-weight: bold; color: #333;">NIT {NIT}</div>
    <div style="font-size: 10px; color: #666; margin-top: 2px;">Vigilado Supersalud</div>
</div>`,

    doble_logo: `<table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
    <tbody>
        <tr>
            <td style="width: 20%; text-align: left; vertical-align: middle;">
                {LOGO}
            </td>
            <td style="width: 60%; text-align: center; vertical-align: middle; padding: 0 5px;">
                <div style="font-size: 11px; font-weight: bold; text-transform: uppercase; color: #444;">REPÚBLICA DE COLOMBIA</div>
                <div style="font-size: 14px; font-weight: bold; text-transform: uppercase; color: #000;">{NOMBRE_EMPRESA}</div>
                <div style="font-size: 11px; font-weight: bold; color: #333;">NIT {NIT}</div>
                <div style="font-size: 10px; color: #555;">Alcaldía Municipal</div>
            </td>
            <td style="width: 20%; text-align: right; vertical-align: middle;">
                {LOGO}
            </td>
        </tr>
    </tbody>
</table>`
};

window.insertarTagHtml = function (tag) {
    const textarea = document.getElementById('head_custom_html');
    if (!textarea) return;
    const start = textarea.selectionStart || 0;
    const end = textarea.selectionEnd || 0;
    const text = textarea.value;
    textarea.value = text.substring(0, start) + tag + text.substring(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + tag.length;
    actualizarPreview();
};

window.aplicarPlantillaRapida = function (tipo) {
    const textarea = document.getElementById('head_custom_html');
    if (!textarea) return;
    if (tipo === 'limpiar') {
        textarea.value = '';
    } else if (PLANTILLAS_ENCABEZADO[tipo]) {
        textarea.value = PLANTILLAS_ENCABEZADO[tipo];
    }
    textarea.focus();
    actualizarPreview();
};

window.actualizarPreview = function () {
    const textarea = document.getElementById('head_custom_html');
    const previewBox = document.getElementById('previewEncabezadoContenedor');
    if (!previewBox) return;

    const htmlContent = textarea ? textarea.value : '';
    if (!htmlContent || !htmlContent.trim()) {
        previewBox.innerHTML = '<div class="text-center text-muted p-4"><i>El encabezado está vacío. Escribe tu código HTML o selecciona una plantilla arriba.</i></div>';
        return;
    }

    const host = document.getElementById('host')?.value || '';
    const empNombre = document.getElementById('empresa_nombre_preview')?.value || 'IPS MUNICIPAL DE IPIALES ESE';
    const empNit = document.getElementById('empresa_nit_preview')?.value || '900190473 - 9';
    const version = document.getElementById('txtVersion')?.value || '1.0';
    const fecha = document.getElementById('datFecha')?.value || '';
    const slcDoc = document.getElementById('slcDocFte');
    let codDoc = 'CDP';
    if (slcDoc && slcDoc.selectedOptions && slcDoc.selectedOptions[0]) {
        const txt = slcDoc.selectedOptions[0].text;
        codDoc = txt.split(' - ')[0] || 'CDP';
    }

    const imgLogo = `<img src="${host}/assets/images/logo.png" style="max-width: 140px; height: auto;" alt="Logo" onerror="this.onerror=null;this.src='../../assets/images/logo.png';">`;

    let renderHtml = htmlContent
        .replace(/{LOGO}/gi, imgLogo)
        .replace(/{NOMBRE_EMPRESA}/gi, empNombre)
        .replace(/{NIT}/gi, empNit)
        .replace(/{VERSION_DOC}/gi, version)
        .replace(/{VERSION}/gi, version)
        .replace(/{FECHA_DOC}/gi, fecha)
        .replace(/{FECHA}/gi, fecha)
        .replace(/{CODIGO}/gi, codDoc)
        .replace(/{DOC_FTE}/gi, codDoc);

    previewBox.innerHTML = renderHtml;
};

// Eventos para actualizar la vista previa al abrir el modal o cambiar a la pestaña
$(document).on('shown.bs.modal', '#modalForms', function () {
    actualizarPreview();
});

$(document).on('shown.bs.tab', 'button[data-bs-target="#tab-encabezado"]', function () {
    actualizarPreview();
});