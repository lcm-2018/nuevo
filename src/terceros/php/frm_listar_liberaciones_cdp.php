<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

$id_cdp = isset($_POST['id_cdp']) ? $_POST['id_cdp'] : -1;
$otro_form = isset($_POST['otro_form']) ? $_POST['otro_form'] : 0;

?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header p-2 text-center" style="background-color: #16a085 !important;">
            <h5 style="color: white;">LISTA DE LIBERACIONES A CDP</h5>
        </div>
        <div class="px-2">
            <form id="frm_liberacionescdp">
                <input type="hidden" id="id_cdp" name="id_cdp" value="<?php echo $id_cdp ?>">
                <div class=" w-100 text-left">
                    <table id="tb_liberacionescdp" class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100" style="width:100%; font-size:80%">
                        <thead>
                            <tr class="text-center centro-vertical">
                                <th class="bg-sofia">Id Lib</th>
                                <th class="bg-sofia">Fecha</th>
                                <th class="bg-sofia" style="min-width: 50%;">Concepto</th>
                                <th class="bg-sofia">Valor</th>
                                <th class="bg-sofia">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-left centro-vertical" id="body_tb_liberacionescdp"></tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center pt-3">
        <!--<button type="button" class="btn btn-primary btn-sm" id="btn_imprimir_liberaciones_cdp">Imprimir</button>-->
        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>

<script>
    (function($) {
        $(document).ready(function() {
            $('#tb_liberacionescdp').DataTable({
                language: dataTable_es,
                processing: true,
                serverSide: true,
                searching: false,
                autoWidth: false,
                ajax: {
                    url: ValueInput('host') + '/src/terceros/php/listar_liberaciones_cdp.php',
                    type: 'POST',
                    dataType: 'json',
                    data: function(data) {
                        data.id_cdp = $('#id_cdp').val();
                    }
                },
                columns: [{
                        'data': 'id_pto_cdp_det'
                    },
                    {
                        'data': 'fecha'
                    },
                    {
                        'data': 'concepto_libera'
                    },
                    {
                        'data': 'valor_liberado'
                    },
                    {
                        'data': 'botones'
                    },
                ],
                columnDefs: [{
                    class: 'text-wrap',
                    targets: [2]
                }],
                order: [
                    [2, "asc"]
                ],
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'TODO'],
                ]
            });
            $('#tb_liberacionescdp').wrap('<div class="overflow"/>');
        });
    })(jQuery);
</script>