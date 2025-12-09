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
            <h5 style="color: white;">REGISTRAR FIRMA</h5>
        </div>
        <form id="formAddFirma" enctype="multipart/form-data">
            <div class="row px-4 pt-2">
                <div class="col-md-6 mb-3">
                    <label for="txtNomVariable" class="small">NOMBRE DE VARIABLE</label>
                    <input id="txtNomVariable" type="text" name="txtNomVariable" class="form-control form-control-sm bg-input" placeholder="Ej: firma1">
                    <small class="text-muted">Se concatenará con ${nombre_variable}</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="buscaTercero" class="small">RESPONSABLE (TERCERO)</label>
                    <input type="text" id="buscaTercero" name="buscaTercero" class="form-control form-control-sm bg-input awesomplete">
                    <input type="hidden" id="id_tercero" name="id_tercero" value="0">
                </div>
            </div>
            <div class="row px-4">
                <div class="col-md-6 mb-3">
                    <label for="txtCargo" class="small">CARGO</label>
                    <input id="txtCargo" type="text" name="txtCargo" class="form-control form-control-sm bg-input" placeholder="Cargo del responsable">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="fileFirma" class="small">IMAGEN FIRMA (Solo PNG)</label>
                    <input type="file" class="form-control form-control-sm bg-input" id="fileFirma" name="fileFirma" accept=".png">
                    <small class="text-muted">Formato: PNG | Máximo: 2MB</small>
                </div>
            </div>
            <div>
                <div class="text-center pb-3">
                    <button class="btn btn-primary btn-sm" type="button" id="btnAddFirma">Agregar</button>
                    <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>