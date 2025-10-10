<?php
$sessionStarted = false;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $sessionStarted = true;
}
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';

$cmd = \Config\Clases\Conexion::getConexion();

try {

    $sql = "SELECT `id_tipo`,`tipo_compra` FROM `tb_tipo_compra` ORDER BY `tipo_compra` ASC";
    $rs = $cmd->query($sql);
    $tipo = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">REGISTRAR TIPO DE BIEN O SERVICIO</h5>
        </div>
        <form id="formAddTipoBnSv">
            <div class="row px-4 pt-2">
                <div class="col-md-4 mb-3">
                    <label for="slcTipo" class="small">TIPO DE CONTRATO</label>
                    <select id="slcTipo" name="slcTipo" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example">
                        <option value="0">-- Seleccionar --</option>
                        <?php
                        foreach ($tipo as $tc) {
                            echo '<option value="' . $tc['id_tipo'] . '">' . $tc['tipo_compra'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-8 mb-3">
                    <label for="txtTipoBnSv" class="small">NOMBRE TIPO DE BIEN O SERVICIO</label>
                    <input id="txtTipoBnSv" type="text" name="txtTipoBnSv" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example">
                </div>
            </div>
            <div class="row px-4 pt-2">
                <div class="col-md-12 mb-3">
                    <label for="txtObjPre" class="small">OBJETO PREDEFINIDO</label>
                    <textarea id="txtObjPre" type="text" name="txtObjPre" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example" rows="3" placeholder="Objeto predefinido del contrato"></textarea>
                </div>
            </div>
            <div class="text-center py-3">
                <button class="btn btn-primary btn-sm" id="btnAddTipoBnSv">Agregar</button>
                <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal"> Cancelar</a>
            </div>
        </form>
    </div>
</div>