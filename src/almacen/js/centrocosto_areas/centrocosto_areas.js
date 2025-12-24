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
        $('#tb_cencos_areas').DataTable({
            dom: setdom,
            buttons: $('#peReg').val() == 1 ? [{
                text: '<span class="fa-solid fa-plus "></span>',
                className: 'btn btn-success btn-sm shadow',
                action: function (e, dt, node, config) {
                    $.post("frm_reg_cencos_areas.php", function (he) {
                        $('#divTamModalForms').removeClass('modal-xl');
                        $('#divTamModalForms').removeClass('modal-sm');
                        $('#divTamModalForms').addClass('modal-lg');
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
                url: 'listar_cencos_areas.php',
                type: 'POST',
                dataType: 'json',
                data: function (data) {
                    data.nom_area = $('#txt_nombre_filtro').val();
                    data.id_cencosto = $('#sl_centrocosto_filtro').val();
                    data.id_sede = $('#sl_sede_filtro').val();
                    data.estado = $('#sl_estado_filtro').val();
                }
            },
            columns: [
                { 'data': 'id_area' }, //Index=0              
                { 'data': 'nom_area' },
                { 'data': 'nom_tipo_area' },
                { 'data': 'nom_centrocosto' },
                { 'data': 'nom_sede' },
                { 'data': 'usr_responsable' },
                { 'data': 'nom_bodega' },
                { 'data': 'estado' },
                { 'data': 'botones' }
            ],
            columnDefs: [
                { class: 'text-wrap', targets: [1, 2] },
                { visible: false, targets: 6 },
                { orderable: false, targets: 8 }
            ],
            order: [
                [0, "desc"]
            ],
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
        });


        $('#tb_cencos_areas').wrap('<div class="overflow"/>');
    });

    //Buascar registros
    $('#btn_buscar_filtro').on("click", function () {
        $('#tb_cencos_areas').DataTable().ajax.reload(null, false);
    });

    $('.filtro').keypress(function (e) {
        if (e.keyCode == 13) {
            $('#tb_cencos_areas').DataTable().ajax.reload(null, false);
        }
    });

    // Autocompletar Usuarios reposnables
    $('#divForms').on("input", "#txt_responsable", function () {
        $(this).autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "../common/cargar_usuariosistema_ls.php",
                    dataType: "json",
                    type: 'POST',
                    data: { term: request.term }
                }).done(function (data) {
                    response(data);
                });
            },
            minLength: 2,
            select: function (event, ui) {
                $('#id_txt_responsable').val(ui.item.id);
            }
        });
    });

    //Editar un registro    
    $('#tb_cencos_areas').on('click', '.btn_editar', function () {
        let id = $(this).attr('value');
        $.post("frm_reg_cencos_areas.php", { id: id }, function (he) {
            $('#divTamModalForms').addClass('modal-lg');
            $('#divModalForms').modal('show');
            $("#divForms").html(he);
        });
    });

    //Guardar registro 
    $('#divForms').on("click", "#btn_guardar", function () {
        $('.is-invalid').removeClass('is-invalid');
        var error = verifica_vacio($('#txt_nom_area'));
        error += verifica_vacio_2($('#id_txt_responsable'), $('#txt_responsable'));
        error += verifica_vacio($('#sl_centrocosto'));
        error += verifica_vacio($('#sl_sede'));
        error += verifica_vacio($('#sl_estado'));

        if (error >= 1) {
            mjeError('Los datos resaltados son obligatorios');
        } else {
            var data = $('#frm_reg_cencos_areas').serialize();
            $.ajax({
                type: 'POST',
                url: 'editar_cencos_areas.php',
                dataType: 'json',
                data: data + "&oper=add"
            }).done(function (r) {
                if (r.mensaje == 'ok') {
                    $('#tb_cencos_areas').DataTable().ajax.reload(null, false);
                    $('#id_area').val(r.id);
                    mje("Proceso realizado correctamente");
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

    //Borrar un registro 
    $('#tb_cencos_areas').on('click', '.btn_eliminar', function () {
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
                    url: 'editar_cencos_areas.php',
                    dataType: 'json',
                    data: { id: id, oper: 'del' }
                }).done(function (r) {

                    if (r.mensaje == 'ok') {
                        $('#tb_cencos_areas').DataTable().ajax.reload(null, false);
                        mje("Proceso realizado correctamente");
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

    //Imprimir registros
    $('#btn_imprime_filtro').on('click', function () {
        $('#tb_cencos_areas').DataTable().ajax.reload(null, false);
        $.post("imp_cencos_areas.php", {
            nom_area: $('#txt_nombre_filtro').val(),
            id_cencosto: $('#sl_centrocosto_filtro').val(),
            id_sede: $('#sl_sede_filtro').val(),
            estado: $('#sl_estado_filtro').val()
        }, function (he) {
            $('#divTamModalImp').removeClass('modal-sm');
            $('#divTamModalImp').removeClass('modal-lg');
            $('#divTamModalImp').addClass('modal-xl');
            $('#divModalImp').modal('show');
            $("#divImp").html(he);
        });
    });

})(jQuery);
