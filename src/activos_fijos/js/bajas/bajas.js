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
        $('#tb_bajas').DataTable({

            dom: setdom,
            buttons: $('#peReg').val() == 1 ? [{
                text: '<span class="fa-solid fa-plus "></span>',
                className: 'btn btn-success btn-sm shadow',
                action: function (e, dt, node, config) {
                    $.post("frm_reg_bajas.php", function (he) {
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
                url: 'listar_bajas.php',
                type: 'POST',
                dataType: 'json',
                data: function (data) {
                    data.id_baja = $('#txt_id_baja_filtro').val();
                    data.fec_ini = $('#txt_fecini_filtro').val();
                    data.fec_fin = $('#txt_fecfin_filtro').val();
                    data.estado = $('#sl_estado_filtro').val();
                }
            },
            columns: [
                { 'data': 'id_baja' }, //Index=0
                { 'data': 'fec_baja' },
                { 'data': 'hor_baja' },
                { 'data': 'observaciones' },
                { 'data': 'estado' },
                { 'data': 'nom_estado' },
                { 'data': 'botones' }
            ],
            columnDefs: [
                { class: 'text-wrap', targets: 3 },
                { visible: false, targets: 4 },
                { orderable: false, targets: 6 }
            ],
            rowCallback: function (row, data) {
                if (data.estado == 1) {
                    $($(row).find("td")[0]).css("background-color", "yellow");
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


        $('#tb_bajas').wrap('<div class="overflow"/>');
    });

    //Buscar registros de baja
    $('#btn_buscar_filtro').on("click", function () {
        $('.is-invalid').removeClass('is-invalid');
        $('#tb_bajas').DataTable().ajax.reload(null, false);
    });

    $('.filtro').keypress(function (e) {
        if (e.keyCode == 13) {
            $('#tb_bajas').DataTable().ajax.reload(null, false);
        }
    });

    //Editar un registro baja
    $('#tb_bajas').on('click', '.btn_editar', function () {
        let id = $(this).attr('value');
        $.post("frm_reg_bajas.php", { id: id }, function (he) {
            $('#divTamModalForms').addClass('modal-xl');
            $('#divModalForms').modal('show');
            $("#divForms").html(he);
        });
    });

    //Guardar registro baja
    $('#divForms').on("click", "#btn_guardar", function () {
        $('.is-invalid').removeClass('is-invalid');

        var error = verifica_vacio($('#txt_obs_baja'));

        if (error >= 1) {
            mjeError('Los datos resaltados son obligatorios');
        } else {
            var data = $('#frm_reg_bajas').serialize();
            $.ajax({
                type: 'POST',
                url: 'editar_bajas.php',
                dataType: 'json',
                data: data + "&oper=add"
            }).done(function (r) {
                if (r.mensaje == 'ok') {
                    $('#tb_bajas').DataTable().ajax.reload(null, false);
                    $('#id_baja').val(r.id);
                    $('#txt_ide').val(r.id);

                    $('#btn_cerrar').prop('disabled', false);
                    $('#btn_imprimir').prop('disabled', false);

                    mje("Proceso realizado con éxito");
                } else {
                    mjeError(r.mensaje);
                }
            }).always(function () {
                ocultarOverlay();
            }).fail(function () {
                alert('Ocurrió un error');
            });
        }
    });

    //Borrar un registro baja
    $('#tb_bajas').on('click', '.btn_eliminar', function () {
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
                    url: 'editar_bajas.php',
                    dataType: 'json',
                    data: { id: id, oper: 'del' }
                }).done(function (r) {

                    if (r.mensaje == 'ok') {
                        $('#tb_bajas').DataTable().ajax.reload(null, false);
                        mje("Proceso realizado con éxito");
                    } else {
                        mjeError(r.mensaje);
                    }
                }).always(function () {
                    ocultarOverlay();
                }).fail(function () {
                    alert('Ocurrió un error');
                });
            }
        });
    });

    //Cerrar un registro baja
    $('#divForms').on("click", "#btn_cerrar", function () {
        let id = $(this).attr('value');
        Swal.fire({
            title: "¿Confirmar Acción?",
            text: "No podrá revertir esta acción",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si",
            cancelButtonText: "No",
        }).then((result) => {
            if (result.isConfirmed) {
                mostrarOverlay();
                $.ajax({
                    type: 'POST',
                    url: 'editar_bajas.php',
                    dataType: 'json',
                    data: { id: $('#id_baja').val(), oper: 'close' }
                }).done(function (r) {

                    if (r.mensaje == 'ok') {
                        $('#tb_bajas').DataTable().ajax.reload(null, false);

                        $('#txt_est_baja').val('CERRADO');

                        $('#btn_guardar').prop('disabled', true);
                        $('#btn_cerrar').prop('disabled', true);
                        $('#btn_anular').prop('disabled', false);

                        mje("Proceso realizado con éxito");
                    } else {
                        mjeError(r.mensaje);
                    }
                }).always(function () {
                    ocultarOverlay();
                });
            }
        });
    });

    //Anular un registro baja
    $('#divForms').on("click", "#btn_anular", function () {
        let id = $(this).attr('value');
        Swal.fire({
            title: "¿Confirmar Acción?",
            text: "No podrá revertir esta acción",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si",
            cancelButtonText: "No",
        }).then((result) => {
            if (result.isConfirmed) {
                mostrarOverlay();
                $.ajax({
                    type: 'POST',
                    url: 'editar_bajas.php',
                    dataType: 'json',
                    data: { id: $('#id_baja').val(), oper: 'annul' }
                }).done(function (r) {

                    if (r.mensaje == 'ok') {
                        $('#tb_bajas').DataTable().ajax.reload(null, false);

                        $('#txt_est_baja').val('ANULADO');

                        $('#btn_guardar').prop('disabled', true);
                        $('#btn_cerrar').prop('disabled', true);
                        $('#btn_anular').prop('disabled', true);

                        mje("Proceso realizado con éxito");
                    } else {
                        mjeError(r.mensaje);
                    }
                }).always(function () {
                    ocultarOverlay();
                });
            }
        });
    });

    /* ---------------------------------------------------
    DETALLES
    -----------------------------------------------------*/
    $('#divModalBus').on('dblclick', '#tb_activos_fijos tr', function () {
        let id_acf = $(this).find('td:eq(0)').text();
        $.post("frm_reg_bajas_detalles.php", { id_acf: id_acf }, function (he) {
            $('#divTamModalReg').addClass('modal-lg');
            $('#divModalReg').modal('show');
            $("#divFormsReg").html(he);

        });
    });

    $('#divForms').on('click', '#tb_bajas_detalles .btn_editar', function () {
        let id = $(this).attr('value');
        $.post("frm_reg_bajas_detalles.php", { id: id }, function (he) {
            $('#divTamModalReg').addClass('modal-lg');
            $('#divModalReg').modal('show');
            $("#divFormsReg").html(he);
        });
    });

    //Guardar registro Detalle
    $('#divFormsReg').on("click", "#btn_guardar_detalle", function () {
        $('.is-invalid').removeClass('is-invalid');

        var error = verifica_vacio($('#txt_obs_baja'));

        if (error >= 1) {
            mjeError('Los datos resaltados son obligatorios');
        } else {
            var data = $('#frm_reg_bajas_detalles').serialize();
            $.ajax({
                type: 'POST',
                url: 'editar_bajas_detalles.php',
                dataType: 'json',
                data: data + "&id_baja=" + $('#id_baja').val() + '&oper=add'
            }).done(function (r) {
                if (r.mensaje == 'ok') {
                    $('#tb_bajas_detalles').DataTable().ajax.reload(null, false);

                    $('#id_detalle').val(r.id);

                    $('#divModalReg').modal('hide');
                    mje("Proceso realizado con éxito");
                } else {
                    mjeError(r.mensaje);
                }
            }).always(function () {
                ocultarOverlay();
            }).fail(function () {
                alert('Ocurrió un error');
            });
        }
    });

    //Borrarr un registro Detalle
    $('#divForms').on('click', '#tb_bajas_detalles .btn_eliminar', function () {
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
                    url: 'editar_bajas_detalles.php',
                    dataType: 'json',
                    data: { id: id, id_baja: $('#id_baja').val(), oper: 'del' }
                }).done(function (r) {
                    if (r.mensaje == 'ok') {
                        $('#tb_bajas_detalles').DataTable().ajax.reload(null, false);

                        mje("Proceso realizado con éxito");
                    } else {
                        mjeError(r.mensaje);
                    }
                }).always(function () {
                    ocultarOverlay();
                }).fail(function () {
                    alert('Ocurrió un error');
                });
            }
        });
    });

    //Imprimir listado de registros
    $('#btn_imprime_filtro').on('click', function () {
        $('#tb_bajas').DataTable().ajax.reload(null, false);
        $('.is-invalid').removeClass('is-invalid');
        var verifica = verifica_vacio($('#txt_fecini_filtro'));
        verifica += verifica_vacio($('#txt_fecfin_filtro'));
        if (verifica >= 1) {
            mjeError('Debe especificar un rango de fechas');
        } else {
            $.post("imp_bajas.php", {
                id_baja: $('#txt_id_baja_filtro').val(),
                fec_ini: $('#txt_fecini_filtro').val(),
                fec_fin: $('#txt_fecfin_filtro').val(),
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

    //Imprimit un baja
    $('#divForms').on("click", "#btn_imprimir", function () {
        $.post("imp_baja.php", {
            id: $('#id_baja').val()
        }, function (he) {
            $('#divTamModalImp').removeClass('modal-sm');
            $('#divTamModalImp').removeClass('modal-lg');
            $('#divTamModalImp').addClass('modal-xl');
            $('#divModalImp').modal('show');
            $("#divImp").html(he);
        });
    });

})(jQuery);