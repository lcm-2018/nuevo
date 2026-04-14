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

// ── Parámetros ────────────────────────────────────────────────
$id_tercero = isset($_POST['id_tercero']) ? intval($_POST['id_tercero']) : 0;
$fecha_ini  = isset($_POST['fecha_ini'])  ? $_POST['fecha_ini']          : '';
$fecha_fin  = isset($_POST['fecha_fin'])  ? $_POST['fecha_fin']          : '';
$formato    = isset($_POST['formato'])    ? strtolower(trim($_POST['formato'])) : 'pdf';
// Mapeo retrocompatible: 'word' legacy => 'excel'
if ($formato === 'word') $formato = 'excel';

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
    + floatval($resumen['total_incap'] ?? 0)
    + floatval($resumen['otros_dev'] ?? 0);
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


// ═══════════════════════════════════════════════════════════════
//  SALIDA: EXCEL o HTML (imprimir)
// ═══════════════════════════════════════════════════════════════
$nombreArchivo = "Form220_{$cedula}_{$periodo}";

// ── Funciones auxiliares de celdas HTML ───────────────────────
function thDian(string $txt, string $bg = '#2E75B6', string $color = '#fff', int $colspan = 1, string $align = 'center'): string
{
    $cs = $colspan > 1 ? " colspan='{$colspan}'" : '';
    return "<th{$cs} style='background:{$bg};color:{$color};font-size:7pt;font-weight:bold;text-align:{$align};padding:2px 4px;border:1px solid #999;'>{$txt}</th>";
}
function tdDian(string $txt, string $bg = '#ffffff', string $align = 'left', bool $bold = false, int $colspan = 1): string
{
    $b  = $bold ? 'font-weight:bold;' : '';
    $cs = $colspan > 1 ? " colspan='{$colspan}'" : '';
    return "<td{$cs} style='background:{$bg};font-size:7pt;{$b}text-align:{$align};padding:2px 4px;border:1px solid #999;'>{$txt}</td>";
}

// ── Construir tabla HTML del Form 220 ─────────────────────────
// $esExcel=true omite base64 y writing-mode que Excel no soporta
$esExcel = ($formato === 'excel');
ob_start();
?>
<table style='border-collapse:collapse;width:100%;font-family:Arial,sans-serif;'>
  <!-- ENCABEZADO -->
  <tr>
    <td style='width:120px;border:1px solid #999;padding:4px;vertical-align:middle;text-align:center;'>
<?php
$logoPath = __DIR__ . '/../../image1.png';
if ($esExcel) {
    // Excel no renderiza base64: mostramos solo texto
    echo "<span style='font-size:8pt;font-weight:bold;'>DIAN</span>";
} elseif (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    echo "<img src='data:image/png;base64,{$logoData}' style='max-height:30px;'>";
}
?>
    </td>
    <td colspan='2' style='background:#2E75B6;color:#fff;font-size:10pt;font-weight:bold;text-align:center;border:1px solid #999;padding:4px;'>
      Certificado de Ingresos y Retenciones por Rentas de<br>
      Trabajo y de Pensiones &ndash; A&ntilde;o gravable <?= htmlspecialchars($periodo) ?>
    </td>
    <td style='background:#2E75B6;color:#fff;font-size:20pt;font-weight:bold;text-align:center;border:1px solid #999;padding:4px;width:60px;'>220</td>
  </tr>
  <tr>
    <td colspan='3' style='background:#D6E4F0;font-size:6pt;text-align:center;border:1px solid #999;padding:2px;'>
      Antes de diligenciar este formulario lea cuidadosamente las instrucciones
    </td>
    <td style='background:#D6E4F0;font-size:6pt;text-align:center;border:1px solid #999;padding:2px;'>4. N&uacute;mero de formulario</td>
  </tr>
