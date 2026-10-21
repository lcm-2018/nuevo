<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
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

$listado = [];
$obligadosLookup = [];
$recaudadosLookup = [];

try {
    // 1. Consultar únicamente los documentos requeridos
    $sql = "SELECT
                `ctb_doc`.`id_ctb_doc`
                , `ctb_doc`.`id_manu`
                , DATE_FORMAT(`ctb_doc`.`fecha`, '%Y-%m-%d') AS `fecha`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM
                `ctb_doc`
                INNER JOIN `ctb_fuente` 
                    ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                LEFT JOIN `tb_terceros` 
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE (`ctb_doc`.`id_vigencia` = $id_vigencia AND `ctb_fuente`.`cod` = 'FELE' AND `ctb_doc`.`estado` = 2)";
    $rs = $cmd->query($sql);
    $listado = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);

    if (!empty($listado)) {
        $ids_docs = [];
        foreach ($listado as $doc) {
            $ids_docs[] = intval($doc['id_ctb_doc']);
        }
        $idsIn = implode(',', $ids_docs);

        // 2. Consultar totales obligados SOLO para los documentos encontrados
        $sql = "SELECT
                    `id_ctb_doc`
                    , SUM(`debito`) AS `valor`
                FROM
                    `ctb_libaux`
                WHERE `id_ctb_doc` IN ($idsIn)
                GROUP BY `id_ctb_doc`";
        $rs = $cmd->query($sql);
        $obligados = $rs->fetchAll();
        $rs->closeCursor();
        unset($rs);
        $obligadosLookup = array_column($obligados, 'valor', 'id_ctb_doc');
        unset($obligados);

        // 3. Consultar recaudos SOLO para los documentos encontrados
        $sql = "SELECT
                    `pto_rec`.`id_manu` AS `id_ctb_doc`
                    , SUM(IFNULL(`pto_rec_detalle`.`valor`,0) - IFNULL(`pto_rec_detalle`.`valor_liberado`,0)) AS `val_recaudado`
                FROM
                    `pto_rec_detalle`
                    INNER JOIN `pto_rec` 
                        ON (`pto_rec_detalle`.`id_pto_rac` = `pto_rec`.`id_pto_rec`)
                WHERE `pto_rec`.`estado` > 0 AND `pto_rec`.`id_manu` IN ($idsIn)
                GROUP BY `pto_rec`.`id_manu`";
        $rs = $cmd->query($sql);
        $recaudados = $rs->fetchAll();
        $rs->closeCursor();
        unset($rs);
        $recaudadosLookup = array_column($recaudados, 'val_recaudado', 'id_ctb_doc');
        unset($recaudados);
    }
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
                        $val_obligado = isset($obligadosLookup[$id_doc]) ? $obligadosLookup[$id_doc] : 0;
                        $val_recaudado = isset($recaudadosLookup[$id_doc]) ? $recaudadosLookup[$id_doc] : 0;
                        $saldo = $val_obligado - $val_recaudado;
                        if ($saldo > 0) {
                            $obligar = '<a value="' . $id_doc . '" onclick="cargarListaObligacionRads(' . $id_doc . ')" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow editar" title="Obligar"><span class="fas fa-plus-square"></span></a>';
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