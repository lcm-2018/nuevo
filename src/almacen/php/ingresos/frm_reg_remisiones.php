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
$sql = "SELECT far_remision_interna.*,
            CASE far_remision_interna.estado WHEN 1 THEN 'Activa' WHEN 2 THEN 'Cerrada' END AS des_estado
        FROM far_remision_interna
        WHERE id_rem_int=" . $id . " LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs ? $rs->fetch() : null;

if (empty($obj)) {
    $obj = [];
    $n = $rs ? $rs->columnCount() : 0;
    for ($i = 0; $i < $n; $i++) :
        $col = $rs->getColumnMeta($i);
        $name = $col['name'] ?? null;
        if ($name) {
            $obj[$name] = NULL;
        }
    endfor;
    $obj['des_estado'] = 'Activa';
}
?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h7 class="text-white mb-0">REGISTRAR REMISION INTERNA</h7>
        </div>
        <div class="p-2">

            <!--Formulario de registro de Detalle-->
            <form id="frm_reg_remisiones">
                <input type="hidden" id="id_rem_int" name="id_rem_int" value="<?php echo $id ?>">                
                <div class="row mb-2">
                    <div class="col-md-12">
                        <label for="txt_num_remision" class="small">Num. Remisión</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_num_remision" name="txt_num_remision" value="<?php echo $obj['num_remision'] ?>">
                    </div>
                    <div class="col-md-12">
                        <label for="txt_desc_remision" class="small">Descripción</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_desc_remision" name="txt_desc_remision" value="<?php echo $obj['desc_remision'] ?>">
                    </div>
                    <div class="col-md-12">
                        <label for="txt_estado_remision" class="small">Estado</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_estado_remision" name="txt_estado_remision" value="<?php echo $obj['des_estado'] ?>" readonly="readonly">
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center pt-3">
        <button type="button" class="btn btn-primary btn-sm" id="btn_guardar_remision">Guardar</button>
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>