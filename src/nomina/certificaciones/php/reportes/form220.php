<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../../index.php");
    exit();
}

include_once '../../../../../config/autoloader.php';
require_once '../../../../../vendor/autoload.php';

use Src\Nomina\Certificaciones\Php\Clases\Certificados;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Usuarios\Login\Php\Clases\Usuario;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

// ── Parámetros ────────────────────────────────────────────────
$id_tercero = isset($_POST['id_tercero']) ? intval($_POST['id_tercero']) : 0;
$fecha_ini  = isset($_POST['fecha_ini'])  ? $_POST['fecha_ini']          : '';
$fecha_fin  = isset($_POST['fecha_fin'])  ? $_POST['fecha_fin']          : '';
$formato    = isset($_POST['formato'])    ? strtolower(trim($_POST['formato'])) : 'pdf';

if (!$id_tercero || !$fecha_ini || !$fecha_fin) exit('Parámetros insuficientes.');

// ── Datos ─────────────────────────────────────────────────────
$Cert    = new Certificados();
$empresa = (new Usuario())->getEmpresa();
$empleado = $Cert->getDatosEmpleadoPorTercero($id_tercero);
if (empty($empleado)) exit('No se encontró un empleado asociado al tercero seleccionado.');
$id_empleado = intval($empleado['id_empleado']);

$ids_nomina = $Cert->getNominasPorRango($fecha_ini, $fecha_fin);
if (empty($ids_nomina)) exit('No se encontraron nóminas liquidadas (estado 5) en el rango.');

$resumen = $Cert->getResumenAnual($id_empleado, $ids_nomina);
if (empty($resumen)) exit('El empleado no tiene movimientos en el período seleccionado.');

function copR(float $v): string
{
    return number_format($v, 0, ',', '.');
}

$anio_ini = date('Y', strtotime($fecha_ini));
$anio_fin = date('Y', strtotime($fecha_fin));
$periodo  = $anio_ini === $anio_fin ? $anio_ini : "$anio_ini - $anio_fin";

$id_vigencia_cert = Nomina::getIdVigenciaPorAnio($anio_fin);
$params_liq = [];
if ($id_vigencia_cert > 0) {
    $raw = Nomina::getParamLiqPorVigencia($id_vigencia_cert);
    $params_liq = array_column($raw, 'valor', 'id_concepto');
}
$uvt = floatval($params_liq[6] ?? 0);

$hoy            = date('d/m/Y');
$cedula         = $empleado['no_documento'] ?? '';
$periodoIni     = date('d/m/Y', strtotime($fecha_ini));
$periodoFin     = date('d/m/Y', strtotime($fecha_fin));

// Valores calculados
$v = [];
$v['salarios']     = floatval($resumen['total_laborado'] ?? 0);
$v['varios']       = 0;
$v['honorarios']   = 0;
$v['servicios']    = 0;
$v['comisiones']   = 0;
$v['presociales']  = floatval($resumen['total_prima_serv'] ?? 0)
    + floatval($resumen['total_prima_nav'] ?? 0)
    + floatval($resumen['total_bsp'] ?? 0)
    + floatval($resumen['total_cesantias'] ?? 0)
    + floatval($resumen['total_int_cesantias'] ?? 0)
    + floatval($resumen['total_vacaciones'] ?? 0);
$v['viaticos']     = 0;
$v['represent']    = floatval($resumen['total_g_representa'] ?? 0);
$v['compensa']     = 0;
$v['otros']        = floatval($resumen['total_aux_transporte'] ?? 0)
    + floatval($resumen['total_aux_alim'] ?? 0)
    + floatval($resumen['total_horas_ext'] ?? 0)
    + floatval($resumen['total_incap'] ?? 0);
$v['cesantias']    = 0;
$v['pension']      = 0;
$v['total_ing']    = $v['salarios'] + $v['presociales'] + $v['represent'] + $v['otros'];

