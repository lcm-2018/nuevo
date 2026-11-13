<?php

use Src\Common\Php\Clases\Imprimir;
use Src\Common\Php\Clases\Reportes as CReportes;
use Src\Common\Php\Clases\Valores;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Liquidado\Php\Clases\Detalles;
use Src\Usuarios\Login\Php\Clases\Usuario;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

include_once '../../../../../config/autoloader.php';

// Obtener parámetros: id y tipo (E=Excel, P=PDF)
$id_nomina = isset($_POST['id']) ? intval($_POST['id']) : exit('ID de nómina no proporcionado');
$tipo = isset($_POST['tipo']) ? strtoupper(trim($_POST['tipo'])) : 'E';

// Validar tipo
if (!in_array($tipo, ['E', 'P'])) {
    exit('Tipo de reporte no válido. Use E para Excel o P para PDF');
}

// Obtener información de la nómina
$nomina = Nomina::getRegistro($id_nomina);
if (empty($nomina) || $nomina['id_nomina'] == 0) {
    exit('Nómina no encontrada');
}

$mes = mb_strtoupper(Valores::NombreMes($nomina['mes']));
$usuario = new Usuario();
$empresa = $usuario->getEmpresa();
$decimales = isset(Valores::getOwnerConfig()['decimales']) ? intval(Valores::getOwnerConfig()['decimales']) : 0;

// Obtener datos de descuentos pendientes de retroactivo
$detallesObj = new Detalles();
$datos = $detallesObj->getDatosDescuentosPendientesRetroactivo($id_nomina);

if (empty($datos)) {
    echo "<script>alert('No se encontraron descuentos ni retenciones pendientes para esta nómina de retroactivo.'); window.close();</script>";
    exit('No se encontraron descuentos ni retenciones pendientes para esta nómina de retroactivo.');
}

/**
 * Retorna el nombre descriptivo del concepto pendiente
 */
function getNombreConceptoDescuento($id_concepto, $nom_concepto_base = '')
{
    switch (intval($id_concepto)) {
        case 19:
            return 'RETENCIÓN EN LA FUENTE (PENDIENTE)';
        case 2:
            return 'REINTEGRO AUXILIO DE TRANSPORTE (PENDIENTE)';
        case 3:
            return 'REINTEGRO AUXILIO DE ALIMENTACIÓN (PENDIENTE)';
        case 5:
            return 'REINTEGRO EXCESO BSP (PENDIENTE)';
        default:
            return !empty($nom_concepto_base)
                ? mb_strtoupper(trim($nom_concepto_base)) . ' (PENDIENTE)'
                : 'DESCUENTO PENDIENTE (CONCEPTO ' . $id_concepto . ')';
    }
}

