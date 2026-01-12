<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$cmd = \Config\Clases\Conexion::getConexion();

$id_sede = isset($_POST['id_sede']) ? $_POST['id_sede'] : -1;
$id_bodega = isset($_POST['id_bodega']) && $_POST['id_bodega'] ? $_POST['id_bodega'] : -1;
$id_pedido = isset($_POST['id_pedido']) && $_POST['id_pedido'] ? $_POST['id_pedido'] : -1;

?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h7 style="color: white;">ARTICULOS DEL PEDIDO DE ALMACÉN - ORDEN DE COMPRA</h7>
        </div>
        <div class="p-2">

            <!--Formulario de busqueda de articulos-->
            <form id="frm_buscar_articulos">
                <input type="hidden" id="id_sede_fil" value="<?php echo $id_sede ?>">
                <input type="hidden" id="id_bodega_fil" value="<?php echo $id_bodega ?>">
                <input type="hidden" id="id_pedido_fil" value="<?php echo $id_pedido ?>">
                <div class="row mb-2">
                    <div class="col-md-2">
                        <input type="text" class="filtro_art form-control form-control-sm bg-input" id="txt_codigo_art_ped_fil" placeholder="Codigo">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="filtro_art form-control form-control-sm bg-input" id="txt_nombre_art_ped_fil" placeholder="Nombre">
                    </div>
                    <div class="col-md-3">
                        <div class="form-control form-control-sm bg-input">
                            <input class="filtro_art form-check-input" type="checkbox" id="chk_concanpen_ped_fil" checked>
                            <label class="filtro_art form-check-label small" for="chk_concanpen_ped_fil">Con Cantidad Pendiente</label>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <a type="button" id="btn_buscar_articulo_ped_fil" class="btn btn-outline-success btn-sm" title="Filtrar">
                            <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                        </a>
                    </div>
                </div>
            </form>
            <div style="height:400px" class="overflow-auto">
                <table id="tb_articulos_pedido" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%; font-size:80%">
                    <thead>
                        <tr class="text-center">
                            <th>Id</th>
                            <th>Id. Articulo</th>
                            <th>Código</th>
                            <th>Artículo</th>
                            <th>Cant. Ordenada</th>
                            <th>Cant. Ingresada</th>
                            <th>Cant. Pendiente</th>
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
            $('#tb_articulos_pedido').DataTable({
                language: dataTable_es,
                processing: true,
                serverSide: true,
                searching: false,
                autoWidth: false,
                ajax: {
                    url: 'buscar_articulos_pedido_lista.php',
                    type: 'POST',
                    dataType: 'json',
                    data: function(data) {
                        data.id_sede = $('#id_sede_fil').val();
                        data.id_bodega = $('#id_bodega_fil').val();
                        data.id_pedido = $('#id_pedido_fil').val();
                        data.codigo = $('#txt_codigo_art_ped_fil').val();
                        data.nombre = $('#txt_nombre_art_ped_fil').val();
                        data.can_pen = $('#chk_concanpen_ped_fil').is(':checked') ? 1 : 0;
                    }
                },
                columns: [{
                        'data': 'id_ped_detalle'
                    }, //Index=0
                    {
                        'data': 'id_med'
                    },
                    {
                        'data': 'cod_medicamento'
                    },
                    {
                        'data': 'nom_medicamento'
                    },
                    {
                        'data': 'cantidad_ord'
                    },
                    {
                        'data': 'cantidad_ing'
                    },
                    {
                        'data': 'cantidad_pen'
                    }
                ],
                columnDefs: [{
                        class: 'text-wrap',
                        targets: [3]
                    },
                    {
                        width: '5%',
                        targets: [0, 1, 2, 4, 5, 6]
                    }
                ],
                order: [
                    [0, "asc"]
                ],
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'TODO'],
                ]
            });
            $('#tb_articulos_pedido').wrap('<div class="overflow"/>');
        });
    })(jQuery);

    //Buascar registros de articulos 
    $('#btn_buscar_articulo_ped_fil').on("click", function() {
        $('#tb_articulos_pedido').DataTable().ajax.reload(null, false);
    });

    $('.filtro_art').keypress(function(e) {
        if (e.keyCode == 13) {
            $('#tb_articulos_pedido').DataTable().ajax.reload(null, false);
        }
    });

    $('.filtro_art').click(function(e) {
        $('#tb_articulos_pedido').DataTable().ajax.reload(null, false);
    });
</script>