$v['salud_emp']    = floatval($resumen['total_salud_emp'] ?? 0);
$v['pension_emp']  = floatval($resumen['total_pension_emp'] ?? 0);
$v['solidaridad']  = floatval($resumen['total_solidaridad'] ?? 0);
$v['covid']        = 0;
$v['vol_pension']  = 0;
$v['afc']          = 0;
$v['retencion']    = floatval($resumen['total_retencion'] ?? 0);
$v['ret_covid']    = 0;

$v['patrimonio']   = $uvt * 4500;
$v['ingr_uvt']     = $uvt * 1400;

// ══════════════════════════════════════════════════════════════
//  GENERACIÓN DEL DOCUMENTO (PhpWord)
// ══════════════════════════════════════════════════════════════
$phpWord = new PhpWord();
$phpWord->setDefaultFontName('Arial');
$phpWord->setDefaultFontSize(7);
$phpWord->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);

// ── Colores DIAN ──────────────────────────────────────────────
$DARK  = '1F3864';
$MED   = '2E75B6';
$LIGHT = 'D6E4F0';
$HIGH  = 'BDD7EE';
$WHITE = 'FFFFFF';

// ── Estilos ───────────────────────────────────────────────────
$fWhite  = ['bold' => true, 'size' => 7, 'color' => 'FFFFFF'];
$fNorm   = ['size' => 7];
$fBold   = ['size' => 7, 'bold' => true];
$fLabel  = ['size' => 6, 'bold' => true, 'color' => '333333'];
$fSmall  = ['size' => 6];
$fNote   = ['size' => 6, 'italic' => true];
$pC      = ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0];
$pR      = ['alignment' => Jc::RIGHT,  'spaceAfter' => 0, 'spaceBefore' => 0];
$pL      = ['spaceAfter' => 0, 'spaceBefore' => 0];

// Helper: crea estilo de celda con borde + color de fondo
function cs($bg = 'FFFFFF', $extra = [])
{
    return array_merge([
        'borderTopSize' => 4,
        'borderTopColor' => '999999',
        'borderBottomSize' => 4,
        'borderBottomColor' => '999999',
        'borderLeftSize' => 4,
        'borderLeftColor' => '999999',
        'borderRightSize' => 4,
        'borderRightColor' => '999999',
        'valign' => 'center',
        'bgColor' => $bg,
    ], $extra);
}

$tblStyle = ['borderSize' => 4, 'borderColor' => '999999', 'cellSpacing' => 0, 'cellMargin' => 30];
$logoPath = __DIR__ . '/../../image1.png';
$W = 10540; // ancho total usable (twips)

$section = $phpWord->addSection([
    'pageSizeW' => 12240,
    'pageSizeH' => 15840,
    'marginTop' => 400,
    'marginBottom' => 400,
    'marginLeft' => 850,
    'marginRight' => 850,
]);

// ═══════════════════════════════════════════════════════════════
//  1. ENCABEZADO PRINCIPAL
// ═══════════════════════════════════════════════════════════════
$t = $section->addTable($tblStyle);
$t->addRow(600);

$c = $t->addCell(2000, cs($WHITE));
if (file_exists($logoPath)) {
    $c->addImage($logoPath, ['width' => 100, 'height' => 30, 'alignment' => Jc::CENTER]);
}

$c = $t->addCell(6540, cs($MED));
$c->addText('Certificado de Ingresos y Retenciones por Rentas de', ['bold' => true, 'size' => 9, 'color' => $WHITE], $pC);
$c->addText("Trabajo y de Pensiones Año gravable {$periodo}", ['bold' => true, 'size' => 9, 'color' => $WHITE], $pC);

$c = $t->addCell(2000, cs($MED));
$c->addText('220', ['bold' => true, 'size' => 26, 'color' => $WHITE], $pC);

// Instrucciones
$t->addRow(200);
$c = $t->addCell(8540, cs($LIGHT, ['gridSpan' => 2]));
$c->addText('Antes de diligenciar este formulario lea cuidadosamente las instrucciones', $fSmall, $pC);
$c = $t->addCell(2000, cs($LIGHT));
$c->addText('4. Número de formulario', $fLabel, $pC);

