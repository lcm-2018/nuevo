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

// Ordenar datos primero por sede y luego por nombre
usort($datos, function ($a, $b) {
    // Primero ordenar por sede
    $sedeCompare = strcasecmp($a['sede'] ?? '', $b['sede'] ?? '');
    if ($sedeCompare !== 0) {
        return $sedeCompare;
    }
    // Si las sedes son iguales, ordenar por nombre
    return strcasecmp($a['nombre'] ?? '', $b['nombre'] ?? '');
});

$usuario    = new Usuario();
$empresa    = $usuario->getEmpresa();
$rubros     = (new Rubros)->getRubros2();
$count      = count($datos);

// Definición de columnas con su configuración
// 'key' => nombre del campo en datos
// 'label' => etiqueta para el encabezado
// 'group' => grupo al que pertenece (para colspan en encabezado)
// 'type' => 'text', 'number', 'days' (para formato)
// 'align' => alineación
// 'fixed' => true si la columna siempre debe mostrarse
// 'bold' => true si debe mostrarse en negrita
$columnas = [
    ['key' => 'id_empleado', 'label' => 'ID', 'group' => null, 'type' => 'text', 'align' => 'center', 'fixed' => true],
    ['key' => 'nombre', 'label' => 'NOMBRE', 'group' => null, 'type' => 'text', 'align' => 'left', 'fixed' => true, 'nowrap' => true],
    ['key' => 'no_documento', 'label' => 'No. DOC.', 'group' => null, 'type' => 'text', 'align' => 'center', 'fixed' => true],
    ['key' => 'sede', 'label' => 'SEDE', 'group' => null, 'type' => 'text', 'align' => 'left', 'fixed' => true],
    ['key' => 'descripcion_carg', 'label' => 'CARGO', 'group' => null, 'type' => 'text', 'align' => 'left', 'fixed' => true],
    ['key' => 'sal_base', 'label' => 'BASE', 'group' => null, 'type' => 'number', 'align' => 'right', 'fixed' => true],
    // Grupo DIAS
    ['key' => 'dias_incapacidad', 'label' => 'INC.', 'group' => 'DIAS', 'type' => 'days', 'align' => 'center'],
    ['key' => 'dias_licencias', 'label' => 'LIC.', 'group' => 'DIAS', 'type' => 'days', 'align' => 'center'],
    ['key' => 'dias_vacaciones', 'label' => 'VAC.', 'group' => 'DIAS', 'type' => 'days', 'align' => 'center'],
    ['key' => 'dias_otros', 'label' => 'OTRO.', 'group' => 'DIAS', 'type' => 'days', 'align' => 'center'],
    ['key' => 'dias_lab', 'label' => 'LAB.', 'group' => 'DIAS', 'type' => 'days', 'align' => 'center', 'fixed' => true],
    // Grupo VALOR
    ['key' => 'valor_incap', 'label' => 'INC.', 'group' => 'VALOR', 'type' => 'number', 'align' => 'right'],
    ['key' => 'valor_licencias', 'label' => 'LIC.', 'group' => 'VALOR', 'type' => 'number', 'align' => 'right'],
    ['key' => 'valor_vacacion', 'label' => 'VAC.', 'group' => 'VALOR', 'type' => 'number', 'align' => 'right'],
    ['key' => 'valor_otros', 'label' => 'OTRO.', 'group' => 'VALOR', 'type' => 'number', 'align' => 'right'],
    ['key' => 'valor_laborado', 'label' => 'LAB.', 'group' => 'VALOR', 'type' => 'number', 'align' => 'right', 'fixed' => true],
    // Columnas individuales de devengado
    ['key' => 'aux_tran', 'label' => 'AUX. T.', 'group' => null, 'type' => 'number', 'align' => 'right'],
    ['key' => 'aux_alim', 'label' => 'AUX. A.', 'group' => null, 'type' => 'number', 'align' => 'right'],
    ['key' => 'horas_ext', 'label' => 'EXTRAS', 'group' => null, 'type' => 'number', 'align' => 'right'],
    ['key' => 'val_bsp', 'label' => 'BSP', 'group' => null, 'type' => 'number', 'align' => 'right'],
    ['key' => 'val_prima_vac', 'label' => 'P. VAC.', 'group' => null, 'type' => 'number', 'align' => 'right'],
    ['key' => 'g_representa', 'label' => 'G. REP.', 'group' => null, 'type' => 'number', 'align' => 'right'],
    ['key' => 'val_bon_recrea', 'label' => 'B. REC.', 'group' => null, 'type' => 'number', 'align' => 'right'],
    ['key' => 'valor_ps', 'label' => 'P. SERV.', 'group' => null, 'type' => 'number', 'align' => 'right'],
    ['key' => 'valor_pv', 'label' => 'P. NAV.', 'group' => null, 'type' => 'number', 'align' => 'right'],
    ['key' => 'val_cesantias', 'label' => 'CES.', 'group' => null, 'type' => 'number', 'align' => 'right'],
    ['key' => 'val_icesantias', 'label' => 'I. CES.', 'group' => null, 'type' => 'number', 'align' => 'right'],
    ['key' => 'val_compensa', 'label' => 'COMP.', 'group' => null, 'type' => 'number', 'align' => 'right'],
    ['key' => 'devengado', 'label' => 'T. DEV.', 'group' => null, 'type' => 'number', 'align' => 'right', 'fixed' => true, 'bold' => true],
    // Grupo DEDUCCIONES
    ['key' => 'valor_salud', 'label' => 'SALUD', 'group' => 'DEDUCCIONES', 'type' => 'number', 'align' => 'right'],
    ['key' => 'valor_pension', 'label' => 'PENS.', 'group' => 'DEDUCCIONES', 'type' => 'number', 'align' => 'right'],
    ['key' => 'val_psolidaria', 'label' => 'P. SOL.', 'group' => 'DEDUCCIONES', 'type' => 'number', 'align' => 'right'],
    ['key' => 'valor_libranza', 'label' => 'LIB.', 'group' => 'DEDUCCIONES', 'type' => 'number', 'align' => 'right'],
    ['key' => 'valor_embargo', 'label' => 'EMB.', 'group' => 'DEDUCCIONES', 'type' => 'number', 'align' => 'right'],
    ['key' => 'valor_sind', 'label' => 'SIND.', 'group' => 'DEDUCCIONES', 'type' => 'number', 'align' => 'right'],
    ['key' => 'val_retencion', 'label' => 'R. FTE.', 'group' => 'DEDUCCIONES', 'type' => 'number', 'align' => 'right'],
    ['key' => 'valor_dcto', 'label' => 'DCTO.', 'group' => 'DEDUCCIONES', 'type' => 'number', 'align' => 'right'],
    // Totales
    ['key' => 'deducciones', 'label' => 'T. DED.', 'group' => null, 'type' => 'number', 'align' => 'right', 'fixed' => true, 'bold' => true],
    ['key' => 'neto', 'label' => 'NETO', 'group' => null, 'type' => 'number', 'align' => 'right', 'fixed' => true, 'bold' => true],
];