</table>
<table style='border-collapse:collapse;width:100%;font-family:Arial,sans-serif;margin-top:2px;'>
  <!-- RETENEDOR -->
  <tr>
    <td rowspan='4' style='background:#1F3864;color:#fff;font-size:7pt;font-weight:bold;<?= $esExcel ? "mso-rotate:270;width:30px" : "writing-mode:vertical-rl;transform:rotate(180deg);width:18px" ?>;text-align:center;border:1px solid #999;padding:2px;'>Retenedor</td>
    <?= thDian('5. N&uacute;mero de Identificaci&oacute;n Tributaria (NIT)', '#D6E4F0', '#333', 1, 'left') ?>
    <?= thDian('6. DV.', '#D6E4F0', '#333', 1, 'left') ?>
    <?= thDian('7. Primer apellido', '#D6E4F0', '#333', 1, 'left') ?>
    <?= thDian('8. Segundo apellido', '#D6E4F0', '#333', 1, 'left') ?>
    <?= thDian('9. Primer nombre', '#D6E4F0', '#333', 1, 'left') ?>
    <?= thDian('10. Otros nombres', '#D6E4F0', '#333', 1, 'left') ?>
  </tr>
  <tr>
    <?= tdDian($empresa['nit'] ?? '', '#fff', 'center', true) ?>
    <?= tdDian($empresa['dv'] ?? '', '#fff', 'center', true) ?>
    <?= tdDian('', '#fff') ?>
    <?= tdDian('', '#fff') ?>
    <?= tdDian('', '#fff') ?>
    <?= tdDian('', '#fff') ?>
  </tr>
  <tr>
    <?= thDian('11. Raz&oacute;n social', '#D6E4F0', '#333', 6, 'left') ?>
  </tr>
  <tr>
    <?= tdDian(mb_strtoupper($empresa['nombre'] ?? ''), '#fff', 'left', true, 6) ?>
  </tr>
</table>
<table style='border-collapse:collapse;width:100%;font-family:Arial,sans-serif;margin-top:2px;'>
  <!-- TRABAJADOR -->
  <tr>
    <td rowspan='2' style='background:#1F3864;color:#fff;font-size:7pt;font-weight:bold;<?= $esExcel ? "mso-rotate:270;width:30px" : "writing-mode:vertical-rl;transform:rotate(180deg);width:18px" ?>;text-align:center;border:1px solid #999;padding:2px;'>Trabajador</td>
    <?= thDian('24. Tipo de documento', '#D6E4F0', '#333', 1, 'left') ?>
    <?= thDian('25. N&uacute;mero de identificaci&oacute;n', '#D6E4F0', '#333', 1, 'left') ?>
    <?= thDian('Apellidos y nombres', '#D6E4F0', '#333', 4, 'left') ?>
  </tr>
  <tr>
    <?= tdDian('CC', '#fff', 'center', true) ?>
    <?= tdDian($cedula, '#fff', 'center', true) ?>
    <?= tdDian(mb_strtoupper($empleado['apellido1'] ?? ''), '#fff', 'left', true) ?>
    <?= tdDian(mb_strtoupper($empleado['apellido2'] ?? ''), '#fff', 'left', true) ?>
    <?= tdDian(mb_strtoupper($empleado['nombre1'] ?? ''), '#fff', 'left', true) ?>
    <?= tdDian(mb_strtoupper($empleado['nombre2'] ?? ''), '#fff', 'left', true) ?>
  </tr>
</table>
<table style='border-collapse:collapse;width:100%;font-family:Arial,sans-serif;margin-top:2px;'>
  <!-- PER&Iacute;ODO -->
  <tr>
    <?= thDian('Periodo de la Certificaci&oacute;n', '#D6E4F0', '#333') ?>
    <?= thDian('32. Fecha de expedi&oacute;n', '#D6E4F0', '#333') ?>
    <?= thDian('33. Lugar donde se practic&oacute; la retenci&oacute;n', '#D6E4F0', '#333') ?>
    <?= thDian('34. C&oacute;d. Dpto.', '#D6E4F0', '#333') ?>
    <?= thDian('35. C&oacute;d. Ciudad/Municipio', '#D6E4F0', '#333') ?>
  </tr>
  <tr>
    <?= tdDian("30. DE: {$periodoIni}   31. A: {$periodoFin}", '#fff', 'left', true) ?>
    <?= tdDian($hoy, '#fff', 'center', true) ?>
    <?= tdDian(mb_strtoupper($empresa['ciudad'] ?? ''), '#fff', 'center', true) ?>
    <?= tdDian($empresa['cod_dept'] ?? '', '#fff', 'center', true) ?>
    <?= tdDian($empresa['cod_ciudad'] ?? '', '#fff', 'center', true) ?>
  </tr>
</table>
<table style='border-collapse:collapse;width:100%;font-family:Arial,sans-serif;margin-top:2px;'>
  <!-- INGRESOS -->
  <tr>
    <?= thDian('Concepto de los Ingresos', '#2E75B6') ?>
    <?= thDian('', '#2E75B6') ?>
    <?= thDian('Valor', '#2E75B6', '#fff', 2) ?>
  </tr>
