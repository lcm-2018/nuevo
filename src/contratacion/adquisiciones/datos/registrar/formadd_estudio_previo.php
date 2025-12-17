<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}

include_once '../../../../../config/autoloader.php';

$id_compra = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida ');

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_adquisicion`, `val_contrato`
            FROM
                `ctt_adquisiciones` WHERE `id_adquisicion` = $id_compra";
    $rs = $cmd->query($sql);
    $adquisicion = $rs->fetch();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `id_form_pago`, `descripcion`
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
                `tb_terceros`.`id_tercero_api`
                , `tb_terceros`.`nit_tercero`
                , `tb_terceros`.`nom_tercero`
            FROM
                `tb_terceros`
                INNER JOIN `tb_rel_tercero` 
                    ON (`tb_rel_tercero`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE `tb_terceros`.`estado` = 1 AND `tb_rel_tercero`.`id_tipo_tercero` = 3";
    $rs = $cmd->query($sql);
    $supervisor = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
            `id_poliza`, `descripcion`, `porcentaje`
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
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                SUM(`cocd`.`cantidad` *`cocd`.`val_unid`) AS `total`
            FROM
                `ctt_orden_compra_detalle` AS `cocd`
                INNER JOIN `ctt_orden_compra` 
                    ON (`cocd`.`id_oc` = `ctt_orden_compra`.`id_oc`)
                INNER JOIN `ctt_adquisiciones` AS `adq`
                    ON (`ctt_orden_compra`.`id_adq` = `adq`.`id_adquisicion`)
            WHERE (`adq`.`id_adquisicion` = $id_compra)";
    $rs = $cmd->query($sql);
    $valor = $rs->fetch()['total'] ?? 0;
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">REGISTRAR ESTUDIOS PREVIOS</h5>
        </div>
        <form id="formAddEstudioPrevio">
            <input type="hidden" name="id_compra" value="<?= $id_compra ?>">
            <div class="row px-4 pt-2">
                <div class="col-md-4 mb-3">
                    <label for="datFecIniEjec" class="small">FECHA INICIAL</label>
                    <input type="date" name="datFecIniEjec" id="datFecIniEjec" class="form-control form-control-sm bg-input">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="datFecFinEjec" class="small">FECHA FINAL</label>
                    <input type="date" name="datFecFinEjec" id="datFecFinEjec" class="form-control form-control-sm bg-input">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="numValContrata" class="small">Valor total contrata</label>
                    <input type="number" name="numValContrata" id="numValContrata" class="form-control form-control-sm bg-input" value="<?= $adquisicion['val_contrato'] > 0 ? $adquisicion['val_contrato'] : $valor ?>">
                </div>
            </div>
            <div class="row px-4">
                <div class="col-md-4 mb-3">
                    <label for="slcFormPago" class="small">FORMA DE PAGO</label>
                    <select id="slcFormPago" name="slcFormPago" class="form-select form-select-sm bg-input" aria-label="Default select example">
                        <option value="0">-- Seleccionar --</option>
                        <?php
                        foreach ($forma_pago as $fp) {
                            echo '<option value="' . $fp['id_form_pago'] . '">' . $fp['descripcion'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="slcSupervisor" class="small">SUPERVISOR</label>
                    <select id="slcSupervisor" name="slcSupervisor" class="form-select form-select-sm bg-input" aria-label="Default select example">
                        <option value="0">-- Seleccionar --</option>
                        <option value="A">PENDIENTE</option>
                        <?php
                        foreach ($supervisor as $s) {
                            echo '<option value="' . $s['id_tercero_api'] . '">' . $s['nom_tercero'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="numDS" class="small">Número DC</label>
                    <input type="number" name="numDS" id="numDS" class="form-control form-control-sm bg-input">
                </div>
            </div>
            <?php if (!empty($polizas)) { ?>
                <div class="row px-4 pt-2">
                    <span class="small px-4">PÓLIZAS</span>
                    <?php
                    $cant = 1;
                    foreach ($polizas as $pz) { ?>
                        <div class="col-md-4 mb-3">
                            <div class="input-group input-group-sm">
                                <div class="input-group-text">
                                    <input class="form-check-input mt-0" type="checkbox" aria-label="Checkbox for following text input" id="check_<?= $cant;
                                                                                                                                                    $cant++ ?>" name="check[]" value="<?= $pz['id_poliza'] ?>">
                                </div>
                                <div class="form-control form-control-sm" aria-label="Text input with checkbox" style="font-size: 55%;"><?= $pz['descripcion'] . ' ' . $pz['porcentaje'] . '%' ?> </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            <?php } ?>
        </form>
        <div class="px-4">
            <nav>
                <div class="nav nav-tabs small" id="nav-tab" role="tablist">
                    <button class="nav-link active small" id="nav_necesidad-tab" data-bs-toggle="tab" data-bs-target="#nav_necesidad" type="button" role="tab" aria-controls="nav_necesidad" aria-selected="true" title="Descripción de la necesidad">Necesidad</button>
                    <button class="nav-link small" id="nav-actividad-tab" data-bs-toggle="tab" data-bs-target="#nav-actividad" type="button" role="tab" aria-controls="nav-actividad" aria-selected="false">Actividades</button>
                    <button class="nav-link small" id="nav-producto-tab" data-bs-toggle="tab" data-bs-target="#nav-producto" type="button" role="tab" aria-controls="nav-producto" aria-selected="false" title="Productos a entregar">Productos</button>
                    <button class="nav-link small" id="nav-obligacion-tab" data-bs-toggle="tab" data-bs-target="#nav-obligacion" type="button" role="tab" aria-controls="nav-obligacion" aria-selected="false" title="Obligaciones del contratista">Obligaciones</button>
                    <button class="nav-link small" id="nav-valor-tab" data-bs-toggle="tab" data-bs-target="#nav-valor" type="button" role="tab" aria-controls="nav-valor" aria-selected="false" title="Descripción del valor">Valor</button>
                    <button class="nav-link small" id="nav-pago-tab" data-bs-toggle="tab" data-bs-target="#nav-pago" type="button" role="tab" aria-controls="nav-pago" aria-selected="false" title="Forma de Pago">Pago</button>
                    <button class="nav-link small" id="nav-requisito-tab" data-bs-toggle="tab" data-bs-target="#nav-requisito" type="button" role="tab" aria-controls="nav-requisito" aria-selected="false" title="Requisitos mínimos habilitanes">Requisitos</button>
                    <button class="nav-link small" id="nav-garantia-tab" data-bs-toggle="tab" data-bs-target="#nav-garantia" type="button" role="tab" aria-controls="nav-garantia" aria-selected="false" title="Garantías de Contratación">Garantías</button>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav_necesidad" role="tabpanel" aria-labelledby="nav_regTercro-tab">
                    <textarea name="txtDescNec" id="txtDescNec" cols="30" rows="14" class="form-control form-control-sm bg-input"></textarea>
                </div>
                <div class="tab-pane fade" id="nav-actividad" role="tabpanel" aria-labelledby="nav-actividad-tab">
                    <textarea name="txtActEspecificas" id="txtActEspecificas" cols="30" rows="14" class="form-control form-control-sm bg-input"></textarea>
                </div>
                <div class="tab-pane fade" id="nav-producto" role="tabpanel" aria-labelledby="nav-producto-tab">
                    <textarea name="txtProdEntrega" id="txtProdEntrega" cols="30" rows="14" class="form-control form-control-sm bg-input"></textarea>
                </div>
                <div class="tab-pane fade" id="nav-obligacion" role="tabpanel" aria-labelledby="nav-obligacion-tab">
                    <textarea name="txtObligContratista" id="txtObligContratista" cols="30" rows="14" class="form-control form-control-sm bg-input"></textarea>
                </div>
                <div class="tab-pane fade" id="nav-valor" role="tabpanel" aria-labelledby="nav-valor-tab">
                    <textarea name="txtDescValor" id="txtDescValor" cols="30" rows="14" class="form-control form-control-sm bg-input"></textarea>
                </div>
                <div class="tab-pane fade" id="nav-pago" role="tabpanel" aria-labelledby="nav-pago-tab">
                    <textarea name="txtFormPago" id="txtFormPago" cols="30" rows="14" class="form-control form-control-sm bg-input"></textarea>
                </div>
                <div class="tab-pane fade" id="nav-requisito" role="tabpanel" aria-labelledby="nav-requisito-tab">
                    <textarea name="txtReqMinHab" id="txtReqMinHab" cols="30" rows="14" class="form-control form-control-sm bg-input"></textarea>
                </div>
                <div class="tab-pane fade" id="nav-garantia" role="tabpanel" aria-labelledby="nav-garantia-tab">
                    <textarea name="txtGarantias" id="txtGarantias" cols="30" rows="14" class="form-control form-control-sm bg-input"></textarea>
                </div>
            </div>
        </div>
        <div class="text-center py-3">
            <button class="btn btn-primary btn-sm" id="btnAddNewEstudioPrevio">Registrar</button>
            <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal"> Cancelar</a>
        </div>
    </div>
</div>