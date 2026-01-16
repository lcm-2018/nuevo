<?php

use Config\Clases\Conexion;
use Src\Common\Php\Clases\Imprimir;
use Src\Common\Php\Clases\Reportes as CReportes;
use Src\Common\Php\Clases\Valores;
use Src\Nomina\Configuracion\Php\Clases\Rubros;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Liquidado\Php\Clases\Detalles;
use Src\Nomina\Liquidado\Php\Clases\Reportes;
use Src\Usuarios\Login\Php\Clases\Usuario;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

include_once '../../../../../config/autoloader.php';
$id_nomina  = isset($_POST['id']) ? intval($_POST['id']) : exit('Acceso Denegado');
$documento  = "REPORTE DE LIQUIDACIÓN DE EMPLEADOS";
$nomina     = Nomina::getRegistro($id_nomina);
$mes        = mb_strtoupper(Valores::NombreMes($nomina['mes']));

$conexion   = Conexion::getConexion();
$datos      = (new Detalles())->getRegistrosDT(1, -1, ['id_nomina' => $id_nomina], 1, 'ASC');

$usuario    = new Usuario();
$empresa    = $usuario->getEmpresa();
$rubros     = (new Rubros)->getRubros2();
$count      = count($datos);

// Generar tabla de detalles de empleados similar a detalles.php
$body = '';
$totales = [
    'sal_base' => 0,
    'dias_incapacidad' => 0,
    'dias_licencias' => 0,
    'dias_vacaciones' => 0,
    'dias_otros' => 0,
    'dias_lab' => 0,
    'valor_incap' => 0,
    'valor_licencias' => 0,
    'valor_vacacion' => 0,
    'valor_otros' => 0,
    'valor_laborado' => 0,
    'aux_tran' => 0,
    'aux_alim' => 0,
    'horas_ext' => 0,
    'val_bsp' => 0,
    'val_prima_vac' => 0,
    'g_representa' => 0,
    'val_bon_recrea' => 0,
    'valor_ps' => 0,
    'valor_pv' => 0,
    'val_cesantias' => 0,
    'val_icesantias' => 0,
    'val_compensa' => 0,
    'devengado' => 0,
    'valor_salud' => 0,
    'valor_pension' => 0,
    'val_psolidaria' => 0,
    'valor_libranza' => 0,
    'valor_embargo' => 0,
    'valor_sind' => 0,
    'val_retencion' => 0,
    'valor_dcto' => 0,
    'deducciones' => 0,
    'neto' => 0
];

// Calcular licencias y otros valores para cada empleado
foreach ($datos as &$d) {
    // Calcular valor licencias (luto + materna/paterna)
    $d['valor_licencias'] = ($d['valor_luto'] ?? 0) + ($d['valor_mp'] ?? 0);
    $d['valor_otros'] = 0;

    // Calcular devengado
    $d['devengado'] = ($d['valor_laborado'] ?? 0) + ($d['val_compensa'] ?? 0) + ($d['valor_incap'] ?? 0) +
        ($d['valor_licencias'] ?? 0) + ($d['valor_vacacion'] ?? 0) + ($d['aux_tran'] ?? 0) +
        ($d['aux_alim'] ?? 0) + ($d['horas_ext'] ?? 0) + ($d['val_bsp'] ?? 0) +
        ($d['val_prima_vac'] ?? 0) + ($d['g_representa'] ?? 0) + ($d['val_bon_recrea'] ?? 0) +
        ($d['valor_ps'] ?? 0) + ($d['valor_pv'] ?? 0) + ($d['val_cesantias'] ?? 0) +
        ($d['val_icesantias'] ?? 0);

    // Calcular deducciones
    $d['deducciones'] = ($d['valor_salud'] ?? 0) + ($d['valor_pension'] ?? 0) + ($d['val_psolidaria'] ?? 0) +
        ($d['valor_libranza'] ?? 0) + ($d['valor_embargo'] ?? 0) + ($d['valor_sind'] ?? 0) +
        ($d['val_retencion'] ?? 0) + ($d['valor_dcto'] ?? 0);

    // Calcular neto
    $d['neto'] = $d['devengado'] - $d['deducciones'];
}
unset($d);

