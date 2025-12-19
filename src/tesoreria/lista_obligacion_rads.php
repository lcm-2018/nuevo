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

$id_vigencia = $_SESSION['id_vigencia'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
    $sql = "SELECT
                `ctb_doc`.`id_ctb_doc`
                , `ctb_doc`.`id_manu`
                , DATE_FORMAT(`ctb_doc`.`fecha`, '%Y-%m-%d') AS `fecha`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
                , IFNULL(`obligado`.`valor`, 0) AS `valor`
                , IFNULL(`recaudado`.`val_recaudado`, 0) AS `valor_pagado`
            FROM
                `ctb_doc`
                INNER JOIN `ctb_fuente` 
                    ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                LEFT JOIN `tb_terceros` 
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
                LEFT JOIN 
                    (SELECT
                        `id_ctb_doc`
                        , SUM(`debito`) AS `valor`
                    FROM
                        `ctb_libaux`
                    GROUP BY `id_ctb_doc`) AS `obligado`
                    ON (`ctb_doc`.`id_ctb_doc` = `obligado`.`id_ctb_doc`)
                LEFT JOIN
                    (SELECT
                        SUM(IFNULL(`pto_rec_detalle`.`valor`,0) - IFNULL(`pto_rec_detalle`.`valor_liberado`,0)) AS `val_recaudado` 
                        , `pto_rec`.`id_manu` AS `id_ctb_doc`
                    FROM
                        `pto_rec_detalle`
                        INNER JOIN `pto_rec` 
                            ON (`pto_rec_detalle`.`id_pto_rac` = `pto_rec`.`id_pto_rec`)
                        WHERE (`pto_rec`.`estado` > 0)
                        GROUP BY `pto_rec`.`id_manu`) AS `recaudado`
                    ON (`ctb_doc`.`id_ctb_doc` = `recaudado`.`id_ctb_doc`)
            WHERE (`ctb_doc`.`id_vigencia` = $id_vigencia AND `ctb_fuente`.`cod` = 'FELE' AND `ctb_doc`.`estado` = 2)";
    $rs = $cmd->query($sql);
    $listado = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<script>
    $('#tableObligacionesRads').DataTable({
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ]
    });
    $('#tableObligacionesRads').wrap('<div class="overflow" />');
</script>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE OBLIGACIONES RECONOCIDAS</h5>
        </div>
        <div class="pb-3"></div>
        <div class="px-3">
            <table id="tableObligacionesRads" class="table table-striped table-bordered nowrap table-sm table-hover shadow" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="bg-sofia">Num </th>
                        <th class="bg-sofia">Fecha</th>
                        <th class="bg-sofia">Tercero</th>
                        <th class="bg-sofia">Doc</th>
                        <th class="bg-sofia">Valor</th>
                        <th class="bg-sofia">Acciones</th>

                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($listado as $ce) {
                        $obligar = null;
                        $id_doc = $ce['id_ctb_doc'];
                        $obligar = '<a value="' . $id_doc . '" onclick="cargarListaObligacionRads(' . $id_doc . ')" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow editar" title="Obligar"><span class="fas fa-plus-square"></span></a>';
                        $saldo = $ce['valor_pagado'] - $ce['valor'];
                        if ($saldo > 0) {
                    ?>
                            <tr>
                                <td class="text-start"><?= $ce['id_manu']  ?></td>
                                <td class="text-start"><?= $ce['fecha'];  ?></td>
                                <td class="text-start"><?= $ce['nom_tercero']; ?></td>
                                <td class="text-start"><?= $ce['nit_tercero']; ?></td>
                                <td class="text-end"><?= number_format($saldo, 2, ',', '.') ?></td>
                                <td class=" text-center"> <?= $obligar ?></td>
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
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cerrar</a>
    </div>
</div>