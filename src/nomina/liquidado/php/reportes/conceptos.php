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

// Obtener parámetros: id, tipo (E=Excel, P=PDF), id_concepto
$id_nomina = isset($_POST['id']) ? intval($_POST['id']) : exit('ID de nómina no proporcionado');
$tipo = isset($_POST['tipo']) ? strtoupper($_POST['tipo']) : 'P'; // Por defecto PDF
$id_concepto = isset($_POST['id_concepto']) ? intval($_POST['id_concepto']) : exit('ID de concepto no proporcionado');

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

// Obtener nombre del concepto
$nombreConcepto = Detalles::getNombreConcepto($id_concepto);

// Obtener datos del reporte según el concepto
$detallesObj = new Detalles();

// Determinar el tipo de reporte y las columnas según el concepto
$columnas = [];
$datos = [];
$columnaExtra = '';

// Mapeo de conceptos según tabla nom_conceptos_liquidacion:
// 1=SUELDO BÁSICO, 2=AUXILIO DE TRANSPORTE, 3=AUXILIO DE ALIMENTACIÓN, 4=HORAS EXTRA,
// 5=BONIFICACIÓN POR SERVICIOS PRESTADOS, 6=VACACIONES, 7=PRIMA DE VACACIONES,
// 8=BONIFICACIÓN DE RECREACIÓN, 9=INCAPACIDAD, 10=LICENCIA REMUNERADA,
// 11=GASTOS DE REPRESENTACIÓN, 12=INDEMNIZACIÓN POR VACACIONES, 13=APORTE A SALUD,
// 14=APORTE A PENSIÓN, 15=APORTE A SOLIDARIDAD PENSIONAL, 16=LIBRANZA, 17=EMBARGO,
// 18=SINDICATO, 19=RETENCIÓN EN LA FUENTE, 20=NETO
switch ($id_concepto) {
    case 1: // SUELDO BÁSICO
    case 2: // AUXILIO DE TRANSPORTE
    case 3: // AUXILIO DE ALIMENTACIÓN
    case 5: // BONIFICACIÓN POR SERVICIOS PRESTADOS
    case 11: // GASTOS DE REPRESENTACIÓN
    case 15: // APORTE A SOLIDARIDAD PENSIONAL
        // Conceptos generales con DOCUMENTO, NOMBRE, DIAS, VALOR
        $datos = $detallesObj->getDatosReporteGeneral($id_nomina, getColumnaPorConcepto($id_concepto), getTablaPorConcepto($id_concepto));
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS', 'VALOR'];
        $columnaExtra = 'general';
        break;

    case 4: // HORAS EXTRA
        $datos = $detallesObj->getDatosReporteHorasExtras($id_nomina);
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS', 'TIPO HORA', 'CANTIDAD', 'VALOR'];
        $columnaExtra = 'horas_extras';
        break;

    case 10: // LICENCIA REMUNERADA (Luto, Maternidad, Paternidad)
        $datos = $detallesObj->getDatosReporteLicenciasRemuneradas($id_nomina);
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS', 'TIPO LICENCIA', 'VALOR'];
        $columnaExtra = 'licencias';
        break;

    case 6: // VACACIONES
        $datos = $detallesObj->getDatosReporteVacaciones($id_nomina);
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS INACTIVOS', 'VALOR'];
        $columnaExtra = 'vacaciones';
        break;

    case 7: // PRIMA DE VACACIONES
        $datos = $detallesObj->getDatosReporteVacaciones($id_nomina);
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS HABILES', 'PRIMA VAC.'];
        $columnaExtra = 'prima_vacaciones';
        break;

    case 8: // BONIFICACIÓN DE RECREACIÓN
        $datos = $detallesObj->getDatosReporteVacaciones($id_nomina);
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS BR', 'BON. RECREACIÓN'];
        $columnaExtra = 'bon_recreacion';
        break;

    case 9: // INCAPACIDAD
        $datos = $detallesObj->getDatosReporteIncapacidades($id_nomina);
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS', 'TIPO INCAPACIDAD', 'VALOR'];
        $columnaExtra = 'incapacidades';
        break;

    case 12: // INDEMNIZACIÓN POR VACACIONES
        $datos = $detallesObj->getDatosReporteIndemnizacionVac($id_nomina);
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS', 'VALOR'];
        $columnaExtra = 'general';
        break;

    case 13: // APORTE A SALUD
        $datos = $detallesObj->getDatosReporteSeguridadSocial($id_nomina, 'salud');
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS', 'VALOR'];
        $columnaExtra = 'general';
        break;

    case 14: // APORTE A PENSIÓN
        $datos = $detallesObj->getDatosReporteSeguridadSocial($id_nomina, 'pension');
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS', 'VALOR'];
        $columnaExtra = 'general';
        break;

    case 16: // LIBRANZA
        $datos = $detallesObj->getDatosReporteLibranzas($id_nomina);
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS', 'BANCO', 'VALOR'];
        $columnaExtra = 'libranzas';
        break;

    case 17: // EMBARGO
        $datos = $detallesObj->getDatosReporteEmbargos($id_nomina);
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS', 'JUZGADO', 'VALOR'];
        $columnaExtra = 'embargos';
        break;

    case 18: // SINDICATO
        $datos = $detallesObj->getDatosReporteSindicatos($id_nomina);
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS', 'SINDICATO', 'VALOR'];
        $columnaExtra = 'sindicatos';
        break;

    case 19: // RETENCIÓN EN LA FUENTE
        $datos = $detallesObj->getDatosReporteRetencion($id_nomina);
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS', 'BASE RETENCIÓN', 'VALOR'];
        $columnaExtra = 'retencion';
        break;

    case 20: // NETO
        $datos = $detallesObj->getDatosReporteConcepto($id_nomina, $id_concepto);
        $columnas = ['DOCUMENTO', 'MUNICIPIO', 'NOMBRE', 'BANCO', 'COD_BANCO', 'TIPO', 'CUENTA', 'DIAS LIQUIDADO', 'VALOR'];
        $columnaExtra = 'netos';
        break;

    default:
        // Conceptos no mapeados - usar estructura básica
        $datos = [];
        $columnas = ['DOCUMENTO', 'NOMBRE', 'DIAS', 'VALOR'];
        $columnaExtra = 'general';
        break;
}

