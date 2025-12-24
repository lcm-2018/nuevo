<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$cmd = \Config\Clases\Conexion::getConexion();
$fecha_sis = date('Y-m-d');
?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center bg-sofia">
            <h5 class="text-white mb-0">PEDIDOS DE BODEGA</h5>
        </div>
        <div class="p-2">

            <!--Formulario de busqueda de articulos-->
            <form id="frm_buscar_pedidos">
                <div class="row mb-2">
                    <div class="col-md-2">
                        <input type="text" class="filtro_ped form-control form-control-sm bg-input" id="txt_num_ped_fil" placeholder="No. Pedido">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="filtro_ped form-control form-control-sm bg-input" id="txt_fecini_fil" name="txt_fecini_fil" placeholder="Fecha Inicial">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="filtro_ped form-control form-control-sm bg-input" id="txt_fecfin_fil" name="txt_fecfin_fil" placeholder="Fecha Final">
                    </div>
                    <div class="col-md-4">
                        <div class="form-control form-control-sm bg-input">
                            <input class="filtro_ped form-check-input" type="checkbox" id="chk_pedpar_fil">
                            <label class="filtro_ped form-check-label small" for="chk_pedpar_fil">Incluir Pedidos Con Entrega Incompleta</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="btn_buscar_ped_fil" class="btn btn-outline-success btn-sm" title="Filtrar">
                            <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </form>
            <div style="height:400px" class="overflow-auto">
                <table id="tb_pedidos_tra" class="table table-striped table-bordered table-sm table-hover shadow align-middle w-100" style="font-size:80%">
                    <thead>
                        <tr class="text-center">
                            <th rowspan="2" class="bg-sofia">Id</th>
                            <th rowspan="2" class="bg-sofia">No. Pedido</th>
                            <th rowspan="2" class="bg-sofia">Fecha Pedido</th>
                            <th rowspan="2" class="bg-sofia">Detalle</th>
                            <th colspan="4" class="bg-sofia">Unidad Origen (Proveedor)</th>
                            <th colspan="4" class="bg-sofia">Unidad Destino (Quien Solicita)</th>
                            <th rowspan="2" class="bg-sofia">Ver</th>
                        </tr>
                        <tr class="text-center">
                            <th class="bg-sofia">Id.Sede</th>
                            <th class="bg-sofia">Sede</th>
                            <th class="bg-sofia">Id.Bodega</th>
                            <th class="bg-sofia">Bodega</th>
                            <th class="bg-sofia">Id.Sede</th>
                            <th class="bg-sofia">Sede</th>
                            <th class="bg-sofia">Id.Bodega</th>
                            <th class="bg-sofia">Bodega</th>
                        </tr>
                    </thead>
                    <tbody class="text-start"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="text-end pt-3 rigth">
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Salir</a>
    </div>
</div>

<script>
    (function($) {
        $(document).ready(function() {
            $('#tb_pedidos_tra').DataTable({
                language: dataTable_es,
                processing: true,
                serverSide: true,
                searching: false,
                autoWidth: true,
                ajax: {
                    url: 'buscar_pedidos_lista.php',
                    type: 'POST',
                    dataType: 'json',
                    data: function(data) {
                        data.num_pedido = $('#txt_num_ped_fil').val();
                        data.fec_ini = $('#txt_fecini_fil').val();
                        data.fec_fin = $('#txt_fecfin_fil').val();
                        data.ped_parcial = $('#chk_pedpar_fil').is(':checked') ? 1 : 0;
                    }
                },
                columns: [{
                        'data': 'id_pedido'
                    }, //Index=0
                    {
                        'data': 'num_pedido'
                    },
                    {
                        'data': 'fec_pedido'
                    },
                    {
                        'data': 'detalle'
                    },
                    {
                        'data': 'id_sede_origen'
                    },
                    {
                        'data': 'nom_sede_provee'
                    },
                    {
                        'data': 'id_bodega_origen'
                    },
                    {
                        'data': 'nom_bodega_provee'
                    },
                    {
                        'data': 'id_sede_destino'
                    },
                    {
                        'data': 'nom_sede_solicita'
                    },
                    {
                        'data': 'id_bodega_destino'
                    },
                    {
                        'data': 'nom_bodega_solicita'
                    },
                    {
                        'data': 'botones'
                    }
                ],
                columnDefs: [{
                        class: 'text-wrap',
                        targets: 3
                    },
                    {
                        width: '5%',
                        targets: [0, 1, 2, 5, 7, 9, 11]
                    },
                    {
                        visible: false,
                        targets: [4, 6, 8, 10]
                    },
                    {
                        orderable: false,
                        targets: 12
                    }
                ],
                order: [
                    [0, "desc"]
                ],
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'TODO'],
                ]
            });

            $('#tb_pedidos_tra').wrap('<div class="overflow-auto"></div>');
        });
    })(jQuery);

    //Buascar registros de articulos 
    $('#btn_buscar_ped_fil').on("click", function() {
        $('#tb_pedidos_tra').DataTable().ajax.reload(null, false);
    });

    $('.filtro_ped').keypress(function(e) {
        if (e.keyCode == 13) {
            $('#tb_pedidos_tra').DataTable().ajax.reload(null, false);
        }
    });

    $('.filtro_ped').mouseup(function(e) {
        $('#tb_pedidos_tra').DataTable().ajax.reload(null, false);
    });
</script>