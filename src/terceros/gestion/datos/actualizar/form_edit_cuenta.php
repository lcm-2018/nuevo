<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include('../../../../../config/autoloader.php');
$idT = $_POST['id_t'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `id_banco`, `nom_banco`
            FROM
                `tb_bancos`
            ORDER BY `nom_banco` ASC";
    $rs = $cmd->query($sql);
    $bancos = $rs->fetchAll(PDO::FETCH_ASSOC);

    // Get current account for this tercero
    $sqlCta = "SELECT * FROM `ctt_cuenta_bancaria` WHERE `id_tercero` = ? ORDER BY `id_cta` DESC LIMIT 1";
    $stmt = $cmd->prepare($sqlCta);
    $stmt->execute([$idT]);
    $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$id_banco_actual = $cuenta ? $cuenta['id_banco'] : '0';
$tipo_cuenta_actual = $cuenta ? $cuenta['tipo_cuenta'] : '0';
$num_cuenta_actual = $cuenta ? $cuenta['num_cuenta'] : '';
$id_cta_actual = $cuenta ? $cuenta['id_cta'] : '0';

?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center p-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">EDITAR CUENTA BANCARIA</h5>
        </div>
        <form id="formEditCuenta" enctype="multipart/form-data">
            <input type="hidden" id="idCta" name="idCta" value="<?php echo $id_cta_actual ?>">
            <input type="hidden" id="idTercero" name="idTercero" value="<?php echo $idT ?>">
            <div class="row px-4 pt-4 mb-3">
                <div class="col-md-4">
                    <label for="slcBancoE" class="small">Banco</label>
                    <select class="form-select form-select-sm bg-input" id="slcBancoE" name="slcBancoE">
                        <option value="0">-- Seleccionar --</option>
                        <?php
                        foreach ($bancos as $b) {
                            $selected = ($b['id_banco'] == $id_banco_actual) ? 'selected' : '';
                            echo '<option value="' . $b['id_banco'] . '" ' . $selected . '>' . $b['nom_banco'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="slcTipoCtaE" class="small">Tipo Cuenta</label>
                    <select class="form-select form-select-sm bg-input" id="slcTipoCtaE" name="slcTipoCtaE">
                        <option value="0">-- Seleccionar --</option>
                        <option value="Ahorros" <?php echo ($tipo_cuenta_actual == 'Ahorros') ? 'selected' : '' ?>>Ahorros</option>
                        <option value="Corriente" <?php echo ($tipo_cuenta_actual == 'Corriente') ? 'selected' : '' ?>>Corriente</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="numCuentaE" class="small">Número de cuenta</label>
                    <input type="text" class="form-control form-control-sm bg-input" id="numCuentaE" name="numCuentaE" value="<?php echo $num_cuenta_actual ?>">
                </div>
            </div>
        </form>
        <div class="text-end p-3">
            <button class="btn btn-primary btn-sm" id="btnUpdateCuenta">Actualizar</button>
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </div>
</div>
