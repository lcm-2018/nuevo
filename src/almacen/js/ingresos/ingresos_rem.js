(function ($) {
    $(document).ready(function () {
        var tabla = $('#tb_ingresos_rem').DataTable({
            dom: setdom,
            buttons: $('#peReg').val() == 1 ? [{
                text: '<span class="fa-solid fa-plus "></span>',
                className: 'btn btn-success btn-sm shadow',
                action: function (e, dt, node, config) {
                    $.post("frm_reg_remisiones.php", function (he) {
                        $('#divTamModalAux').removeClass('modal-lg modal-xl modal-xxl');
                        $('#divTamModalAux').addClass('modal-sm');
                        $('#divModalAux').modal('show');
                        $("#divFormsAux").html(he);
                    });
                }
            }] : [],
            language: dataTable_es,
            processing: true,
            serverSide: true,
            ajax: {
                url: 'listar_remisiones.php',
                type: 'POST',
                dataType: 'json',
                data: function (data) {
                    data.id_tercero = $('#id_tercero_rem').val();
                    data.estado = $('#sl_estado_rem').val();
                }
            },
            columns: [
                { 'data': 'id_rem_int' }, //Index=0
                { 'data': 'fec_creacion' },
                { 'data': 'num_remision' },
                { 'data': 'desc_remision' },
                { 'data': 'estado' },
                { 'data': 'botones' }
            ],
            columnDefs: [
                { class: 'text-wrap', targets: 3 },
                { orderable: false, targets: 5 }
            ],
            order: [
                [0, "asc"]
            ],
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ]
        });

        $('#tb_ingresos_rem').wrap('<div class="overflow"/>');
    });

})(jQuery);