<?php
$ingresos = [
    ['Pagos por salarios o emolumentos eclesiásticos', 36, $v['salarios']],
    ['Pagos realizados con bonos electrónicos o de papel, cheques, tarjetas, vales, etc.', 37, $v['varios']],
    ['Pagos por honorarios', 38, $v['honorarios']],
    ['Pagos por servicios', 39, $v['servicios']],
    ['Pagos por comisiones', 40, $v['comisiones']],
    ['Pagos por prestaciones sociales', 41, $v['presociales']],
    ['Pagos por viáticos', 42, $v['viaticos']],
    ['Pagos por gastos de representación', 43, $v['represent']],
    ['Pagos por compensaciones por trabajo asociado cooperativo', 44, $v['compensa']],
    ['Otros pagos', 45, $v['otros']],
    ['Cesantías e intereses de cesantías efectivamente pagadas', 46, $v['cesantias']],
    ['Pensiones de jubilación, vejez o invalidez', 47, $v['pension']],
    ['Total de Ingresos brutos (Sume 36 a 47)', 48, $v['total_ing'], true],
];
foreach ($ingresos as $row) {
    $isT = $row[3] ?? false;
    $bg  = $isT ? '#BDD7EE' : '#ffffff';
    echo '<tr>';
    echo tdDian($row[0], $bg, 'left', $isT);
    echo tdDian((string)$row[1], $bg, 'center', false);
    echo "<td style='background:{$bg};font-size:7pt;border:1px solid #999;padding:2px 4px;'>\$</td>";
    echo tdDian(copR($row[2]), $bg, 'right', $isT);
    echo '</tr>';
}
?>
  <!-- APORTES -->
  <tr>
    <?= thDian('Concepto de los aportes', '#2E75B6') ?>
    <?= thDian('', '#2E75B6') ?>
    <?= thDian('Valor', '#2E75B6', '#fff', 2) ?>
  </tr>
<?php
$aportes = [
    ['Aportes obligatorios por salud a cargo del trabajador', 49, $v['salud_emp']],
    ['Aportes obligatorios a fondos de pensiones y solidaridad a cargo del trabajador', 50, $v['pension_emp']],
    ['Cotizaciones voluntarias al régimen de ahorro individual - RAIS', 51, $v['solidaridad']],
    ['Aportes voluntarios al impuesto solidario por COVID 19', 52, $v['covid']],
    ['Aportes voluntarios a fondos de pensiones', 53, $v['vol_pension']],
    ['Aportes a cuentas AFC', 54, $v['afc']],
    ['Valor de la retención en la fuente por ingresos laborales y de pensiones', 55, $v['retencion'], true],
    ['Retenciones por aportes obligatorios al impuesto solidario por COVID 19', 56, $v['ret_covid']],
];
foreach ($aportes as $row) {
    $isT = $row[3] ?? false;
    $bg  = $isT ? '#BDD7EE' : '#ffffff';
    echo '<tr>';
    echo tdDian($row[0], $bg, 'left', $isT);
    echo tdDian((string)$row[1], $bg, 'center', false);
    echo "<td style='background:{$bg};font-size:7pt;border:1px solid #999;padding:2px 4px;'>\$</td>";
    echo tdDian(copR($row[2]), $bg, 'right', $isT);
    echo '</tr>';
}
?>
  <tr>
    <td colspan='4' style='background:#D6E4F0;font-size:6pt;font-weight:bold;border:1px solid #999;padding:2px 4px;'>Nombre del pagador o agente retenedor</td>
  </tr>
</table>

<table style='border-collapse:collapse;width:100%;font-family:Arial,sans-serif;margin-top:2px;'>
  <!-- DATOS CARGO TRABAJADOR -->
  <tr><td colspan='5' style='background:#1F3864;color:#fff;font-size:7pt;font-weight:bold;text-align:center;border:1px solid #999;padding:2px;'>Datos a cargo del trabajador o pensionado</td></tr>
  <tr>
    <?= thDian('Concepto de otros ingresos', '#2E75B6') ?>
    <?= thDian('', '#2E75B6') ?>
    <?= thDian('Valor recibido', '#2E75B6') ?>
    <?= thDian('', '#2E75B6') ?>
    <?= thDian('Valor retenido', '#2E75B6') ?>
  </tr>
