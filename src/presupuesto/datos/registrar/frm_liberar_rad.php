<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}
include_once '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();

$id_rad = isset($_POST['id_rad']) ? $_POST['id_rad'] : 0;
$otro_form = isset($_POST['otro_form']) ? $_POST['otro_form'] : 0;

// se vuelve a consultar los datos del cdp con el id que viene del boton
//------------------------------------
$sql = "SELECT
            `id_pto_rad`
            , `id_manu`
            , DATE_FORMAT(`fecha`,'%Y-%m-%d') AS `fecha` 
            , `num_factura`
        FROM
            `pto_rad` AS `pr`
        WHERE (`id_pto_rad`  = $id_rad) LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs->fetch();
//----------------------------------------------------- hago la consulta aqui para que los saldos sean cajas de texto
$sql = "WITH 
            `val_original` AS (
                SELECT
                    `prd`.`id_rubro`,
                    SUM(IFNULL(`prd`.`valor`,0)) AS `valor`
                FROM
                    `pto_rad_detalle` AS `prd`
                WHERE `prd`.`id_pto_rad` = $id_rad
                GROUP BY `prd`.`id_rubro`
            ),
            `val_liberado` AS (
                SELECT
                    `prd1`.`id_rubro`,
                    SUM(IFNULL(`prd1`.`valor_liberado`,0)) AS `liberado`
                FROM
                    `pto_rad_liberacion` AS `prl`
                    INNER JOIN `pto_rad` AS `pr1` ON `prl`.`id_rad_rel` = `pr1`.`id_pto_rad`
                    INNER JOIN `pto_rad_detalle` AS `prd1` ON `prd1`.`id_pto_rad` = `pr1`.`id_pto_rad`
                WHERE `prl`.`id_rad_main` = $id_rad AND `pr1`.`estado` = 2
                GROUP BY `prd1`.`id_rubro`
            )
        SELECT
            `vo`.`id_rubro`,
            `vo`.`valor`,
            IFNULL(`vl`.`liberado`, 0) AS `liberado`,
            `pc`.`cod_pptal`,
            `pc`.`nom_rubro`
        FROM `val_original` AS `vo`
        LEFT JOIN `val_liberado` AS `vl` ON `vo`.`id_rubro` = `vl`.`id_rubro`
        INNER JOIN `pto_cargue` AS `pc` ON `pc`.`id_cargue` = `vo`.`id_rubro`";
$rs = $cmd->query($sql);
$obj_saldos = $rs->fetchAll(PDO::FETCH_ASSOC);
$rs->closeCursor();
unset($rs);

//---------------------------------------------------
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header p-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LIBERACION DE SALDOS</h5>
        </div>
        <div class="p-3">
            <form id="frm_liberarsaldos">
                <input type="hidden" id="id_rad" name="id_rad" value="<?= $id_rad ?>">
                <div class="row mb-1">
                    <div class="col-md-3">
                        <label for="txt_num_cdp" class="small">NUMERO - FACTURA</label>
                    </div>
                    <div class="col-md-9">
                        <input type="text" class="filtro form-control form-control-sm bg-secondary-subtle"
                            id="txt_num_cdp" name="txt_num_cdp" readonly="true"
                            value="<?= $obj['id_manu'] . ' - ' . $obj['num_factura'] ?>">
                    </div>
                </div>
                <div class="row mb-1">
                    <div class="col-md-3">
                        <label for="txt_fec_cdp" class="small">FECHA</label>
                    </div>
                    <div class="col-md-9">
                        <input type="text" class="filtro form-control form-control-sm bg-secondary-subtle"
                            id="txt_fec_cdp" name="txt_fec_cdp" readonly="true" value="<?= $obj['fecha'] ?>">
                    </div>
                </div>
                <div class="row mb-1">
                    <div class="col-md-3">
                        <label for="txt_fec_lib" class="small">FECHA LIBERACION</label>
                    </div>
                    <div class="col-md-9">
                        <input type="date" class="form-control form-control-sm bg-input" id="txt_fec_lib"
                            name="txt_fec_lib" placeholder="Fecha liberacion" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="row mb-1">
                    <div class="col-md-3">
                        <label for="txt_concepto_lib" class="small">CONCEPTO LIBERACION</label>
                    </div>
                    <div class="col-md-9">
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_concepto_lib"
                            name="txt_concepto_lib" placeholder="Concepto liberacion">
                    </div>
                </div>

                <div class=" w-100 text-left">
                    <table id="tb_saldos"
                        class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100"
                        style="width:100%; font-size:80%">
                        <thead>
                            <tr class="text-center centro-vertical">
                                <th class="bg-sofia">Id Rubro</th>
                                <th class="bg-sofia" style="min-width: 50%;">Codigo</th>
                                <th class="bg-sofia">Valor</th>
                                <th class="bg-sofia">Valor a liberar</th>
                            </tr>
                        </thead>
                        <tbody class="text-left centro-vertical" id="body_tb_saldos"></tbody>
                        <?php
                        foreach ($obj_saldos as $dll) {
                            ?>
                            <tr>
                                <td class="border" colspan="1">
                                    <input type="text" name="txt_id_rubro[]"
                                        class="form-control form-control-sm bg-plain bg-secondary-subtle"
                                        value="<?= $dll['id_rubro'] ?>" readonly="true">
                                </td>
                                <td class="border" colspan="1">
                                    <input type="text" name="txt_codigo[]"
                                        class="form-control form-control-sm  bg-plain bg-secondary-subtle"
                                        value="<?= $dll['cod_pptal'] ?>" readonly="true">
                                </td>
                                <td class="border" colspan="1">
                                    <input type="text" name="txt_valor[]"
                                        class="form-control form-control-sm bg-plain bg-secondary-subtle"
                                        value="<?= $dll['valor'] - $dll['liberado'] ?>" readonly="true">
                                </td>
                                <td class="border" colspan="1">
                                    <input type="text" name="txt_valor_liberar[]"
                                        class="form-control form-control-sm valfno bg-plain bg-input"
                                        value="<?= $dll['valor'] - $dll['liberado'] ?>">
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </table>
                </div>
            </form>
        </div>
    </div>
    <div class="text-end py-3">
        <a type="button" class="btn btn-primary btn-sm" onclick="RegLiberacionRad()">Liberar</a>
        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>