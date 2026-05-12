<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../../index.php");
    exit();
}

include_once '../../../../../config/autoloader.php';

use Src\Nomina\Certificaciones\Php\Clases\Certificados;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Usuarios\Login\Php\Clases\Usuario;

$id_tercero = isset($_POST['id_tercero']) ? intval($_POST['id_tercero']) : 0;
$fecha_ini  = isset($_POST['fecha_ini'])  ? $_POST['fecha_ini'] : '';
$fecha_fin  = isset($_POST['fecha_fin'])  ? $_POST['fecha_fin'] : '';

if (!$id_tercero || !$fecha_ini || !$fecha_fin) exit('Parámetros insuficientes.');

$Cert    = new Certificados();
$empleado = $Cert->getDatosEmpleadoPorTercero($id_tercero);
if (empty($empleado)) exit('No se encontró un empleado asociado al tercero seleccionado.');

$anio_fin = date('Y', strtotime($fecha_fin));
$id_vigencia_cert = isset($_SESSION['id_vigencia']) ? intval($_SESSION['id_vigencia']) : 0;
if ($id_vigencia_cert <= 0) {
    $id_vigencia_cert = Nomina::getIdVigenciaPorAnio($anio_fin);
}

$resumen = $Cert->getResumenForm220Libaux($id_tercero, $fecha_ini, $fecha_fin, $id_vigencia_cert);
if (empty($resumen)) exit('El empleado no tiene movimientos contables homologados para el Formato 220 en el período seleccionado.');
$detalle = $Cert->getDetalleForm220Libaux($id_tercero, $fecha_ini, $fecha_fin, $id_vigencia_cert);
if (empty($detalle)) exit('El empleado no tiene detalle contable homologado para el Formato 220 en el período seleccionado.');

// Preparar los conceptos al estilo Consolidado como en Excel
$conceptos = [];
foreach ($detalle as $fila) {
    $conceptos[] = [
        'concepto' => trim(($fila['cuenta'] ?? '') . ' - ' . ($fila['nombre'] ?? '')),
        'devengado' => floatval($fila['devengado'] ?? 0),
        'deducido' => floatval($fila['deducido'] ?? 0),
    ];
}

$tot_devengado = 0;
$tot_deducido = 0;

foreach ($conceptos as $c) {
    $tot_devengado += $c['devengado'];
    $tot_deducido += $c['deducido'];
}

$nombreArchivo = "Consolidado_" . ($empleado['no_documento'] ?? 'Emp') . "_" . date('Ymd');

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header("Content-Disposition: attachment; filename=\"{$nombreArchivo}.xls\"");
header('Pragma: no-cache');
header('Expires: 0');
echo "\xEF\xBB\xBF"; // UTF-8 BOM
echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
echo "<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">";
echo "<style>
        td, th { font-family: Arial, sans-serif; font-size: 12px; }
      </style>";
echo "</head>";
echo "<body>";

echo "<table border='1' cellspacing='0' cellpadding='5' style='border-collapse: collapse;'>";

// Información del Empleado
$nombreEmp = mb_strtoupper($empleado['nombre'] ?? '');
$ideEmp = $empleado['no_documento'] ?? '';
echo "<tr>";
echo "<td colspan='3' style='font-size: 14px; font-weight: bold; text-align: center; padding: 10px;'>EMPLEADO: {$nombreEmp} - IDENTIFICACIÓN: <span x:str=\"{$ideEmp}\" style='mso-number-format:\"@\";'>{$ideEmp}</span></td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='3' style='font-size: 12px; font-weight: bold; text-align: center; padding: 5px; color: #555;'>CONSOLIDADO {$fecha_ini} - {$fecha_fin}</td>";
echo "</tr>";

// Encabezado principal
echo "<tr style='background-color: #d0d0d0; font-weight: bold;'>";
echo "<td style='padding: 8px;'>RESUMEN CONSOLIDADO</td>";
echo "<td style='text-align: right; padding: 8px;'>$ " . number_format($tot_devengado, 2, ',', '.') . "</td>";
echo "<td style='text-align: right; padding: 8px;'>$ " . number_format($tot_deducido, 2, ',', '.') . "</td>";
echo "</tr>";

echo "<tr style='background-color: #e0e0e0; font-weight: bold; font-size: 11px;'>";
echo "<td style='padding: 4px;'>CONCEPTO</td>";
echo "<td style='text-align: right; padding: 4px;'>DEVENGADO</td>";
echo "<td style='text-align: right; padding: 4px;'>DEDUCIDO</td>";
echo "</tr>";

foreach ($conceptos as $d) {
    echo "<tr>";
    echo "<td style='padding: 3px; font-size: 11px;'>" . htmlspecialchars($d['concepto']) . "</td>";
    echo "<td style='padding: 3px; font-size: 11px; text-align: right;'>$ " . number_format($d['devengado'], 2, ',', '.') . "</td>";
    echo "<td style='padding: 3px; font-size: 11px; text-align: right;'>$ " . number_format($d['deducido'], 2, ',', '.') . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</body></html>";
exit();
