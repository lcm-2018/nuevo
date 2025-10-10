<?php
$sessionStarted = false;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $sessionStarted = true;
}
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';

$cmd = \Config\Clases\Conexion::getConexion();
try {

    $sql = "SELECT 
                `id_tipo_b_s`, `tipo_compra`, `tipo_bn_sv`
            FROM
                `tb_tipo_bien_servicio`
            INNER JOIN `tb_tipo_compra` 
                ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_compra`.`id_tipo`)
            ORDER BY `tipo_compra`, `tipo_bn_sv`";
    $rs = $cmd->query($sql);
    $tbnsv = $rs->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">REGISTRAR BIEN O SERVICIO</h5>
        </div>
        <form id="formAddBnSv">
            <div class="row px-4 pt-2">
                <div class="col-md-12 mb-3">
                    <label for="slcTipoBnSv" class="small">TIPO DE BIEN O SERVICIO</label>
                    <select id="slcTipoBnSv" name="slcTipoBnSv" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example">
                        <option value="0">-- Seleccionar --</option>
                        <?php
                        foreach ($tbnsv as $tbs) {
                            echo '<option value="' . $tbs['id_tipo_b_s'] . '">' . $tbs['tipo_compra'] . ' -> ' . $tbs['tipo_bn_sv'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="row px-4">
                <div class="col-md-12 mb-3">
                    <label for="txtBnSv" class="small">NOMBRE DE BIEN O SERVICIO</label>
                    <div class="input-group input-group-sm" id="celdaPR">
                        <input id="txtBnSv" type="text" name="txtBnSv[]" class="form-control py-0 sm bg-input" aria-label="Default select example">
                        <button class="btn btn-success btn-circle shadow-gb btn_addBnSv" type="button"><span class="fas fa-plus fa-lg"></span></button>
                    </div>
                </div>
            </div>
            <div id="content_inputs" class="px-4">

            </div>
            <div>
                <div class="text-center pb-3">
                    <button class="btn btn-primary btn-sm" id="btnAddBnSv">Agregar</button>
                    <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>