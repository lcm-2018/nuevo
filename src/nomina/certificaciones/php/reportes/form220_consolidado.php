<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../../index.php");
    exit();
}

include_once '../../../../../config/autoloader.php';

use Src\Nomina\Certificaciones\Php\Clases\Certificados;
use Src\Usuarios\Login\Php\Clases\Usuario;

$id_tercero = isset($_POST['id_tercero']) ? intval($_POST['id_tercero']) : 0;
$fecha_ini  = isset($_POST['fecha_ini'])  ? $_POST['fecha_ini'] : '';
$fecha_fin  = isset($_POST['fecha_fin'])  ? $_POST['fecha_fin'] : '';

if (!$id_tercero || !$fecha_ini || !$fecha_fin) exit('Parámetros insuficientes.');

$Cert    = new Certificados();
$empleado = $Cert->getDatosEmpleadoPorTercero($id_tercero);
if (empty($empleado)) exit('No se encontró un empleado asociado al tercero seleccionado.');
$id_empleado = intval($empleado['id_empleado']);

$ids_nomina = $Cert->getNominasPorRango($fecha_ini, $fecha_fin);
if (empty($ids_nomina)) exit('No se encontraron nóminas liquidadas (estado 5) en el rango.');

$resumen = $Cert->getResumenAnual($id_empleado, $ids_nomina);
if (empty($resumen)) exit('El empleado no tiene movimientos en el período seleccionado.');

// Preparar los conceptos al estilo Consolidado como en Excel
$conceptos = [];

// DEVENGADOS
function add_dev(&$arr, $nombre, $valor)
{
    if ($valor >= 0) {
        $arr[] = ['concepto' => $nombre, 'devengado' => floatval($valor), 'deducido' => 0];
    }
}

add_dev($conceptos, 'Auxilio de alimentación', $resumen['total_aux_alim'] ?? 0);
add_dev($conceptos, 'Auxilio de transporte', $resumen['total_aux_transporte'] ?? 0);
add_dev($conceptos, 'Bonificación de recreación', 0);
add_dev($conceptos, 'Bonificación Servicios Prestados', $resumen['total_bsp'] ?? 0);
add_dev($conceptos, 'Cesantías', $resumen['total_cesantias'] ?? 0);
add_dev($conceptos, 'Compensatorios', 0);
add_dev($conceptos, 'Gastos de representación', $resumen['total_g_representa'] ?? 0);
add_dev($conceptos, 'Horas extra', $resumen['total_horas_ext'] ?? 0);
add_dev($conceptos, 'Incapacidades', $resumen['total_incap'] ?? 0);
add_dev($conceptos, 'Indemnización de vacaciones', 0);
add_dev($conceptos, 'Intereses a las cesantías', $resumen['total_int_cesantias'] ?? 0);
add_dev($conceptos, 'Laborado', $resumen['total_laborado'] ?? 0);
add_dev($conceptos, 'Licencias', 0);
add_dev($conceptos, 'Otros devengados', $resumen['otros_dev'] ?? 0);
add_dev($conceptos, 'Prima de navidad', $resumen['total_prima_nav'] ?? 0);
add_dev($conceptos, 'Prima de servicios', $resumen['total_prima_serv'] ?? 0);
add_dev($conceptos, 'Prima de vacaciones', 0);
add_dev($conceptos, 'Vacaciones', $resumen['total_vacaciones'] ?? 0);
add_dev($conceptos, 'Viáticos', 0);

// DEDUCIDOS
function add_ded(&$arr, $nombre, $valor)
{
    if ($valor >= 0) {
        $arr[] = ['concepto' => $nombre, 'devengado' => 0, 'deducido' => floatval($valor)];
    }
}

add_ded($conceptos, 'Aporte a pensión', $resumen['total_pension_emp'] ?? 0);
add_ded($conceptos, 'Aporte a salud', $resumen['total_salud_emp'] ?? 0);
add_ded($conceptos, 'Fondo de Solidaridad Pensional', $resumen['total_solidaridad'] ?? 0);
add_ded($conceptos, 'Retención en la fuente', $resumen['total_retencion'] ?? 0);

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
