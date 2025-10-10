<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';
$id_rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
$permisos = new \Src\Common\Php\Clases\Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$id_c = isset($_POST['id_c']) ? $_POST['id_c'] : 0;
$id_ter = $_POST['tercero'];
$id_adquisicion = $_POST['id_adquisicion'];
?>
<div class="px-0">
    <div class="shadow">
         <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">DESIGNAR SUPERVISOR DE CONTRATO</h5>
        </div>
        <form id="formDesingSupervisor">
            <input type="hidden" name="id_con_final" value="<?php echo $id_c ?>">
            <input type="hidden" name="id_ter_sup" value="<?php echo $id_ter ?>">
            <input type="hidden" name="id_adquisicion" value="<?php echo $id_adquisicion ?>">
            <div class="row px-4 pt-2">
                <div class="col-md-6 mb-3">
                    <label for="datFecDesigSup" class="small">FECHA DESIGNACÓN</label>
                    <input type="date" name="datFecDesigSup" id="datFecDesigSup" class="form-control form-control-sm bg-input" value="<?php echo date('Y-m-d') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="numMemorando" class="small">Número Memorando</label>
                    <input type="text" name="numMemorando" id="numMemorando" class="form-control form-control-sm bg-input">
                </div>
            </div>
            <div class="row px-4">
                <div class="col-md-12 mb-3">
                    <label for="txtaObservaciones" class="small">OBSERVACIONES</label>
                    <textarea name="txtaObservaciones" id="txtaObservaciones" class="form-control form-control-sm bg-input" rows="3"></textarea>
                </div>
            </div>
            <div class="text-center pb-3">
                <button class="btn btn-primary btn-sm" id="btnDesigSupervisor">Registrar</button>
                <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
            </div>
        </form>
    </div>
</div>