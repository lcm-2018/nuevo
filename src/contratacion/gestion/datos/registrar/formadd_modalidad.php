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
$id_rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
$permisos = new \Src\Common\Php\Clases\Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$cmd = \Config\Clases\Conexion::getConexion();

?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">REGISTRAR MODALIDAD DE CONTRATACION</h5>
        </div>
        <form id="formAddModalidad">
            <div class="row px-4 pt-2">
                <div class="col-md-12 mb-3">
                    <label for="txtModalidad" class="small">NOMBRE MODALIDAD</label>
                    <input id="txtModalidad" type="text" name="txtModalidad" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example">
                </div>
            </div>
            <div class="text-center pb-3">
                <button class="btn btn-primary btn-sm" id="btnAddModalidad">Agregar</button>
                <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal"> Cancelar</a>
            </div>
        </form>
    </div>
</div>