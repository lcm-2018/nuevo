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

$id_doc = isset($_POST['id_doc']) ? $_POST['id_doc'] : 0;
$id_tercero = isset($_POST['id_tercero']) ? $_POST['id_tercero'] : 0;
$id_doc_rad = isset($_POST['id_doc_rad']) ? $_POST['id_doc_rad'] : 0;
$id_vigencia = $_SESSION['id_vigencia'];
// Consulta tipo de presupuesto
$cmd = \Config\Clases\Conexion::getConexion();

try {
    $sql = "SELECT
                `ctb_doc`.`id_ctb_doc`
                , `ctb_doc`.`id_manu`
                , `ctb_doc`.`id_tercero`
                , `ctb_doc`.`estado`
                , `ctb_factura`.`num_doc` AS `num_factura`
                , DATE_FORMAT(`ctb_doc`.`fecha`,'%Y-%m-%d') AS `fecha`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
                , `causado`.`valor` AS `val_causado`
                , `recaudado`.`valor` AS `val_recaudado`
            FROM
                `ctb_doc`
                LEFT JOIN `ctb_fuente` 
                    ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                LEFT JOIN `tb_terceros` 
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
                LEFT JOIN `ctb_factura` 
                    ON (`ctb_doc`.`id_ctb_doc` = `ctb_factura`.`id_ctb_doc`)
                LEFT JOIN
                    (SELECT
                        `id_ctb_doc`, SUM(`debito`) AS `valor`
                    FROM
                        `ctb_libaux`
                    GROUP BY `id_ctb_doc`) AS `causado`
                    ON (`causado`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                LEFT JOIN
                    (SELECT
                        `ctb_doc`.`id_ctb_doc`
                        , SUM(IFNULL(`pto_rec_detalle`.`valor`,0) - IFNULL(`pto_rec_detalle`.`valor_liberado`,0)) AS `valor`
                    FROM
                        `ctb_doc`
                        INNER JOIN `pto_rad` 
                            ON (`ctb_doc`.`id_rad` = `pto_rad`.`id_pto_rad`)
                        INNER JOIN `pto_rad_detalle` 
                            ON (`pto_rad_detalle`.`id_pto_rad` = `pto_rad`.`id_pto_rad`)
                        INNER JOIN `pto_rec_detalle`
                        ON (`pto_rec_detalle`.`id_pto_rad_detalle` = `pto_rad_detalle`.`id_pto_rad_det`)
                        INNER JOIN `pto_rec` 
                            ON (`pto_rec_detalle`.`id_pto_rac` = `pto_rec`.`id_pto_rec`)
                    WHERE (`ctb_doc`.`estado` > 0 AND `pto_rad`.`estado` > 0 AND `pto_rec`.`estado` > 0 AND `pto_rec`.`id_manu` = `ctb_doc`.`id_ctb_doc` AND `ctb_doc`.`id_vigencia` = $id_vigencia)
                    GROUP BY `ctb_doc`.`id_ctb_doc`) AS `recaudado`
                    ON (`recaudado`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
        
            WHERE (`ctb_fuente`.`cod` = 'FELE' AND `ctb_doc`.`id_tercero` = $id_tercero AND `ctb_doc`.`id_vigencia` = $id_vigencia AND `ctb_doc`.`estado` = 2)";
    $rs = $cmd->query($sql);
    $causaciones = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
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
            <h5 class="mb-0" style="color: white;">LISTA DE CAUSACIONES PARA PAGO DEL TERCERO</h5>
        </div>
        <div class="px-3 pt-2">
            <table id="tableCausacionPagos" class="table table-striped table-bordered table-sm table-hover shadow" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="bg-sofia">No causación</th>
                        <th class="bg-sofia">Fecha</th>
                        <th class="bg-sofia">Valor Causado</th>
                        <th class="bg-sofia">Valor Recaudado</th>
                        <th class="bg-sofia">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <div id="datostabla">
                        <?php
                        foreach ($causaciones as $ce) {
                            $id = $ce['id_ctb_doc'];
                            $fecha = $ce['fecha'];
                            $fact = $ce['num_factura'];
                            $editar = null;
                            if ($permisos->PermisosUsuario($opciones, 5601, 2) || $id_rol == 1) {
                                $editar = '<button value="' . $id_doc . '" onclick="cargaRubroPagInvoice(' . $id . ', this, \'' . $fact . '\')" class="btn btn-outline-info btn-xs rounded-circle me-1 shadow" title="Imputar"><span class="fas fa-chevron-circle-down"></span></button>';
                            }

                            $saldo = $ce['val_causado'] - $ce['val_recaudado'];
                            if (!($saldo > 0)) {
                                $editar = null;
                            }
                        ?>
                            <tr id="<?= $id; ?>">
                                <td class="text-start"><?= $ce['id_manu']; ?></td>
                                <td class="text-start"><?= $fecha;  ?></td>
                                <td class="text-end">$ <?= number_format($ce['val_causado'], 2, '.', ','); ?></td>
                                <td class="text-end">$ <?= number_format($ce['val_recaudado'], 2, '.', ','); ?></td>
                                <td> <?= $editar; ?></td>

                            </tr>
                        <?php
                        }
                        ?>
                    </div>
                </tbody>
            </table>
            <div id="detalle-rubros">

            </div>
            <div class="text-end py-3">
                <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</a>
            </div>

        </div>


    </div>
    <?php
