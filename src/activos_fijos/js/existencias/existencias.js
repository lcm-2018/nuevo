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
        $('#tb_activos').DataTable({
            language: dataTable_es,
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: 'listar_existencias.php',
                type: 'POST',
                dataType: 'json',
                data: function (data) {
                    data.id_sede = $('#sl_sede_filtro').val();
                    data.id_area = $('#sl_area_filtro').val();
                    data.codigo = $('#txt_codigo_filtro').val();
                    data.nombre = $('#txt_nombre_filtro').val();
                    data.id_subgrupo = $('#sl_subgrupo_filtro').val();
                    data.con_existencia = $('#sl_conexi_filtro').val();
                    data.art_activo = $('#chk_artact_filtro').is(':checked') ? 1 : 0;
                }
            },
            columns: [
                { 'data': 'id_med' }, //Index=0
                { 'data': 'cod_medicamento' },
                { 'data': 'nom_medicamento' },
                { 'data': 'nom_subgrupo' },
                { 'data': 'existencia' },
                { 'data': 'val_promedio' },
                { 'data': 'val_total' },
                { 'data': 'val_ult_compra' },
                { 'data': 'estado' },
                { 'data': 'botones' }
            ],
            columnDefs: [
                { class: 'text-wrap', targets: [2, 3] },
                { orderable: false, targets: [9] }
            ],
            order: [
                [2, "ASC"]
            ],
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
        });

        $('#tb_activos').wrap('<div class="overflow"/>');
    });

    //Buascar registros de Articulos
    $('#sl_sede_filtro').on("change", function () {
        $('#sl_area_filtro').load('../common/cargar_areas_sede.php', { id_sede: $(this).val(), titulo: '--Area--' }, function () { });
    });
    $('#sl_sede_filtro').trigger('change');

    //Buascar registros de Articulos
    $('#btn_buscar_filtro').on("click", function () {
        $('#tb_activos').DataTable().ajax.reload(null, false);
    });

    $('.filtro').keypress(function (e) {
        if (e.keyCode == 13) {
            $('#tb_activos').DataTable().ajax.reload(null, false);
        }
    });

    //Examinar los Activos Fijos
    $('#tb_activos').on('click', '.btn_examinar', function () {
        let id = $(this).attr('value');
        $.post("frm_activos_fijos.php", {
            id: id,
            id_sede: $('#sl_sede_filtro').val(),
            id_area: $('#sl_area_filtro').val(),
        }, function (he) {
            $('#divTamModalForms').addClass('modal-xl');
            $('#divModalForms').modal('show');
            $("#divForms").html(he);
        });
    });

    /* ---------------------------------------------------
    IMPRESORA
    -----------------------------------------------------*/
    //Imprimir listado de registros

    $('#btn_imprime_filtro').on('click', function () {
        $('#tb_activos').DataTable().ajax.reload(null, false);
        $('.is-invalid').removeClass('is-invalid');
        let id_reporte = $('#sl_tipo_reporte').val();
        let reporte = "imp_existencias.php";

        switch (id_reporte) {
            case '1':
                reporte = "imp_existencias_as.php";
                break;
            case '2':
                reporte = "imp_existencias_asa.php";
                break;
        }

        $.post(reporte, {
            id_sede: $('#sl_sede_filtro').val(),
            id_area: $('#sl_area_filtro').val(),
            codigo: $('#txt_codigo_filtro').val(),
            nombre: $('#txt_nombre_filtro').val(),
            id_subgrupo: $('#sl_subgrupo_filtro').val(),
            con_existencia: $('#sl_conexi_filtro').val(),
            art_activo: $('#chk_artact_filtro').is(':checked') ? 1 : 0,
            id_reporte: id_reporte,
        }, function (he) {
            $('#divTamModalImp').removeClass('modal-sm');
            $('#divTamModalImp').removeClass('modal-lg');
            $('#divTamModalImp').addClass('modal-xl');
            $('#divModalImp').modal('show');
            $("#divImp").html(he);
        });
    });

})(jQuery);