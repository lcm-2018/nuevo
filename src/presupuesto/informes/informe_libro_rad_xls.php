<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

set_time_limit(10000);
ini_set('memory_limit', '512M');

if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

$vigencia    = $_SESSION['vigencia'] ?? '';
$fecha_corte = $_POST['fecha_corte'] ?? '';
$fecha_ini   = $_POST['fecha_ini'] ?? '';

include_once '../../../config/autoloader.php';
include_once '../../financiero/consultas.php';

$cmd = \Config\Clases\Conexion::getConexion();

// Obtener sedes activas usando la función centralizada
$datos_sedes    = obtenerSedesActivas($cmd);
$sedes          = $datos_sedes['sedes'] ?? [];
$sede_principal = $datos_sedes['sede_principal'] ?? null;

// Array para consolidar reconocimientos de todas las sedes
$causaciones = [];

// Consulta SQL con sentencias preparadas y compatibilidad con ONLY_FULL_GROUP_BY (MySQL 8+)
$sql = "SELECT
            DATE_FORMAT(`pto_rad`.`fecha`, '%Y-%m-%d') AS `fecha`,
            `pto_rad`.`id_manu`,
            `pto_rad`.`id_pto_rad`,
            `pto_rad`.`objeto`,
            `pto_rad`.`num_factura`,
            `pto_cargue`.`cod_pptal` AS `rubro`,
            `pto_cargue`.`nom_rubro`,
            `tt`.`id_tercero_api`,
            `tt`.`id_rubro`,
            `tt`.`val1`,
            `tt`.`val2`,
            `tt`.`valor`,
            `tb_terceros`.`nom_tercero`,
            `tb_terceros`.`nit_tercero`
        FROM `pto_rad`
        INNER JOIN (
            SELECT
                `id_pto_rad`,
                `id_rubro`,
                SUM(IFNULL(`valor`, 0)) AS `val1`,
                SUM(IFNULL(`valor_liberado`, 0)) AS `val2`,
                SUM(IFNULL(`valor`, 0) - IFNULL(`valor_liberado`, 0)) AS `valor`,
                `id_tercero_api`
            FROM `pto_rad_detalle`
            GROUP BY `id_pto_rad`, `id_rubro`, `id_tercero_api`
        ) AS `tt` ON `tt`.`id_pto_rad` = `pto_rad`.`id_pto_rad`
        INNER JOIN `pto_cargue` 
            ON `tt`.`id_rubro` = `pto_cargue`.`id_cargue`
        LEFT JOIN `tb_terceros` 
            ON `tt`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`
        WHERE `pto_rad`.`estado` = 2 
          AND DATE_FORMAT(`pto_rad`.`fecha`, '%Y-%m-%d') BETWEEN :fecha_ini AND :fecha_corte
        ORDER BY `pto_rad`.`fecha` ASC, `pto_rad`.`id_manu` ASC";

// Iterar sobre todas las sedes activas
foreach ($sedes as $sede) {
    if (!empty($sede['es_principal'])) {
        $cmd_sede = $cmd;
    } else {
        $cmd_sede = conectarSede($sede['bd_sede']);
        if ($cmd_sede === null) {
            error_log("No se pudo conectar a la sede {$sede['nom_sede']} para el informe de reconocimientos");
            continue; // Saltar esta sede si falla la conexión
        }
    }

    try {
        $stmt = $cmd_sede->prepare($sql);
        $stmt->execute([
            ':fecha_ini'   => $fecha_ini,
            ':fecha_corte' => $fecha_corte,
        ]);
        $causaciones_sede = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        // Evitar uso de referencias y array_merge repetitivo en memoria
        $nom_sede = $sede['nom_sede'] ?? '';
        foreach ($causaciones_sede as $row) {
            $row['nom_sede'] = $nom_sede;
            $causaciones[] = $row;
        }
    } catch (PDOException $e) {
        error_log("Error consultando reconocimientos en sede {$sede['nom_sede']}: " . $e->getMessage());
    }

    // Cerrar la referencia de conexión si no es la principal
    if (empty($sede['es_principal'])) {
        $cmd_sede = null;
    }
}

// Restaurar la conexión activa a la base de datos de la sede principal si cambió
if (!empty($sede_principal['bd_sede'])) {
    conectarSede($sede_principal['bd_sede']);
}

// PHP 8+: operador spaceship (<=>) encadenado con null-coalescing y orden numérico en id_manu
usort($causaciones, static fn(array $a, array $b): int =>
    (($a['nom_sede'] ?? '') <=> ($b['nom_sede'] ?? ''))
    ?: (($a['fecha'] ?? '') <=> ($b['fecha'] ?? ''))
    ?: ((int)($a['id_manu'] ?? 0) <=> (int)($b['id_manu'] ?? 0))
    ?: (($a['id_manu'] ?? '') <=> ($b['id_manu'] ?? ''))
);

$nom_informe = "RELACION DE RECONOCIMIENTOS";
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
            <th>Liberado</th>
            <th>Neto</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (!empty($causaciones)) {
            $total_val1 = 0.0;
            $total_val2 = 0.0;
            $total_neto = 0.0;

            foreach ($causaciones as $rp) {
                $val1  = (float)($rp['val1'] ?? 0);
                $val2  = (float)($rp['val2'] ?? 0);
                $valor = (float)($rp['valor'] ?? 0);

                if ($valor != 0.0) {
                    $total_val1 += $val1;
                    $total_val2 += $val2;
                    $total_neto += $valor;

                    $nit_raw = $rp['nit_tercero'] ?? '';
                    $nit_formateado = is_numeric($nit_raw) 
                        ? number_format((float)$nit_raw, 0, "", ".") 
                        : (string)$nit_raw;

                    echo "<tr>
                        <td style='text-align:left'>" . htmlspecialchars((string)($rp['nom_sede'] ?? ''), ENT_QUOTES, 'UTF-8') . "</td>
                        <td style='text-align:left;mso-number-format:\@;'>" . htmlspecialchars((string)($rp['id_manu'] ?? ''), ENT_QUOTES, 'UTF-8') . "</td>
                        <td style='text-align:left;mso-number-format:\@;'>" . htmlspecialchars((string)($rp['num_factura'] ?? ''), ENT_QUOTES, 'UTF-8') . "</td>
                        <td style='text-align:left;white-space: nowrap;'>" . htmlspecialchars((string)($rp['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') . "</td>
                        <td style='text-align:left'>" . htmlspecialchars((string)($rp['nom_tercero'] ?? ''), ENT_QUOTES, 'UTF-8') . "</td>
                        <td style='text-align:right;white-space: nowrap;mso-number-format:\@;'>" . htmlspecialchars($nit_formateado, ENT_QUOTES, 'UTF-8') . "</td>
                        <td style='text-align:left'>" . htmlspecialchars((string)($rp['objeto'] ?? ''), ENT_QUOTES, 'UTF-8') . "</td>
                        <td style='text-align:left;mso-number-format:\@;'>" . htmlspecialchars((string)($rp['rubro'] ?? ''), ENT_QUOTES, 'UTF-8') . "</td>
                        <td style='text-align:right'>" . number_format($val1, 2, ".", ",") . "</td>
                        <td style='text-align:right'>" . number_format($val2, 2, ".", ",") . "</td>
                        <td style='text-align:right'>" . number_format($valor, 2, ".", ",") . "</td>
                    </tr>";
                }
            }

            echo "<tr>
                <th colspan='8' style='text-align:center'>TOTAL</th>
                <th style='text-align:right'>" . number_format($total_val1, 2, ".", ",") . "</th>
                <th style='text-align:right'>" . number_format($total_val2, 2, ".", ",") . "</th>
                <th style='text-align:right'>" . number_format($total_neto, 2, ".", ",") . "</th>
            </tr>";
        } else {
            echo "<tr><td colspan='11' style='text-align:center'>No hay datos para mostrar</td></tr>";
        }
        ?>
    </tbody>
</table>