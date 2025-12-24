<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo '<script>window.location.replace("../../../index.php");</script>';
    exit();
}
include '../../../../config/autoloader.php';
include '../common/cargar_combos.php';
include '../common/funciones_generales.php';

$cmd = \Config\Clases\Conexion::getConexion();

$id = isset($_POST['id']) ? $_POST['id'] : -1;
$sql = "SELECT acf_orden_ingreso_acfs.*
        FROM acf_orden_ingreso_acfs
        WHERE id_act_fij=" . $id . " LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs->fetch();

if (empty($obj)) {
    $n = $rs->columnCount();
    for ($i = 0; $i < $n; $i++) :
        $col = $rs->getColumnMeta($i);
        $name = $col['name'];
        $obj[$name] = NULL;
    endfor;
    //Inicializa variable por defecto
    $obj['valor'] = $_POST['val_unitario'];
    $obj['tipo_activo'] = 0;
}
?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h7 style="color: white;">REGISRTAR DATOS BASICOS DE ACTIVO FIJO</h7>
        </div>
        <div class="p-2">

            <!--Formulario de registro de Detalle-->
            <form id="frm_reg_activofijo_detalle">
                <input type="hidden" id="id_act_fijo" name="id_act_fijo" value="<?php echo $id ?>">
                <div class=" row">
                    <div class="col-md-4">
                        <label for="txt_placa" class="small">Placa</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_placa" name="txt_placa" value="<?php echo $obj['placa'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="txt_serial" class="small">No. Serial</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_serial" name="txt_serial" value="<?php echo $obj['num_serial'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="txt_val_uni" class="small">Vr. Unitario</label>
                        <input type="text" class="form-control form-control-sm bg-input numberfloat" id="txt_val_uni" name="txt_val_uni" value="<?php echo $obj['valor'] ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="sl_tercero" class="small">Marca</label>
                        <select class="form-select form-select-sm bg-input" id="sl_marca" name="sl_marca">
                            <?php marcas($cmd, '', $obj['id_marca']) ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="sl_tipoactivo" class="small">Tipo Activo</label>
                        <select class="form-select form-select-sm bg-input" id="sl_tipoactivo" name="sl_tipoactivo">
                            <?php tipos_activo('', $obj['tipo_activo']) ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center pt-3">
        <button type="button" class="btn btn-primary btn-sm" id="btn_guardar_actfij">Guardar</button>
        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>