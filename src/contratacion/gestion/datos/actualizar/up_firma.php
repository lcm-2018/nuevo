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

$idFirma = isset($_POST['idFirma']) ? $_POST['idFirma'] : exit('Acción no permitida');

include_once '../../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();

try {
    $sql = "SELECT 
                `ctt_firmas`.`id`,
                `ctt_firmas`.`id_variable`,
                `ctt_firmas`.`id_tercero_api`,
                `ctt_firmas`.`cargo`,
                `ctt_firmas`.`nom_imagen`,
                `ctt_variables_forms`.`variable`,
                `tb_terceros`.`nom_tercero`,
                `tb_terceros`.`nit_tercero`
            FROM `ctt_firmas`
            INNER JOIN `ctt_variables_forms` 
                ON (`ctt_firmas`.`id_variable` = `ctt_variables_forms`.`id_var`)
            INNER JOIN `tb_terceros`
                ON (`ctt_firmas`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE `ctt_firmas`.`id` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $idFirma, PDO::PARAM_INT);
    $stmt->execute();
    $firma = $stmt->fetch(PDO::FETCH_ASSOC);
    $firma['variable'] = str_replace('${', '', $firma['variable']);
    $firma['variable'] = str_replace('}', '', $firma['variable']);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

if (!empty($firma)) {
    try {
        // Obtener terceros para el select
        $sql = "SELECT `id_tercero_api`, `nit_tercero`, `nom_tercero`
                FROM `tb_terceros`
                ORDER BY `nom_tercero`";
        $rs = $cmd->query($sql);
        $terceros = $rs->fetchAll(PDO::FETCH_ASSOC);
        $rs->closeCursor();
        unset($rs);
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
?>
    <div class="px-0">
        <div class="shadow">
            <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                <h5 style="color: white;">ACTUALIZAR FIRMA</h5>
            </div>
            <form id="formActualizaFirma" enctype="multipart/form-data">
                <input type="hidden" id="idFirma" name="idFirma" value="<?= $firma['id'] ?>">
                <input type="hidden" id="idVariable" name="idVariable" value="<?= $firma['id_variable'] ?>">
                <input type="hidden" id="nomImagenActual" name="nomImagenActual" value="<?= $firma['nom_imagen'] ?>">

                <div class="row px-4 pt-2">
                    <div class="col-md-6 mb-3">
                        <label for="txtNomVariable" class="small">NOMBRE DE VARIABLE</label>
                        <input id="txtNomVariable" type="text" name="txtNomVariable" class="form-control form-control-sm bg-input" value="<?= $firma['variable'] ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="buscaTercero" class="small">RESPONSABLE (TERCERO)</label>
                        <input type="text" id="buscaTercero" name="buscaTercero" class="form-control form-control-sm bg-input awesomplete" value="<?= $firma['nom_tercero'] ?>">
                        <input type="hidden" id="id_tercero" name="id_tercero" value="<?= $firma['id_tercero_api'] ?>">
                    </div>
                </div>
                <div class="row px-4">
                    <div class="col-md-6 mb-3">
                        <label for="txtCargo" class="small">CARGO</label>
                        <input id="txtCargo" type="text" name="txtCargo" class="form-control form-control-sm bg-input" value="<?= $firma['cargo'] ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="fileFirma" class="small">CAMBIAR IMAGEN (Solo PNG)</label>
                        <input type="file" class="form-control form-control-sm bg-input" id="fileFirma" name="fileFirma" accept=".png">
                        <small class="text-muted">Actual: <?= $firma['nom_imagen'] ?> | Formato: PNG | Máximo: 2MB</small>
                    </div>
                </div>
                <div class="text-center pb-3">
                    <button class="btn btn-primary btn-sm" type="button" id="btnUpFirma">Actualizar</button>
                    <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
<?php
} else {
    echo 'Error al intentar obtener datos';
}
?>