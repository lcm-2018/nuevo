<?php

use Src\Common\Php\Clases\Imprimir;
use Src\Common\Php\Clases\Reportes as CReportes;
use Src\Common\Php\Clases\Valores;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Empleados\Php\Clases\Sindicatos;
use Src\Usuarios\Login\Php\Clases\Usuario;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

include_once '../../../../../config/autoloader.php';

// Obtener parámetros: id y tipo (E=Excel, P=PDF)
$id_nomina = isset($_POST['id']) ? intval($_POST['id']) : exit('ID de nómina no proporcionado');
$tipo = isset($_POST['tipo']) ? strtoupper($_POST['tipo']) : 'P'; // Por defecto PDF

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

// Obtener datos de sindicatos agrupadas por sindicato
$sindicatosObj = new Sindicatos();
$datosSindicatos = $sindicatosObj->getSindicatosPorNomina($id_nomina);

if (empty($datosSindicatos)) {
    exit('No se encontraron aportes sindicales para esta nómina');
}

// Si es tipo Excel, generar archivo Excel
if ($tipo == 'E') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Sindicatos_Nomina_' . $id_nomina . '_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
    echo "<head>";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">";
    echo "</head>";
    echo "<body>";

    // Encabezado
    echo "<table border='1'>";
    echo "<tr><td colspan='3' style='text-align: center; font-weight: bold; font-size: 14px;'>" . mb_strtoupper($empresa['nombre']) . "</td></tr>";
    echo "<tr><td colspan='3' style='text-align: center; font-weight: bold;'>REPORTE DE APORTES SINDICALES</td></tr>";
    echo "<tr><td colspan='3' style='text-align: center;'>NÓMINA No. {$id_nomina} - MES: {$mes} - VIGENCIA: {$nomina['vigencia']}</td></tr>";
    echo "<tr><td colspan='3'>&nbsp;</td></tr>";

    $gran_total = 0;

    foreach ($datosSindicatos as $entidad) {
        // Encabezado de entidad
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<td colspan='3' style='font-weight: bold;'>{$entidad['nit_entidad']} {$entidad['nom_entidad']}</td>";
        echo "</tr>";

        // Encabezados de columnas
        echo "<tr style='background-color: #e0e0e0; font-weight: bold;'>";
        echo "<td>Documento</td>";
        echo "<td>Nombre</td>";
        echo "<td style='text-align: right;'>Valor</td>";
        echo "</tr>";

        // Empleados
        foreach ($entidad['empleados'] as $empleado) {
            echo "<tr>";
            echo "<td>{$empleado['no_documento']}</td>";
            echo "<td>{$empleado['nombre_empleado']}</td>";
            echo "<td style='text-align: right;'>" . number_format($empleado['valor'], 0, ',', '.') . "</td>";
            echo "</tr>";
        }

        // Total por entidad
        echo "<tr style='background-color: #f0f0f0; font-weight: bold;'>";
        echo "<td colspan='2' style='text-align: right;'>TOTAL:</td>";
        echo "<td style='text-align: right;'>" . number_format($entidad['total'], 0, ',', '.') . "</td>";
        echo "</tr>";
        echo "<tr><td colspan='3'>&nbsp;</td></tr>";

        $gran_total += $entidad['total'];
    }

    // Gran total
    echo "<tr style='background-color: #d0d0d0; font-weight: bold; font-size: 15px;'>";
    echo "<td colspan='2' style='text-align: right;'>GRAN TOTAL:</td>";
    echo "<td style='text-align: right;'>" . number_format($gran_total, 0, ',', '.') . "</td>";
    echo "</tr>";

    echo "</table>";
    echo "</body></html>";
    exit();
}

// Si es tipo PDF, generar PDF
$html = '';
$gran_total = 0;

foreach ($datosSindicatos as $entidad) {
    // Encabezado de entidad
    $html .= "<table style='width: 100%; border-collapse: collapse; margin-bottom: 15px;' border='1'>";
    $html .= "<tr style='background-color: #f0f0f0;'>";
    $html .= "<th colspan='3' style='text-align: left; padding: 8px; font-size: 12px; font-weight: bold;'>";
    $html .= "{$entidad['nit_entidad']} {$entidad['nom_entidad']}";
    $html .= "</th>";
    $html .= "</tr>";

    // Encabezados de columnas
    $html .= "<tr style='background-color: #e0e0e0;'>";
    $html .= "<th style='padding: 6px; text-align: left; font-size: 11px;'>Documento</th>";
    $html .= "<th style='padding: 6px; text-align: left; font-size: 11px;'>Nombre</th>";
    $html .= "<th style='padding: 6px; text-align: right; font-size: 11px;'>Valor</th>";
    $html .= "</tr>";

    // Empleados
    foreach ($entidad['empleados'] as $empleado) {
        $valor_fmt = number_format($empleado['valor'], 0, ',', '.');
        $html .= "<tr>";
        $html .= "<td style='padding: 4px 8px;'>{$empleado['no_documento']}</td>";
        $html .= "<td style='padding: 4px 8px;'>{$empleado['nombre_empleado']}</td>";
        $html .= "<td style='padding: 4px 8px; text-align: right;'>{$valor_fmt}</td>";
        $html .= "</tr>";
    }

    // Total por entidad
    $total_fmt = number_format($entidad['total'], 0, ',', '.');
    $html .= "<tr style='background-color: #f0f0f0; font-weight: bold;'>";
    $html .= "<td colspan='2' style='padding: 6px 8px; text-align: right;'>TOTAL:</td>";
    $html .= "<td style='padding: 6px 8px; text-align: right;'>{$total_fmt}</td>";
    $html .= "</tr>";
    $html .= "</table>";

    $gran_total += $entidad['total'];
}

// Gran total
$gran_total_fmt = number_format($gran_total, 0, ',', '.');
$html .= "<table style='width: 100%; border-collapse: collapse; margin-top: 15px;' border='1'>";
$html .= "<tr style='background-color: #d0d0d0;'>";
$html .= "<td style='padding: 10px; font-size: 14px; font-weight: bold; text-align: right; width: 70%;'>GRAN TOTAL:</td>";
$html .= "<td style='padding: 10px; font-size: 14px; font-weight: bold; text-align: right; width: 30%;'>{$gran_total_fmt}</td>";
$html .= "</tr>";
$html .= "</table>";

$documento = "Reporte de Aportes Sindicales";
$otro = "NÓMINA No. {$id_nomina} - MES: {$mes} - VIGENCIA: {$nomina['vigencia']}";

$firmas = (new CReportes())->getFormFirmas(
    ['nom_tercero' => $nomina['elabora'], 'cargo' => $nomina['cargo']],
    51,
    $nomina['vigencia'] . '-' . $nomina['mes'] . '-01',
    ''
);

$Imprimir = new Imprimir($documento, "letter");
$Imprimir->addEncabezado($documento, $otro);
$Imprimir->addContenido($html);
$Imprimir->addFirmas($firmas);
$pdf = isset($_POST['pdf']) ? filter_var($_POST['pdf'], FILTER_VALIDATE_BOOLEAN) : false;
$resul = $Imprimir->render($pdf);

if ($pdf) {
    $Imprimir->getPDF($resul);
    exit();
}