// Funciones auxiliares para conceptos generales
function getColumnaPorConcepto($id)
{
    $mapeo = [
        1 => 'val_liq_dias',      // SUELDO BÁSICO
        2 => 'val_liq_auxt',       // AUXILIO DE TRANSPORTE
        3 => 'aux_alim',           // AUXILIO DE ALIMENTACIÓN
        5 => 'val_bsp',            // BSP (nom_liq_bsp)
        11 => 'g_representa',      // GASTOS DE REPRESENTACIÓN
        15 => 'aporte_solidaridad_pensional', // SOLIDARIDAD PENSIONAL
    ];
    return $mapeo[$id] ?? 'valor';
}

function getTablaPorConcepto($id)
{
    $mapeo = [
        1 => 'nom_liq_dlab_auxt',   // SUELDO BÁSICO
        2 => 'nom_liq_dlab_auxt',   // AUXILIO DE TRANSPORTE
        3 => 'nom_liq_dlab_auxt',   // AUXILIO DE ALIMENTACIÓN
        5 => 'nom_liq_bsp',         // BSP
        11 => 'nom_liq_dlab_auxt',  // GASTOS DE REPRESENTACIÓN
        15 => 'nom_liq_segsocial_empdo', // SOLIDARIDAD PENSIONAL
    ];
    return $mapeo[$id] ?? 'nom_liq_dlab_auxt';
}

if (empty($datos)) {
    exit('No se encontraron datos para este concepto en la nómina');
}

// Calcular total
$total = 0;
foreach ($datos as $d) {
    $total += floatval($d['valor'] ?? 0);
}