// ─────────────────────────────────────────────────────────────
// CASO 1: EXCEL (E)
// ─────────────────────────────────────────────────────────────
if ($tipo === 'E') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Descuentos_Pendientes_Nomina_' . $id_nomina . '_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
    echo "<head>";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">";
    echo "</head>";
    echo "<body>";

    // Encabezado institucional
    echo "<table border='1'>";
    echo "<tr><td colspan='9' style='text-align: center; font-weight: bold; font-size: 14px; background-color: #f7f9f9;'>" . mb_strtoupper($empresa['nombre']) . "</td></tr>";
    echo "<tr><td colspan='9' style='text-align: center; font-weight: bold; font-size: 12px;'>REPORTE DE DESCUENTOS Y RETENCIONES PENDIENTES POR DESCONTAR (RETROACTIVO)</td></tr>";
    echo "<tr><td colspan='9' style='text-align: center;'>NÓMINA No. {$id_nomina} - MES: {$mes} - VIGENCIA: {$nomina['vigencia']} - TIPO: " . ($nomina['tipo_nomina'] ?? 'RETROACTIVO') . " - DESCRIPCIÓN: {$nomina['descripcion']}</td></tr>";
    echo "<tr><td colspan='9'>&nbsp;</td></tr>";

    // Encabezados de columnas
    echo "<tr style='background-color: #16a085; color: #ffffff; font-weight: bold; text-align: center;'>";
    echo "<th style='padding: 6px;'>#</th>";
    echo "<th style='padding: 6px;'>DOCUMENTO</th>";
    echo "<th style='padding: 6px;'>NOMBRE EMPLEADO</th>";
    echo "<th style='padding: 6px;'>SEDE</th>";
    echo "<th style='padding: 6px;'>CARGO</th>";
    echo "<th style='padding: 6px;'>CONCEPTO</th>";
    echo "<th style='padding: 6px; text-align: right;'>TOTAL A DESCONTAR</th>";
    echo "<th style='padding: 6px; text-align: right;'>DESCONTADO EN NÓMINA</th>";
    echo "<th style='padding: 6px; text-align: right;'>VALOR PENDIENTE</th>";
    echo "</tr>";

    $gran_total_esperado = 0;
    $gran_total_descontado = 0;
    $gran_total_pendiente = 0;
    $i = 1;

    foreach ($datos as $d) {
        $conceptoNombre = getNombreConceptoDescuento($d['id_concepto'], $d['nom_concepto_base']);
        $valTotal = floatval($d['valor_total']);
        $valDescontado = floatval($d['valor_descontado']);
        $valPendiente = floatval($d['valor_pendiente']);

        $gran_total_esperado += $valTotal;
        $gran_total_descontado += $valDescontado;
        $gran_total_pendiente += $valPendiente;

        echo "<tr>";
        echo "<td style='text-align: center;'>" . $i++ . "</td>";
        echo "<td style=\"mso-number-format:'\@'; text-align: center;\">" . htmlspecialchars($d['documento']) . "</td>";
        echo "<td>" . htmlspecialchars($d['nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($d['sede']) . "</td>";
        echo "<td>" . htmlspecialchars($d['cargo']) . "</td>";
        echo "<td>" . htmlspecialchars($conceptoNombre) . "</td>";
        echo "<td style='text-align: right;'>" . number_format($valTotal, $decimales, ',', '.') . "</td>";
        echo "<td style='text-align: right;'>" . number_format($valDescontado, $decimales, ',', '.') . "</td>";
        echo "<td style='text-align: right; font-weight: bold; color: #c0392b;'>" . number_format($valPendiente, $decimales, ',', '.') . "</td>";
        echo "</tr>";
    }

    // Fila de totales
    echo "<tr style='background-color: #e8f8f5; font-weight: bold;'>";
    echo "<td colspan='6' style='text-align: right; padding: 6px;'>TOTAL GENERAL:</td>";
    echo "<td style='text-align: right; padding: 6px;'>$ " . number_format($gran_total_esperado, $decimales, ',', '.') . "</td>";
    echo "<td style='text-align: right; padding: 6px;'>$ " . number_format($gran_total_descontado, $decimales, ',', '.') . "</td>";
    echo "<td style='text-align: right; padding: 6px; color: #c0392b;'>$ " . number_format($gran_total_pendiente, $decimales, ',', '.') . "</td>";
    echo "</tr>";

    echo "</table>";
    echo "</body>";
    echo "</html>";
    exit();
}

// ─────────────────────────────────────────────────────────────
// CASO 2: IMPRESIÓN / PDF (P)
// ─────────────────────────────────────────────────────────────
$html = '';
$html .= "<table style='width: 100%; border-collapse: collapse; font-size: 10px;' border='1'>";
$html .= "<thead>";
$html .= "<tr style='background-color: #16a085; color: white; font-weight: bold; text-align: center;'>";
$html .= "<th style='padding: 5px; width: 4%;'>#</th>";
$html .= "<th style='padding: 5px; width: 10%;'>DOCUMENTO</th>";
$html .= "<th style='padding: 5px; width: 22%;'>NOMBRE EMPLEADO</th>";
$html .= "<th style='padding: 5px; width: 14%;'>SEDE</th>";
$html .= "<th style='padding: 5px; width: 14%;'>CARGO</th>";
$html .= "<th style='padding: 5px; width: 14%;'>CONCEPTO</th>";
$html .= "<th style='padding: 5px; text-align: right; width: 7%;'>TOTAL</th>";
$html .= "<th style='padding: 5px; text-align: right; width: 7%;'>DESCONTADO</th>";
$html .= "<th style='padding: 5px; text-align: right; width: 8%;'>PENDIENTE</th>";
$html .= "</tr>";
$html .= "</thead>";
$html .= "<tbody>";

$gran_total_esperado = 0;
$gran_total_descontado = 0;
$gran_total_pendiente = 0;
$i = 1;

foreach ($datos as $d) {
    $conceptoNombre = getNombreConceptoDescuento($d['id_concepto'], $d['nom_concepto_base']);
    $valTotal = floatval($d['valor_total']);
    $valDescontado = floatval($d['valor_descontado']);
    $valPendiente = floatval($d['valor_pendiente']);

    $gran_total_esperado += $valTotal;
    $gran_total_descontado += $valDescontado;
    $gran_total_pendiente += $valPendiente;

    $html .= "<tr>";
    $html .= "<td style='text-align: center; padding: 3px;'>" . $i++ . "</td>";
    $html .= "<td style='text-align: center; padding: 3px;'>" . htmlspecialchars($d['documento']) . "</td>";
    $html .= "<td style='padding: 3px;'>" . htmlspecialchars($d['nombre']) . "</td>";
    $html .= "<td style='padding: 3px;'>" . htmlspecialchars($d['sede']) . "</td>";
    $html .= "<td style='padding: 3px;'>" . htmlspecialchars($d['cargo']) . "</td>";
    $html .= "<td style='padding: 3px; font-size: 9px;'>" . htmlspecialchars($conceptoNombre) . "</td>";
    $html .= "<td style='text-align: right; padding: 3px;'>" . number_format($valTotal, $decimales, ',', '.') . "</td>";
    $html .= "<td style='text-align: right; padding: 3px;'>" . number_format($valDescontado, $decimales, ',', '.') . "</td>";
    $html .= "<td style='text-align: right; padding: 3px; font-weight: bold; color: #c0392b;'>" . number_format($valPendiente, $decimales, ',', '.') . "</td>";
    $html .= "</tr>";
}

$html .= "<tr style='background-color: #f0f0f0; font-weight: bold;'>";
$html .= "<td colspan='6' style='text-align: right; padding: 5px;'>TOTAL GENERAL:</td>";
$html .= "<td style='text-align: right; padding: 5px;'>$ " . number_format($gran_total_esperado, $decimales, ',', '.') . "</td>";
$html .= "<td style='text-align: right; padding: 5px;'>$ " . number_format($gran_total_descontado, $decimales, ',', '.') . "</td>";
$html .= "<td style='text-align: right; padding: 5px; color: #c0392b;'>$ " . number_format($gran_total_pendiente, $decimales, ',', '.') . "</td>";
$html .= "</tr>";
$html .= "</tbody>";
$html .= "</table>";

$documento = "Reporte de Descuentos Pendientes (Retroactivo)";
$otro = "NÓMINA No. {$id_nomina} - MES: {$mes} - VIGENCIA: {$nomina['vigencia']}";

$firmas = (new CReportes())->getFormFirmas(
    ['nom_tercero' => $nomina['elabora'], 'cargo' => $nomina['cargo']],
    51,
    $nomina['fecha'],
    'CNOM'
);

$Imprimir = new Imprimir($documento, "legal", "L");
$Imprimir->addEncabezado($documento, $otro, 'CNOM', 51);
$Imprimir->addContenido($html);
$Imprimir->addFirmas($firmas);
$pdf = isset($_POST['pdf']) ? filter_var($_POST['pdf'], FILTER_VALIDATE_BOOLEAN) : false;
$resul = $Imprimir->render($pdf);

if ($pdf) {
    $Imprimir->getPDF($resul);
    exit();
}

