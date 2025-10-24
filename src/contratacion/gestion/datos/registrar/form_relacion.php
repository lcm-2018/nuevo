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
    $sql = "SELECT
                `rel`.`id_relacion`
                , `rel`.`user_rel` 
                , `sus`.`num_documento`
                , CONCAT_WS(' ', `sus`.`nombre1`
                , `sus`.`nombre2`
                , `sus`.`apellido1`
                , `sus`.`apellido2`) AS `nombre`
            FROM
                `ctt_relacion_user` AS `rel`
                INNER JOIN `seg_usuarios_sistema` AS `sus` 
                    ON (`rel`.`user_rel` = `sus`.`id_usuario`)
            WHERE (`rel`.`id_relacion` = $id)";
    $rs = $cmd->query($sql);
    $relacion = $rs->fetch(PDO::FETCH_ASSOC);
    if (empty($relacion)) {
        $relacion = ['id_relacion' => 0, 'user_rel' => 0, 'num_documento' => '', 'nombre' => ''];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">RELACION DE USUARIO</h5>
        </div>
        <form id="formRelacionUser">
            <input type="hidden" id="id_user" name="id_user" value="<?= $id_user ?>">
            <input type="hidden" id="id_relacion" name="id_relacion" value="<?= $id ?>">
            <div class="row px-4 pt-2">
                <div class="col-md-12 mb-3">
                    <label for="buscaUser" class="small">BUSCAR USUARIO</label>
                    <input type="text" class="form-control form-control-sm bg-input" id="buscaUser" name="buscaUser" placeholder="Escriba el nombre del usuario" autocomplete="off" value="<?= $relacion['nombre'] != '' ? $relacion['nombre'] . ' -> ' . $relacion['num_documento'] : '' ?>">
                    <input type="hidden" id="id_usuario" name="id_usuario" value="<?= $relacion['user_rel'] ?>">
                </div>
            </div>
            <div class="text-center pb-3">
                <button class="btn btn-primary btn-sm" id="btnGuardaRelacionUser">Guardar</button>
                <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
            </div>
        </form>
    </div>
</div>