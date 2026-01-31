<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../config/autoloader.php';


use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);


// Consulta tipo de presupuesto
$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];
$id_cop_add = $_POST['id_cop_add'] ?? 0;
$id_tipo = $_POST['id_tipo'];
$ini = $vigencia . '-01-01';
$fin = $vigencia . '-12-31';


try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_pto` FROM `pto_presupuestos` WHERE (`id_vigencia` = $id_vigencia AND `id_tipo` = 2)";
    $rs = $cmd->query($sql);
    $listappto = $rs->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    if ($_SESSION['pto'] == '1') {
        $sql = "SELECT 
                `t1`.`id_ctb_doc`
                , `t1`.`causacion`
                , `t1`.`registro`
                , `t1`.`id_tercero`
                , `t1`.`fecha`
                , SUM(`t1`.`valor`) AS `valor`
                , SUM(`t1`.`valor_pagado`) AS `valor_pagado`
                , `t1`.`num_contrato`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM 
                (SELECT
                    `ctb_doc`.`id_ctb_doc`
                    , `ctb_doc`.`id_manu` AS `causacion`
                    , `pto_crp`.`id_manu` AS `registro`
                    , `ctb_doc`.`id_tercero`
                    , DATE_FORMAT(`ctb_doc`.`fecha`,'%Y-%m-%d') AS `fecha`
                    , `pto_cop_detalle`.`valor`
                    , IFNULL(`pto_pag_detalle`.`valor_pago`,0) AS `valor_pagado`
                    , `ctt_contratos`.`num_contrato`
                FROM
                    `pto_cop_detalle`
                    LEFT JOIN 
                        (SELECT
                            `id_pto_cop_det`
                            , IFNULL(SUM(`valor`),0) - IFNULL(SUM(`valor_liberado`),0) AS valor_pago
                        FROM
                            `pto_pag_detalle`
                                INNER JOIN `ctb_doc`
                                    ON (`pto_pag_detalle`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                        WHERE (`ctb_doc`.`estado` > 0)
                        GROUP BY `id_pto_cop_det`)AS `pto_pag_detalle`
                        ON (`pto_pag_detalle`.`id_pto_cop_det` = `pto_cop_detalle`.`id_pto_cop_det`)
                    INNER JOIN `ctb_doc` 
                        ON (`pto_cop_detalle`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc` AND `ctb_doc`.`estado` = 2)
                    INNER JOIN `pto_crp` 
                        ON ( `pto_crp`.`id_pto_crp` = `ctb_doc`.`id_crp`)
                    INNER JOIN `pto_cdp` 
                        ON (`pto_crp`.`id_cdp` = `pto_cdp`.`id_pto_cdp`)
                    LEFT JOIN `ctt_adquisiciones` 
                        ON (`ctt_adquisiciones`.`id_cdp` = `pto_cdp`.`id_pto_cdp`)
                    LEFT JOIN `ctt_contratos` 
                        ON (`ctt_contratos`.`id_compra` = `ctt_adquisiciones`.`id_adquisicion`)
                WHERE `ctb_doc`.`id_crp` IS NOT NULL) AS `t1`
                LEFT JOIN `tb_terceros` 
                    ON (`t1`.`id_tercero` = `tb_terceros`.`id_tercero_api`) 
            WHERE  `valor` > `valor_pagado` AND DATE_FORMAT(`t1`.`fecha`,'%Y-%m-%d') BETWEEN '$ini' AND '$fin'
            GROUP BY `id_ctb_doc`";
    } else {
        $sql = "SELECT 
                    `ctb_doc`.`id_ctb_doc`
                    , `ctb_doc`.`id_manu` AS `causacion`
                    , 0 AS `registro`
                    , `ctb_doc`.`id_tercero`
                    , `ctb_doc`.`fecha`
                    , `causado`.`valor`
                    , IFNULL(`pagado`.`valor`,0) AS `valor_pagado`
                    , '' AS `num_contrato`
                    , `tb_terceros`.`nom_tercero`
                    , `tb_terceros`.`nit_tercero`
                FROM 
                    `ctb_doc`
                    INNER JOIN
                        (SELECT
                            `ctb_libaux`.`id_ctb_doc`
                            , SUM(`ctb_libaux`.`debito`) AS `valor`
                        FROM
                            `ctb_libaux`
                            INNER JOIN `ctb_doc` 
                            ON (`ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                        WHERE (`ctb_doc`.`id_ctb_doc_tipo3` IS NULL AND `ctb_doc`.`id_tipo_doc` = 3 AND `ctb_doc`.`estado` = 2)
                        GROUP BY `ctb_libaux`.`id_ctb_doc`) AS `causado`
                        ON(`causado`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                    LEFT JOIN
                        (SELECT
                            `ctb_libaux`.`id_ctb_doc`
                            , `ctb_doc`.`id_ctb_doc_tipo3`
                            , SUM(`ctb_libaux`.`debito`) AS `valor`
                        FROM
                            `ctb_libaux`
                            INNER JOIN `ctb_doc` 
                            ON (`ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                        WHERE (`ctb_doc`.`id_ctb_doc_tipo3` > 0 AND `ctb_doc`.`estado` > 0)
                        GROUP BY `ctb_libaux`.`id_ctb_doc`) AS `pagado`
                        ON(`causado`.`id_ctb_doc` = `pagado`.`id_ctb_doc_tipo3`)
                        LEFT JOIN `tb_terceros` 
                            ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
                WHERE DATE_FORMAT(`ctb_doc`.`fecha`,'%Y-%m-%d') BETWEEN '$ini' AND '$fin'";
    }
    $sql2 = $sql;
    $rs = $cmd->query($sql);
    $listado = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

?>
<script>
    $('#tableObligacionesPago').DataTable({
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ],
        columnDefs: [{
            targets: opCaracterJS == '2' ? [] : [1, 2],
            "visible": false
        }],
    });
    $('#tableObligacionesPago').wrap('<div class="overflow" />');
    $('#tableObligacionesPago_filter #verAnulados').remove();
    $('#tableObligacionesPago_filter label label').remove();
