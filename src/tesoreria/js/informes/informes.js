(function ($) {
    //---- ejemplos de ocasionar disparador - trigger en los select y los botones
    //$('#sl_centroCosto').trigger('change');
    //$('#btn_buscar_filtro').trigger('click');

    //otra opcion de llamar botones
    /*$('#btn_imprimir').on("click", function()
    {
        alert("imprimiendo!.....");
    });*/

    $('#frm_libros_aux_tesoreria').on("click", "#btn_consultar", function () {
        $tipo_libro = $('#sl_tipo_libro').val();
        mostrarOverlay();
        if ($tipo_libro == 1) {
            $.post(ValueInput('host') + '/src/tesoreria/php/informes/imp_rel_causacion.php', {
                fec_ini: $('#txt_fecini').val(),
                fec_fin: $('#txt_fecfin').val()
            }, function (he) {
                $('#divTamModalImp').removeClass('modal-sm');
                $('#divTamModalImp').removeClass('modal-lg');
                $('#divTamModalImp').addClass('modal-xl');
                $('#divModalImp').modal('show');
                $("#divImp").html(he);
            }).always(function () {
                ocultarOverlay();
            });
        }
        else {
            $.post(ValueInput('host') + '/src/tesoreria/php/informes/imp_rel_compr_egresos.php', {
                fec_ini: $('#txt_fecini').val(),
                fec_fin: $('#txt_fecfin').val()
            }, function (he) {
                $('#divTamModalImp').removeClass('modal-sm');
                $('#divTamModalImp').removeClass('modal-lg');
                $('#divTamModalImp').addClass('modal-xl');
                $('#divModalImp').modal('show');
                $("#divImp").html(he);
            }).always(function () {
                ocultarOverlay();
            });
        }
    });
    $('#areaReporte').on('click', '#btnExcelEntrada', function () {
        let encoded = btoa($('#areaImprimir').html());
        $('<form action="php/informes/reporte_excel.php" method="post"><input type="hidden" name="xls" value="' + encoded + '" /></form>').appendTo('body').submit();
    });
    document.addEventListener("keyup", (e) => {
        if (e.target.id == "terceromov") {
            $("#terceromov").autocomplete({
                source: function (request, response) {
                    mostrarOverlay();
                    $.ajax({
                        url: "datos/consultar/buscar_terceros.php",
                        type: "POST",
                        dataType: "json",
                        data: {
                            term: request.term,
                        },
                        success: function (data) {
                            response(data);
                        },
                    }).always(() => {
                        ocultarOverlay();
                    });
                },
                select: function (event, ui) {
                    $("#terceromov").val(ui.item.label);
                    $("#id_tercero").val(ui.item.id);
                    return false;
                },
                focus: function (event, ui) {
                    $("#terceromov").val(ui.item.label);
                    return false;
                },
            });
        }
    });
})(jQuery);