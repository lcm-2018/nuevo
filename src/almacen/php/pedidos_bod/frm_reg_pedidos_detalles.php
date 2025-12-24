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

$id_med = isset($_POST['id_med']) ? $_POST['id_med'] : -1;
$id = isset($_POST['id']) ? $_POST['id'] : -1;
$sql = "SELECT far_pedido_detalle.*,
            far_medicamentos.nom_medicamento AS nom_articulo
        FROM far_pedido_detalle
        INNER JOIN far_medicamentos ON (far_medicamentos.id_med = far_pedido_detalle.id_medicamento)
        WHERE far_pedido_detalle.id_ped_detalle=" . $id . " LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs->fetch();

if (empty($obj)) {
    $n = $rs->columnCount();
    for ($i = 0; $i < $n; $i++) :
        $col = $rs->getColumnMeta($i);
        $name = $col['name'];
        $obj[$name] = NULL;
    endfor;

    $articulo = datos_articulo($cmd, $id_med);
    $obj['id_medicamento'] = $articulo['id_med'];
    $obj['nom_articulo'] = $articulo['nom_articulo'];
    $obj['valor'] = $articulo['val_promedio'];
}
?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h7 style="color: white;">REGISTRAR DETALLE PEDIDO</h7>
        </div>
        <div class="p-2">

            <!--Formulario de registro de Detalle-->
            <form id="frm_reg_pedidos_detalles">
                <input type="hidden" id="id_detalle" name="id_detalle" value="<?php echo $id ?>">
                <div class=" row">
                    <div class="col-md-12">
                        <label for="txt_nom_art" class="small">Articulo</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_nom_art" class="small" value="<?php echo $obj['nom_articulo'] ?>" readonly="readonly">
                        <input type="hidden" id="id_txt_nom_med" name="id_txt_nom_med" value="<?php echo $obj['id_medicamento'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="txt_can_ped" class="small">Cantidad</label>
                        <input type="number" class="form-control form-control-sm bg-input numberint" id="txt_can_ped" name="txt_can_ped" value="<?php echo $obj['cantidad'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="txt_val_pro" class="small">Vr. Promedio</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_val_pro" name="txt_val_pro" value="<?php echo $obj['valor'] ?>" readonly="readonly">
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center pt-3">
        <button type="button" class="btn btn-primary btn-sm" id="btn_guardar_detalle">Guardar</button>
        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>