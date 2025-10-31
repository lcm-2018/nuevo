<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';
$id = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida');
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `id_perfil`,`descripcion` FROM `ctt_perfil_tercero` WHERE `id_perfil` = $id";
    $rs = $cmd->query($sql);
    $data = $rs->fetch(PDO::FETCH_ASSOC);
    if (empty($data)) {
        $data = [
            'id_perfil' => 0,
            'descripcion' => ''
        ];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header p-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">GESTIÓN DE PERFIL DE TERCERO</h5>
        </div>
        <form id="formPerfilTercero">
            <input type="number" id="id_perfil" name="id_perfil" value="<?= $id ?>" hidden>
            <div class="row px-4 pt-2">
                <div class="form-group col-md-12">
                    <label for="txtPerfilTercero" class="small">Perfil</label>
                    <input type="text" class="form-control form-control-sm bg-input" id="txtPerfilTercero" name="txtPerfilTercero" value="<?= $data['descripcion'] ?>">
                </div>
        </form>
        <div class="text-end p-3 w-100">
            <button class="btn btn-primary btn-sm" id="btnGuardaPerfilTercero">Guardar</button>
            <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal"> Cancelar</a>
        </div>
    </div>
</div>