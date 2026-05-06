<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}
include '../../../../../config/autoloader.php';

$id_t = isset($_POST['idt']) ? $_POST['idt'] : 0;
$id_deduc = 0;

$intereses = 0;
$medicina = 0;
$polizas = 0;
$afc = 0;
$pension = 0;

if ($id_t > 0) {
    try {
        $cmd = \Config\Clases\Conexion::getConexion();
        $sql = "SELECT * FROM tb_terceros_deducciones WHERE id_tercero_api = ?";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([$id_t]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $id_deduc = $row['id_deduccion'];
            $intereses = $row['intereses_vivienda'];
            $medicina = $row['medicina_prepagada'];
            $polizas = $row['polizas_salud'];
            $afc = $row['ahorros_afc'];
            $pension = $row['aportes_pension'];
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header bg-sofia mb-3 p-2 text-white">
            <h5 class="m-0">
                <i class="fas fa-file-invoice-dollar fa-lg mb-1 me-2"></i> GESTIONAR DEDUCCIONES
            </h5>
        </div>
        <div class="px-3 pb-2">
            <form id="formAddDeduccion">
                <input type="hidden" id="idTercero" name="idTercero" value="<?php echo $id_t; ?>">
                <input type="hidden" id="idDeduccion" name="idDeduccion" value="<?php echo $id_deduc; ?>">
                
                <div class="row align-items-center mb-2">
                    <div class="col-6">
                        <label for="txtIntereses" class="small text-secondary">Intereses por crédito de vivienda</label>
                    </div>
                    <div class="col-6">
                        <input type="number" step="0.01" class="form-control form-control-sm text-end" id="txtIntereses" name="txtIntereses" value="<?php echo $intereses; ?>" required>
                    </div>
                </div>

                <div class="row align-items-center mb-2">
                    <div class="col-6">
                        <label for="txtMedicina" class="small text-secondary">Medicina prepagada</label>
                    </div>
                    <div class="col-6">
                        <input type="number" step="0.01" class="form-control form-control-sm text-end" id="txtMedicina" name="txtMedicina" value="<?php echo $medicina; ?>" required>
                    </div>
                </div>

                <div class="row align-items-center mb-2">
                    <div class="col-6">
                        <label for="txtPolizas" class="small text-secondary">Pólizas de salud</label>
                    </div>
                    <div class="col-6">
                        <input type="number" step="0.01" class="form-control form-control-sm text-end" id="txtPolizas" name="txtPolizas" value="<?php echo $polizas; ?>" required>
                    </div>
                </div>

                <div class="row align-items-center mb-2">
                    <div class="col-6">
                        <label for="txtAfc" class="small text-secondary">Ahorros a cuentas AFC</label>
                    </div>
                    <div class="col-6">
                        <input type="number" step="0.01" class="form-control form-control-sm text-end" id="txtAfc" name="txtAfc" value="<?php echo $afc; ?>" required>
                    </div>
                </div>

                <div class="row align-items-center mb-2">
                    <div class="col-6">
                        <label for="txtPension" class="small text-secondary">Aportes Voluntarios a Pensión</label>
                    </div>
                    <div class="col-6">
                        <input type="number" step="0.01" class="form-control form-control-sm text-end" id="txtPension" name="txtPension" value="<?php echo $pension; ?>" required>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="text-end pt-3">
        <button type="button" class="btn btn-secondary btn-sm shadow" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary btn-sm shadow" id="btnGuardaDeduccion">Guardar</button>
    </div>
</div>
