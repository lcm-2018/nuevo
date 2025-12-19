<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include_once '../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$id_doc = $_POST['id_doc'] ?? 0;
$id_cop = $_POST['id_cop'] ?? 0;
$id_fp = $_POST['id_fp'] ?? 0;
$valor_pago = $_POST['valor'] ?? 0;

$valor_descuento = 0;

$cmd = \Config\Clases\Conexion::getConexion();

try {
    $sql = "SELECT
                `tes_detalle_pago`.`id_detalle_pago`
                ,`tb_bancos`.`nom_banco`
                , `tes_cuentas`.`nombre`
                , `tes_forma_pago`.`forma_pago`
                , `tes_detalle_pago`.`documento`
                , `tes_detalle_pago`.`valor`
            FROM
                `tes_detalle_pago`
                INNER JOIN `tes_forma_pago` 
                    ON (`tes_detalle_pago`.`id_forma_pago` = `tes_forma_pago`.`id_forma_pago`)
                INNER JOIN `tes_cuentas` 
                    ON (`tes_detalle_pago`.`id_tes_cuenta` = `tes_cuentas`.`id_tes_cuenta`)
                INNER JOIN `tb_bancos` 
                    ON (`tes_cuentas`.`id_banco` = `tb_bancos`.`id_banco`)
            WHERE (`tes_detalle_pago`.`id_ctb_doc` = $id_doc);";
    $rs = $cmd->query($sql);
    $rubros = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
// consultar id bancos de tb_bancos
try {
    $sql = "SELECT 
                `tb_bancos`.`id_banco`, `tb_bancos`.`nom_banco`
            FROM `tb_bancos` 
            LEFT JOIN `tes_cuentas`
                ON (`tb_bancos`.`id_banco` = `tes_cuentas`.`id_banco`)
            WHERE `tes_cuentas`.`id_banco` IS NOT NULL
            GROUP BY `tb_bancos`.`id_banco`
            ORDER BY `nom_banco` ASC";
    $rs = $cmd->query($sql);
    $bancos = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
// Consultar forma de pago de tes_forma_pago
try {
    $sql = "SELECT `id_forma_pago`, `forma_pago` FROM `tes_forma_pago` ORDER BY `forma_pago` ASC";
    $rs = $cmd->query($sql);
    $formas_pago = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Consultar el valor de los descuentos realizados a la cuenta de ctb_causa_retencion
try {
    $sql = "SELECT SUM(`valor_retencion`) AS `valor` FROM `ctb_causa_retencion` WHERE `id_ctb_doc` = $id_cop";
    $rs = $cmd->query($sql);
    $descuentos = $rs->fetch();
    $valor_descuento = $valor_descuento + $descuentos['valor'];
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
// consultar el valor registrado en tes_detalle_pago para el id_ctb_doc
try {
    $sql = "SELECT SUM(`valor`) AS `valor` FROM `tes_detalle_pago` WHERE `id_ctb_doc` = $id_doc";
    $rs = $cmd->query($sql);
    $pagos = $rs->fetch();
    $valor_programado = $pagos['valor'];
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$valor_pagar = $valor_pago - $valor_descuento - $valor_programado;
if ($_SESSION['pto'] == '0') {
    try {
        $sql = "SELECT 
                    `causado`.`valor` AS `valor_pagar`
                    , IFNULL(`pagado`.`valor`,0) AS `valor_pagado`
                FROM 
                    `ctb_doc`
                    INNER JOIN
                        (SELECT
                            `ctb_libaux`.`id_ctb_doc`
                            , SUM(`ctb_libaux`.`credito`) AS `valor`
                        FROM
                            `ctb_libaux`
                            INNER JOIN `ctb_doc` 
                            ON (`ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                        WHERE (`ctb_libaux`.`id_ctb_doc` = $id_cop AND `ctb_libaux`.`ref` = 1)) AS `causado`
                        ON(`causado`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                    LEFT JOIN
                        (SELECT
                            `ctb_doc`.`id_ctb_doc_tipo3`
                            , SUM(`tes_detalle_pago`.`valor`) AS `valor`
                        FROM
                            `tes_detalle_pago`
                            INNER JOIN `ctb_doc` 
                                ON (`tes_detalle_pago`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                        WHERE (`ctb_doc`.`id_ctb_doc_tipo3` = $id_cop)) AS `pagado`
                        ON(`causado`.`id_ctb_doc` = `pagado`.`id_ctb_doc_tipo3`)";
        $rs = $cmd->query($sql);
        $pagos = $rs->fetch();
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
    $valor_pagar = !empty($pagos) ? $pagos['valor_pagar'] - $pagos['valor_pagado'] : 0;
}

// Construir opciones de select
$optionsBancos = '<option value="0">--Seleccione--</option>';
foreach ($bancos as $banco) {
    $optionsBancos .= '<option value="' . $banco['id_banco'] . '">' . $banco['nom_banco'] . '</option>';
}

$optionsFormasPago = '<option value="0">--Seleccione--</option>';
foreach ($formas_pago as $forma_pago) {
    $optionsFormasPago .= '<option value="' . $forma_pago['id_forma_pago'] . '">' . $forma_pago['forma_pago'] . '</option>';
}

// Construir filas de la tabla
$filasTabla = '';
foreach ($rubros as $ce) {
    $id = $ce['id_detalle_pago'];
    $editar = '';
    if ($permisos->PermisosUsuario($opciones, 5601, 3) || $id_rol == 1) {
        $editar = '<a value="' . $id_doc . '" onclick="eliminarFormaPago(' . $id . ')" class="btn btn-outline-danger btn-xs rounded-circle shadow" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
    }
    $filasTabla .= '<tr id="' . $id . '">
        <td class="text-start">' . $ce['nom_banco'] . '</td>
        <td class="text-start">' . $ce['nombre'] . '</td>
        <td class="text-center">' . $ce['forma_pago'] . '</td>
        <td class="text-center">' . $ce['documento'] . '</td>
        <td class="text-end">' . number_format($ce['valor'], 2, '.', ',') . '</td>
        <td class="text-center">' . $editar . '</td>
    </tr>';
}
?>
<script>
    $('#tableCausacionPagos').DataTable({
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ]
    });
    $('#tableCausacionPagos').wrap('<div class="overflow" />');
</script>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE CUENTAS BANCARIAS Y FORMA DE PAGO</h5>
        </div>
        <div class="px-4 mt-3">
            <form id="formAddFormaPago">
                <input type="hidden" name="id_doc" id="id_doc" value="<?= $id_doc; ?>">
                <input type="hidden" name="id_pto_cop" id="id_pto_cop" value="<?= $id_cop; ?>">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label for="banco" class="small fw-bold">BANCO</label>
                        <select name="banco" id="banco" class="form-select form-select-sm bg-input" required onclick="mostrarCuentas(value);">
                            <?= $optionsBancos; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="cuentas" class="small fw-bold">CUENTA</label>
                        <div id="divBanco">
                            <select name="cuentas" id="cuentas" class="form-select form-select-sm bg-input">
                                <option value="0">--Seleccione--</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label for="forma_pago_det" class="small fw-bold">FORMA DE PAGO</label>
                        <div id="divForma">
                            <select name="forma_pago_det" id="forma_pago_det" class="form-select form-select-sm bg-input" required onchange="buscarCheque(value);">
                                <?= $optionsFormasPago; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label for="documento" class="small fw-bold">DOCUMENTO</label>
                        <input type="text" name="documento" id="documento" class="form-control form-control-sm bg-input" value="" required>
                    </div>
                    <div class="col-md-2">
                        <label for="valor_pag" class="small fw-bold">VALOR</label>
                        <input type="text" name="valor_pag" id="valor_pag" class="form-control form-control-sm bg-input text-end" max="<?= $valor_pagar; ?>" value="<?= $valor_pagar; ?>" required onkeyup="NumberMiles(this)" ondblclick="valorMovTeroreria('');">
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label for="divSaldoDisp" class="small fw-bold">SALDO</label>
                        <div id="divSaldoDisp" class="form-control form-control-sm bg-secondary-subtle text-end"></div>
                        <input type="hidden" name="numSaldoDips" id="numSaldoDips" value="0">
                    </div>
                </div>
            </form>
            <table id="tableCausacionPagos" class="table table-striped table-bordered table-sm table-hover shadow" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="bg-sofia">Banco</th>
                        <th class="bg-sofia">Cuenta</th>
                        <th class="bg-sofia">Forma de pago</th>
                        <th class="bg-sofia">Documento</th>
                        <th class="bg-sofia">Valor</th>
                        <th class="bg-sofia">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?= $filasTabla; ?>
                </tbody>
            </table>
            <div class="text-end py-3">
                <a type="button" class="btn btn-success btn-sm" onclick="GuardaFormaPago(this)">Guardar</a>
                <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</a>
            </div>
        </div>
    </div>
</div>