</script>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE OBLIGACIONES PARA PAGO DE TESORERÍA</h5>
        </div>
        <div class="pb-3"></div>
        <div class="px-3">
            <form id="formObligacionesPago">
                <input type="hidden" name="id_tipo" value="<?= $id_tipo; ?>">
                <table id="tableObligacionesPago" class="table table-striped table-bordered nowrap table-sm table-hover shadow w-100 align-middle">
                    <thead>
                        <tr>
                            <th class="bg-sofia" title="Seleccionar todos"><input type="checkbox" id="checkAll" onclick="checkAll(this)"></th>
                            <th class="bg-sofia">Causación</th>
                            <th class="bg-sofia">Rp</th>
                            <th class="bg-sofia">Contrato</th>
                            <th class="bg-sofia">Fecha</th>
                            <th class="bg-sofia">Cc / Nit</th>
                            <th class="bg-sofia">Terceros</th>
                            <th class="bg-sofia">Valor</th>
                            <th class="bg-sofia">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($listado as $ce) {

                            $id_doc = $ce['id_ctb_doc'];
                            $fecha = date('Y-m-d', strtotime($ce['fecha']));
                            $editar = null;

                            // Consulta terceros en la api

                            $tercero = $ce['nom_tercero'];
                            $ccnit = $ce['nit_tercero'];

                            // fin api terceros

                            $saldo_rp = $ce['valor'] - $ce['valor_pagado'];

                            if ($permisos->PermisosUsuario($opciones, 5601, 3) || $id_rol == 1) {
                                $editar = '<a value="' . $id_doc . '" onclick="cargarListaDetallePago(' . $id_doc . ',0)" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow editar" title="Causar"><span class="fas fa-plus-square"></span></a>';
                            }

                            if ($saldo_rp > 0) {
                        ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="check[]" class="check-item" value="<?php echo $id_doc; ?>">
                                    </td>
                                    <td class="text-start"><?php echo $ce['causacion']; ?></td>
                                    <td class="text-start"><?php echo $ce['registro'] ?></td>
                                    <td class="text-start"><?php echo $ce['num_contrato']   ?></td>
                                    <td class="text-start"><?php echo $fecha; ?></td>
                                    <td class="text-start"><?php echo $ccnit; ?></td>
                                    <td class="text-start text-wrap"><?php echo $tercero; ?></td>
                                    <td class="text-end"> <?php echo number_format($saldo_rp, 2, ',', '.'); ?></td>
                                    <td class="text-center"> <?php echo $editar; ?></td>
                                </tr>
                        <?php
                            }
                        }

                        ?>

                    </tbody>
                </table>
            </form>
        </div>
    </div>
    <div class="text-end pt-3">
        <?php
        if ($id_tipo == 4) {
            echo '<button type="button" class="btn btn-primary btn-sm" onclick="ProcesarLotesPagos(this)">Procesar lote</button>';
        }
        ?>
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cerrar</a>
    </div>
</div>
<?php
$cmd = null;
