(function ($) {
    $(document).ready(function () {
        $('#tb_traslados_detalles').DataTable({

            dom: setdom,
            buttons: $('#peReg').val() == 1 ? [{
                text: '<span class="fa-solid fa-plus "></span>',
                className: 'btn btn-success btn-sm shadow',
                action: function (e, dt, node, config) {
                    $.post("../common/buscar_activo_fijo_frm.php", { proceso: 'tras', id_area: $('#sl_area_origen').val() }, function (he) {
                        $('#divTamModalBus').removeClass('modal-lg');
                        $('#divTamModalBus').removeClass('modal-sm');
                        $('#divTamModalBus').addClass('modal-xl');
                        $('#divModalBus').modal('show');
                        $("#divFormsBus").html(he);
                    });
                }
            }] : [],
            language: dataTable_es,
            processing: true,
            serverSide: true,
            ajax: {
                url: 'listar_traslados_detalles.php',
                type: 'POST',
                dataType: 'json',
                data: function (data) {
                    data.id_traslado = $('#id_traslado').val();
                }
            },
            columns: [
                { 'data': 'id_traslado_detalle' }, //Index=0
                { 'data': 'placa' },
                { 'data': 'nom_articulo' },
                { 'data': 'des_activo' },
                { 'data': 'estado_general' },
                { 'data': 'observacion' },
                { 'data': 'botones' }
            ],
            columnDefs: [
                { class: 'text-wrap', targets: [2, 3, 5] },
                { orderable: false, targets: 6 }
            ],
            order: [
                [0, "asc"]
            ],
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
        }).on('draw', function () {
            let table = $('#tb_traslados_detalles').DataTable();
            let rows = table.rows({ filter: 'applied' }).count();
            if (rows > 0) {
                $('#sl_sede_origen').prop('disabled', true);
                $('#sl_area_origen').prop('disabled', true);
            } else {
                $('#sl_sede_origen').prop('disabled', false);
                $('#sl_area_origen').prop('disabled', false);
            }
        });


        $('#tb_traslados_detalles').wrap('<div class="overflow"/>');
    });

})(jQuery);