<?php
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
    $bg  = $isT ? '#BDD7EE' : '#ffffff';
    echo '<tr>';
    echo tdDian($row[0], $bg, 'left', $isT);
    echo tdDian((string)$row[1], $bg, 'center');
    echo tdDian('', $bg, 'right');
    echo tdDian((string)$row[2], $bg, 'center');
    echo tdDian('', $bg, 'right');
    echo '</tr>';
}
?>
  <tr>
    <td colspan='2' style='background:#BDD7EE;font-size:7pt;font-weight:bold;border:1px solid #999;padding:2px 4px;'>
      Total retenciones a&ntilde;o gravable <?= htmlspecialchars($periodo) ?> (Sume 55 + 56 + 70)
    </td>
    <td style='background:#BDD7EE;font-size:7pt;font-weight:bold;border:1px solid #999;padding:2px 4px;text-align:center;'>71</td>
    <td colspan='2' style='background:#BDD7EE;font-size:7pt;font-weight:bold;border:1px solid #999;padding:2px 4px;text-align:right;'>$ <?= copR($v['retencion']) ?></td>
  </tr>
</table>

<table style='border-collapse:collapse;width:100%;font-family:Arial,sans-serif;margin-top:2px;'>
  <!-- BIENES POSE&Iacute;DOS -->
  <tr>
    <?= thDian('&Iacute;tem', '#2E75B6', '#fff') ?>
    <?= thDian('72. Identificaci&oacute;n de los bienes pose&iacute;dos', '#2E75B6', '#fff') ?>
    <?= thDian('73. Valor patrimonial', '#2E75B6', '#fff') ?>
  </tr>
<?php for ($i = 1; $i <= 5; $i++): ?>
  <tr>
    <?= tdDian((string)$i, '#fff', 'center', false) ?>
    <?= tdDian('', '#fff') ?>
    <?= tdDian('', '#fff', 'right') ?>
  </tr>
<?php endfor; ?>
  <tr>
    <td colspan='2' style='background:#BDD7EE;font-size:7pt;font-weight:bold;border:1px solid #999;padding:2px 4px;'>
      Deudas vigentes a 31 de Diciembre de <?= htmlspecialchars($periodo) ?>
    </td>
    <td style='background:#BDD7EE;font-size:7pt;font-weight:bold;border:1px solid #999;padding:2px 4px;text-align:center;'>74</td>
  </tr>
</table>

<table style='border-collapse:collapse;width:100%;font-family:Arial,sans-serif;margin-top:2px;'>
  <!-- DEPENDIENTES -->
  <tr><td colspan='4' style='background:#1F3864;color:#fff;font-size:7pt;font-weight:bold;text-align:center;border:1px solid #999;padding:2px;'>
    Identificaci&oacute;n del dependiente econ&oacute;mico de acuerdo al par&aacute;grafo 2 del art&iacute;culo 387 del Estatuto Tributario
  </td></tr>
  <tr>
    <?= thDian('75. Tipo documento', '#D6E4F0', '#333') ?>
    <?= thDian('76. No. Documento', '#D6E4F0', '#333') ?>
    <?= thDian('77. Apellidos y Nombres', '#D6E4F0', '#333') ?>
    <?= thDian('78. Parentesco', '#D6E4F0', '#333') ?>
  </tr>
  <tr>
    <?= tdDian('', '#fff') ?>
    <?= tdDian('', '#fff') ?>
    <?= tdDian('', '#fff') ?>
    <?= tdDian('', '#fff') ?>
  </tr>
</table>

<table style='border-collapse:collapse;width:100%;font-family:Arial,sans-serif;margin-top:2px;'>
  <!-- CERTIFICACI&Oacute;N -->
  <tr>
    <td style='border:1px solid #999;padding:4px;font-size:7pt;width:60%;vertical-align:top;'>
      <strong>Certifico que durante el a&ntilde;o gravable de <?= htmlspecialchars($periodo) ?>:</strong><br>
      1. Mi patrimonio bruto era igual o inferior a 4.500 UVT ($ <?= copR($v['patrimonio']) ?>).<br>
      2. Mis ingresos brutos fueron inferiores a 1.400 UVT ($ <?= copR($v['ingr_uvt']) ?>).<br>
      3. No fui responsable del impuesto sobre las ventas.<br>
      4. Mis consumos mediante tarjeta de crédito no excedieron 1.400 UVT ($ <?= copR($v['ingr_uvt']) ?>).<br>
      5. El total de mis compras y consumos no superó 1.400 UVT ($ <?= copR($v['ingr_uvt']) ?>).<br>
      6. El valor total de mis consignaciones bancarias no excedió los 1.400 UVT ($ <?= copR($v['ingr_uvt']) ?>).<br>
      <strong>Por lo tanto, manifiesto que no estoy obligado a presentar declaración de renta y complementario por el a&ntilde;o gravable <?= htmlspecialchars($periodo) ?>.</strong>
    </td>
    <td style='border:1px solid #999;padding:4px;font-size:7pt;text-align:center;vertical-align:bottom;width:40%;'>
      <br><br><br>
      Firma del Trabajador o Pensionado
    </td>
  </tr>
