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

    $sql = "SELECT
                `id_responsabilidad`, `codigo`, `descripcion`
            FROM
                `tb_responsabilidades_tributarias`
            WHERE (`id_responsabilidad` = $id)";
    $rs = $cmd->query($sql);
    $data = $rs->fetch(PDO::FETCH_ASSOC);
    if (empty($data)) {
        $data = [
            'id_responsabilidad' => 0,
            'codigo' => '',
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
        <div class="card-header text-center p-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">GESTIÓN DE RESPONSABILIDAD ECONÓMICA</h5>
        </div>
        <form id="formGestRespEcon">
            <input type="number" id="id_responsabilidad" name="id_responsabilidad" value="<?= $id ?>" hidden>
            <div class="row px-4 pt-2">
                <div class="form-group col-md-4">
                    <label for="codigoRespEcono" class="small">Código</label>
                    <input type="text" class="form-control form-control-sm bg-input" id="codigoRespEcono" name="codigoRespEcono" value="<?= $data['codigo'] ?>">
                </div>
                <div class="form-group col-md-8">
                    <label for="nombreRespEcono" class="small">Responsabilidad Económica</label>
                    <input type="text" class="form-control form-control-sm bg-input" id="nombreRespEcono" name="nombreRespEcono" value="<?= $data['descripcion'] ?>">
                </div>
        </form>
        <div class="text-end p-3 w-100">
            <button class="btn btn-primary btn-sm" id="btnGuardaResponsabilidad">Guardar</button>
            <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal"> Cancelar</a>
        </div>
    </div>
</div>