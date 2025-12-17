<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$id_tipo = isset($_POST['id_tipo']) ? $_POST['id_tipo'] : exit('Acceso no permitido');
$id_tipo = $id_tipo != '0' ? base64_decode($id_tipo) : 0;
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `ctb_retencion_tipo`.`id_retencion_tipo`
                , `ctb_retencion_tipo`.`tipo`
                , `ctb_retencion_tipo`.`id_tercero`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM
                `ctb_retencion_tipo`
                LEFT JOIN `tb_terceros` 
                    ON (`ctb_retencion_tipo`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE `ctb_retencion_tipo`.`id_retencion_tipo` = $id_tipo";
    $rs = $cmd->query($sql);
    $datos = $rs->fetch(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
if (empty($datos)) {
    $datos = [
        'id_retencion_tipo' => 0,
        'tipo' => '',
        'id_tercero' => 0,
        'nom_tercero' => '',
    ];
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">GESTIÓN TIPO DE RETENCIONES</b></h5>
        </div>
        <form id="formGestTpRet">
            <input type="hidden" name="tipo_retencion" value="<?php echo $id_tipo; ?>">
            <div class="row mb-2 px-4 pt-2">
                <div class="col-md-12">
                    <label for="txtTipoRte" class="small">Tipo Retención </label>
                    <input type="text" name="txtTipoRte" id="txtTipoRte" class="form-control form-control-sm bg-input" value="<?= $datos['tipo']; ?>">
                </div>
            </div>
            <div class="row mb-2 px-4">
                <div class="col-md-12">
                    <label class="small" for="SeaTercer">Responsable</label>
                    <input type="text" class="form-control form-control-sm bg-input" id="SeaTercer" value="<?= $datos['nom_tercero'] != '' ? $datos['nom_tercero'] . ' -> ' . $datos['nit_tercero'] : '' ?>">
                    <input type="hidden" id="id_tercero" name="id_tercero" value="<?= $datos['id_tercero'] ?>">
                </div>
            </div>
        </form>
        <div class="text-end pb-3 px-4 w-100">
            <button class="btn btn-primary btn-sm" style="width: 5rem;" id="btnGuardaTpRte">Guardar</button>
            <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
        </div>
    </div>
</div>