foreach ($datos as $d) {
    // Acumular totales
    foreach ($totales as $key => &$total) {
        $total += $d[$key] ?? 0;
    }
    unset($total);

    $body .= "<tr>
        <td style='text-align: center; font-size: 7px;'>{$d['id_empleado']}</td>
        <td style='text-align: left; font-size: 7px; white-space: nowrap;'>{$d['nombre']}</td>
        <td style='text-align: center; font-size: 7px;'>{$d['no_documento']}</td>
        <td style='text-align: left; font-size: 7px;'>{$d['sede']}</td>
        <td style='text-align: left; font-size: 7px;'>{$d['descripcion_carg']}</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['sal_base'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: center; font-size: 7px;'>{$d['dias_incapacidad']}</td>
        <td style='text-align: center; font-size: 7px;'>{$d['dias_licencias']}</td>
        <td style='text-align: center; font-size: 7px;'>{$d['dias_vacaciones']}</td>
        <td style='text-align: center; font-size: 7px;'>{$d['dias_otros']}</td>
        <td style='text-align: center; font-size: 7px;'>{$d['dias_lab']}</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['valor_incap'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['valor_licencias'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['valor_vacacion'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['valor_otros'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['valor_laborado'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['aux_tran'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['aux_alim'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['horas_ext'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['val_bsp'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['val_prima_vac'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['g_representa'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['val_bon_recrea'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['valor_ps'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['valor_pv'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['val_cesantias'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['val_icesantias'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['val_compensa'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px; font-weight: bold;'>" . number_format($d['devengado'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['valor_salud'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['valor_pension'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['val_psolidaria'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['valor_libranza'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['valor_embargo'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['valor_sind'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['val_retencion'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px;'>" . number_format($d['valor_dcto'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px; font-weight: bold;'>" . number_format($d['deducciones'] ?? 0, 0, ',', '.') . "</td>
        <td style='text-align: right; font-size: 7px; font-weight: bold;'>" . number_format($d['neto'] ?? 0, 0, ',', '.') . "</td>
    </tr>";
}

// Fila de totales
$body .= "<tr style='background-color: #f0f0f0; font-weight: bold;'>
    <td colspan='5' style='text-align: right; font-size: 7px;'>TOTALES:</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['sal_base'], 0, ',', '.') . "</td>
    <td style='text-align: center; font-size: 7px;'>{$totales['dias_incapacidad']}</td>
    <td style='text-align: center; font-size: 7px;'>{$totales['dias_licencias']}</td>
    <td style='text-align: center; font-size: 7px;'>{$totales['dias_vacaciones']}</td>
    <td style='text-align: center; font-size: 7px;'>{$totales['dias_otros']}</td>
    <td style='text-align: center; font-size: 7px;'>{$totales['dias_lab']}</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['valor_incap'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['valor_licencias'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['valor_vacacion'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['valor_otros'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['valor_laborado'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['aux_tran'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['aux_alim'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['horas_ext'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['val_bsp'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['val_prima_vac'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['g_representa'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['val_bon_recrea'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['valor_ps'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['valor_pv'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['val_cesantias'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['val_icesantias'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['val_compensa'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['devengado'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['valor_salud'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['valor_pension'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['val_psolidaria'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['valor_libranza'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['valor_embargo'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['valor_sind'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['val_retencion'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['valor_dcto'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['deducciones'], 0, ',', '.') . "</td>
    <td style='text-align: right; font-size: 7px;'>" . number_format($totales['neto'], 0, ',', '.') . "</td>
</tr>";
$objeto = "<p style='text-align: justify; font-size: 10px; margin-bottom: 10px;'><strong>OBJETO:</strong> PAGO NÓMINA N° {$id_nomina}, {$mes} VIGENCIA {$nomina['vigencia']}, ADMINISTRATIVO-ASISTENCIAL, {$count} EMPLEADOS ADSCRITOS A {$empresa['nombre']}.</p>";
$html =
    <<<HTML
    {$objeto}
    <table border='1' cellpadding='2' cellspacing='0' style='width: 100%; border-collapse: collapse; font-size: 7px;'>
        <tr>
            <th colspan='39' style='text-align: center; font-size: 9px; padding: 5px;'>{$nomina['descripcion']} N° {$id_nomina} - MES DE {$mes} VIGENCIA {$nomina['vigencia']} - {$count} EMPLEADOS</th>
        </tr>
        <tr>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>ID</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>NOMBRE</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>No. DOC.</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>SEDE</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>CARGO</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>BASE</th>
            <th colspan='5' style='text-align: center; font-size: 6px;'>DIAS</th>
            <th colspan='5' style='text-align: center; font-size: 6px;'>VALOR</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>AUX. T.</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>AUX. A.</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>EXTRAS</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>BSP</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>P. VAC.</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>G. REP.</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>B. REC.</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>P. SERV.</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>P. NAV.</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>CES.</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>I. CES.</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>COMP.</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>T. DEV.</th>
            <th colspan='8' style='text-align: center; font-size: 6px;'>DEDUCCIONES</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>T. DED.</th>
            <th rowspan='2' style='text-align: center; font-size: 6px;'>NETO</th>
        </tr>
        <tr>
            <th style='text-align: center; font-size: 6px;'>INC.</th>
            <th style='text-align: center; font-size: 6px;'>LIC.</th>
            <th style='text-align: center; font-size: 6px;'>VAC.</th>
            <th style='text-align: center; font-size: 6px;'>OTRO.</th>
            <th style='text-align: center; font-size: 6px;'>LAB.</th>
            <th style='text-align: center; font-size: 6px;'>INC.</th>
            <th style='text-align: center; font-size: 6px;'>LIC.</th>
            <th style='text-align: center; font-size: 6px;'>VAC.</th>
            <th style='text-align: center; font-size: 6px;'>OTRO.</th>
            <th style='text-align: center; font-size: 6px;'>LAB.</th>
            <th style='text-align: center; font-size: 6px;'>SALUD</th>
            <th style='text-align: center; font-size: 6px;'>PENS.</th>
            <th style='text-align: center; font-size: 6px;'>P. SOL.</th>
            <th style='text-align: center; font-size: 6px;'>LIB.</th>
            <th style='text-align: center; font-size: 6px;'>EMB.</th>
            <th style='text-align: center; font-size: 6px;'>SIND.</th>
            <th style='text-align: center; font-size: 6px;'>R. FTE.</th>
            <th style='text-align: center; font-size: 6px;'>DCTO.</th>
        </tr>
        <tbody>
            {$body}
        </tbody>
    </table>
    HTML;

$firmas = (new CReportes())->getFormFirmas(['nom_tercero' => $nomina['elabora'], 'cargo' => $nomina['cargo']], 51, $nomina['vigencia'] . '-' . $nomina['mes'] . '-01', '');

$Imprimir = new Imprimir($documento, "legal", "L");
$Imprimir->addEncabezado($documento);
$Imprimir->addContenido($html);
$Imprimir->addFirmas($firmas);
$pdf = isset($_POST['pdf']) ? filter_var($_POST['pdf'], FILTER_VALIDATE_BOOLEAN) : false;
$resul = $Imprimir->render($pdf);


if ($pdf) {
    $Imprimir->getPDF($resul);
    exit();
}
