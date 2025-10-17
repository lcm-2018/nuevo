<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';
$id_cc = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida ');
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `ctt_contratos`.`id_contrato_compra`
                , `ctt_contratos`.`id_compra`
                , `ctt_contratos`.`fec_fin`
                , `ctt_contratos`.`fec_ini`
                , `ctt_contratos`.`val_contrato`
                , `ctt_contratos`.`id_forma_pago`
                , `ctt_contratos`.`id_supervisor`
                , `ctt_adquisiciones`.`id_tercero`
                , `tb_terceros`.`nit_tercero`
                , `tb_terceros`.`nom_tercero`
            FROM
                `ctt_contratos`
            INNER JOIN `ctt_adquisiciones` 
                ON (`ctt_contratos`.`id_compra` = `ctt_adquisiciones`.`id_adquisicion`)
            LEFT JOIN `tb_terceros` 
                ON (`ctt_adquisiciones`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE `id_contrato_compra` = $id_cc";
    $rs = $cmd->query($sql);
    $contrato = $rs->fetch();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$id_tercero = isset($contrato) ? $contrato['id_tercero'] : 0;
$id_contra = isset($contrato) ? $contrato['id_contrato_compra'] : 0;
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_garantia`
                , `id_contrato_compra`
                , `id_poliza`
            FROM
                `ctt_garantias_compra`
            WHERE `id_contrato_compra`  = '$id_contra'";
    $rs = $cmd->query($sql);
    $garantias = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_form_pago`
                , `descripcion`
            FROM
                `tb_forma_pago_compras` ORDER BY `descripcion` ASC ";
    $rs = $cmd->query($sql);
    $forma_pago = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`id_tercero_api`
            FROM
                `tb_terceros`
                INNER JOIN  `tb_rel_tercero`
                    ON (`tb_rel_tercero`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE `tb_terceros`.`estado` = 1 AND `tb_rel_tercero`.`id_tipo_tercero` = 3";
    $rs = $cmd->query($sql);
    $supervisor = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
            `id_poliza`
            , `descripcion`
            , `porcentaje`
        FROM
            `tb_polizas` ORDER BY `descripcion` ASC";
    $rs = $cmd->query($sql);
    $polizas = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">ACTUALIZAR CONTRATO</h5>
        </div>
        <form id="formUpContraCompra">
            <input type="hidden" name="id_cc" value="<?= $id_cc ?>">
            <div class="row px-4 pt-2">
                <div class="col-md-4 mb-3">
                    <label for="datFecIniEjec" class="small">FECHA INICIAL CONTRATO</label>
                    <input type="date" name="datFecIniEjec" id="datFecIniEjec" class="form-control form-control-sm bg-input" value="<?= $contrato['fec_ini'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="datFecFinEjec" class="small">FECHA FINAL CONTRATO</label>
                    <input type="date" name="datFecFinEjec" id="datFecFinEjec" class="form-control form-control-sm bg-input" value="<?= $contrato['fec_fin'] ?>">
                </div>
                <?php
                $fini = new DateTime($contrato['fec_ini']);
                $ffin = new DateTime($contrato['fec_fin']);
                $ffin_ajustada = clone $ffin;
                $ffin_ajustada->modify('+1 day');
                $diff = $fini->diff($ffin_ajustada);
                $dias = $diff->d > 0 ? $diff->d . ' día(s)' : '';
                ?>
                <div class="col-md-4 mb-3">
                    <label for="divDuraContrato" class="small">DURACIÓN DEL CONTRATO</label>
                    <div id="divDuraContrato" class="form-control form-control-sm">
                        <?= $diff->m . ' mes(es) ' . $dias ?>
                    </div>
                </div>
            </div>
            <div class="row px-4">
                <div class="col-md-12 mb-3">
                    <label for="SeaTercer" class="small">TERCERO</label>
                    <input type="text" id="SeaTercer" class="form-control form-control-sm bg-input" placeholder="Buscar tercero" value="<?= $contrato['nom_tercero'] . ' -> ' . $contrato['nit_tercero'] ?>">
                    <input type="hidden" name="id_tercero" id="id_tercero" value="<?= $id_tercero ?>">
                </div>
            </div>
            <div class="row px-4">
                <div class="col-md-4 mb-3">
                    <label for="numValContrata" class="small">Valor Contrato</label>
                    <input type="number" name="numValContrata" id="numValContrata" class="form-control form-control-sm bg-input" value="<?= $contrato['val_contrato'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="slcFormPago" class="small">FORMA DE PAGO</label>
                    <select id="slcFormPago" name="slcFormPago" class="form-select form-select-sm bg-input" aria-label="Default select example">
                        <?php
                        foreach ($forma_pago as $fp) {
                            $selecionada = '';
                            if ($fp['id_form_pago'] == $contrato['id_forma_pago']) {
                                $selecionada = 'selected';
                            }
                            echo '<option ' . $selecionada . ' value="' . $fp['id_form_pago'] . '">' . $fp['descripcion'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="slcSupervisor" class="small">SUPERVISOR</label>
                    <select id="slcSupervisor" name="slcSupervisor" class="form-select form-select-sm bg-input" aria-label="Default select example">
                        <?php
                        foreach ($supervisor as $s) {
                            $selecionada = '';
                            if ($s['id_tercero_api'] == $contrato['id_supervisor']) {
                                $selecionada = 'selected';
                            }
                            echo '<option ' . $selecionada . ' value="' . $s['id_tercero_api'] . '">' . $s['nom_tercero'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <?php if (!empty($polizas)) { ?>
                <label for="slcSupervisor" class="small px-4">PÓLIZAS</label>
            <?php } ?>
            <div class="row px-4">

                <?php
                $cant = 1;
                foreach ($polizas as $pz) {
                    $chequeado = '';
                    $idp = $pz['id_poliza'];
                    $key = array_search($idp, array_column($garantias, 'id_poliza'));
                    if (false !== $key) {
                        $chequeado = 'checked';
                    }
                ?>
                    <div class="col-md-4 mb-3">
                        <div class="input-group input-group-sm">
                            <div class="input-group-text">
                                <input class="form-check-input mt-0" type="checkbox" aria-label="Checkbox for following text input" id="check_<?= $cant;
                                                                                                                                                $cant++ ?>" name="check[]" value="<?= $pz['id_poliza'] ?>" <?= $chequeado ?>>
                            </div>
                            <div class="form-control form-control-sm text-start" aria-label="Text input with checkbox" style="font-size: 55%;"><?= $pz['descripcion'] . ' ' . $pz['porcentaje'] ?> </div>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>
            <div class="text-center pb-3">
                <button class="btn btn-primary btn-sm" id="btnUpContratoCompra">Actualizar</button>
                <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal"> Cancelar</a>
            </div>
        </form>
    </div>
</div>