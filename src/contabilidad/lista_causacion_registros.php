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
$id_vigencia = $_SESSION['id_vigencia'];
unset($_SESSION['id_doc']);
// Consulta tipo de presupuesto
function pesos($valor)
{
    return number_format($valor, 2, ',', '.');
}
$id_r = $_POST['dato'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_pto` FROM `pto_presupuestos` WHERE (`id_tipo` = 2 AND `id_vigencia` = $id_vigencia)";
    $rs = $cmd->query($sql);
    $listappto = $rs->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $sql = "SELECT
                `pto_crp`.`id_pto_crp`
                , `pto_crp`.`id_manu`
                , `pto_crp`.`id_tercero_api`
                , `pto_crp`.`fecha`
                , `pto_crp`.`objeto`
                , `pto_crp`.`id_cdp`
                , `pto_crp`.`num_contrato`
                , `ctt_contratos`.`id_contrato_compra`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM
                `pto_crp`
                LEFT JOIN `ctt_adquisiciones` 
                    ON (`pto_crp`.`id_cdp` = `ctt_adquisiciones`.`id_cdp`)
                LEFT JOIN `ctt_contratos` 
                    ON (`ctt_adquisiciones`.`id_adquisicion` = `ctt_contratos`.`id_compra`)
                LEFT JOIN `tb_terceros`
                    ON (`pto_crp`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE (`pto_crp`.`estado` = 2 AND `pto_crp`.`causado` = 0 AND `pto_crp`.`id_pto` = {$listappto['id_pto']})";
    $rs = $cmd->query($sql);
    $listado = $rs->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $sql = "SELECT
                `pto_crp`.`id_pto_crp`
                , SUM(IFNULL(`pto_crp_detalle`.`valor`,0) - IFNULL(`pto_crp_detalle`.`valor_liberado`,0)) AS `valor`
            FROM
                `pto_crp_detalle`
                INNER JOIN `pto_crp` 
                    ON (`pto_crp_detalle`.`id_pto_crp` = `pto_crp`.`id_pto_crp`)
            WHERE (`pto_crp`.`estado` = 2 AND `pto_crp`.`id_pto` = {$listappto['id_pto']})
            GROUP BY `pto_crp`.`id_pto_crp`";
    $rs = $cmd->query($sql);
    $liquidados = $rs->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
// Consultas totales obligados
try {
    $sql = "SELECT
                `pto_crp`.`id_pto_crp`
                , SUM(IFNULL(`pto_cop_detalle`.`valor`,0) - IFNULL(`pto_cop_detalle`.`valor_liberado`,0)) AS `valor`
            FROM
                `pto_cop_detalle`
                INNER JOIN `ctb_doc` 
                    ON (`pto_cop_detalle`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                INNER JOIN `pto_crp_detalle` 
                    ON (`pto_cop_detalle`.`id_pto_crp_det` = `pto_crp_detalle`.`id_pto_crp_det`)
                INNER JOIN `pto_crp` 
                    ON (`pto_crp_detalle`.`id_pto_crp` = `pto_crp`.`id_pto_crp`)
            WHERE (`ctb_doc`.`id_vigencia` = $id_vigencia AND `ctb_doc`.`estado` = 2)
            GROUP BY `pto_crp`.`id_pto_crp`";
    $rs = $cmd->query($sql);
    $causados = $rs->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $sql = "SELECT
                `pto_crp`.`id_pto_crp`
                , `ctt_novedad_adicion_prorroga`.`id_adq`
            FROM
                `ctt_novedad_adicion_prorroga`
                INNER JOIN `pto_crp` 
                    ON (`ctt_novedad_adicion_prorroga`.`id_cdp` = `pto_crp`.`id_cdp`)
            WHERE (`pto_crp`.`estado` = 2 AND `pto_crp`.`id_pto` = {$listappto['id_pto']})";
    $rs = $cmd->query($sql);
    $adiciones = $rs->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$fecha = date('Y-m-d', strtotime($listado[0]['fecha']));
if ($id_r == 3) {
    try {
        $cmd = \Config\Clases\Conexion::getConexion();
        $sql = "SELECT
                    `nom_nomina_pto_ctb_tes`.`id`
                    , `nom_nomina_pto_ctb_tes`.`id_nomina`
                    , `nom_nomina_pto_ctb_tes`.`tipo`
                    , `nom_nomina_pto_ctb_tes`.`cdp`
                    , `nom_nomina_pto_ctb_tes`.`crp`
                    , `nom_nominas`.`descripcion`
                    , `nom_nominas`.`mes`
                    , `nom_nominas`.`vigencia`
                    , `nom_nominas`.`estado`
                FROM
                    `nom_nomina_pto_ctb_tes`
                    INNER JOIN `nom_nominas` 
                        ON (`nom_nomina_pto_ctb_tes`.`id_nomina` = `nom_nominas`.`id_nomina`)
                WHERE (`nom_nominas`.`estado` = 3) AND`nom_nomina_pto_ctb_tes`.`tipo` <> 'PL'
                UNION 
                SELECT
                    `nom_nomina_pto_ctb_tes`.`id`
                    , `nom_nomina_pto_ctb_tes`.`id_nomina`
                    , `nom_nomina_pto_ctb_tes`.`tipo`
                    , `nom_nomina_pto_ctb_tes`.`cdp`
                    , `nom_nomina_pto_ctb_tes`.`crp`
                    , 'PLANILLA PATRONAL' AS `descripcion`
                    , `nom_nominas`.`mes`
                    , `nom_nominas`.`vigencia`
                    , `nom_nominas`.`planilla` AS `estado`
                FROM
                    `nom_nomina_pto_ctb_tes`
                    INNER JOIN `nom_nominas` 
                        ON (`nom_nomina_pto_ctb_tes`.`id_nomina` = `nom_nominas`.`id_nomina`)
                WHERE (`nom_nominas`.`planilla` = 3 AND `nom_nomina_pto_ctb_tes`.`tipo` = 'PL')";
        $rs = $cmd->query($sql);
        $nominas = $rs->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
    $rp = [];
    foreach ($nominas as $nm) {
        if ($nm['crp'] != '') {
            $rp[] = $nm['crp'];
        }
    }
    $rp = implode(',', $rp);
    if (!empty($nominas)) {
        try {
            $sql = "SELECT 
                        `pto_crp`.`id_pto_crp`
                        , `t1`.`valor`
                        , `pto_crp`.`id_manu`
                        , `pto_crp`.`fecha`
                        , `pto_crp`.`objeto`
                    FROM 
                        (SELECT
                            `id_pto_crp`
                            , SUM(`valor`) AS `valor`
                        FROM
                            `pto_crp_detalle`
                        WHERE `id_pto_crp` IN ($rp) GROUP BY `id_pto_crp`) AS `t1`
                    INNER JOIN `pto_crp`
                        ON(`pto_crp`.`id_pto_crp` = `t1`.`id_pto_crp`)";
            $rs = $cmd->query($sql);
            $valores = $rs->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        }
    }
    if ($_SESSION['pto'] != '1') {
        $valores = [];
        foreach ($nominas as $n) {
            $valores[] = [
                'id_pto_crp' => $n['crp'],
                'valor' => '0',
                'id_manu' => '',
                'fecha' => date('Y-m-d'),
                'objeto' => $n['descripcion'] . ' ' . $n['mes'] . ' ' . $n['vigencia'] . ' No. ' . $n['id_nomina'],
            ];
        }
    }
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
        ],
        columnDefs: [{
            class: 'text-wrap',
            targets: [4]
        }],
    });
    $('#tableContrtacionCdp').wrap('<div class="overflow" />');
</script>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE REGISTROS PRESUPUESTALES PARA OBLIGACION </h5>
        </div>
        <div class="p-3">
            <table id="tableContrtacionRp" class="table table-striped table-bordered nowrap table-sm table-hover shadow" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="bg-sofia">Num</th>
                        <?= $_SESSION['pto'] == '1' ? '<th class="bg-sofia">Rp</th><th class="bg-sofia">Contrato</th>' : ''; ?>
                        <th class="bg-sofia">Fecha</th>
                        <th class="bg-sofia">Terceros</th>
                        <?= $_SESSION['pto'] == '1' ? '<th class="bg-sofia">Valor</th>' : ''; ?>
                        <th class="bg-sofia">Acciones</th>

                    </tr>
                </thead>
                <tbody>
                    <?php
                    $acciones = null;
                    if ($id_r == 1 || $id_r == 2) {
                        foreach ($listado as $ce) {
                            $id_ter = $ce['id_tercero_api'];
                            $id_crp = $ce['id_pto_crp'];
                            $id_ctt = $ce['id_contrato_compra'];
                            $filtro = [];
                            $sum_lq = 0;
                            $sum_cs = 0;
                            $filtro = array_filter($adiciones, function ($adiciones) use ($id_ctt) {
                                return $adiciones["id_adq"] == $id_ctt;
                            });
                            if (!empty($filtro)) {
                                foreach ($filtro as $f) {
                                    //por cada adicion se debe buscar el id_crp_pto en $liquidados y $causados para determinar el valor que falta por causar
                                    $key = array_search($f['id_pto_crp'], array_column($liquidados, 'id_pto_crp'));
                                    $valor_liquidado = $key !== false ? $liquidados[$key]['valor'] : 0;
                                    $key = array_search($f['id_pto_crp'], array_column($causados, 'id_pto_crp'));
                                    $valor_causado = $key !== false ? $causados[$key]['valor'] : 0;
                                    $sum_lq += $valor_liquidado;
                                    $sum_cs += $valor_causado;
                                }
                            }
                            $tercero = !empty($ce['nom_tercero']) ? ltrim($ce['nom_tercero']) : '---';
                            // Obtener el saldo del registro por obligar valor del registro - el valor obligado efectivamente
                            $key = array_search($id_crp, array_column($liquidados, 'id_pto_crp'));
                            $valor_liquidado = $key !== false ? $liquidados[$key]['valor'] : 0;
                            $key = array_search($id_crp, array_column($causados, 'id_pto_crp'));
                            $valor_causado = $key !== false ? $causados[$key]['valor'] : 0;
                            $saldo_rp = $valor_liquidado + $sum_lq - $sum_cs - $valor_causado;
                            if ($ce['num_contrato'] != '') {
                                $numeroc = $ce['num_contrato'];
                                if ($permisos->PermisosUsuario($opciones, 5501, 3)  || $id_rol == 1) {
                                    $editar = '<a value="' . $id_crp . '" onclick="cargarListaDetalleCont(' . $id_crp . ')" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow editar" title="Causar"><span class="fas fa-plus-square "></span></a>';
                                    $acciones = '<button  class="btn btn-outline-pry btn-sm" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
                                                ...
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a value="' . $id_crp . '" class="dropdown-item sombra carga" href="#">Historial</a>
                                                </div>';
                                } else {
                                    $editar = null;
                                    $detalles = null;
                                }
                                $acciones = null;
                                $fecha = date('Y-m-d', strtotime($ce['fecha']));
                                if ($saldo_rp > 0) {
                    ?>
                                    <tr>
                                        <td class="text-center"><input type="checkbox" value="" id="defaultCheck1"></td>
                                        <td class="text-start"><?php echo $ce['id_manu']; ?></td>
                                        <td class="text-start"><?php echo $numeroc  ?></td>
                                        <td class="text-start"><?php echo $fecha; ?></td>
                                        <td class="text-start"><?php echo $tercero; ?></td>
                                        <td class="text-end"> <?php echo  $saldo_rp; ?></td>
                                        <td class="text-center"> <?php echo $editar .  $acciones; ?></td>
                                    </tr>
                                <?php
                                }
                            }
                        }
                    } else if ($id_r == 3) {
                        if (isset($valores)) {
                            foreach ($valores as $vl) {

                                $key = array_search($vl['id_pto_crp'], array_column($nominas, 'crp'));
                                if ($key !== false && $nominas[$key]['estado'] == 3) {
                                    $id_nomina = $nominas[$key]['id_nomina'] . '|' . $nominas[$key]['crp'] . '|' . $nominas[$key]['tipo'];
                                    $causar = '<button value="' . $id_nomina . '" onclick="CausaNomina(this)" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow editar" title="Causar"><span class="fas fa-plus-square "></span></button>';
                                ?>
                                    <tr>
                                        <td class="text-center"><?php echo $nominas[$key]['id_nomina'] ?></td>
                                        <?= $_SESSION['pto'] == '1' ? '<td class="text-start">' . $vl['id_manu'] . '</td><td class="text-start">-</td>' : ''; ?>
                                        <td class="text-start"><?php echo '<input type="date" class="form-control form-control-sm bg-input" name="fec_doc[]" value="' . date('Y-m-d', strtotime($vl['fecha'])) . '" min="' . date('Y-m-d', strtotime($vl['fecha'])) . '" max="' . $_SESSION['vigencia'] . '-12-31">'; ?></td>
                                        <td class="text-start"><?php echo $vl['objeto']; ?></td>
                                        <?= $_SESSION['pto'] == '1' ? '<td class="text-end">' . pesos($vl['valor']) . '</td>' : ''; ?>
                                        <td class="text-center"> <?php echo $causar ?></td>
                                    </tr>
                    <?php
                                } else {
                                    $id_nomina = 0;
                                }
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center">No hay registros</td></tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="text-end pt-3">
        <?php if (false) { ?>
            <a type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal"> Procesar lote</a>
        <?php } ?>
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cerrar</a>
    </div>
</div>
<?php