// ═══════════════════════════════════════════════════════════════
//  2. RETENEDOR
// ═══════════════════════════════════════════════════════════════
$t = $section->addTable($tblStyle);
// Fila encabezados
$t->addRow(220);
$c = $t->addCell(500, cs($DARK, ['vMerge' => 'restart', 'textDirection' => 'btLr']));
$c->addText('Retenedor', $fWhite, $pC);
$c = $t->addCell(2300, cs($LIGHT));
$c->addText('5. Número de Identificación Tributaria (NIT)', $fLabel, $pL);
$c = $t->addCell(500, cs($LIGHT));
$c->addText('6. DV.', $fLabel, $pC);
$c = $t->addCell(1500, cs($LIGHT));
$c->addText('7. Primer apellido', $fLabel, $pL);
$c = $t->addCell(1500, cs($LIGHT));
$c->addText('8. Segundo apellido', $fLabel, $pL);
$c = $t->addCell(1500, cs($LIGHT));
$c->addText('9. Primer nombre', $fLabel, $pL);
$c = $t->addCell(2240, cs($LIGHT));
$c->addText('10. Otros nombres', $fLabel, $pL);

// Datos retenedor
$t->addRow(250);
$c = $t->addCell(500, cs($DARK, ['vMerge' => 'continue']));
$c = $t->addCell(2300, cs($WHITE));
$c->addText($empresa['nit'] ?? '', $fBold, $pC);
$c = $t->addCell(500, cs($WHITE));
$c->addText($empresa['dv'] ?? '', $fBold, $pC);
$c = $t->addCell(1500, cs($WHITE));
$c->addText('', $fNorm, $pL);
$c = $t->addCell(1500, cs($WHITE));
$c->addText('', $fNorm, $pL);
$c = $t->addCell(1500, cs($WHITE));
$c->addText('', $fNorm, $pL);
$c = $t->addCell(2240, cs($WHITE));
$c->addText('', $fNorm, $pL);

// Razón social
$t->addRow(200);
$c = $t->addCell(500, cs($DARK, ['vMerge' => 'continue']));
$c = $t->addCell(10040, cs($LIGHT, ['gridSpan' => 6]));
$c->addText('11. Razón social', $fLabel, $pL);

$t->addRow(250);
$c = $t->addCell(500, cs($DARK, ['vMerge' => 'continue']));
$c = $t->addCell(10040, cs($WHITE, ['gridSpan' => 6]));
$c->addText(mb_strtoupper($empresa['nombre'] ?? ''), $fBold, $pL);

// ═══════════════════════════════════════════════════════════════
//  3. TRABAJADOR
// ═══════════════════════════════════════════════════════════════
$t = $section->addTable($tblStyle);
$t->addRow(220);
$c = $t->addCell(500, cs($DARK, ['vMerge' => 'restart', 'textDirection' => 'btLr']));
$c->addText('Trabajador', $fWhite, $pC);
$c = $t->addCell(1200, cs($LIGHT));
$c->addText('24. Tipo de documento', $fLabel, $pL);
$c = $t->addCell(1800, cs($LIGHT));
$c->addText('25. Número de identificación', $fLabel, $pL);
$c = $t->addCell(7040, cs($LIGHT, ['gridSpan' => 4]));
$c->addText('Apellidos y nombres', $fLabel, $pL);

$t->addRow(250);
$c = $t->addCell(500, cs($DARK, ['vMerge' => 'continue']));
$c = $t->addCell(1200, cs($WHITE));
$c->addText('CC', $fBold, $pC);
$c = $t->addCell(1800, cs($WHITE));
$c->addText($cedula, $fBold, $pC);
$c = $t->addCell(1760, cs($WHITE));
$c->addText(mb_strtoupper($empleado['apellido1'] ?? ''), $fBold, $pL);
$c = $t->addCell(1760, cs($WHITE));
$c->addText(mb_strtoupper($empleado['apellido2'] ?? ''), $fBold, $pL);
$c = $t->addCell(1760, cs($WHITE));
$c->addText(mb_strtoupper($empleado['nombre1'] ?? ''), $fBold, $pL);
$c = $t->addCell(1760, cs($WHITE));
$c->addText(mb_strtoupper($empleado['nombre2'] ?? ''), $fBold, $pL);