// Función para obtener el valor de una celda según el tipo de columna extra
function getCeldaValor($d, $campo, $columnaExtra)
{
    switch ($campo) {
        case 'DOCUMENTO':
            return $d['documento'] ?? '';
        case 'MUNICIPIO':
            return $d['municipio'] ?? '';
        case 'NOMBRE':
            return $d['nombre'] ?? '';
        case 'BANCO':
            return $d['banco'] ?? '';
        case 'COD_BANCO':
            return $d['cod_banco'] ?? '';
        case 'TIPO':
            return isset($d['tipo_cta']) ? ($d['tipo_cta'] == 1 ? 'AHORROS' : 'CORRIENTE') : '';
        case 'CUENTA':
            return $d['cuenta'] ?? '';
        case 'DIAS LIQUIDADO':
        case 'DIAS':
            return $d['dias'] ?? $d['dias_liquidado'] ?? 0;
        case 'DIAS INACTIVOS':
            return $d['diasi'] ?? 0;
        case 'DIAS HABILES':
            return $d['diash'] ?? 0;
        case 'DIAS BR':
            return $d['diasbr'] ?? 2;
        case 'VALOR':
            return $d['valor'] ?? 0;
        case 'TIPO HORA':
            return $d['tipo_hora'] ?? '';
        case 'CANTIDAD':
            return $d['cantidad'] ?? 0;
        case 'BASE RETENCIÓN':
            return $d['base_retencion'] ?? 0;
        case 'JUZGADO':
            return $d['juzgado'] ?? '';
        case 'SINDICATO':
            return $d['sindicato'] ?? '';
        case 'PRIMA VAC.':
            return $d['prima_vac'] ?? 0;
        case 'BON. RECREACIÓN':
            return $d['bon_recrea'] ?? 0;
        case 'TIPO INCAPACIDAD':
            return $d['tipo_incapacidad'] ?? '';
        case 'TIPO LICENCIA':
            return $d['tipo_licencia'] ?? '';
        case 'CONCEPTO':
            return $d['concepto'] ?? '';
        default:
            return '';
    }
}

// Función para formatear valor según el tipo de campo
function formatearCelda($valor, $campo)
{
    $camposNumericos = ['VALOR', 'BASE RETENCIÓN', 'PRIMA VAC.', 'BON. RECREACIÓN'];
    $camposCentrados = ['DIAS LIQUIDADO', 'DIAS', 'DIAS INACTIVOS', 'DIAS HABILES', 'DIAS BR', 'CANTIDAD', 'COD_BANCO', 'TIPO'];

    if (in_array($campo, $camposNumericos)) {
        return '$ ' . number_format(floatval($valor), 2, ',', '.');
    }
    return $valor;
}

$numColumnas = count($columnas);

// Si es tipo Excel, generar archivo Excel
if ($tipo == 'E') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombreConcepto . '_Nomina_' . $id_nomina . '_' . date('Y-m-d') . '.xls"');
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
    echo "<tr><td colspan='{$numColumnas}' style='text-align: center; font-weight: bold; font-size: 14px;'>" . mb_strtoupper($empresa['nombre']) . "</td></tr>";
    echo "<tr><td colspan='{$numColumnas}' style='text-align: center; font-weight: bold;'>REPORTE DE " . mb_strtoupper($nombreConcepto) . "</td></tr>";
    echo "<tr><td colspan='{$numColumnas}' style='text-align: center;'>NÓMINA No. {$id_nomina} - MES: {$mes} - VIGENCIA: {$nomina['vigencia']}</td></tr>";
    echo "<tr><td colspan='{$numColumnas}'>&nbsp;</td></tr>";

    // Total por concepto
    echo "<tr style='background-color: #d0d0d0; font-weight: bold;'>";
    echo "<td colspan='" . ($numColumnas - 1) . "' style='text-align: left; padding: 5px;'>TOTAL POR CONCEPTO</td>";
    echo "<td style='text-align: right; padding: 5px;'>$ " . number_format($total, 0, ',', '.') . "</td>";
    echo "</tr>";

    // Encabezados de columnas
    echo "<tr style='background-color: #e0e0e0; font-weight: bold;'>";
    foreach ($columnas as $col) {
        $align = in_array($col, ['VALOR', 'BASE RETENCIÓN', 'PRIMA VAC.', 'BON. RECREACIÓN']) ? 'right' : 'left';
        echo "<td style='text-align: {$align};'>{$col}</td>";
    }
    echo "</tr>";

    // Datos
    foreach ($datos as $d) {
        echo "<tr>";
        foreach ($columnas as $col) {
            $valor = getCeldaValor($d, $col, $columnaExtra);
            $valorFormateado = formatearCelda($valor, $col);
            $align = in_array($col, ['VALOR', 'BASE RETENCIÓN', 'PRIMA VAC.', 'BON. RECREACIÓN']) ? 'right' : 'left';
            if (in_array($col, ['DIAS LIQUIDADO', 'DIAS', 'CANTIDAD', 'COD_BANCO', 'TIPO'])) {
                $align = 'center';
            }
            echo "<td style='text-align: {$align};'>{$valorFormateado}</td>";
        }
        echo "</tr>";
    }

    echo "</table>";
    echo "</body></html>";
    exit();
}

