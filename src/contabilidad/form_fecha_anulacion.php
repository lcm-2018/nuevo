<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../config/autoloader.php';

$fecha_max = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-12-31'));
$id_ctb_doc = $_POST['id'];

$fecha = date("Y-m-d");
// consultar la fecha de cierre del periodo del módulo de presupuesto 
$cmd = \Config\Clases\Conexion::getConexion();

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_manu` FROM `ctb_doc` WHERE `id_ctb_doc` = $id_ctb_doc";
    $rs = $cmd->query($sql);
    $numero = $rs->fetch();
    $consecutivo = $numero['id_manu'];
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<div class="px-0">
    <form id="formAnulaDocCtb">
        <input type="hidden" id="id_pto_doc" name="id_pto_doc" value="<?php echo $id_ctb_doc; ?>">
        <div class="shadow mb-3">
            <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
                <h5 style="color: white;"><i class="fas fa-lock fa-lg" style="color: #FCF3CF"></i>&nbsp;ANULACIÓN DE DOCUMENTO CONTABLE</h5>
            </div>
            <div class="pt-3 p-3">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label for="numDoc" class="small">NUMERO</label>
                        <input type="text" id="numDoc" name="numDoc" class="form-control form-control-sm bg-input" value="<?php echo $consecutivo; ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="fecha" class="small">FECHA</label>
                        <input type="date" name="fecha" id="fecha" class="form-control form-control-sm bg-input" value="<?php echo date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-12">
                        <label for="numDoc" class="small">CONCEPTO</label>
                        <textarea id="objeto" type="text" name="objeto" class="form-control form-control-sm bg-input py-0 sm" aria-label="Default select example" rows="3" required="required"></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-end">
            <button type="button" class="btn btn-primary btn-sm" onclick="changeEstadoAnulaCtb()">Anular</button>
            <a class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</a>
        </div>
    </form>
</div>