// ═══════════════════════════════════════════════════════════════
//  4. PERÍODO
// ═══════════════════════════════════════════════════════════════
$t = $section->addTable($tblStyle);
$t->addRow(220);
$c = $t->addCell(2800, cs($LIGHT));
$c->addText('Periodo de la Certificación', $fLabel, $pC);
$c = $t->addCell(1700, cs($LIGHT));
$c->addText('32. Fecha de expedición', $fLabel, $pC);
$c = $t->addCell(2800, cs($LIGHT));
$c->addText('33. Lugar donde se practicó la retención', $fLabel, $pC);
$c = $t->addCell(1500, cs($LIGHT));
$c->addText('34. Cód. Dpto.', $fLabel, $pC);
$c = $t->addCell(1740, cs($LIGHT));
$c->addText('35. Cód. Ciudad/ Municipio', $fLabel, $pC);

$t->addRow(250);
$c = $t->addCell(2800, cs($WHITE));
$c->addText("30. DE:  {$periodoIni}   31. A:  {$periodoFin}", $fBold, $pL);
$c = $t->addCell(1700, cs($WHITE));
$c->addText($hoy, $fBold, $pC);
$c = $t->addCell(2800, cs($WHITE));
$c->addText(mb_strtoupper($empresa['ciudad'] ?? ''), $fBold, $pC);
$c = $t->addCell(1500, cs($WHITE));
$c->addText($empresa['cod_dept'] ?? '', $fBold, $pC);
$c = $t->addCell(1740, cs($WHITE));
$c->addText($empresa['cod_ciudad'] ?? '', $fBold, $pC);

// ═══════════════════════════════════════════════════════════════
//  5. INGRESOS (casillas 36 – 48)
// ═══════════════════════════════════════════════════════════════
$wConc = 7200;
$wCas = 500;
$wPes = 340;
$wVal = 2500;

$t = $section->addTable($tblStyle);
$t->addRow(230);
$c = $t->addCell($wConc, cs($MED));
$c->addText('Concepto de los Ingresos', $fWhite, $pC);
$c = $t->addCell($wCas, cs($MED));
$c->addText('', $fWhite, $pC);
$c = $t->addCell($wPes + $wVal, cs($MED, ['gridSpan' => 2]));
$c->addText('Valor', $fWhite, $pC);

$ingresos = [
    ['Pagos por salarios o emolumentos eclesiásticos', 36, $v['salarios']],
    ['Pagos realizados con bonos electrónicos o de papel de servicio, cheques, tarjetas, vales, etc.', 37, $v['varios']],
    ['Pagos por honorarios', 38, $v['honorarios']],
    ['Pagos por servicios', 39, $v['servicios']],
    ['Pagos por comisiones', 40, $v['comisiones']],
    ['Pagos por prestaciones sociales', 41, $v['presociales']],
    ['Pagos por viáticos', 42, $v['viaticos']],
    ['Pagos por gastos de representación', 43, $v['represent']],
    ['Pagos por compensaciones por el trabajo asociado cooperativo', 44, $v['compensa']],
    ['Otros pagos', 45, $v['otros']],
    ['Cesantías e intereses de cesantías efectivamente pagadas en el periodo', 46, $v['cesantias']],
    ['Pensiones de jubilación, vejez o invalidez', 47, $v['pension']],
    ['Total de Ingresos brutos (Sume 36 a 47)', 48, $v['total_ing'], true],
];

foreach ($ingresos as $row) {
    $isTotal = $row[3] ?? false;
    $bg = $isTotal ? $HIGH : $WHITE;
    $ft = $isTotal ? $fBold : $fNorm;
    $t->addRow(220);
    $c = $t->addCell($wConc, cs($bg));
    $c->addText($row[0], $ft, $pL);
    $c = $t->addCell($wCas, cs($bg));
    $c->addText((string)$row[1], $fLabel, $pC);
    $c = $t->addCell($wPes, cs($bg));
    $c->addText('$', $fNorm, $pL);
    $c = $t->addCell($wVal, cs($bg));
    $c->addText(copR($row[2]), $ft, $pR);
}