</table>

<p style='font-size:6pt;font-weight:bold;text-align:center;margin-top:6px;'>
  NOTA: este certificado sustituye para todos los efectos legales la declaración de Renta y Complementario para el trabajador o pensionado que lo firme.
</p>
<p style='font-size:6pt;font-style:italic;text-align:center;'>
  Para aquellos trabajadores independientes contribuyentes del impuesto unificado deber&aacute;n presentar la declaración anual consolidada del Régimen Simple de Tributación (SIMPLE).
</p>
<?php
$htmlBody = ob_get_clean();

// ── SALIDA según formato ─────────────────────────────────────
if ($formato === 'excel') {
    // Descarga como Excel (HTML con headers MSO para colores correctos)
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"{$nombreArchivo}.xls\"");
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\""
       . " xmlns:x=\"urn:schemas-microsoft-com:office:excel\""
       . " xmlns=\"http://www.w3.org/TR/REC-html40\">";
    echo "<head>"
       . "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">"
       . "<meta name=\"ProgId\" content=\"Excel.Sheet\">"
       . "<meta name=\"Generator\" content=\"Microsoft Excel 11\">"
       // Estilos MSO que fuerzan colores de fondo y fuentes en Excel
       . "<style>"
       . "  table { border-collapse: collapse; }"
       . "  td, th {"
       . "    font-family: Arial, sans-serif;"
       . "    font-size: 7pt;"
       . "    mso-number-format: '\\@';" // tratar celdas como texto
       . "  }"
       . "  /* Forzar que background-color se aplique en Excel */"
       . "  .xl-bg { mso-pattern: auto; }"
       . "</style>"
       . "</head>";
    echo "<body>{$htmlBody}</body></html>";
    exit();
}

// ── Formato HTML: vista carta + auto-print ─────────────────
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Formulario 220 &ndash; <?= htmlspecialchars($periodo) ?></title>
  <style>
    /* ── Reset ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── Página (fondo gris, hoja blanca centrada) ── */
    html, body {
      background: #6c757d;
      font-family: Arial, sans-serif;
      font-size: 7pt;
    }
    body {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 18px 0 30px;
      min-height: 100vh;
    }

    /* ── Hoja tamaño carta ── */
    #hoja {
      background: #ffffff;
      width: 816px;          /* 8.5 in × 96 dpi */
      min-height: 1056px;    /* 11 in × 96 dpi  */
      padding: 18px 22px 24px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.35);
    }

    /* ── Tablas ── */
    table { border-collapse: collapse; width: 100%; }
    td, th { padding: 2px 4px; }

    /* ══ IMPRESIÓN: hoja carta sin márgenes del browser + colores exactos ══ */
    @media print {
      html, body {
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
      }
      #hoja {
        width: 100% !important;
        min-height: unset !important;
        box-shadow: none !important;
        padding: 6mm 8mm !important;
      }
      .no-print { display: none !important; }

      /* CRÍTICO: forzar colores e imágenes de fondo en impresión */
      * {
        -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
        color-adjust: exact !important;
      }
    }
    @page { size: letter portrait; margin: 8mm 10mm; }
  </style>
</head>
<body>

<!-- Botón imprimir (solo pantalla) -->
<div class="no-print" style="margin-bottom:10px;">
  <button onclick="window.print()"
          style="background:#1a6eb5;color:#fff;border:none;padding:7px 22px;
                 border-radius:5px;font-size:9pt;cursor:pointer;font-family:Arial,sans-serif;">
    <span style="margin-right:5px;">🖨️</span> Imprimir / Guardar PDF
  </button>
</div>

<div id="hoja">
<?= $htmlBody ?>
</div>

<script>
  // Auto-imprimir al cargar la página
  window.addEventListener('load', function () {
    window.print();
  });
</script>

</body>
</html>
<?php
exit();

