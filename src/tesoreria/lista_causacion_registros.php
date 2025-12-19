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
unset($_SESSION['id_doc']);
$vigencia = $_SESSION['vigencia'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `nom_nomina_pto_ctb_tes`.`id`
                , `nom_nomina_pto_ctb_tes`.`id_nomina`
                , `nom_nomina_pto_ctb_tes`.`tipo`
                , `nom_nomina_pto_ctb_tes`.`cdp`
                , `nom_nomina_pto_ctb_tes`.`crp`
                , `nom_nomina_pto_ctb_tes`.`cnom`
                , `nom_nominas`.`descripcion`
                , `nom_nominas`.`mes`
                , `nom_nominas`.`vigencia`
                , `nom_nominas`.`estado`
                , DATE_FORMAT(`ctb_doc`.`fecha`, '%Y-%m-%d') AS `fecha`
                , `tb_terceros`.`nom_tercero`
                , `valores`.`valor`
            FROM
                `nom_nominas`
                INNER JOIN `nom_nomina_pto_ctb_tes` 
                    ON (`nom_nomina_pto_ctb_tes`.`id_nomina` = `nom_nominas`.`id_nomina`)
                LEFT JOIN `ctb_doc`
                    ON (`nom_nomina_pto_ctb_tes`.`cnom` = `ctb_doc`.`id_ctb_doc`)
                LEFT JOIN `tb_terceros`
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
                LEFT JOIN 
                    (SELECT
                        `id_ctb_doc`
                        , SUM(`valor`) AS `valor` 
                    FROM
                        `pto_cop_detalle`
                    GROUP BY `id_ctb_doc`) AS `valores`
                    ON (`nom_nomina_pto_ctb_tes`.`cnom` = `valores`.`id_ctb_doc`)
            WHERE `nom_nominas`.`estado` = 4 OR (`nom_nominas`.`planilla` = 4 AND `nom_nomina_pto_ctb_tes`.`tipo` = 'PL')";
    $rs = $cmd->query($sql);
    $nominas = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<script>
    $('#tableContrtacionRp').DataTable({
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ],
        columnDefs: [{
            class: 'text-wrap',
            targets: [1],
        }],

    });
    $('#tableContrtacionCdp').wrap('<div class="overflow" />');
</script>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE REGISTROS PRESUPUESTALES PARA OBLIGACION </h5>
        </div>
        <div class="pb-3"></div>
        <div class="px-3">
            <table id="tableContrtacionRp" class="table table-striped table-bordered nowrap table-sm table-hover shadow" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="bg-sofia">ID</th>
                        <th class="bg-sofia">Descripción</th>
                        <th class="bg-sofia">Fecha</th>
                        <th class="bg-sofia">Terceros</th>
                        <th class="bg-sofia">Valor</th>
                        <th class="bg-sofia">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (isset($nominas)) {
                        foreach ($nominas as $nm) {
                            $ids = $nm['cdp'] . '|' . $nm['crp'] . '|' . $nm['cnom'];
                            $id_nomina = $nm['id_nomina'];
                            $pl = $nm['tipo'] == 'PL' ? 'PATRONALES' : '';
                            $causar = '<button text="' . base64_encode($id_nomina . '|' . $nm['tipo'] . '|' . $ids) . '" onclick="CausaCENomina(this)" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow editar" title="Causar"><span class="fas fa-plus-square"></span></button>';
                    ?>
                            <tr>
                                <td class="text-center"><?= $id_nomina ?></td>
                                <td class="text-start"><?= $nm['descripcion'] . ' ' . $pl . ', NÓMINA No. ' . $nm['id_nomina'] . ' DE ' . $vigencia ?></td>
                                <td class="text-start"><?= '<input type="date" name="fec_doc[]" class="form-control form-control-sm bg-input" value="' . $nm['fecha'] . '" min="' . $nm['fecha'] . '" max="' . $vigencia . '-12-31">' ?></td>
                                <td class="text-start"><?= $nm['nom_tercero'] ?></td>
                                <td class="text-start text-end"><?= '$ ' . number_format($nm['valor'], 2, ',', '.') ?></td>
                                <td class="text-center"> <?= $causar ?></td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center">No hay registros</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="text-end pt-3">
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</a>
    </div>
</div>