// ═══════════════════════════════════════════════════════════════
//  6. APORTES (casillas 49 – 56)
// ═══════════════════════════════════════════════════════════════
$t->addRow(230);
$c = $t->addCell($wConc, cs($MED));
$c->addText('Concepto de los aportes', $fWhite, $pC);
$c = $t->addCell($wCas, cs($MED));
$c->addText('', $fWhite, $pC);
$c = $t->addCell($wPes + $wVal, cs($MED, ['gridSpan' => 2]));
$c->addText('Valor', $fWhite, $pC);

$aportes = [
    ['Aportes obligatorios por salud a cargo del trabajador', 49, $v['salud_emp']],
    ['Aportes obligatorios a fondos de pensiones y solidaridad pensional a cargo del trabajador', 50, $v['pension_emp']],
    ['Cotizaciones voluntarias al régimen de ahorro individual con solidaridad - RAIS', 51, $v['solidaridad']],
    ['Aportes voluntarios al impuesto solidario por COVID 19', 52, $v['covid']],
    ['Aportes voluntarios a fondos de pensiones', 53, $v['vol_pension']],
    ['Aportes a cuentas AFC', 54, $v['afc']],
    ['Valor de la retención en la fuente por ingresos laborales y de pensiones', 55, $v['retencion'], true],
    ['Retenciones por aportes obligatorios al impuesto solidario por COVID 19', 56, $v['ret_covid']],
];

foreach ($aportes as $row) {
    $isTotal = $row[3] ?? false;
    $bg = $isTotal ? $HIGH : $WHITE;
    $ft = $isTotal ? $fBold : $fNorm;
    $t->addRow(220);
    $c = $t->addCell($wConc, cs($bg));
    $c->addText($row[0], $ft, $pL);
    $c = $t->addCell($wCas, cs($bg));
    $c->addText((string)$row[1], $fLabel, $pC);
    $c = $t->addCell($wPes, cs($bg));
    $c->addText('$', $fNorm, $pL);
    $c = $t->addCell($wVal, cs($bg));
    $c->addText(copR($row[2]), $ft, $pR);
}

// Nombre del pagador
$t->addRow(220);
$c = $t->addCell($W, cs($LIGHT, ['gridSpan' => 4]));
$c->addText('Nombre del pagador o agente retenedor', $fLabel, $pL);

// ═══════════════════════════════════════════════════════════════
//  7. DATOS A CARGO DEL TRABAJADOR – OTROS INGRESOS (57 – 71)
// ═══════════════════════════════════════════════════════════════
$wOC = 4200;
$wON = 500;
$wOR = 2120;
$wON2 = 500;
$wOV = 3220;

$t2 = $section->addTable($tblStyle);
$t2->addRow(230);
$c = $t2->addCell($W, cs($DARK, ['gridSpan' => 5]));
$c->addText('Datos a cargo del trabajador o pensionado', $fWhite, $pC);

$t2->addRow(230);
$c = $t2->addCell($wOC, cs($MED));
$c->addText('Concepto de otros ingresos', $fWhite, $pC);
$c = $t2->addCell($wON, cs($MED));
$c->addText('', $fWhite, $pC);
$c = $t2->addCell($wOR, cs($MED));
$c->addText('Valor recibido', $fWhite, $pC);
$c = $t2->addCell($wON2, cs($MED));
$c->addText('', $fWhite, $pC);
$c = $t2->addCell($wOV, cs($MED));
$c->addText('Valor retenido', $fWhite, $pC);

$otros = [
    ['Arrendamientos', 57, 64],
    ['Honorarios, comisiones y servicios', 58, 65],
    ['Intereses y rendimientos financieros', 59, 66],
    ['Enajenación de activos fijos', 60, 67],
    ['Loterías, rifas, apuestas y similares', 61, 68],
    ['Otros', 62, 69],
    ['Totales: (Valor recibido: Sume 57 a 62), (Valor retenido: Sume 64 a 69)', 63, 70, true],
];

