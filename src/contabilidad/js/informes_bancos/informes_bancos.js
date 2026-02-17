(function ($) {
    $('#frm_libros_aux_bancos').on("click", "#btn_consultar", function () {
        if ($('#id_txt_cuentainicial').val() == "" || $('#id_txt_cuentafinal').val() == "") {
            mjeError("Debe seleccionar la cuenta inicial y la cuenta final");
        }
        else {
            mostrarOverlay();
            $.post(ValueInput('host') + '/src/contabilidad/php/informes_bancos/imp_libros_bancos.php', {
                id_cuenta_ini: $('#id_txt_cuentainicial').val(),
                id_cuenta_fin: $('#id_txt_cuentafinal').val(),
                fec_ini: $('#txt_fecini').val(),
                fec_fin: $('#txt_fecfin').val(),
                id_tipo_doc: $('#sl_tipo_documento').val(),
                id_tercero: $('#id_txt_tercero').val()
            }, function (he) {
                $('#divTamModalImp').removeClass('modal-sm');
                $('#divTamModalImp').removeClass('modal-lg');
                $('#divTamModalImp').addClass('modal-xl');
                $('#divModalImp').modal('show');
                $("#divImp").html(he);
                ocultarOverlay();
            });
        }
    });
    $('#frm_libros_aux_bancos').on("click", "#btn_csv", function () {
        if ($('#id_txt_cuentainicial').val() == "" || $('#id_txt_cuentafinal').val() == "") {
            mjeError("Debe seleccionar la cuenta inicial y la cuenta final");
        } else {
            // Mostrar overlay de carga
            mostrarOverlay();

            // Crear un iframe oculto para la descarga
            let iframeName = 'iframe_download_' + new Date().getTime();
            let iframe = $('<iframe>', {
                name: iframeName,
                id: iframeName,
                style: 'display:none'
            });
            $('body').append(iframe);

            // Crear un form temporal para enviar el POST al iframe
            let form = $('<form>', {
                method: 'POST',
                action: ValueInput('host') + '/src/contabilidad/php/informes_bancos/imp_libros_bancos_excel.php',
                target: iframeName
            });

            form.append($('<input>', { type: 'hidden', name: 'id_cuenta_ini', value: $('#id_txt_cuentainicial').val() }));
            form.append($('<input>', { type: 'hidden', name: 'id_cuenta_fin', value: $('#id_txt_cuentafinal').val() }));
            form.append($('<input>', { type: 'hidden', name: 'fec_ini', value: $('#txt_fecini').val() }));
            form.append($('<input>', { type: 'hidden', name: 'fec_fin', value: $('#txt_fecfin').val() }));
            form.append($('<input>', { type: 'hidden', name: 'id_tipo_doc', value: $('#sl_tipo_documento').val() }));
            form.append($('<input>', { type: 'hidden', name: 'id_tercero', value: $('#id_txt_tercero').val() }));

            $('body').append(form);
            form.submit();

            // Ocultar overlay después de un breve delay (tiempo para que inicie la descarga)
            setTimeout(function () {
                ocultarOverlay();
                form.remove();
                iframe.remove();
            }, 2000); // 2 segundos es suficiente para que inicie la descarga
        }
    });
})(jQuery);

//buscar con 2 letras cuentas y terceros 
document.addEventListener("keyup", (e) => {
    if (e.target.id == "txt_cuentainicial") {
        $("#txt_cuentainicial").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: ValueInput('host') + '/src/contabilidad/php/informes_bancos/buscar_cuentas.php',
                    type: "POST",
                    dataType: "json",
                    data: {
                        term: request.term,
                    },
                    success: function (data) {
                        response(data);
                    },
                });
            },
            select: function (event, ui) {
                $("#txt_cuentainicial").val(ui.item.label);
                $("#id_txt_cuentainicial").val(ui.item.id);
                return false;
            },
            focus: function (event, ui) {
                $("#txt_cuentainicial").val(ui.item.label);
                return false;
            },
        });
    }
    if (e.target.id == "txt_cuentafinal") {
        $("#txt_cuentafinal").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: ValueInput('host') + '/src/contabilidad/php/informes_bancos/buscar_cuentas.php',
                    type: "POST",
                    dataType: "json",
                    data: {
                        term: request.term,
                    },
                    success: function (data) {
                        response(data);
                    },
                });
            },
            select: function (event, ui) {
                $("#txt_cuentafinal").val(ui.item.label);
                $("#id_txt_cuentafinal").val(ui.item.id);
                return false;
            },
            focus: function (event, ui) {
                $("#txt_cuentafinal").val(ui.item.label);
                return false;
            },
        });
    }
    if (e.target.id == "txt_tercero_filtro") {
        $("#txt_tercero_filtro").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: ValueInput('host') + '/src/contabilidad/php/informes_bancos/buscar_terceros.php',
                    type: "POST",
                    dataType: "json",
                    data: {
                        term: request.term,
                    },
                    success: function (data) {
                        response(data);
                    },
                });
            },
            select: function (event, ui) {
                $("#txt_tercero_filtro").val(ui.item.label);
                $("#id_txt_tercero").val(ui.item.id);
                return false;
            },
            focus: function (event, ui) {
                $("#txt_tercero_filtro").val(ui.item.label);
                return false;
            },
        });
    }
});