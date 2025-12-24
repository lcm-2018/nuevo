<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include '../common/cargar_combos.php';

$cmd = \Config\Clases\Conexion::getConexion();

$id = isset($_POST['id']) ? $_POST['id'] : -1;
$sql = "SELECT id_med,cod_medicamento,nom_medicamento
        FROM far_medicamentos 
        WHERE id_med=" . $id . " LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs->fetch();

if (empty($obj)) {
    //Inicializa variable por defecto
    $obj['id_med'] = 0;
}

?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center bg-sofia">
            <h5 class="text-white mb-0">REGISTRO DE MOVIMIENTOS</h5>
        </div>
        <div class="p-2">

            <!--Formulario de registro de Articulos-->
            <form id="frm_reg_articulos">
                <input type="hidden" id="id_articulo" name="id_articulo" value="<?php echo $id ?>">
                <div class=" row">
                    <div class="col-md-1">
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_cod_art" name="txt_cod_art" value="<?php echo $obj['cod_medicamento'] ?>" readonly="readonly">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_nom_art" name="txt_nom_art" value="<?php echo $obj['nom_medicamento'] ?>" readonly="readonly">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control form-control-sm bg-input" id="txt_fecini_fil" name="txt_fecini_fil" placeholder="Fecha Inicial">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class=" form-control form-control-sm bg-input" id="txt_fecfin_fil" name="txt_fecini_fil" placeholder="Fecha Final">
                    </div>
                    <div class="col-md-1">
                        <button type="button" id="btn_buscar_fil_kar" class="btn btn-outline-success btn-sm" title="Buscar">
                            <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </form>

            <!--Lista de CUMS-->
            <div class="tab-pane fade show active" id="nav_lista_cums" role="tabpanel" aria-labelledby="nav_lista_cums-tab">
                <table id="tb_kardex" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100" style="font-size:80%">
                    <thead>
                        <tr class="text-center">
                            <th class="bg-sofia">Id</th>
                            <th class="bg-sofia">Fecha</th>
                            <th class="bg-sofia">Comprobante</th>
                            <th class="bg-sofia">Sede</th>
                            <th class="bg-sofia">Bodega</th>
                            <th class="bg-sofia">Lote</th>
                            <th class="bg-sofia">Detalle</th>
                            <th class="bg-sofia">Vr. Unitario</th>
                            <th class="bg-sofia">Vr. Promedio</th>
                            <th class="bg-sofia">Can. Ingreso</th>
                            <th class="bg-sofia">Can. Egreso</th>
                            <th class="bg-sofia">Existencia</th>
                        </tr>
                    </thead>
                    <tbody class="text-start"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="text-center pt-3">
        <button type="button" class="btn btn-primary btn-sm" id="btn_imprimir">Imprimir</button>
        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Salir</a>
    </div>
</div>

<script>
    (function($) {
        $(document).ready(function() {
            $('#tb_kardex').DataTable({
                language: dataTable_es,
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: 'listar_kardex.php',
                    type: 'POST',
                    dataType: 'json',
                    data: function(data) {
                        data.id_articulo = $('#id_articulo').val();
                        data.fec_ini = $('#txt_fecini_fil').val();
                        data.fec_fin = $('#txt_fecfin_fil').val();
                    }
                },
                columns: [{
                        'data': 'id_kardex'
                    }, //Index=0
                    {
                        'data': 'fec_movimiento'
                    },
                    {
                        'data': 'comprobante'
                    },
                    {
                        'data': 'nom_sede'
                    },
                    {
                        'data': 'nom_bodega'
                    },
                    {
                        'data': 'lote'
                    },
                    {
                        'data': 'detalle'
                    },
                    {
                        'data': 'val_ingreso'
                    },
                    {
                        'data': 'val_promedio'
                    },
                    {
                        'data': 'can_ingreso'
                    },
                    {
                        'data': 'can_egreso'
                    },
                    {
                        'data': 'existencia'
                    }
                ],
                columnDefs: [{
                    orderable: false,
                    targets: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]
                }, {
                    targets: [4, 6],
                    className: 'text-wrap'
                }],
                order: [
                    [0, "ASC"]
                ],
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'TODO'],
                ]
            });
            $('#tb_kardex').wrap('<div class="overflow"/>');
        });
    })(jQuery);
</script>