foreach ($otros as $row) {
    $isT = $row[3] ?? false;
    $bg = $isT ? $HIGH : $WHITE;
    $ft = $isT ? $fBold : $fNorm;
    $t2->addRow(220);
    $c = $t2->addCell($wOC, cs($bg));
    $c->addText($row[0], $ft, $pL);
    $c = $t2->addCell($wON, cs($bg));
    $c->addText((string)$row[1], $fLabel, $pC);
    $c = $t2->addCell($wOR, cs($bg));
    $c->addText('', $fNorm, $pR);
    $c = $t2->addCell($wON2, cs($bg));
    $c->addText((string)$row[2], $fLabel, $pC);
    $c = $t2->addCell($wOV, cs($bg));
    $c->addText('', $fNorm, $pR);
}

// Total retenciones (71)
$t2->addRow(260);
$c = $t2->addCell($wOC, cs($HIGH));
$c->addText("Total retenciones año gravable {$periodo} (Sume 55 + 56 + 70)", $fBold, $pL);
$c = $t2->addCell($wON, cs($HIGH));
$c->addText('71', $fLabel, $pC);
$c = $t2->addCell($wOR + $wON2 + $wOV, cs($HIGH, ['gridSpan' => 3]));
$c->addText('$ ' . copR($v['retencion']), $fBold, $pR);

// ═══════════════════════════════════════════════════════════════
//  8. BIENES POSEÍDOS (72 – 74)
// ═══════════════════════════════════════════════════════════════
$t3 = $section->addTable($tblStyle);
$t3->addRow(230);
$c = $t3->addCell(700, cs($MED));
$c->addText('Ítem', $fWhite, $pC);
$c = $t3->addCell(6840, cs($MED));
$c->addText('72. Identificación de los bienes poseídos', $fWhite, $pL);
$c = $t3->addCell(3000, cs($MED));
$c->addText('73. Valor patrimonial', $fWhite, $pC);

for ($i = 1; $i <= 5; $i++) {
    $t3->addRow(200);
    $c = $t3->addCell(700, cs($WHITE));
    $c->addText((string)$i, $fLabel, $pC);
    $c = $t3->addCell(6840, cs($WHITE));
    $c->addText('', $fNorm, $pL);
    $c = $t3->addCell(3000, cs($WHITE));
    $c->addText('', $fNorm, $pR);
}

// Deudas (74)
$t3->addRow(230);
$c = $t3->addCell(7540, cs($HIGH, ['gridSpan' => 2]));
$c->addText("Deudas vigentes a 31 de Diciembre de {$periodo}", $fBold, $pL);
$c = $t3->addCell(3000, cs($HIGH));
$c->addText('74', $fLabel, $pC);

// ═══════════════════════════════════════════════════════════════
//  9. DEPENDIENTES ECONÓMICOS (75 – 78)
// ═══════════════════════════════════════════════════════════════
$t4 = $section->addTable($tblStyle);
$t4->addRow(230);
$c = $t4->addCell($W, cs($DARK, ['gridSpan' => 4]));
$c->addText('Identificación del dependiente económico de acuerdo al parágrafo 2 del artículo 387 del Estatuto Tributario', $fWhite, $pC);

$t4->addRow(220);
$c = $t4->addCell(1500, cs($LIGHT));
$c->addText('75. Tipo documento', $fLabel, $pC);
$c = $t4->addCell(2540, cs($LIGHT));
$c->addText('76. No. Documento', $fLabel, $pC);
$c = $t4->addCell(4000, cs($LIGHT));
$c->addText('77. Apellidos y Nombres', $fLabel, $pC);
$c = $t4->addCell(2500, cs($LIGHT));
$c->addText('78. Parentesco', $fLabel, $pC);

$t4->addRow(200);
$c = $t4->addCell(1500, cs($WHITE));
$c->addText('', $fNorm, $pL);
$c = $t4->addCell(2540, cs($WHITE));
$c->addText('', $fNorm, $pL);
$c = $t4->addCell(4000, cs($WHITE));
$c->addText('', $fNorm, $pL);
$c = $t4->addCell(2500, cs($WHITE));
$c->addText('', $fNorm, $pL);

