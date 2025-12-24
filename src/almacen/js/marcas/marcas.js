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
        $('#tb_marcas').DataTable({

            dom: setdom,
            buttons: $('#peReg').val() == 1 ? [{
                text: '<span class="fa-solid fa-plus "></span>',
                className: 'btn btn-success btn-sm shadow',
                action: function (e, dt, node, config) {
                    $.post("frm_reg_marcas.php", function (he) {
                        //$('#divTamModalForms').removeClass('modal-xl');
                        //$('#divTamModalForms').removeClass('modal-sm');
                        //$('#divTamModalForms').removeClass('modal-lg');
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
                url: 'listar_marcas.php',
                type: 'POST',
                dataType: 'json',
                data: function (data) {
                    data.nombre = $('#txt_nombre_filtro').val();
                }
            },
            columns: [
                { 'data': 'id' }, //Index=0
                { 'data': 'descripcion' },
                { 'data': 'botones' }
            ],
            columnDefs: [
                { class: 'text-wrap', targets: 1 },
                { orderable: false, targets: 2 }
            ],
            order: [
                [0, "desc"]
            ],
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
        });


        $('#tb_marcas').wrap('<div class="overflow"/>');
    });

    //Buascar registros
    $('#btn_buscar_filtro').on("click", function () {
        $('#tb_marcas').DataTable().ajax.reload(null, false);
    });

    $('.filtro').keypress(function (e) {
        if (e.keyCode == 13) {
            $('#tb_marcas').DataTable().ajax.reload(null, false);
        }
    });

    //Editar un registro    
    $('#tb_marcas').on('click', '.btn_editar', function () {
        let id = $(this).attr('value');
        $.post("frm_reg_marcas.php", { id: id }, function (he) {
            $('#divTamModalForms').addClass('modal-lg');
            $('#divModalForms').modal('show');
            $("#divForms").html(he);
        });
    });

    //Guardar registro 
    $('#divForms').on("click", "#btn_guardar", function () {
        $('.is-invalid').removeClass('is-invalid');
        var error = verifica_vacio($('#txt_nom_marca'));

        if (error >= 1) {
            mjeError('Los datos resaltados son obligatorios');
        } else {
            var data = $('#frm_reg_marcas').serialize();
            $.ajax({
                type: 'POST',
                url: 'editar_marcas.php',
                dataType: 'json',
                data: data + "&oper=add"
            }).done(function (r) {
                if (r.mensaje == 'ok') {
                    $('#tb_marcas').DataTable().ajax.reload(null, false);
                    $('#id_marca').val(r.id);
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

    //Borrarr un registro 
    $('#tb_marcas').on('click', '.btn_eliminar', function () {
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
                    url: 'editar_marcas.php',
                    dataType: 'json',
                    data: { id: id, oper: 'del' }
                }).done(function (r) {

                    if (r.mensaje == 'ok') {
                        $('#tb_marcas').DataTable().ajax.reload(null, false);
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
        $('#tb_marcas').DataTable().ajax.reload(null, false);
        $.post("imp_marcas.php", {
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
