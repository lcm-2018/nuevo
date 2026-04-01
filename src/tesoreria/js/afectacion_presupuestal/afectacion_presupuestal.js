(function ($) {
    'use strict';

    var EVENT_NS = '.afectacionPresupuestal';
    var tablaRubrosInstance = null;

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------
    function getIdPtoRad() {
        return parseInt($.trim($('#hd_id_pto_rad').val()), 10) || 0;
    }

    function getIdPtoRec() {
        return parseInt($.trim($('#hd_id_pto_rec').val()), 10) || 0;
    }

    function limpiarRubroSeleccionado() {
        $('#hd_id_txt_rubro').val('0');
        $('#hd_tipo_dato').val('');
        $('#txt_rubro').removeData('selectedLabel');
    }

    // -------------------------------------------------------
    // Autocomplete de rubro
    // -------------------------------------------------------
    function inicializarAutocompleteRubro() {
        var $inputRubro = $('#txt_rubro');
        if (!$inputRubro.length) {
            return;
        }

        // Destruir instancia previa si existe
        if ($inputRubro.data('ui-autocomplete')) {
            $inputRubro.autocomplete('destroy');
        }

        $inputRubro.data('selectedLabel', $inputRubro.val());

        $inputRubro.autocomplete({
            minLength: 2,
            source: function (request, response) {
                $.ajax({
                    url: ValueInput('host') + '/src/tesoreria/php/afectacion_presupuestal/buscar_rubros.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { term: request.term },
                    success: function (data) {
                        response($.isArray(data) ? data : []);
                    },
                    error: function () {
                        response([]);
                    }
                });
            },
            select: function (event, ui) {
                $('#txt_rubro').val(ui.item.label);
                $('#txt_rubro').data('selectedLabel', ui.item.label);
                $('#hd_id_txt_rubro').val(ui.item.id);
                $('#hd_tipo_dato').val(ui.item.tipo_dato);
                $('#hd_anio').val(ui.item.anio);
                return false;
            },
            focus: function (event, ui) {
                $('#txt_rubro').val(ui.item.label);
                return false;
            }
        });
    }

    // -------------------------------------------------------
    // DataTable de rubros
    // -------------------------------------------------------
    function inicializarTablaRubros() {
        var $tabla = $('#tb_rubros');
        if (!$tabla.length) {
            return;
        }

        if ($.fn.DataTable.isDataTable('#tb_rubros')) {
            $tabla.DataTable().destroy();
            tablaRubrosInstance = null;
        }

        tablaRubrosInstance = $tabla.DataTable({
            dom: "<'row'<'col-md-6'l><'col-md-6'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            language: dataTable_es,
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: ValueInput('host') + '/src/tesoreria/php/afectacion_presupuestal/listar_rubros.php',
                type: 'POST',
                dataType: 'json',
                data: function (d) {
                    d.id_pto_rad = getIdPtoRad();
                    return d;
                }
            },
            columns: [
                { data: 'id_pto_rad_det' },
                { data: 'rubro' },
                { data: 'valor' },
                { data: 'botones', orderable: false, searchable: false }
            ],
            columnDefs: [
                { className: 'text-wrap', targets: [1] }
            ],
            order: [[0, 'desc']],
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO']
            ]
        });

        if (!$tabla.parent().hasClass('overflow')) {
            $tabla.wrap('<div class="overflow"></div>');
        }
    }

    function recargarTablaRubros() {
        var $tabla = $('#tb_rubros');
        if (!$tabla.length) { return; }

        if ($.fn.DataTable.isDataTable('#tb_rubros')) {
            var dt = $tabla.DataTable();
            dt.columns.adjust();
            dt.ajax.reload(null, false);
        } else {
            inicializarTablaRubros();
        }
    }

    // -------------------------------------------------------
    // Inicialización del módulo (espera a que el DOM esté listo)
    // -------------------------------------------------------
    function inicializarModulo(intentos) {
        intentos = intentos || 0;
        var tieneTabla  = $('#tb_rubros').length > 0;
        var tieneRubro  = $('#txt_rubro').length > 0;

        if (!tieneTabla || !tieneRubro) {
            if (intentos < 20) {
                setTimeout(function () {
                    inicializarModulo(intentos + 1);
                }, 150);
            }
            return;
        }

        inicializarTablaRubros();
        inicializarAutocompleteRubro();

        // Si el modal ya está visible cuando se carga el script (caso habitual con $.getScript)
        // esperamos un tick para que Bootstrap termine de mostrar el modal
        setTimeout(function () {
            recargarTablaRubros();
        }, 200);
    }

    inicializarModulo();

    // -------------------------------------------------------
    // Evento shown del modal — por si se reabre
    // -------------------------------------------------------
    $('#divModalReg')
        .off('shown.bs.modal' + EVENT_NS)
        .on('shown.bs.modal' + EVENT_NS, function () {
            if ($('#tb_rubros').length) {
                recargarTablaRubros();
            }
        });

    // -------------------------------------------------------
    // Botón Guardar encabezado
    // -------------------------------------------------------
    $('#divFormsReg')
        .off('click' + EVENT_NS, '#btn_guardar_encabezado')
        .on('click' + EVENT_NS, '#btn_guardar_encabezado', function () {
            var id_pto_rad = getIdPtoRad();
            var oper = (id_pto_rad === 0) ? 'add' : 'edit';
            var datos = $('#frm_afectacion_presupuestal').serialize();
            var url = ValueInput('host') + '/src/tesoreria/php/afectacion_presupuestal/editar_afectacion_presupuestal.php';

            $.ajax({
                type: 'POST',
                url: url,
                dataType: 'json',
                data: datos + '&oper=' + oper
            }).done(function (r) {
                if (r && r.mensaje === 'ok') {
                    if (r.id) {
                        $('#hd_id_pto_rad').val(r.id);
                    }
                    if (r.id2) {
                        $('#hd_id_pto_rec').val(r.id2);
                    }
                    mje('Encabezado guardado correctamente');
                    recargarTablaRubros();
                } else {
                    mjeError((r && r.mensaje) ? r.mensaje : 'Ocurrio un error al guardar');
                }
            }).fail(function (xhr) {
                mjeError('Error de comunicacion: ' + xhr.status);
            });
        });

    // -------------------------------------------------------
    // Botón Agregar rubro (detalle)
    // -------------------------------------------------------
    $('#divFormsReg')
        .off('click' + EVENT_NS, '#btn_agregar_rubro')
        .on('click' + EVENT_NS, '#btn_agregar_rubro', function () {
            var id_pto_rad = getIdPtoRad();
            var id_pto_rec = getIdPtoRec();
            var tipo_dato  = $.trim($('#hd_tipo_dato').val());
            var id_rubro   = parseInt($.trim($('#hd_id_txt_rubro').val()), 10) || 0;
            var valor      = $.trim($('#txt_valor').val());

            if (id_pto_rad === 0) {
                mjeError('Primero debe guardar el encabezado de la afectacion presupuestal');
                return;
            }
            if (id_pto_rec === 0) {
                mjeError('El encabezado no tiene registro REC asociado. Guarde nuevamente el encabezado.');
                return;
            }
            if (id_rubro === 0 || tipo_dato === '' || tipo_dato === '0') {
                mjeError('Debe seleccionar un rubro valido de la lista');
                return;
            }
            if (valor === '') {
                mjeError('El valor no puede estar vacio');
                return;
            }

            var datos = $('#frm_afectacion_presupuestal').serialize();
            var url   = ValueInput('host') + '/src/tesoreria/php/afectacion_presupuestal/editar_detalles_rubros.php';

            $.ajax({
                type: 'POST',
                url: url,
                dataType: 'json',
                data: datos + '&oper=add'
            }).done(function (r) {
                if (r && r.mensaje === 'ok') {
                    if (r.id_pto_rad) { $('#hd_id_pto_rad').val(r.id_pto_rad); }
                    if (r.id_pto_rec) { $('#hd_id_pto_rec').val(r.id_pto_rec); }
                    $('#txt_rubro').val('');
                    $('#txt_valor').val('');
                    limpiarRubroSeleccionado();
                    setTimeout(recargarTablaRubros, 100);
                    mje('Rubro agregado');
                } else {
                    mjeError((r && r.mensaje) ? r.mensaje : 'No fue posible agregar el rubro');
                }
            }).fail(function (xhr) {
                mjeError('Error de comunicacion: ' + xhr.status);
            });
        });

    // -------------------------------------------------------
    // Botón eliminar rubro (delegado en tabla)
    // -------------------------------------------------------
    $('#divFormsReg')
        .off('click' + EVENT_NS, '.btn_eliminar_rubro')
        .on('click' + EVENT_NS, '.btn_eliminar_rubro', function () {
            var id = $(this).attr('value');
            if (!id || parseInt(id, 10) <= 0) {
                mjeError('Registro no valido');
                return;
            }
            $.ajax({
                type: 'POST',
                url: ValueInput('host') + '/src/tesoreria/php/afectacion_presupuestal/editar_detalles_rubros.php',
                dataType: 'json',
                data: { id: id, oper: 'del' }
            }).done(function (r) {
                if (r && r.mensaje === 'ok') {
                    recargarTablaRubros();
                } else {
                    mjeError((r && r.mensaje) ? r.mensaje : 'No fue posible eliminar el rubro');
                }
            }).fail(function () {
                mjeError('Error de comunicacion al eliminar');
            });
        });

    // -------------------------------------------------------
    // Reinicializar autocomplete al hacer foco en el campo
    // -------------------------------------------------------
    $('#divFormsReg')
        .off('focus' + EVENT_NS, '#txt_rubro')
        .on('focus' + EVENT_NS, '#txt_rubro', function () {
            inicializarAutocompleteRubro();
        });

    // Limpiar selección si el usuario borra el texto manualmente
    $('#divFormsReg')
        .off('input' + EVENT_NS, '#txt_rubro')
        .on('input' + EVENT_NS, '#txt_rubro', function () {
            var selectedLabel = $(this).data('selectedLabel') || '';
            if ($(this).val() !== selectedLabel) {
                limpiarRubroSeleccionado();
            }
        });

})(jQuery);
