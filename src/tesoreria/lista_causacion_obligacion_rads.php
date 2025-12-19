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

$id_cop = $_POST['id_cop'] ?? '';
$id_pag_doc = $_POST['id_doc'] ?? '';
$fecha = $_POST['fecha'] ?? date('Y-m-d');
$objeto = $_POST['objeto'] ?? 'Recaudación de Obligaciones';
$factura = $_POST['factura'] ?? '000';
// Consulta tipo de presupuesto
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `tt`.`id_rubro`
                , `tt`.`id_pto_rad_det`
                , `tt`.`valor`  AS `reconocido`
                , `tt`.`valor`/`total` AS `porcentaje`
                , (SELECT SUM(`debito`) AS `valor`
                    FROM `ctb_libaux`
                    WHERE (`id_ctb_doc` = $id_cop)) AS `causado`
                , (SELECT
                        SUM(IFNULL(`pto_rec_detalle`.`valor`,0) - IFNULL(`pto_rec_detalle`.`valor_liberado`,0)) AS `val_recaudado` 
                    FROM
                        `pto_rec_detalle`
                        INNER JOIN `pto_rec` 
                            ON (`pto_rec_detalle`.`id_pto_rac` = `pto_rec`.`id_pto_rec`)
                    WHERE (`pto_rec`.`estado` > 0 AND `pto_rec`.`id_manu` = $id_cop)) AS `radicado`
                , CONCAT(`pto_cargue`.`cod_pptal`, ' - ', `pto_cargue`.`nom_rubro`) AS `rubro`
                , `tt`.`id_tercero` AS `id_tercero_api`
            FROM
                (SELECT
                    `pto_rad_detalle`.`id_rubro`
                    , `pto_rad_detalle`.`id_pto_rad_det`
                    , `ctb_doc`.`id_tercero`
                    , SUM(IFNULL(`pto_rad_detalle`.`valor`,0) - IFNULL(`pto_rad_detalle`.`valor_liberado`,0)) AS `valor`
                    , (SELECT SUM(IFNULL(`prd_sub`.`valor`,0) - IFNULL(`prd_sub`.`valor_liberado`,0))
                        FROM `ctb_doc` AS `cd_sub`
                            INNER JOIN `pto_rad_detalle` AS `prd_sub`
                            ON (`cd_sub`.`id_rad` = `prd_sub`.`id_pto_rad`)
                        WHERE (`cd_sub`.`id_ctb_doc` = $id_cop)) AS `total`
                FROM
                    `ctb_doc`
                    INNER JOIN `pto_rad_detalle`
                    ON (`ctb_doc`.`id_rad` = `pto_rad_detalle`.`id_pto_rad`)
                WHERE (`ctb_doc`.`id_ctb_doc` = $id_cop)
                GROUP BY `pto_rad_detalle`.`id_pto_rad_det`,`pto_rad_detalle`.`id_rubro`) AS `tt`
                INNER JOIN `pto_cargue`
                    ON (`pto_cargue`.`id_cargue` = `tt`.`id_rubro`)";
    $rs = $cmd->query($sql);
    $rubros = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $tercero = !empty($rubros) ? $rubros[0]['id_tercero_api'] : 0;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<script>
    $('#tableContrtacionRp').DataTable({
        dom: "<'row'<'col-md-2'l><'col-md-10'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ]
    });
    $('#tableContrtacionRpRubros').wrap('<div class="overflow" />');
</script>
<div class="px-0">

    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE REGISTROS PRESUPUESTALES PARA RECAUDACIÓN</h5>
        </div>
        <div class="pb-3"></div>
        <form id="rubrosPagar">
            <input type="hidden" name="id_pto_rp" id="id_pto_rp" value="<?= $id_cop; ?>">
            <input type="hidden" name="id_pag_doc" value="<?= $id_pag_doc; ?>">
            <input type="hidden" name="id_tercero" value="<?= $tercero; ?>">
            <input type="hidden" name="fecha" value="<?= $fecha; ?>">
            <input type="hidden" name="objeto" value="<?= $objeto; ?>">
            <input type="hidden" name="factura" value="<?= $factura; ?>">

            <div class="px-3 pt-3">
                <table id="tableContrtacionRpRubros" class="table table-striped table-bordered table-sm table-hover shadow" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Rubro</th>
                            <th style="width: 15%;">Valor RAD</th>
                            <th style="width: 15%;">Valor Causado</th>
                            <th style="width: 15%;">Valor Pago</th>
                            <!--<th style="width: 15%;">Acciones</th>-->
                        </tr>
                    </thead>
                    <tbody>

                        <?php
                        foreach ($rubros as $ce) {
                            $id_doc = 0;
                            $valor = 0;
                            $id_det = $ce['id_pto_rad_det'];
                            $pagado = $ce['radicado'] > 0 ? $ce['radicado'] : 0;
                            $obligado = ($ce['causado'] - $pagado) * $ce['porcentaje'];
                            $valor =  $obligado - $pagado;
                            $valor_mil = number_format($obligado, 2, '.', ',');

                        ?>
                            <tr>
                                <td class="text-start"><?= $ce['rubro']; ?></td>
                                <td class="text-end"><?= '$ ' . number_format($ce['reconocido'], 2, '.', ','); ?></td>
                                <td class="text-end"><?= '$ ' . number_format($obligado, 2, '.', ','); ?></td>
                                <td class="text-end">
                                    <input type="text" name="detalle[<?= $id_det; ?>]" id="detalle_<?= $id_det; ?>" class="form-control form-control-sm bg-input detalle-pag" value="<?= $valor_mil; ?>" style="text-align: right;" required onkeyup="NumberMiles(this)" max="<?= $valor_mil; ?>">
                                </td>
                            </tr>
                        <?php
                        }
                        ?>

                    </tbody>
                </table>
                <div class="text-end p-3">
                    <button type="button" class="btn btn-success btn-sm" onclick="rubrosaPagar(this,1);"> Guardar</button>
                    <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php
$cmd = null;