// Si es tipo PDF, generar PDF
$html = '';

// Total por concepto
$html .= "<table style='width: 100%; border-collapse: collapse; margin-bottom: 10px;' border='1'>";
$html .= "<tr style='background-color: #d0d0d0;'>";
$html .= "<th style='text-align: left; padding: 8px; font-size: 11px; font-weight: bold;'>TOTAL POR CONCEPTO</th>";
$html .= "<th style='text-align: right; padding: 8px; font-size: 11px; font-weight: bold;'>$ " . number_format($total, 0, ',', '.') . "</th>";
$html .= "</tr>";
$html .= "</table>";

// Tabla de datos
$html .= "<table style='width: 100%; border-collapse: collapse;' border='1'>";

// Encabezados
$html .= "<tr style='background-color: #e0e0e0;'>";
foreach ($columnas as $col) {
    $align = in_array($col, ['VALOR', 'BASE RETENCIÓN', 'PRIMA VAC.', 'BON. RECREACIÓN']) ? 'right' : 'left';
    if (in_array($col, ['DIAS LIQUIDADO', 'DIAS', 'CANTIDAD', 'COD_BANCO', 'TIPO'])) {
        $align = 'center';
    }
    $html .= "<th style='padding: 4px; text-align: {$align}; font-size: 7px;'>{$col}</th>";
}
$html .= "</tr>";

// Datos
foreach ($datos as $d) {
    $html .= "<tr>";
    foreach ($columnas as $col) {
        $valor = getCeldaValor($d, $col, $columnaExtra);
        $valorFormateado = formatearCelda($valor, $col);
        $align = in_array($col, ['VALOR', 'BASE RETENCIÓN', 'PRIMA VAC.', 'BON. RECREACIÓN']) ? 'right' : 'left';
        if (in_array($col, ['DIAS LIQUIDADO', 'DIAS', 'CANTIDAD', 'COD_BANCO', 'TIPO'])) {
            $align = 'center';
        }
        $html .= "<td style='padding: 3px; font-size: 7px; text-align: {$align};'>{$valorFormateado}</td>";
    }
    $html .= "</tr>";
}

$html .= "</table>";

$documento = "Reporte de " . $nombreConcepto;
$otro = "NÓMINA No. {$id_nomina} - MES: {$mes} - VIGENCIA: {$nomina['vigencia']}";

$firmas = (new CReportes())->getFormFirmas(
    ['nom_tercero' => $nomina['elabora'], 'cargo' => $nomina['cargo']],
    51,
    $nomina['vigencia'] . '-' . $nomina['mes'] . '-01',
    ''
);

// Usar Landscape si hay muchas columnas
$orientacion = $numColumnas > 6 ? "L" : "P";
$Imprimir = new Imprimir($documento, "letter", $orientacion);
$Imprimir->addEncabezado($documento, $otro);
$Imprimir->addContenido($html);
$Imprimir->addFirmas($firmas);
$pdf = isset($_POST['pdf']) ? filter_var($_POST['pdf'], FILTER_VALIDATE_BOOLEAN) : false;
$resul = $Imprimir->render($pdf);

if ($pdf) {
    $Imprimir->getPDF($resul);
    exit();
}
