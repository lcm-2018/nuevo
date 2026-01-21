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
        $('#tb_cuentas').DataTable({
            dom: setdom,
            buttons: $('#peReg').val() == 1 ? [{
                text: '<span class="fa-solid fa-plus "></span>',
                className: 'btn btn-success btn-sm shadow',
                action: function (e, dt, node, config) {
                    mostrarOverlay();
                    $.post("frm_reg_cuentas_fac.php", function (he) {
                        $('#divTamModalForms').removeClass('modal-xl');
                        $('#divTamModalForms').removeClass('modal-sm');
                        $('#divTamModalForms').addClass('modal-lg');
                        $('#divModalForms').modal('show');
                        $("#divForms").html(he);
                        ocultarOverlay();
                    });
                }
            }] : [],
            language: dataTable_es,
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: 'listar_cuentas_fac.php',
                type: 'POST',
                dataType: 'json',
                data: function (data) {
                    data.nombre = $('#txt_nombre_filtro').val();
                }
            },
            columns: [
                { 'data': 'id_homo' }, //Index=0
                { 'data': 'nom_regimen' },
                { 'data': 'nom_cobertura' },
                { 'data': 'nom_modalidad' },
                { 'data': 'fecha_vigencia' },
                { 'data': 'cta_presupuesto' },
                { 'data': 'cta_presupuesto_ant' },
                { 'data': 'cta_debito' },
                { 'data': 'cta_credito' },
                { 'data': 'cta_copago' },
                { 'data': 'cta_copago_capitado' },
                { 'data': 'cta_glosaini_debito' },
                { 'data': 'cta_glosaini_credito' },
                { 'data': 'cta_glosadefinitiva' },
                { 'data': 'cta_devolucion' },
                { 'data': 'cta_devolucion_anterior' },
                { 'data': 'cta_caja' },
                { 'data': 'cta_fac_global' },
                { 'data': 'cta_x_ident' },
                { 'data': 'cta_baja' },
                { 'data': 'vigente' },
                { 'data': 'estado' },
                { 'data': 'botones' }
            ],
            columnDefs: [
                { class: 'text-wrap', targets: [2, 3] },
                { visible: false, targets: 20 },
                { orderable: false, targets: 22 },
            ],
            rowCallback: function (row, data, index) {
                if (data.vigente == 'X') {
                    $($(row).find("td")[0]).css("background-color", "#ffc107");
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
        $('#tb_cuentas').wrap('<div class="overflow"/>');
    });

    //Buascar registros
    $('#btn_buscar_filtro').on("click", function () {
        $('#tb_cuentas').DataTable().ajax.reload(null, false);
    });

    $('.filtro').keypress(function (e) {
        if (e.keyCode == 13) {
            $('#tb_cuentas').DataTable().ajax.reload(null, false);
        }
    });

    //Editar un registro    
    $('#tb_cuentas').on('click', '.btn_editar', function () {
        let id = $(this).attr('value');
        mostrarOverlay();
        $.post("frm_reg_cuentas_fac.php", { id: id }, function (he) {
            $('#divTamModalForms').addClass('modal-lg');
            $('#divModalForms').modal('show');
            $("#divForms").html(he);
            ocultarOverlay();
        });
    });

    $('#divForms').on("input", "#sl_regimen", function () {
        $('#sl_cobertura').find('option').prop('disabled', true).hide();
        $('#sl_modalidad').find('option').prop('disabled', true).hide();
        let id = $(this).val();
        let cobertura = $('#sl_cobertura').find('option:selected').val();
        let modalidad = $('#sl_modalidad').find('option:selected').val();
        if (id == 1 || id == 2) {
            $('#sl_cobertura').find('option[value=""],option[value="1"]').prop('disabled', false).show();
            if (!(cobertura == "1")) {
                $('#sl_cobertura').val('')
            }
            $('#sl_modalidad').find('option').prop('disabled', false).show();
        } else if (id == 3) {
            $('#sl_cobertura').find('option[value=""],option[value="8"],option[value="9"]').prop('disabled', false).show();
            if (!(cobertura == "8" || cobertura == "9")) {
                $('#sl_cobertura').val('')
            }
            $('#sl_modalidad').find('option[value=""],option[value="4"]').prop('disabled', false).show();
            if (!(modalidad == "4")) {
                $('#sl_modalidad').val('')
            }
        } else if (id == 4) {
            $('#sl_cobertura').find('option[value=""],option[value="15"]').prop('disabled', false).show();
            if (!(cobertura == "15")) {
                $('#sl_cobertura').val('')
            }
            $('#sl_modalidad').find('option[value=""],option[value="4"]').prop('disabled', false).show();
            if (!(modalidad == "4")) {
                $('#sl_modalidad').val('')
            }
        } else if (id == 5) {
            $('#sl_cobertura').find('option').not('[value=""],[value="1"],[value="8"],[value="15"]').prop('disabled', false).show();
            if (cobertura == "1" || cobertura == "8" || cobertura == "15") {
                $('#sl_cobertura').val('')
            }
            $('#sl_modalidad').find('option').prop('disabled', false).show();
        }
        if ($('#sl_cobertura').find('option:selected').val() == "8") {
            $('#sl_cobertura').val('')
        }
    });

    // Autocompletar cuenta contable presupuesto
    $('#divForms').on("input", ".cuenta_pre", function () {
        $(this).autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "../common/cargar_cta_presupuesto_ls.php",
                    dataType: "json",
                    type: 'POST',
                    data: { term: request.term }
                }).done(function (data) {
                    response(data);
                });
            },
            minLength: 2,
            select: function (event, ui) {
                var that = $(this);
                if (ui.item.tipo == 1 || ui.item.id == '') {
                    $('#' + that.attr('data-campoid')).val(ui.item.id);
                } else {
                    $('#' + that.attr('data-campoid')).val('-1');
                    mjeError('Debe seleccionar una cuenta tipo detalle');
                }
            },
        });
    });

    // Autocompletar cuenta contable 
    $('#divForms').on("input", ".cuenta", function () {
        $(this).autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "../common/cargar_cta_contable_ls.php",
                    dataType: "json",
                    type: 'POST',
                    data: { term: request.term }
                }).done(function (data) {
                    response(data);
                });
            },
            minLength: 2,
            select: function (event, ui) {
                var that = $(this);
                if (ui.item.tipo == 'D' || ui.item.id == '') {
                    $('#' + that.attr('data-campoid')).val(ui.item.id);
                } else {
                    $('#' + that.attr('data-campoid')).val('-1');
                    mjeError('Debe seleccionar una cuenta tipo detalle');
                }
            },
        });
    });

    //Guardar registro 
    $('#divForms').on("click", "#btn_guardar", function () {
        $('.is-invalid').removeClass('is-invalid');
        var error = verifica_vacio($('#sl_regimen'));
        error += verifica_vacio($('#sl_cobertura'));
        error += verifica_vacio($('#sl_modalidad'));
        error += verifica_vacio_2($('#id_txt_cta_pre'), $('#txt_cta_pre'));
        error += verifica_vacio_2($('#id_txt_cta_pre_ant'), $('#txt_cta_pre_ant'));
        error += verifica_vacio_2($('#id_txt_cta_deb'), $('#txt_cta_deb'));
        error += verifica_vacio_2($('#id_txt_cta_cre'), $('#txt_cta_cre'));
        error += verifica_vacio_2($('#id_txt_cta_cop'), $('#txt_cta_cop'));
        error += verifica_vacio_2($('#id_txt_cta_cop_cap'), $('#txt_cta_cop_cap'));
        error += verifica_vacio_2($('#id_txt_cta_gli_deb'), $('#txt_cta_gli_deb'));
        error += verifica_vacio_2($('#id_txt_cta_gli_cre'), $('#txt_cta_gli_cre'));
        error += verifica_vacio_2($('#id_txt_cta_glo_def'), $('#txt_cta_glo_def'));
        error += verifica_vacio_2($('#id_txt_cta_dev'), $('#txt_cta_dev'));
        error += verifica_vacio_2($('#id_txt_cta_dev_ant'), $('#txt_cta_dev_ant'));
        error += verifica_vacio_2($('#id_txt_cta_caj'), $('#txt_cta_caj'));
        error += verifica_vacio_2($('#id_txt_cta_fac_glo'), $('#txt_cta_fac_glo'));
        error += verifica_vacio_2($('#id_txt_cta_x_ide'), $('#txt_cta_x_ide'));
        error += verifica_vacio_2($('#id_txt_cta_baja'), $('#txt_cta_baja'));
        error += verifica_vacio($('#txt_fec_vig'));
        error += verifica_vacio($('#sl_estado'));

        var error1 = verifica_valmin_2($('#id_txt_cta_pre'), $('#txt_cta_pre'), 0)
        error1 += verifica_valmin_2($('#id_txt_cta_pre_ant'), $('#txt_cta_pre_ant'), 0);
        error1 += verifica_valmin_2($('#id_txt_cta_deb'), $('#txt_cta_deb'), 0);
        error1 += verifica_valmin_2($('#id_txt_cta_cre'), $('#txt_cta_cre'), 0);
        error1 += verifica_valmin_2($('#id_txt_cta_cop'), $('#txt_cta_cop'), 0);
        error1 += verifica_valmin_2($('#id_txt_cta_cop_cap'), $('#txt_cta_cop_cap'), 0);
        error1 += verifica_valmin_2($('#id_txt_cta_gli_deb'), $('#txt_cta_gli_deb'), 0);
        error1 += verifica_valmin_2($('#id_txt_cta_gli_cre'), $('#txt_cta_gli_cre'), 0);
        error1 += verifica_valmin_2($('#id_txt_cta_glo_def'), $('#txt_cta_glo_def'), 0);
        error1 += verifica_valmin_2($('#id_txt_cta_dev'), $('#txt_cta_dev'), 0);
        error1 += verifica_valmin_2($('#id_txt_cta_dev_ant'), $('#txt_cta_dev_ant'), 0);
        error1 += verifica_valmin_2($('#id_txt_cta_caj'), $('#txt_cta_caj'), 0);
        error1 += verifica_valmin_2($('#id_txt_cta_fac_glo'), $('#txt_cta_fac_glo'), 0);
        error1 += verifica_valmin_2($('#id_txt_cta_x_ide'), $('#txt_cta_x_ide'), 0);
        error1 += verifica_valmin_2($('#id_txt_cta_baja'), $('#txt_cta_baja'), 0);

        if (error >= 1) {
            mjeError('Los datos resaltados son obligatorios');
        } else if (error1 >= 1) {
            mjeError('Todas las cuentas deben ser tipo detalle');
        } else {
            var data = $('#frm_reg_cuentas_fac').serialize();
            mostrarOverlay();
            $.ajax({
                type: 'POST',
                url: 'editar_cuentas_fac.php',
                dataType: 'json',
                data: data + "&oper=add"
            }).done(function (r) {
                if (r.mensaje == 'ok') {
                    $('#tb_cuentas').DataTable().ajax.reload(null, false);
                    $('#id_cuentafac').val(r.id);
                    mje("Proceso realizado con éxito");
                } else {
                    mjeError(r.mensaje);
                }
            }).fail(function () {
                mjeError('Ocurrió un error');
            }).always(function () {
                ocultarOverlay();
            });
        }
    });

    //Borrarr un registro 
    $('#tb_cuentas').on('click', '.btn_eliminar', function () {
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
                const formData = new FormData();
                formData.append("id", id);
                formData.append("oper", "del");
                mostrarOverlay();
                fetch("editar_cuentas_fac.php", {
                    method: "POST",
                    body: formData,
                })
                    .then((response) => response.json())
                    .then((response) => {
                        if (response.mensaje == "ok") {
                            mje("Registro eliminado exitosamente");
                            $('#tb_cuentas').DataTable().ajax.reload(null, false);
                        } else {
                            mjeError("Error al eliminar");
                        }
                    })
                    .catch((error) => {
                        console.log("Error:");
                    }).finally(() => {
                        ocultarOverlay();
                    });
            }
        });
    });

    //Imprimir registros
    $('#btn_imprime_filtro').on('click', function () {
        $('#tb_cuentas').DataTable().ajax.reload(null, false);
        $.post("imp_cuentas_fac.php", {
            nombre: $('#txt_nombre_filtro').val()
        }, function (he) {
            $('#divTamModalImp').removeClass('modal-sm');
            $('#divTamModalImp').removeClass('modal-lg');
            $('#divTamModalImp').addClass('modal-xl');
            $('#divModalImp').modal('show');
            $("#divImp").html(he);
        });
    });

})(jQuery);