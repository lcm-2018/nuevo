<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include '../common/cargar_combos.php';
include '../common/funciones_generales.php';

$cmd = \Config\Clases\Conexion::getConexion();

$id = isset($_POST['id']) ? $_POST['id'] : -1;
$id_sede = isset($_POST['id_sede']) ? $_POST['id_sede'] : -1;
$id_area = isset($_POST['id_area']) ? $_POST['id_area'] : -1;

$sql = "SELECT far_medicamentos.cod_medicamento,far_medicamentos.nom_medicamento,far_subgrupos.nom_subgrupo
        FROM far_medicamentos 
        INNER JOIN far_subgrupos ON (far_subgrupos.id_subgrupo = far_medicamentos.id_subgrupo)
        WHERE id_med=" . $id . " LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs->fetch();
?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center bg-sofia">
            <h5 class="text-white mb-0">ACTIVOS FIJOS</h5>
        </div>
        <div class="p-2">            
            <form id="frm_reg_articulo">
                <input type="hidden" id="id_articulo" name="id_articulo" value="<?php echo $id ?>">
                <input type="hidden" id="id_sede" name="id_sede" value="<?php echo $id_sede ?>">
                <input type="hidden" id="id_area" name="id_area" value="<?php echo $id_area ?>">
                <div class="row mb-2">
                    <div class="col-md-2">
                        <label class="small">Código</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="placa_componente" value="<?php echo $obj['cod_medicamento'] ?>" readonly="readonly">
                    </div>
                    <div class="col-md-5">
                        <label class="small">Articulo</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="nom_articulo_componente" value="<?php echo $obj['nom_medicamento'] ?> " readonly="readonly">
                    </div>
                    <div class="col-md-5">
                        <label class="small">Subgrupo</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="serial_componente" value="<?php echo $obj['nom_subgrupo'] ?>" readonly="readonly">
                    </div>
                </div>
            </form>
            <table id="tb_activo_fijo" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100" style="font-size:80%">
                <thead>
                    <tr class="text-center">
                        <th class="bg-sofia">Id</th>
                        <th class="bg-sofia">Placa</th>
                        <th class="bg-sofia">No. Serial</th>
                        <th class="bg-sofia">Descripción</th>
                        <th class="bg-sofia">Marca</th>
                        <th class="bg-sofia">Valor</th>
                        <th class="bg-sofia">Sede</th>
                        <th class="bg-sofia">Area</th>
                        <th class="bg-sofia">Responsable</th>
                        <th class="bg-sofia">Estado</th>
                    </tr>
                </thead>
                <tbody class="text-start"></tbody>
            </table>
        </div>
    </div>
    <div class="text-center pt-3">
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Salir</a>
    </div>
</div>

<script>
(function ($) {
    $(document).ready(function () {
        $('#tb_activo_fijo').DataTable({
            dom: setdom,
            language: dataTable_es,
            processing: true,
            serverSide: true,
            ajax: {
                url: 'listar_activos_fijos.php',
                type: 'POST',
                dataType: 'json',
                data: function (data) {
                    data.id_articulo = $('#id_articulo').val();
                    data.id_sede = $('#id_sede').val();
                    data.id_area = $('#id_area').val();
                }
            },
            columns: [
                { 'data': 'id_activo_fijo' }, //Index=0
                { 'data': 'placa' },
                { 'data': 'num_serial' },
                { 'data': 'des_activo' },
                { 'data': 'nom_marca' },
                { 'data': 'valor' },
                { 'data': 'nom_sede' },
                { 'data': 'nom_area' },
                { 'data': 'nom_responsable' },
                { 'data': 'nom_estado_general' }
            ],
            columnDefs: [{
                targets: [3,4, 6, 7, 8],
                className: 'text-wrap'
            }],
            order: [
                [0, "ASC"]
            ],
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
        });

        $('#tb_activo_fijo').wrap('<div class="overflow"/>');
    });
})(jQuery);
</script>
