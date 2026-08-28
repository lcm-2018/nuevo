(function ($) {
    "use strict";

    // Variables globales
    const controllerUrl = 'php/controladores/contratos.php';

    // T3.3: Inicializar Editor WYSIWYG (TinyMCE)
    function initEditor(selector) {
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: selector,
                height: 400,
                menubar: false,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | ' +
                    'bold italic backcolor | alignleft aligncenter ' +
                    'alignright alignjustify | bullist numlist outdent indent | ' +
                    'removeformat | help'
            });
        } else {
            console.warn("TinyMCE no está cargado. Se usará un textarea normal.");
        }
    }

    // Funciones AJAX para Contratos (T3.1)
    const ContratosAPI = {
        save: function (dataForm, callback) {
            // Obtener contenido HTML del editor si existe
            if (typeof tinymce !== 'undefined' && tinymce.get('contenido_contrato')) {
                dataForm.append('contenido_html', tinymce.get('contenido_contrato').getContent());
            }

            $.ajax({
                url: controllerUrl,
                type: 'POST',
                data: dataForm,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    if (callback) callback(res);
                },
                error: function (err) {
                    console.error("Error AJAX:", err);
                    if (callback) callback({ status: 'error', msg: 'Error de red o servidor.' });
                }
            });
        },

        // T3.4 & T3.5: Obtener historial y renderizar Diff visual
        renderDiff: function(oldHtml, newHtml, containerId) {
            if (typeof Diff2Html !== 'undefined' && typeof window.Diff !== 'undefined') {
                // Crear diff string usando la librería diff.js (jsdiff)
                let diffString = window.Diff.createPatch('Contrato', oldHtml, newHtml);
                
                // Renderizar usando Diff2Html estilo GitHub
                let diffHtml = Diff2Html.html(diffString, {
                    drawFileList: false,
                    matching: 'lines',
                    outputFormat: 'side-by-side' // Vista estilo GitHub
                });
                
                $('#' + containerId).html(diffHtml);
            } else {
                $('#' + containerId).html('<div class="alert alert-warning">Librerías de Diff no cargadas.</div>');
            }
        }
    };

    // Listeners para botones y formularios
    $(document).ready(function () {
        
        // Al abrir el modal de crear/editar, inicializar editor
        $('#modalContrato').on('shown.bs.modal', function () {
            initEditor('#contenido_contrato');
        });

        $('#modalContrato').on('hidden.bs.modal', function () {
            $('#formContrato')[0].reset();
            $('#id_contrato').val('');
            if (typeof tinymce !== 'undefined' && tinymce.get('contenido_contrato')) {
                tinymce.remove('#contenido_contrato');
            }
        });

        // T3.3: Lógica de Usar como Plantilla (Clonar)
        $(document).on('click', '.btn-plantilla', function() {
            let rowData = $('#table_contratos').DataTable().row($(this).parents('tr')).data();
            if(rowData) {
                $('#id_contrato').val(''); // Vacío para que sea 'add'
                $('#numero_contrato').val(''); // Vacío para que ingrese uno nuevo
                $('#id_tercero').val(rowData.id_tercero);
                $('#fecha_inicio').val(rowData.fecha_inicio);
                $('#valor').val(rowData.valor);
                
                // Si tienes la versión más reciente en rowData, se podría inyectar aquí.
                // Como no viene en el listado básico, deberíamos obtener el HTML vía AJAX 
                // o decirle al usuario que redacte sobre el original (simulado para este paso).
                $('#contenido_contrato').val("Cargando plantilla del contrato ID " + rowData.id_contrato + "...");
                
                $('#modalContrato').modal('show');
            }
        });

        // Guardar contrato
        $('#formContrato').on('submit', function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            formData.append('action', formData.get('id_contrato') ? 'edit' : 'add');
            
            ContratosAPI.save(formData, function(res) {
                if (res.status === 'ok') {
                    // Refrescar tabla (T3.2)
                    $('#table_contratos').DataTable().ajax.reload();
                    $('#modalContrato').modal('hide');
                    alert(res.msg); // O usar SweetAlert
                } else {
                    alert('Error: ' + res.msg);
                }
            });
        });
    });

    // Exponer API globalmente si es necesario
    window.ContratosAPI = ContratosAPI;

})(jQuery);