// Inicializar totales
$totales = [];
foreach ($columnas as $col) {
    if ($col['type'] === 'number' || $col['type'] === 'days') {
        $totales[$col['key']] = 0;
    }
}

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

    // Acumular totales
    foreach ($totales as $key => &$total) {
        $total += $d[$key] ?? 0;
    }
    unset($total);
}
unset($d);

// Determinar qué columnas tienen al menos un valor distinto de cero
$columnasVisibles = [];
foreach ($columnas as $col) {
    $key = $col['key'];
    $tieneValor = !empty($col['fixed']); // Las columnas fijas siempre se muestran

    if (!$tieneValor && ($col['type'] === 'number' || $col['type'] === 'days')) {
        // Verificar si el total de esta columna es diferente de cero
        $tieneValor = isset($totales[$key]) && $totales[$key] != 0;
    } elseif ($col['type'] === 'text') {
        $tieneValor = true; // Columnas de texto siempre se muestran
    }

    if ($tieneValor) {
        $columnasVisibles[] = $col;
    }
}

// Contar columnas por grupo para los colspan
$grupoCount = [];
foreach ($columnasVisibles as $col) {
    if (!empty($col['group'])) {
        $grupoCount[$col['group']] = ($grupoCount[$col['group']] ?? 0) + 1;
    }
}

