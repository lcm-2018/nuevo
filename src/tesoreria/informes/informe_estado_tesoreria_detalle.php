<?php
session_start();
set_time_limit(5600);
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>ESTADO DE SITUACIÓN DE TESORERÍA</title>
    <style>
        .text { mso-number-format: "\@" }
        .centrar { text-align: center; }
        .resaltar:nth-child(even) { background-color: #F8F9F9; }
        .resaltar:nth-child(odd)  { background-color: #ffffff; }
    </style>
    <?php
    header("Content-type: application/vnd.ms-excel charset=utf-8");
    header("Content-Disposition: attachment; filename=Estado_Situacion_Tesoreria.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    ?>
</head>
<?php
ini_set('max_execution_time', 5600);
include '../../../config/autoloader.php';
include '../../financiero/consultas.php';

$fecha_inicial   = $_POST['fecha_inicial'];
$fecha_corte     = $_POST['fecha_final'];
$inicio          = $_SESSION['vigencia'] . '-01-01';
$incluye_cartera = isset($_POST['incluye_cartera']) ? intval($_POST['incluye_cartera']) : 0;

// Condición LIKE para aprovechar índices (evita SUBSTRING/DATE_FORMAT en WHERE)
$like_cuentas = "(`ctb_pgcp`.`cuenta` LIKE '11%'";
if ($incluye_cartera == 1) {
    $like_cuentas .= " OR `ctb_pgcp`.`cuenta` LIKE '13%'";
}
$like_cuentas .= ")";

$cmd = \Config\Clases\Conexion::getConexion();

// Consulta directa a la BD principal (este informe no consolida sedes).
// El catálogo de cuentas y la fuente se traen en la misma query via JOIN.
try {
    $sql = "SELECT
                `ctb_libaux`.`id_cuenta`
                , `ctb_pgcp`.`cuenta`
                , `ctb_pgcp`.`nombre`
                , `ctb_pgcp`.`tipo_dato`  AS `tipo`
                , `tes_cuentas`.`nombre`  AS `fuente`
                , SUM(CASE WHEN `ctb_doc`.`fecha` >= '$inicio'
                           AND `ctb_doc`.`fecha`  <  '$fecha_inicial'
                      THEN `ctb_libaux`.`debito`  ELSE 0 END) AS `debitoi`
                , SUM(CASE WHEN `ctb_doc`.`fecha` >= '$inicio'
                           AND `ctb_doc`.`fecha`  <  '$fecha_inicial'
                      THEN `ctb_libaux`.`credito` ELSE 0 END) AS `creditoi`
                , SUM(CASE WHEN `ctb_doc`.`fecha` BETWEEN '$fecha_inicial' AND '$fecha_corte'
                      THEN `ctb_libaux`.`debito`  ELSE 0 END) AS `debito`
                , SUM(CASE WHEN `ctb_doc`.`fecha` BETWEEN '$fecha_inicial' AND '$fecha_corte'
                      THEN `ctb_libaux`.`credito` ELSE 0 END) AS `credito`
            FROM
                `ctb_libaux`
                INNER JOIN `ctb_doc`
                    ON `ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`
                INNER JOIN `ctb_pgcp`
                    ON `ctb_libaux`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`
                LEFT JOIN `tes_cuentas`
                    ON `tes_cuentas`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`
            WHERE `ctb_doc`.`estado` = 2
              AND `ctb_doc`.`fecha` BETWEEN '$inicio' AND '$fecha_corte'
              AND $like_cuentas
            GROUP BY `ctb_libaux`.`id_cuenta`
            ORDER BY `ctb_pgcp`.`cuenta` ASC";

    $res   = $cmd->query($sql);
    $datos = $res->fetchAll(PDO::FETCH_ASSOC);
    $res->closeCursor();
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}

// Construir $acum directamente desde $datos (la query ya trae cuenta, nombre, tipo y fuente)
$acum = [];
foreach ($datos as $dato) {
    $cta = $dato['cuenta'];
    if (!isset($acum[$cta])) {
        $acum[$cta] = [
            'cuenta'   => $dato['cuenta'],
            'nombre'   => $dato['nombre'],
            'tipo'     => $dato['tipo'],
            'fuente'   => $dato['fuente'] ?? '',
            'debitoi'  => 0,
            'creditoi' => 0,
            'debito'   => 0,
            'credito'  => 0,
        ];
    }
    $acum[$cta]['debitoi']  += $dato['debitoi'];
    $acum[$cta]['creditoi'] += $dato['creditoi'];
    $acum[$cta]['debito']   += $dato['debito'];
    $acum[$cta]['credito']  += $dato['credito'];
}

ksort($acum, SORT_STRING);

// ----------------------------------------------------------------------
// Nuevo bloque: Valores pendientes por pagar por tercero
// ----------------------------------------------------------------------
try {
    $sql_pendientes = "SELECT 
            `ctb_pgcp`.`cuenta`,
            `ctb_pgcp`.`nombre` AS `nombre_cuenta`,
            `tb_terceros`.`nom_tercero` AS `nombre_tercero`,
            `tb_terceros`.`nit_tercero` AS `nit_tercero`,
            `ctb_doc`.`id_manu` AS `causacion_numero`,
            `ctb_doc`.`fecha` AS `fecha_causacion`,
            SUM(`ctb_libaux`.`credito` - `ctb_libaux`.`debito`) AS `causado`,
            (
                SELECT SUM(aux_pag.`debito` - aux_pag.`credito`) 
                FROM `pto_pag_detalle` pag 
                INNER JOIN `ctb_doc` doc_pag ON pag.`id_ctb_doc` = doc_pag.`id_ctb_doc` 
                INNER JOIN `ctb_libaux` aux_pag ON doc_pag.`id_ctb_doc` = aux_pag.`id_ctb_doc` 
                WHERE pag.`id_pto_cop_det` = `pto_cop_detalle`.`id_pto_cop_det` 
                  AND doc_pag.`estado` = 2 
                  AND aux_pag.`id_cuenta` = `ctb_pgcp`.`id_pgcp`
            ) AS `pagado`
        FROM `pto_cop_detalle`
        INNER JOIN `ctb_doc` ON `pto_cop_detalle`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`
        INNER JOIN `ctb_libaux` ON `ctb_doc`.`id_ctb_doc` = `ctb_libaux`.`id_ctb_doc`
        INNER JOIN `ctb_pgcp` ON `ctb_libaux`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`
        LEFT JOIN `tb_terceros` ON `ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`
        WHERE `ctb_doc`.`fecha` BETWEEN '$fecha_inicial' AND '$fecha_corte'
          AND `ctb_doc`.`estado` = 2
          AND `ctb_pgcp`.`cuenta` LIKE '2%'
        GROUP BY 
            `pto_cop_detalle`.`id_pto_cop_det`, 
            `ctb_doc`.`id_manu`, 
            `ctb_pgcp`.`id_pgcp`,
            `tb_terceros`.`nom_tercero`,
            `tb_terceros`.`nit_tercero`,
            `ctb_doc`.`fecha`,
            `ctb_pgcp`.`cuenta`,
            `ctb_pgcp`.`nombre`
        HAVING (`causado` - IFNULL(`pagado`, 0)) > 0
        ORDER BY `tb_terceros`.`nom_tercero`, `ctb_doc`.`fecha`";

    $res_pendientes = $cmd->query($sql_pendientes);
    $datos_pendientes = $res_pendientes->fetchAll(PDO::FETCH_ASSOC);
    $res_pendientes->closeCursor();
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}

// Datos de empresa
try {
    $sql_emp = "SELECT `razon_social_ips` AS `nombre`, `nit_ips` AS `nit`, `dv` AS `dig_ver` FROM `tb_datos_ips`";
    $res_emp  = $cmd->query($sql_emp);
    $empresa  = $res_emp->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $empresa = ['nombre' => '', 'nit' => '', 'dig_ver' => ''];
}

$nom_informe = "ESTADO DE SITUACIÓN DE TESORERÍA" . ($incluye_cartera ? " (Incluye Cartera)" : "");
?>
<div class="contenedor bg-light" id="areaImprimir">
    <div class="px-2" style="width:90% !important; margin: 0 auto;">
        <br>
        <table style="width:100% !important;" border="0">
            <tr><td colspan="4" style="text-align:center"><?php echo $empresa['nombre']; ?></td></tr>
            <tr><td colspan="4" style="text-align:center"><?php echo $empresa['nit'] . '-' . $empresa['dig_ver']; ?></td></tr>
            <tr><td colspan="4" style="text-align:center"><?php echo $nom_informe; ?></td></tr>
        </table>
        <br>
        <table style="width:100% !important; border-collapse: collapse;" border="1">
            <tr>
                <td>FECHA INICIO</td>
                <td style="text-align:left"><?php echo $fecha_inicial; ?></td>
                <td>FECHA FIN</td>
                <td style="text-align:left"><?php echo $fecha_corte; ?></td>
            </tr>
        </table>
        <br>
        <table class="table-bordered bg-light" style="width:100% !important; border-collapse: collapse;" border="1">
            <thead>
                <tr class="centrar">
                    <th>Cuenta</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Fuente</th>
                    <th>Saldo Final</th>
                </tr>
            </thead>
            <tbody>
        <?php
        if (!empty($acum)) {
            foreach ($acum as $tp) {
                // Cuentas 11 y 13 son de naturaleza débito: saldo = (ini_D - ini_C) + (deb - cre)
                $saldo  = round(($tp['debitoi'] - $tp['creditoi']) + ($tp['debito'] - $tp['credito']), 2);
                $fuente = mb_convert_encoding($tp['fuente'] ?? '', 'UTF-8');

                echo "<tr class='resaltar'>
                    <td class='text'>" . $tp['cuenta'] . "</td>
                    <td class='text'>" . mb_convert_encoding($tp['nombre'], 'UTF-8') . "</td>
                    <td class='text-center'>" . $tp['tipo'] . "</td>
                    <td class='text'>" . $fuente . "</td>
                    <td class='text-end'>" . number_format($saldo, 2, '.', ',') . "</td>
                    </tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No hay datos para mostrar</td></tr>";
        }
        ?>
            </tbody>
        </table>
        <br><br>
        
        <!-- Tabla de valores pendientes por pagar por tercero -->
        <table class="table-bordered bg-light" style="width:100% !important; border-collapse: collapse;" border="1">
            <thead>
                <tr>
                    <th colspan="7" class="centrar" style="background-color: #EAECEE; font-weight: bold;">VALORES PENDIENTES POR PAGAR POR TERCERO</th>
                </tr>
                <tr class="centrar">
                    <th>Tercero</th>
                    <th>CC / NIT</th>
                    <th>No. Causación</th>
                    <th>Fecha Causación</th>
                    <th>Cuenta</th>
                    <th>Nombre Cuenta</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
        <?php
        if (!empty($datos_pendientes)) {
            foreach ($datos_pendientes as $dp) {
                $saldo = round($dp['causado'] - (isset($dp['pagado']) ? $dp['pagado'] : 0), 2);
                if ($saldo > 0) {
                    echo "<tr class='resaltar'>
                        <td class='text'>" . mb_convert_encoding($dp['nombre_tercero'] ?? '', 'UTF-8') . "</td>
                        <td class='text'>" . ($dp['nit_tercero'] ?? '') . "</td>
                        <td class='centrar'>" . $dp['causacion_numero'] . "</td>
                        <td class='centrar'>" . date('Y-m-d', strtotime($dp['fecha_causacion'])) . "</td>
                        <td class='text'>" . $dp['cuenta'] . "</td>
                        <td class='text'>" . mb_convert_encoding($dp['nombre_cuenta'], 'UTF-8') . "</td>
                        <td class='text-end'>" . number_format($saldo, 2, '.', ',') . "</td>
                        </tr>";
                }
            }
        } else {
            echo "<tr><td colspan='7' class='centrar'>No hay valores pendientes por pagar</td></tr>";
        }
        ?>
            </tbody>
        </table>
        
        <br><br><br>
    </div>
</div>
</html>
