<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$cmd = \Config\Clases\Conexion::getConexion();
?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center bg-sofia">
            <h5 class="text-white mb-0">PEDIDOS DE DEPENDENCIA</h5>
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
                <table id="tb_pedidos_egr" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100" style="font-size:80%">
                    <thead>
                        <tr class="text-center">
                            <th class="bg-sofia">Id</th>
                            <th class="bg-sofia">No. Pedido</th>
                            <th class="bg-sofia">Fecha Pedido</th>
                            <th class="bg-sofia">Detalle</th>
                            <th class="bg-sofia">Id.Dependencia</th>
                            <th class="bg-sofia">Dependencia Que Solicita</th>
                            <th class="bg-sofia">Id.Sede</th>
                            <th class="bg-sofia">Sede Proveedor</th>
                            <th class="bg-sofia">Id.Bodega</th>
                            <th class="bg-sofia">Bodega Proveedor</th>
                            <th class="bg-sofia">Ver</th>
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
            $('#tb_pedidos_egr').DataTable({
                language: dataTable_es,
                processing: true,
                serverSide: true,
                searching: false,
                autoWidth: false,
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
                        'data': 'id_cencosto'
                    },
                    {
                        'data': 'nom_centro'
                    },
                    {
                        'data': 'id_sede'
                    },
                    {
                        'data': 'nom_sede'
                    },
                    {
                        'data': 'id_bodega'
                    },
                    {
                        'data': 'nom_bodega'
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
                        targets: [0, 1, 2, 5, 7, 9]
                    },
                    {
                        visible: false,
                        targets: [4, 6, 8]
                    },
                    {
                        orderable: false,
                        targets: 10
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
            $('#tb_pedidos_egr').wrap('<div class="overflow"/>');
        });
    })(jQuery);

    //Buascar registros de articulos 
    $('#btn_buscar_ped_fil').on("click", function() {
        $('#tb_pedidos_egr').DataTable().ajax.reload(null, false);
    });

    $('.filtro_ped').keypress(function(e) {
        if (e.keyCode == 13) {
            $('#tb_pedidos_egr').DataTable().ajax.reload(null, false);
        }
    });

    $('.filtro_ped').mouseup(function(e) {
        $('#tb_pedidos_egr').DataTable().ajax.reload(null, false);
    });
</script>