// Generar encabezado fila 1
$theadRow1 = '';
$gruposProcessed = [];
foreach ($columnasVisibles as $col) {
    if (!empty($col['group'])) {
        if (!in_array($col['group'], $gruposProcessed)) {
            $colspan = $grupoCount[$col['group']];
            $theadRow1 .= "<th colspan='{$colspan}' style='text-align: center; font-size: 6px;'>{$col['group']}</th>";
            $gruposProcessed[] = $col['group'];
        }
    } else {
        $theadRow1 .= "<th rowspan='2' style='text-align: center; font-size: 6px;'>{$col['label']}</th>";
    }
}

// Generar encabezado fila 2 (solo columnas con grupo)
$theadRow2 = '';
foreach ($columnasVisibles as $col) {
    if (!empty($col['group'])) {
        $theadRow2 .= "<th style='text-align: center; font-size: 6px;'>{$col['label']}</th>";
    }
}

// Generar cuerpo de la tabla
$body = '';
foreach ($datos as $d) {
    $body .= "<tr>";
    foreach ($columnasVisibles as $col) {
        $key = $col['key'];
        $value = $d[$key] ?? 0;
        $align = $col['align'];
        $style = "text-align: {$align}; font-size: 7px;";
        if (!empty($col['nowrap'])) {
            $style .= " white-space: nowrap;";
        }
        if (!empty($col['bold'])) {
            $style .= " font-weight: bold;";
        }

        if ($col['type'] === 'number') {
            $value = number_format($value, 0, ',', '.');
        } elseif ($col['type'] === 'days') {
            $value = intval($value);
        }

        $body .= "<td style='{$style}'>{$value}</td>";
    }
    $body .= "</tr>";
}

// Fila de totales
$colspanFixed = 0;
foreach ($columnasVisibles as $col) {
    if ($col['type'] === 'text') {
        $colspanFixed++;
    } else {
        break;
    }
}

$body .= "<tr style='background-color: #f0f0f0; font-weight: bold;'>";
$body .= "<td colspan='{$colspanFixed}' style='text-align: right; font-size: 7px;'>TOTALES:</td>";

$skipCount = 0;
foreach ($columnasVisibles as $col) {
    if ($skipCount < $colspanFixed) {
        $skipCount++;
        continue;
    }

    $key = $col['key'];
    $value = $totales[$key] ?? 0;
    $align = $col['align'];
    $style = "text-align: {$align}; font-size: 7px;";

    if ($col['type'] === 'number') {
        $value = number_format($value, 0, ',', '.');
    } elseif ($col['type'] === 'days') {
        $value = intval($value);
    }

    $body .= "<td style='{$style}'>{$value}</td>";
}
$body .= "</tr>";

// Generar HTML
$html = <<<HTML
    <style>
        .tabla-nomina thead { display: table-header-group; visibility: visible !important; }
        .tabla-nomina thead tr { visibility: visible !important; }
        .tabla-nomina thead th { visibility: visible !important; background-color: #f0f0f0; }
        @media print {
            .tabla-nomina thead { display: table-header-group; visibility: visible !important; }
            .tabla-nomina thead tr { visibility: visible !important; }
            .tabla-nomina thead th { visibility: visible !important; }
        }
    </style>
    <b>OBJETO:</b> {$nomina['descripcion']}
    <table class="tabla-nomina" border='1' cellpadding='2' cellspacing='0' style='width: 100%; border-collapse: collapse; font-size: 7px;'>
        <thead>
            <tr>
                {$theadRow1}
            </tr>
            <tr>
                {$theadRow2}
            </tr>
        </thead>
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
