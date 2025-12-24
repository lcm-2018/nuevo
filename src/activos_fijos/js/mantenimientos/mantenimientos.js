(function ($) {
    $(document).on('show.bs.modal', '.modal', function () {
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function () {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });

    $(document).ready(function () {
        //Tabla de Registros
        $('#tb_mantenimientos').DataTable({

            dom: setdom,
            buttons: $('#peReg').val() == 1 ? [{
                text: '<span class="fa-solid fa-plus "></span>',
                className: 'btn btn-success btn-sm shadow',
                action: function (e, dt, node, config) {
                    $.post("frm_reg_mantenimiento.php", function (he) {
                        $('#divTamModalForms').removeClass('modal-sm');
                        $('#divTamModalForms').removeClass('modal-lg');
                        $('#divTamModalForms').addClass('modal-xl');
                        $('#divModalForms').modal('show');
                        $("#divForms").html(he);
                    });
                }
            }] : [],
            language: dataTable_es,
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: 'listar_mantenimientos.php',
                type: 'POST',
                dataType: 'json',
                data: function (data) {
                    data.id_mantenimiento = $('#txt_idmantenimiento_filtro').val();
                    data.fec_ini = $('#txt_fecini_filtro').val();
                    data.fec_fin = $('#txt_fecfin_filtro').val();
                    data.id_tercero = $('#sl_tercero_filtro').val();
                    data.id_tipo_mant = $('#sl_tipomantenimiento_filtro').val();
                    data.estado = $('#sl_estado_filtro').val();
                }
            },
            columns: [
                { 'data': 'id_mantenimiento' }, //Index=0
                { 'data': 'fec_mantenimiento' },
                { 'data': 'hor_mantenimiento' },
                { 'data': 'tipo_mantenimiento' },
                { 'data': 'observaciones' },
                { 'data': 'nom_responsable' },
                { 'data': 'nom_tercero' },
                { 'data': 'fec_ini_mantenimiento' },
                { 'data': 'fec_fin_mantenimiento' },
                { 'data': 'estado' },
                { 'data': 'nom_estado' },
                { 'data': 'botones' }
            ],
            columnDefs: [
                { class: 'text-wrap', targets: [4, 5, 6] },
                { visible: false, targets: 9 },
                { orderable: false, targets: 11 }
            ],
            rowCallback: function (row, data) {
                if (data.estado == 1) {
                    $($(row).find("td")[0]).css("background-color", "yellow");
                } else if (data.estado == 2) {
                    $($(row).find("td")[0]).css("background-color", "PaleTurquoise");
                } else if (data.estado == 3) {
                    $($(row).find("td")[0]).css("background-color", "DodgerBlue");
                } else if (data.estado == 0) {
                    $($(row).find("td")[0]).css("background-color", "gray");
                }
            },
            order: [
                [0, "desc"]
            ],
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
        });


        $('#tb_mantenimientos').wrap('<div class="overflow"/>');
    });

    //Buascar registros de Ingresos
    $('#btn_buscar_filtro').on("click", function () {
        $('.is-invalid').removeClass('is-invalid');
        $('#tb_mantenimientos').DataTable().ajax.reload(null, false);
    });

    $('.filtro').keypress(function (e) {
        if (e.keyCode == 13) {
            $('#tb_mantenimientos').DataTable().ajax.reload(null, false);
        }
    });

    // Autocompletar Terceros
    $('#divForms').on("input", "#txt_tercero", function () {
        $(this).autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "../common/cargar_terceros_ls.php",
                    dataType: "json",
                    type: 'POST',
                    data: { term: request.term }
                }).done(function (data) {
                    response(data);
                });
            },
            minLength: 2,
            select: function (event, ui) {
                $('#id_txt_tercero').val(ui.item.id);
            }
        });
    });

    //Editar un registro Orden Ingreso
    $('#tb_mantenimientos').on('click', '.btn_editar', function () {
        let id = $(this).attr('value');
        $.post("frm_reg_mantenimiento.php", { id: id }, function (he) {
            $('#divTamModalForms').addClass('modal-xl');
            $('#divModalForms').modal('show');
            $("#divForms").html(he);
        });
    });

    //Guardar registro Orden mantenimiento
    $('#divForms').on("click", "#btn_guardar", function () {
        $('.is-invalid').removeClass('is-invalid');

        var error = verifica_vacio($('#sl_tip_mant'));
        error += verifica_vacio($('#sl_responsable'));
        error += verifica_vacio($('#txt_fec_ini_mant'));
        error += verifica_vacio($('#txt_fec_fin_mant'));
        error += verifica_vacio($('#txt_observaciones_mant'));

        if (error >= 1) {
            mjeError('Los datos resaltados son obligatorios');
        } else {
            var data = $('#frm_reg_mantenimiento').serialize();
            $.ajax({
                type: 'POST',
                url: 'editar_mantenimiento.php',
                dataType: 'json',
                data: data + "&oper=add"
            }).done(function (r) {
                if (r.mensaje == 'ok') {
                    $('#tb_mantenimientos').DataTable().ajax.reload(null, false);
                    $('#id_mantenimiento').val(r.id);
                    $('#txt_id_mant').val(r.id);

                    $('#btn_aprobar').prop('disabled', false);
                    $('#btn_imprimir').prop('disabled', false);

                    mje("Proceso realizado con éxito");
                } else {
                    mjeError(r.mensaje);
                }
            }).always(
                function () { }
            ).fail(function (xhr, textStatus, errorThrown) {
                console.error(xhr.responseText)
                alert('Ocurrió un error');
            });
        }
    });

    //Borrar Orden de mantenimiento
    $('#tb_mantenimientos').on('click', '.btn_eliminar', function () {
        let id = $(this).attr('value');
        Swal.fire({
            title: "¿Está seguro de eliminar el registro?",
            text: "No podrá revertir esta acción",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si, eliminar",
            cancelButtonText: "Cancelar",
        }).then((result) => {
            if (result.isConfirmed) {
                mostrarOverlay();
                $.ajax({
                    type: 'POST',
                    url: 'editar_mantenimiento.php',
                    dataType: 'json',
                    data: { id: id, oper: 'del' }
                }).done(function (r) {

                    if (r.mensaje == 'ok') {
                        $('#tb_mantenimientos').DataTable().ajax.reload(null, false);
                        mje("Proceso realizado con éxito");
                    } else {
                        mjeError(r.mensaje);
                    }
                }).always(function () {

                }).fail(function (xhr, textStatus, errorThrown) {
                    console.error(xhr.responseText)
                    alert('Ocurrió un error');
                });
            }
        });
    });

    //Aprobar orden de mantenimiento
    $('#divForms').on("click", "#btn_aprobar", function () {
        confirmar_proceso('mantenimiento_aprob');
    });
    $('#divModalConfDel').on("click", "#mantenimiento_aprob", function () {
        $.ajax({
            type: 'POST',
            url: 'editar_mantenimiento.php',
            dataType: 'json',
            data: { id: $('#id_mantenimiento').val(), oper: 'aprob' }
        }).done(function (r) {

            if (r.mensaje == 'ok') {
                $('#tb_mantenimientos').DataTable().ajax.reload(null, false);

                $('#estado').val('APROBADO');

                $('#btn_guardar').prop('disabled', true);
                $('#btn_aprobar').prop('disabled', true);
                $('#btn_ejecutar').prop('disabled', false);
                $('#btn_cerrar').prop('disabled', true);
                $('#btn_anular').prop('disabled', false);

                mje("Proceso realizado con éxito");
            } else {
                mjeError(r.mensaje);
            }
        }).always(function () {
            ocultarOverlay();
        }).fail(function () {
            alert('Ocurrió un error');
        });
    });

    //Ejecutar orden de mantenimiento
    $('#divForms').on("click", "#btn_ejecutar", function () {
        confirmar_proceso('mantenimiento_ejecu');
    });
    $('#divModalConfDel').on("click", "#mantenimiento_ejecu", function () {
        $.ajax({
            type: 'POST',
            url: 'editar_mantenimiento.php',
            dataType: 'json',
            data: { id: $('#id_mantenimiento').val(), oper: 'ejecu' }
        }).done(function (r) {

            if (r.mensaje == 'ok') {
                $('#tb_mantenimientos').DataTable().ajax.reload(null, false);

                $('#estado').val('EN EJECUCION');

                $('#btn_guardar').prop('disabled', true);
                $('#btn_aprobar').prop('disabled', true);
                $('#btn_ejecutar').prop('disabled', true);
                $('#btn_cerrar').prop('disabled', false);
                $('#btn_anular').prop('disabled', true);

                mje("Proceso realizado con éxito");
            } else {
                mjeError(r.mensaje);
            }
        }).always(function () {
            ocultarOverlay();
        }).fail(function () {
            alert('Ocurrió un error');
        });
    });

    //Cerrar orden de mantenimiento
    $('#divForms').on("click", "#btn_cerrar", function () {
        confirmar_proceso('mantenimiento_close');
    });
    $('#divModalConfDel').on("click", "#mantenimiento_close", function () {
        $.ajax({
            type: 'POST',
            url: 'editar_mantenimiento.php',
            dataType: 'json',
            data: { id: $('#id_mantenimiento').val(), oper: 'close' }
        }).done(function (r) {

            if (r.mensaje == 'ok') {
                $('#tb_mantenimientos').DataTable().ajax.reload(null, false);

                $('#estado').val('CERRADO');

                $('#btn_guardar').prop('disabled', true);
                $('#btn_aprobar').prop('disabled', true);
                $('#btn_ejecutar').prop('disabled', true);
                $('#btn_cerrar').prop('disabled', true);
                $('#btn_anular').prop('disabled', true);

                mje("Proceso realizado con éxito");
            } else {
                mjeError(r.mensaje);
            }
        }).always(function () {
            ocultarOverlay();
        }).fail(function () {
            alert('Ocurrió un error');
        });
    });

    //Anular orden de mantenimiento
    $('#divForms').on("click", "#btn_anular", function () {
        confirmar_proceso('mantenimiento_annul');
    });
    $('#divModalConfDel').on("click", "#mantenimiento_annul", function () {
        $.ajax({
            type: 'POST',
            url: 'editar_mantenimiento.php',
            dataType: 'json',
            data: { id: $('#id_mantenimiento').val(), oper: 'annul' }
        }).done(function (r) {

            if (r.mensaje == 'ok') {
                $('#tb_mantenimientos').DataTable().ajax.reload(null, false);

                $('#estado').val('EN EJECUCION');

                $('#btn_guardar').prop('disabled', true);
                $('#btn_aprobar').prop('disabled', true);
                $('#btn_ejecutar').prop('disabled', true);
                $('#btn_cerrar').prop('disabled', true);
                $('#btn_anular').prop('disabled', true);

                mje("Proceso realizado con éxito");
            } else {
                mjeError(r.mensaje);
            }
        }).always(function () {
            ocultarOverlay();
        }).fail(function () {
            alert('Ocurrió un error');
        });
    });

    /* ---------------------------------------------------
    DETALLES
    -----------------------------------------------------*/

    $('#divModalBus').on('dblclick', '#tb_activos_fijos tr', function () {
        let data = $('#tb_activos_fijos').DataTable().row(this).data();
        $.post("frm_reg_mantenimiento_detalle.php", { id_acf: data.id_activo_fijo }, function (he) {
            $('#divTamModalReg').removeClass('modal-sm');
            $('#divTamModalReg').removeClass('modal-xl');
            $('#divTamModalReg').addClass('modal-lg');
            $('#divModalReg').modal('show');
            $("#divFormsReg").html(he);
        });
    });

    $('#divForms').on('click', '#tb_mantenimientos_detalles .btn_editar', function () {
        let id = $(this).attr('value');
        $.post("frm_reg_mantenimiento_detalle.php", { id: id }, function (he) {
            $('#divTamModalReg').removeClass('modal-sm');
            $('#divTamModalReg').removeClass('modal-xl');
            $('#divTamModalReg').addClass('modal-lg');
            $('#divModalReg').modal('show');
            $("#divFormsReg").html(he);
        });
    });

    //Guardar registro Detalle
    $('#divFormsReg').on("click", "#btn_guardar_detalle", function () {
        $('.is-invalid').removeClass('is-invalid');

        var error = verifica_vacio($('#txt_placa'));
        error += verifica_vacio($('#txt_observaciones'));

        if (error >= 1) {
            mjeError('Los datos resaltados son obligatorios');
        } else {
            var data = $('#frm_reg_mantenimiento_detalle').serialize();
            $.ajax({
                type: 'POST',
                url: 'editar_mantenimiento_detalle.php',
                dataType: 'json',
                data: data + "&id_mantenimiento=" + $('#id_mantenimiento').val() + '&oper=add'
            }).done(function (r) {
                if (r.mensaje == 'ok') {
                    $('#tb_mantenimientos_detalles').DataTable().ajax.reload(null, false);

                    $('#id_mant_detalle').val(r.id);

                    $('#divModalReg').modal('hide');
                    mje("Proceso realizado con éxito");
                } else {
                    mjeError(r.mensaje);
                }
            }).always(function () { }).fail(function (xhr, textStatus, errorThrown) {
                console.error(xhr.responseText)
                alert('Error al guardar detalle');
            });
        }
    });

    //Borrar registro detalle
    $('#divForms').on('click', '#tb_mantenimientos_detalles .btn_eliminar', function () {
        let id = $(this).attr('value');
        Swal.fire({
            title: "¿Está seguro de eliminar el registro?",
            text: "No podrá revertir esta acción",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si, eliminar",
            cancelButtonText: "Cancelar",
        }).then((result) => {
            if (result.isConfirmed) {
                mostrarOverlay();
                $.ajax({
                    type: 'POST',
                    url: 'editar_mantenimiento_detalle.php',
                    dataType: 'json',
                    data: { id: id, id_mantenimiento: $('#id_mantenimiento').val(), oper: 'del' }
                }).done(function (r) {

                    if (r.mensaje == 'ok') {
                        $('#tb_mantenimientos_detalles').DataTable().ajax.reload(null, false);

                        mje("Proceso realizado con éxito");
                    } else {
                        mjeError(r.mensaje);
                    }
                }).always(function () {
                    ocultarOverlay();
                }).fail(function (xhr, textStatus, errorThrown) {
                    console.error(xhr.responseText)
                });
            }
        });
    });
    //Imprimir listado de registros
    $('#btn_imprime_filtro').on('click', function () {
        $('#tb_mantenimientos').DataTable().ajax.reload(null, false);
        $('.is-invalid').removeClass('is-invalid');
        var verifica = verifica_vacio($('#txt_fecini_filtro'));
        verifica += verifica_vacio($('#txt_fecfin_filtro'));
        if (verifica >= 1) {
            mjeError('Debe especificar un rango de fechas');
        } else {
            $.post("imp_mantenimientos.php", {
                id_mantenimiento: $('#txt_idmantenimiento_filtro').val(),
                fec_ini: $('#txt_fecini_filtro').val(),
                fec_fin: $('#txt_fecfin_filtro').val(),
                id_tercero: $('#sl_tercero_filtro').val(),
                id_tipo_mant: $('#sl_tipomantenimiento_filtro').val(),
                estado: $('#sl_estado_filtro').val()
            }, function (he) {
                $('#divTamModalImp').removeClass('modal-sm');
                $('#divTamModalImp').removeClass('modal-lg');
                $('#divTamModalImp').addClass('modal-xl');
                $('#divModalImp').modal('show');
                $("#divImp").html(he);
            });
        }
    });

    //Imprimit un Mantenimiento
    $('#divForms').on("click", "#btn_imprimir", function () {
        $.post("imp_mantenimiento.php", {
            id: $('#id_mantenimiento').val()
        }, function (he) {
            $('#divTamModalImp').removeClass('modal-sm');
            $('#divTamModalImp').removeClass('modal-lg');
            $('#divTamModalImp').addClass('modal-xl');
            $('#divModalImp').modal('show');
            $("#divImp").html(he);
        });
    });

})(jQuery);