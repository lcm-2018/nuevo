<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
$id_referencia = $_POST['id'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `numero`, `id_tes_cuenta`, `fecha`
            FROM `tes_referencia`
            WHERE `id_referencia` = $id_referencia";
    $rs = $cmd->query($sql);
    $referencia = $rs->fetch(PDO::FETCH_ASSOC);
    if (empty($referencia)) {
        $referencia = ['numero' => '', 'id_tes_cuenta' => 0, 'fecha' => date('Y-m-d')];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_tes_cuenta`, `nombre` FROM `tes_cuentas` WHERE `estado` = 1 ORDER BY `nombre`";
    $rs = $cmd->query($sql);
    $bancos = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">REFERENCIA DE PAGOS</h5>
        </div>
        <form id="formNumReferencia" class="px-3">
            <input type="hidden" name="id_referencia" id="id_referencia" value="<?php echo $id_referencia; ?>">
            <div class="row mb-2 pt-2">
                <div class="col-md-12">
                    <label for="numRef" class="small">Número</label>
                    <input type="number" name="numRef" id="numRef" class="form-control form-control-sm bg-input" value="<?= $referencia['numero'] ?>" required>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-12">
                    <label for="banco" class="small">Cuenta Bancaria</label>
                    <select name="banco" id="banco" class="form-control form-control-sm bg-input" required>
                        <option value="0" class="text-muted">--Seleccionar--</option>
                        <?php foreach ($bancos as $banco) {
                            $slc = ($banco['id_tes_cuenta'] == $referencia['id_tes_cuenta']) ? 'selected' : '';
                            echo '<option value="' . $banco['id_tes_cuenta'] . '" ' . $slc . '>' . $banco['nombre'] . '</option>';
                        } ?>
                    </select>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-12">
                    <label for="fecha" class="small">Fecha</label>
                    <input type="date" name="fecha" id="fecha" class="form-control form-control-sm bg-input" value="<?= $referencia['fecha'] ?>" required>
                </div>
            </div>
            <div class="text-center">
                <button class="btn btn-primary btn-sm" onclick="guardarNumReferencia(this)">Guardar</button>
                <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal"> Cancelar</a>
            </div>
            <br>
        </form>
    </div>
</div>