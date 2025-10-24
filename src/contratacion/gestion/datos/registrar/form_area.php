<?php

use Src\Common\Php\Clases\Combos;

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

$id_user = isset($_POST['id_user']) ? $_POST['id_user'] : exit('Acceso Denegado');
$id = $_POST['id'];

$cmd = \Config\Clases\Conexion::getConexion();

try {
    $sql = "SELECT `id_resp`,`id_user`,`id_area` FROM `ctt_area_user` WHERE `id_resp` = $id";
    $rs = $cmd->query($sql);
    $areas = $rs->fetch(PDO::FETCH_ASSOC);
    if (empty($areas)) {
        $areas = ['id_area' => 0];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$areas = Combos::getArea($areas['id_area']);
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">AREAS DE USUARIO</h5>
        </div>
        <form id="formAreaUser">
            <input type="hidden" id="id_user" name="id_user" value="<?= $id_user ?>">
            <input type="hidden" id="id_resp" name="id_resp" value="<?= $id ?>">
            <div class="row px-4 pt-2">
                <div class="col-md-12 mb-3">
                    <label for="slcAreaUser" class="small">NOMBRE AREA</label>
                    <select class="form-select form-select-sm bg-input" id="slcAreaUser" name="slcAreaUser">
                        <?= $areas ?>
                    </select>
                </div>
            </div>
            <div class="text-center pb-3">
                <button class="btn btn-primary btn-sm" id="btnGuardaAreaUser">Guardar</button>
                <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
            </div>
        </form>
    </div>
</div>