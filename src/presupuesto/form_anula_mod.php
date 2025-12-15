<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../config/autoloader.php';

$id = isset($_POST['data']) ? $_POST['data'] : exit('Acceso no disponible');

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_manu` FROM `pto_mod` WHERE `id_pto_mod` = $id";
    $rs = $cmd->query($sql);
    $numero = $rs->fetch();
    $consecutivo = $numero['id_manu'];
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<div class="px-0">
    <form id="formAnulaDoc">
        <input type="hidden" id="id_pto_mod" name="id_pto_mod" value="<?php echo $id; ?>">
        <div class="shadow mb-3">
            <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
                <h5 class="mb-0" style="color: white;"><i class="fas fa-lock fa-lg" style="color: #FCF3CF"></i>&nbsp;ANULACIÓN DE DOCUMENTO PRESUPUESTAL</h5>
            </div>
            <div class="pt-3 px-3">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="numDoc" class="small">NUMERO</label>
                        <input type="text" id="numDoc" name="numDoc" class="form-control form-control-sm bg-input" value="<?php echo $consecutivo; ?>" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="fecha" class="small">FECHA</label>
                        <input type="date" name="fecha" id="fecha" class="form-control form-control-sm bg-input" value="<?php echo date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="numDoc" class="small">CONCEPTO</label>
                        <textarea id="objeto" type="text" name="objeto" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example" rows="3" required="required"></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-end">
            <button type="button" class="btn btn-primary btn-sm" onclick="changeEstadoAnulacionMod()">Anular</button>
            <a class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</a>
        </div>
    </form>
</div>