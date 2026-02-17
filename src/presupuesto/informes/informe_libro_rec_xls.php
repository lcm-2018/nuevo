<?php
session_start();
set_time_limit(5600);
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$vigencia = $_SESSION['vigencia'];
$fecha_corte = $_POST['fecha_corte'];
$fecha_ini = $_POST['fecha_ini'];
function pesos($valor)
{
    return '$' . number_format($valor, 2);
}
include '../../../config/autoloader.php';
include '../../financiero/consultas.php';
$cmd = \Config\Clases\Conexion::getConexion();

// Obtener sedes activas usando la función centralizada
$datos_sedes = obtenerSedesActivas($cmd);
$sedes = $datos_sedes['sedes'];
$sede_principal = $datos_sedes['sede_principal'];

// Array para consolidar recaudos de todas las sedes
$causaciones = [];

// Iterar sobre todas las sedes activas
foreach ($sedes as $sede) {
    // Usar conexión actual para la sede principal, conectar para las demás
    if ($sede['es_principal'] == 1) {
        $cmd_sede = $cmd;
    } else {
        $cmd_sede = conectarSede($sede['bd_sede']);
        if ($cmd_sede === null) {
            error_log("No se pudo conectar a la sede {$sede['nom_sede']} para el informe de recaudos");
            continue; // Saltar esta sede si falla la conexión
        }
    }

    // Consultar recaudos en esta sede
    try {
        $sql = "SELECT
                    `taux`.`id_pto_rec`
                    , DATE_FORMAT(`pto_rec`.`fecha`,'%Y-%m-%d') AS `fecha`
                    , `pto_rec`.`id_manu`
                    , `pto_rec`.`objeto`
                    , `pto_rec`.`num_factura`
                    , `pto_rec`.`estado`
                    , `pto_rec`.`id_tercero_api`
                    , `pto_cargue`.`nom_rubro`
                    , `pto_cargue`.`cod_pptal` AS `rubro`
                    , `taux`.`id_rubro`
                    , `taux`.`valor`
                    , `tb_terceros`.`nom_tercero`
                    , `tb_terceros`.`nit_tercero`
                FROM
                    (SELECT 	
                        `tb1`.`id_rubro`
                        , SUM(`tb1`.`valor`) AS `valor` 
                        , `tb1`.`id_pto_rec` 
                    FROM
                        (SELECT
                            IF(`rad`.`id_rubro`IS NULL, `rec`.`id_rubro`, `rad`.`id_rubro` ) AS `id_rubro` 
                            , (IFNULL(`rec`.`valor`,0) - IFNULL(`rec`.`valor_liberado`,0)) AS `valor`
                            , `pto_rec`.`id_pto_rec`
                        FROM
                            `pto_rec_detalle` AS `rec`
                            INNER JOIN `pto_rec`
                            ON (`pto_rec`.`id_pto_rec` = `rec`.`id_pto_rac`)
                            LEFT JOIN `pto_rad_detalle`  AS `rad`
                            ON (`rec`.`id_pto_rad_detalle` = `rad`.`id_pto_rad_det`)
                        WHERE `pto_rec`.`estado` = 2 AND DATE_FORMAT(`pto_rec`.`fecha`,'%Y-%m-%d') BETWEEN '$fecha_ini' AND '$fecha_corte') AS `tb1`
                    GROUP BY `tb1`.`id_rubro`,`tb1`.`id_pto_rec`) AS `taux`
                    INNER JOIN `pto_rec`
                        ON (`taux`.`id_pto_rec` = `pto_rec`.`id_pto_rec`)
                    INNER JOIN `pto_cargue`
                        ON (`pto_cargue`.`id_cargue` = `taux`.`id_rubro`)
                    LEFT JOIN `tb_terceros`
                        ON (`pto_rec`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                ORDER BY `pto_rec`.`fecha` ASC, `pto_rec`.`id_manu` ASC";

        $res = $cmd_sede->query($sql);
        $causaciones_sede = $res->fetchAll(PDO::FETCH_ASSOC);
        $res->closeCursor();

        // Agregar nombre de sede a cada registro para referencia
        foreach ($causaciones_sede as &$cau) {
            $cau['nom_sede'] = $sede['nom_sede'];
        }
        unset($cau); // Romper la referencia

        // Consolidar con los resultados de otras sedes
        $causaciones = array_merge($causaciones, $causaciones_sede);
    } catch (PDOException $e) {
        error_log("Error consultando recaudos en sede {$sede['nom_sede']}: " . $e->getMessage());
    }

    // Cerrar la conexión si no es la principal
    if ($sede['es_principal'] != 1) {
        $cmd_sede = null;
    }
}

// Ordenar el resultado consolidado primero por sede, luego por fecha y número manual
usort($causaciones, function ($a, $b) {
    // Primero ordenar por nombre de sede
    $comp = strcmp($a['nom_sede'], $b['nom_sede']);
    if ($comp !== 0) {
        return $comp;
    }
    // Si la sede es la misma, ordenar por fecha
    $comp = strcmp($a['fecha'], $b['fecha']);
    if ($comp !== 0) {
        return $comp;
    }
    // Si la fecha es la misma, ordenar por número manual
    return strcmp($a['id_manu'], $b['id_manu']);
});

$nom_informe = "RELACION DE RECAUDOS";
include_once '../../financiero/encabezado_empresa.php';
?>
<table class="table-hover table-selectable" style="width:100% !important; border-collapse: collapse;" border="1">
    <thead>
        <tr class="centrar">
            <th>Sede</th>
            <th>No reconocimiento</th>
            <th>No factura</th>
            <th>Fecha</th>
            <th>Tercero</th>
            <th>CC/NIT</th>
            <th>Objeto</th>
            <th>Rubro</th>
            <th>Valor</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (!empty($causaciones)) {
            $total = 0;
            foreach ($causaciones as $rp) {
                if ($rp['valor'] >= 0) {
                    $total += $rp['valor'];
                    echo "<tr>
                        <td style='text-align:left'>" . $rp['nom_sede'] . "</td>
                        <td style='text-align:left'>" . $rp['id_manu'] . "</td>
                        <td style='text-align:left'>" . $rp['num_factura'] . "</td>
                        <td style='text-align:left;white-space: nowrap;'>" .   $rp['fecha']   . "</td>
                        <td style='text-align:left'>" .  $rp['nom_tercero'] . "</td>
                        <td style='text-align:right;white-space: nowrap;'>" .  $rp['nit_tercero'] . "</td>
                        <td style='text-align:left'>" . $rp['objeto'] . "</td>
                        <td style='text-align:left'>" .  $rp['rubro'] . "</td>
                        <td style='text-align:right'>" . number_format($rp['valor'], 2, ".", ",")  . "</td>
                    </tr>";
                }
            }
            echo "<tr>
                <th colspan='8' style='text-align:center'>TOTAL</th>
                <th style='text-align:right'>" . number_format($total, 2, ".", ",") . "</th>
            </tr>";
        } else {
            echo "<tr><td colspan='9'  style='text-align:center'>No hay datos para mostrar</td></tr>";
        }
        ?>
    </tbody>
</table>