// ═══════════════════════════════════════════════════════════════
//  10. CERTIFICACIÓN DEL TRABAJADOR + FIRMA
// ═══════════════════════════════════════════════════════════════
$t5 = $section->addTable($tblStyle);
$t5->addRow();
$c = $t5->addCell(6500, cs($WHITE));
$c->addText("Certifico que durante el año gravable de {$periodo}:", $fBold, $pL);
$c->addText("1. Mi patrimonio bruto era igual o inferior a 4.500 UVT (\$ " . copR($v['patrimonio']) . ").", $fSmall, $pL);
$c->addText("2. Mis ingresos brutos fueron inferiores a 1.400 UVT (\$ " . copR($v['ingr_uvt']) . ").", $fSmall, $pL);
$c->addText("3. No fui responsable del impuesto sobre las ventas.", $fSmall, $pL);
$c->addText("4. Mis consumos mediante tarjeta de crédito no excedieron la suma de 1.400 UVT (\$ " . copR($v['ingr_uvt']) . ").", $fSmall, $pL);
$c->addText("5. Que el total de mis compras y consumos no superaron la suma de 1.400 UVT (\$ " . copR($v['ingr_uvt']) . ").", $fSmall, $pL);
$c->addText("6. Que el valor total de mis consignaciones bancarias, depósitos o inversiones financieras no excedieron los 1.400 UVT (\$ " . copR($v['ingr_uvt']) . ").", $fSmall, $pL);
$c->addText("Por lo tanto, manifiesto que no estoy obligado a presentar declaración de renta y complementario por el año gravable {$periodo}.", $fBold, $pL);

$c = $t5->addCell(4040, cs($WHITE, ['valign' => 'bottom']));
$c->addText('', $fNorm, $pC);
$c->addText('', $fNorm, $pC);
$c->addText('', $fNorm, $pC);
$c->addText('Firma del Trabajador o Pensionado', $fLabel, $pC);

// ═══════════════════════════════════════════════════════════════
//  11. NOTA FINAL
// ═══════════════════════════════════════════════════════════════
$section->addTextBreak(1, ['size' => 2], ['spaceAfter' => 0, 'spaceBefore' => 40]);
$section->addText(
    'NOTA: este certificado sustituye para todos los efectos legales la declaración de Renta y Complementario para el trabajador o pensionado que lo firme.',
    ['size' => 6, 'bold' => true],
    ['alignment' => Jc::CENTER, 'spaceAfter' => 20]
);
$section->addText(
    'Para aquellos trabajadores independientes contribuyentes del impuesto unificado deberán presentar la declaración anual consolidada del Régimen Simple de Tributación (SIMPLE).',
    $fNote,
    ['alignment' => Jc::CENTER]
);

// ═══════════════════════════════════════════════════════════════
//  SALIDA: WORD o PDF
// ═══════════════════════════════════════════════════════════════
$nombreArchivo = "Form220_{$cedula}_{$periodo}";

if ($formato === 'word') {
    $tmpFile = tempnam(sys_get_temp_dir(), 'f220_') . '.docx';
    $writer = IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($tmpFile);

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header("Content-Disposition: attachment; filename=\"{$nombreArchivo}.docx\"");
    header('Content-Length: ' . filesize($tmpFile));
    readfile($tmpFile);
    unlink($tmpFile);
    exit();
}

// ── PDF via HTML → dompdf ─────────────────────────────────────
$tmpHtml = tempnam(sys_get_temp_dir(), 'f220h_') . '.html';
$htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
$htmlWriter->save($tmpHtml);
$htmlContent = file_get_contents($tmpHtml);
unlink($tmpHtml);

$cssBoost = '<style>
@page { size: letter portrait; margin: 8mm 12mm; }
body { font-family: Arial, sans-serif; font-size: 7pt; }
table { border-collapse: collapse; width: 100%; page-break-inside: auto; }
td, th { padding: 1px 3px; vertical-align: middle; }
</style>';
$htmlContent = str_replace('</head>', $cssBoost . '</head>', $htmlContent);

$dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
$dompdf->setPaper('letter', 'portrait');
$dompdf->loadHtml($htmlContent);
$dompdf->render();
$dompdf->stream("{$nombreArchivo}.pdf", ['Attachment' => true]);
exit();
