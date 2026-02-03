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
$datos = isset($_POST['id']) ? explode('|', base64_decode($_POST['id'])) : exit('Acceso denegado');
$id_empleado = $datos[0];
$id_nomina   = $datos[1];

$documento  = "Desprendible de Nómina";
$usuario    = new Usuario();
$empresa    = $usuario->getEmpresa();
$nomina     = Nomina::getRegistro($id_nomina);
$mes        = mb_strtoupper(Valores::NombreMes($nomina['mes']));
$otro       = "NÓMINA No. {$id_nomina} - MES: {$mes} - VIGENCIA: {$nomina['vigencia']}";

$detalles = new Detalles();

// Si id_empleado es 0, obtener todos los empleados de la nómina
if ($id_empleado == 0) {
    $empleados = $detalles->getRegistrosDT(1, -1, ['id_nomina' => $id_nomina], 1, 'ASC');
    $documento = "Desprendibles de Nómina - Masivo";
} else {
    // Un solo empleado
    $empleado = $detalles->getRegistrosDT(1, -1, ['id_empleado' => $id_empleado, 'id_nomina' => $id_nomina], 1, 'ASC');
    $empleados = [$empleado];
}

/**
 * Función para generar el HTML de un desprendible individual
 */
function generarDesprendible($d)
{
    // Calcular valores adicionales
    $valor_licencias = ($d['valor_luto'] ?? 0) + ($d['valor_mp'] ?? 0);

    // Definir conceptos de devengados
    $devengados = [
        'Salario Laborado' => $d['valor_laborado'] ?? 0,
        'Compensatorio' => $d['val_compensa'] ?? 0,
        'Incapacidades (' . ($d['dias_incapacidad'] ?? 0) . ' días)' => $d['valor_incap'] ?? 0,
        'Licencias (' . ($d['dias_licencias'] ?? 0) . ' días)' => $valor_licencias,
        'Vacaciones (' . ($d['dias_vacaciones'] ?? 0) . ' días)' => $d['valor_vacacion'] ?? 0,
        'Prima de Vacaciones' => $d['val_prima_vac'] ?? 0,
        'Bonificación Recreación' => $d['val_bon_recrea'] ?? 0,
        'Auxilio de Transporte' => $d['aux_tran'] ?? 0,
        'Auxilio de Alimentación' => $d['aux_alim'] ?? 0,
        'Horas Extras' => $d['horas_ext'] ?? 0,
        'Bonificación Servicios Prestados' => $d['val_bsp'] ?? 0,
        'Gastos de Representación' => $d['g_representa'] ?? 0,
        'Prima de Servicios' => $d['valor_ps'] ?? 0,
        'Prima de Navidad' => $d['valor_pv'] ?? 0,
        'Cesantías' => $d['val_cesantias'] ?? 0,
        'Intereses Cesantías' => $d['val_icesantias'] ?? 0,
    ];

    // Definir conceptos de deducciones
    $deducciones = [
        'Aporte Salud (4%)' => $d['valor_salud'] ?? 0,
        'Aporte Pensión (4%)' => $d['valor_pension'] ?? 0,
        'Pensión Solidaria' => $d['val_psolidaria'] ?? 0,
        'Libranzas' => $d['valor_libranza'] ?? 0,
        'Embargos' => $d['valor_embargo'] ?? 0,
        'Sindicato' => $d['valor_sind'] ?? 0,
        'Retención en la Fuente' => $d['val_retencion'] ?? 0,
        'Otros Descuentos' => $d['valor_dcto'] ?? 0,
    ];

    // Filtrar solo los que tienen valor > 0
    $devengados = array_filter($devengados, function ($v) {
        return $v > 0;
    });
    $deducciones = array_filter($deducciones, function ($v) {
        return $v > 0;
    });

    // Calcular totales
    $total_devengado = array_sum($devengados);
    $total_deducciones = array_sum($deducciones);
    $neto_pagar = $total_devengado - $total_deducciones;

    // Formatear valores para el HTML
    $sal_base_fmt = number_format($d['sal_base'] ?? 0, 0, ',', '.');
    $total_devengado_fmt = number_format($total_devengado, 0, ',', '.');
    $total_deducciones_fmt = number_format($total_deducciones, 0, ',', '.');
    $neto_pagar_fmt = number_format($neto_pagar, 0, ',', '.');

    // Generar filas de devengados
    $filas_devengados = '';
    foreach ($devengados as $concepto => $valor) {
        $valor_fmt = number_format($valor, 0, ',', '.');
        $filas_devengados .= "<tr>
            <td style='padding: 4px 8px;'>{$concepto}</td>
            <td style='text-align: right; padding: 4px 8px;'>{$valor_fmt}</td>
        </tr>";
    }

    // Generar filas de deducciones
    $filas_deducciones = '';
    foreach ($deducciones as $concepto => $valor) {
        $valor_fmt = number_format($valor, 0, ',', '.');
        $filas_deducciones .= "<tr>
            <td style='padding: 4px 8px;'>{$concepto}</td>
            <td style='text-align: right; padding: 4px 8px;'>{$valor_fmt}</td>
        </tr>";
    }

    $html = <<<HTML
<!-- Información del Empleado -->
<table style='width: 100%; border-collapse: collapse; margin-bottom: 15px;' border='1'>
    <tr style='background-color: #f5f5f5;'>
        <th colspan='4' style='text-align: center; padding: 8px; font-size: 12px;'>INFORMACIÓN DEL EMPLEADO</th>
    </tr>
    <tr>
        <td style='padding: 6px; width: 15%; font-weight: bold;'>Nombre:</td>
        <td style='padding: 6px; width: 35%;'>{$d['nombre']}</td>
        <td style='padding: 6px; width: 15%; font-weight: bold;'>Documento:</td>
        <td style='padding: 6px; width: 35%;'>{$d['no_documento']}</td>
    </tr>
    <tr>
        <td style='padding: 6px; font-weight: bold;'>Cargo:</td>
        <td style='padding: 6px;'>{$d['descripcion_carg']}</td>
        <td style='padding: 6px; font-weight: bold;'>Sede:</td>
        <td style='padding: 6px;'>{$d['sede']}</td>
    </tr>
    <tr>
        <td style='padding: 6px; font-weight: bold;'>Salario Base:</td>
        <td style='padding: 6px;'>$ {$sal_base_fmt}</td>
        <td style='padding: 6px; font-weight: bold;'>Días Laborados:</td>
        <td style='padding: 6px;'>{$d['dias_lab']}</td>
    </tr>
</table>

<!-- Devengados y Deducciones -->
<table style='width: 100%; border-collapse: collapse;' border='1'>
    <tr>
        <!-- Columna Devengados -->
        <td style='width: 50%; vertical-align: top; padding: 0;'>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr style='background-color: #d4edda;'>
                    <th colspan='2' style='text-align: center; padding: 8px; font-size: 11px;'>DEVENGADOS</th>
                </tr>
                {$filas_devengados}
                <tr style='background-color: #d4edda; font-weight: bold;'>
                    <td style='padding: 6px 8px;'>TOTAL DEVENGADO</td>
                    <td style='text-align: right; padding: 6px 8px;'>$ {$total_devengado_fmt}</td>
                </tr>
            </table>
        </td>
        <!-- Columna Deducciones -->
        <td style='width: 50%; vertical-align: top; padding: 0;'>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr style='background-color: #f8d7da;'>
                    <th colspan='2' style='text-align: center; padding: 8px; font-size: 11px;'>DEDUCCIONES</th>
                </tr>
                {$filas_deducciones}
                <tr style='background-color: #f8d7da; font-weight: bold;'>
                    <td style='padding: 6px 8px;'>TOTAL DEDUCCIONES</td>
                    <td style='text-align: right; padding: 6px 8px;'>$ {$total_deducciones_fmt}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Neto a Pagar -->
<table style='width: 100%; border-collapse: collapse; margin-top: 15px;' border='1'>
    <tr style='background-color: #cce5ff;'>
        <td style='padding: 10px; font-size: 14px; font-weight: bold; text-align: center; width: 70%;'>NETO A PAGAR</td>
        <td style='padding: 10px; font-size: 14px; font-weight: bold; text-align: right; width: 30%;'>$ {$neto_pagar_fmt}</td>
    </tr>
</table>

<p style='font-size: 10px; text-align: center; margin-top: 15px;'>
    Este desprendible es un documento informativo. Para cualquier aclaración, dirigirse al área de Recursos Humanos.
</p>
HTML;

    return $html;
}

// Generar el contenido HTML
$html = '';
$totalEmpleados = count($empleados);
$contador = 0;

foreach ($empleados as $empleado) {
    $contador++;
    $html .= generarDesprendible($empleado);

    // Añadir salto de página entre empleados (excepto el último)
    if ($contador < $totalEmpleados) {
        $html .= "<div style='page-break-after: always;'></div>";
    }
}

$firmas = (new CReportes())->getFormFirmas(['nom_tercero' => $nomina['elabora'], 'cargo' => $nomina['cargo']], 51, $nomina['vigencia'] . '-' . $nomina['mes'] . '-01', '');

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
