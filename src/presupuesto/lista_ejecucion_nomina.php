<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
// Consulta tipo de presupuesto
$vigencia = $_SESSION['vigencia'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT '0' AS`patronal`, `id_nomina`, `estado`, `descripcion`, `mes`, `vigencia`, `tipo` FROM `nom_nominas` WHERE `estado` = 2
            UNION
            SELECT	
                    `t1`.`seg_patronal` + `t2`.`parafiscales` AS `patronal`
                    , `t1`.`id_nomina`
                    , `nom_nominas`.`planilla` AS estado
                    , `nom_nominas`.`descripcion`
                    , `nom_nominas`.`mes`
                    , `nom_nominas`.`vigencia`
                    , 'PL' AS `tipo`
            FROM
                    (SELECT
                        SUM(`aporte_salud_empresa`) + SUM(`aporte_pension_empresa`) + SUM(`aporte_rieslab`) AS `seg_patronal`
                        , `nn`.`vigencia`
                        , `nn`.`id_nomina`
                    FROM
                        `nom_liq_segsocial_empdo` AS `nlse`
                    INNER JOIN `nom_nominas` AS `nn` 
                        ON (`nlse`.`id_nomina` = `nn`.`id_nomina`)
                    WHERE `nn`.`vigencia` = '$vigencia' AND `nlse`.`estado` = 1
                    GROUP BY `nn`.`id_nomina`) AS`t1`
                    LEFT JOIN 
                    (SELECT
                        SUM(`val_sena`) + SUM(`val_icbf`) + SUM(`val_comfam`) AS `parafiscales`
                        , `nn`.`vigencia`
                        , `nn`.`id_nomina` 
                    FROM
                        `nom_liq_parafiscales` AS `nlp`
                    INNER JOIN `nom_nominas` AS `nn` 
                        ON (`nlp`.`id_nomina` = `nn`.`id_nomina`)
                    WHERE `nn`.`vigencia` = '$vigencia' AND `nlp`.`estado` = 1
                    GROUP BY `nn`.`id_nomina`) AS `t2`
                    ON (`t1`.`id_nomina` = `t2`.`id_nomina`)
            INNER JOIN `nom_nominas` 
                    ON (`t1`.`id_nomina` = `nom_nominas`.`id_nomina`)
            WHERE `nom_nominas`.`planilla` = 2";
    $rs = $cmd->query($sql);
    $solicitudes = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT SUM(`valor`) AS `total`, `id_nomina` FROM `nom_cdp_empleados` WHERE `tipo` = 'M'
            GROUP BY `id_nomina`";
    $rs = $cmd->query($sql);
    $totxnomina = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$meses = [
    '00' => '',
    '01' => 'ENERO',
    '02' => 'FEBRERO',
    '03' => 'MARZO',
    '04' => 'ABRIL',
    '05' => 'MAYO',
    '06' => 'JUNIO',
    '07' => 'JULIO',
    '08' => 'AGOSTO',
    '09' => 'SEPTIEMBRE',
    '10' => 'OCTUBRE',
    '11' => 'NOVIEMBRE',
    '12' => 'DICIEMBRE'
];
?>
<script>
    $('#tableContrtacionCdp').DataTable({
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ]
    });
    $('#tableContrtacionCdp').wrap('<div class="overflow" />');
</script>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE NÓMINA(S) PARA CDP</h5>
        </div>
        <div class="pb-3"></div>
        <div class="px-3">
            <table id="tableContrtacionCdp" class="table table-striped table-bordered table-sm table-hover shadow w-100 align-middle">
                <thead>
                    <tr>
                        <th class="bg-sofia">ID</th>
                        <th class="bg-sofia">DESCRIPCIÓN</th>
                        <th class="bg-sofia">VALOR SOLICITADO</th>
                        <th class="bg-sofia">FECHA</th>
                        <th class="bg-sofia">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($solicitudes as $ce) {
                        $id_nom = $ce['id_nomina'];
                        $key = array_search($id_nom, array_column($totxnomina, 'id_nomina'));
                        $total = $key !== false ? $totxnomina[$key]['total'] : 0;
                        $patronal = '';
                        if ($ce['tipo'] == 'PL') {
                            $patronal = ' (PATRONAL)';
                            $total = $ce['patronal'];
                        }
                        if ($permisos->PermisosUsuario($opciones, 5401, 3) || $id_rol == 1) {
                            $editar = '<button value="' . $id_nom . '|' . $ce['tipo'] . '" onclick="CofirmaCdpRp(this)" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow confirmar" title="Confirmar Generación de CDP y RP"><span class="fas  fa-check-square "></span></button>';
                        } else {
                            $editar = null;
                        }
                        $mesu = $ce['mes'] == '' ? '00' : $ce['mes'];
                    ?> <tr>
                            <td class="text-start"><?= $ce['id_nomina'] ?></td>
                            <td class="text-start"><?= $ce['descripcion'] . ' - ' . $meses[$mesu] . ' DE ' . $ce['vigencia'] . $patronal ?></td>
                            <td class="text-end">$ <?= number_format($total, 2, ',', '.') ?></td>
                            <td class="text-center p-0"><input type="date" class="form-control form-control-sm bg-input border-0" name="fec_doc[]" value="<?= date('Y-m-d') ?>"></td>
                            <td class="text-center"> <?= $editar ?></td>
                        </tr>
                    <?php
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