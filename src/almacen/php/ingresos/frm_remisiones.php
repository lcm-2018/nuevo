<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include '../common/cargar_combos.php';

$cmd = \Config\Clases\Conexion::getConexion();
$id_tercero = isset($_POST['id_tercero']) ? $_POST['id_tercero'] : -1;

$sql = "SELECT nom_tercero
        FROM tb_terceros
        WHERE id_tercero=" . $id_tercero . " LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs ? $rs->fetch() : null;

?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="text-white mb-0">REMISIONES INTERNAS</h5>
        </div>
        <div class="p-3">
            <form id="frm_ingresos_rem">
                <input type="hidden" id="id_tercero_rem" name="id_tercero_rem" value="<?php echo $id_tercero ?>">
                <div class="row mb-2">
                    <div class="col-md-5">
                        <label for="txt_tercero_rem" class="small">Tercero</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_tercero_rem" name="txt_tercero_rem" value="<?php echo $obj['nom_tercero'] ?? '' ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label for="sl_estado_rem" class="small">Estado Remision</label>
                        <select class="form-select form-select-sm bg-input" id="sl_estado_rem" name="sl_estado_rem">
                            <?php estados_remision('', 1) ?>
                        </select>
                    </div>   
                </div>
            </form>
            <!--Formulario de registro de Ordenes de Ingreso-->
            <table id="tb_ingresos_rem" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100" style="font-size:80%">
                <thead>
                    <tr class="text-center">
                        <th class="bg-sofia">Id</th>
                        <th class="bg-sofia">Fecha</th>
                        <th class="bg-sofia">No. Remisión</th>
                        <th class="bg-sofia">Descripción</th>
                        <th class="bg-sofia">Estado</th>
                        <th class="bg-sofia">Acciones</th>
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

<script type="text/javascript" src="../../js/ingresos/ingresos_rem.js?v=<?php echo date('YmdHis') ?>"></script>