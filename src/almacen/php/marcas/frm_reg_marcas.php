<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo '<script>window.location.replace("../../../index.php");</script>';
    exit();
}
include '../../../../config/autoloader.php';
include '../common/cargar_combos.php';

$cmd = \Config\Clases\Conexion::getConexion();

$id = isset($_POST['id']) ? $_POST['id'] : -1;
$sql = "SELECT * FROM acf_marca WHERE id=" . $id . " LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs->fetch();

if (empty($obj)) {
    $n = $rs->columnCount();
    for ($i = 0; $i < $n; $i++):
        $col = $rs->getColumnMeta($i);
        $name = $col['name'];
        $obj[$name] = NULL;
    endfor;
    //Inicializa variable por defecto
}

?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="text-white mb-0">REGISRTAR MARCA</h5>
        </div>
        <div class="p-3">
            <form id="frm_reg_marcas">
                <input type="hidden" id="id_marca" name="id_marca" value="<?php echo $id ?>">
                <div class=" row">
                    <div class="col-md-12">
                        <label for="txt_nom_marca" class="small">Nombre</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_nom_marca" name="txt_nom_marca" required value="<?php echo $obj['descripcion'] ?>">
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center pt-3">
        <button type="button" class="btn btn-primary btn-sm" id="btn_guardar">Guardar</button>
        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>