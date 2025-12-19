<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
// Consulta tipo de presupuesto
$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];
$id_doc = isset($_POST['id_doc']) ?  $_POST['id_doc'] : exit('Acceso no disponible');

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `ctb_doc`.`id_ctb_doc`
                , `pto_crp_detalle`.`id_tercero_api`
                , IFNULL(`pto_crp_detalle`.`valor`,0) - IFNULL(`pto_crp_detalle`.`valor_liberado`,0) AS `valor_crp` 
                , `pto_cdp_detalle`.`id_rubro`
                , `pto_cargue`.`cod_pptal`
                , `pto_cargue`.`nom_rubro`
                , IFNULL(`t1`.`valor`,0) - IFNULL(`t1`.`valor_liberado`,0) AS `valor_cop`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM
                `ctb_doc`
                INNER JOIN `pto_crp` 
                    ON (`ctb_doc`.`id_crp` = `pto_crp`.`id_pto_crp`)
                INNER JOIN `pto_crp_detalle` 
                    ON (`pto_crp_detalle`.`id_pto_crp` = `pto_crp`.`id_pto_crp`)
                INNER JOIN `pto_cdp_detalle` 
                    ON (`pto_crp_detalle`.`id_pto_cdp_det` = `pto_cdp_detalle`.`id_pto_cdp_det`)
                INNER JOIN `pto_cargue` 
                    ON (`pto_cdp_detalle`.`id_rubro` = `pto_cargue`.`id_cargue`)
                LEFT JOIN `tb_terceros`
                    ON (`pto_crp_detalle`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                LEFT JOIN 
            (SELECT
                `id_pto_crp_det`
                , IFNULL(SUM(`valor`),0) AS `valor`
                , IFNULL(SUM(`valor_liberado`),0) AS `valor_liberado`
            FROM
                `pto_cop_detalle`
            WHERE (`id_ctb_doc` = $id_doc) 
            GROUP BY `id_pto_crp_det`) AS `t1`  
                    ON (`t1`.`id_pto_crp_det` = `pto_crp_detalle`.`id_pto_crp_det`)
            WHERE (`ctb_doc`.`id_ctb_doc` = $id_doc)";
    $rs = $cmd->query($sql);
    $listado = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
//consulto los datos del cop
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `id_pto_crp_det`
                , `valor`
                , `valor_liberado`
                , `id_pto_cop_det`
            FROM
                `pto_cop_detalle`
            WHERE (`id_ctb_doc` = $id_doc)";
    $rs = $cmd->query($sql);
    $detalles = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<script>
    $('#tableObligacionesPago').DataTable({
        dom: "<'row'<'col-md-2'l><'col-md-10'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ]
    });
    $('#tableObligacionesPago').wrap('<div class="overflow" />');
</script>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE REGISTROS PARA PAGOS DEL TERCERO OTROS SI </h5>
        </div>
        <div class="pb-3"></div>
        <div class="px-3">
            <table id="tableObligacionesPago" class="table table-striped table-bordered nowrap table-sm table-hover shadow" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 13%;">Causación</th>
                        <th style="width: 13%;">Rp</th>
                        <th style="width: 13%;">Num Contrato</th>
                        <th style="width: 10%;">Fecha</th>
                        <th style="width: 10%;">Cc / Nit</th>
                        <th style="width: 20%;">Terceros</th>
                        <th style="width: 15%;">Valor</th>

                        <th style="width: 5%;">Acciones</th>

                    </tr>
                </thead>
                <tbody>
                    <?php

                    foreach ($listado as $ce) {

                        $id_doc = $ce['id_ctb_doc'];
                        $fecha = date('Y-m-d', strtotime($ce['fecha']));

                        $tercero = !empty($ce['nom_tercero']) ? ltrim($ce['nom_tercero']) : '---';
                        $ccnit = !empty($ce['nit_tercero']) ? $ce['nit_tercero'] : '---';

                        // fin api terceros

                        $saldo_rp = $ce['valor'] - $ce['val_pagado'];

                        if ((intval($permisos['editar'])) === 1) {
                            $editar = '<a value="' . $id_doc . '" onclick="cargarListaDetallePago(' . $id_doc . ')" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow editar" title="Causar"><span class="fas fa-plus-square "></span></a>';
                        } else {
                            $editar = null;
                            $detalles = null;
                        }

                        if ($saldo_rp > 0) {
                    ?>
                            <tr>
                                <td class="text-start"><?php echo $ce['causacion']; ?></td>
                                <td class="text-start"><?php echo $ce['registro'] ?></td>
                                <td class="text-start"><?php echo $ce['num_contrato']   ?></td>
                                <td class="text-start"><?php echo $fecha; ?></td>
                                <td class="text-start"><?php echo $ccnit; ?></td>
                                <td class="text-start"><?php echo $tercero; ?></td>
                                <td class="text-end"><?php echo number_format($saldo_rp, 2, ',', '.'); ?></td>

                                <td class="text-center"> <?php echo $editar; ?></td>
                            </tr>
                    <?php
                        }
                    }

                    ?>

                </tbody>
            </table>
        </div>
    </div>
    <div class="text-end pt-3">
        <a type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Guardar</a>
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Aceptar</a>
    </div>
</div>
<?php
$cmd = null;
