<?php

use Config\Clases\Conexion;
use Src\Common\Php\Clases\Imprimir;
use Src\Common\Php\Clases\Reportes as CReportes;
use Src\Common\Php\Clases\Valores;
use Src\Nomina\Empleados\Php\Clases\Viaticos;
use Src\Nomina\Empleados\Php\Clases\ViaticoNovedades;
use Src\Nomina\Empleados\Php\Clases\Empleados;
use Src\Nomina\Empleados\Php\Clases\Ccostos;
use Src\Nomina\Configuracion\Php\Clases\Cargos;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

include_once '../../../../../config/autoloader.php';

$id_viatico = isset($_POST['id']) ? intval($_POST['id']) : exit('Acceso Denegado');
$documento  = "REPORTE DETALLADO DE VIÁTICOS";

// 1. Obtener datos del Viático
$Viaticos = new Viaticos();
$dataViatico = $Viaticos->getRegistro($id_viatico);

if (empty($dataViatico['id_viatico'])) {
    exit('Viático no encontrado');
}

// 2. Obtener datos del Empleado
$Empleados = new Empleados();
$dataEmpleado = $Empleados->getEmpleados($dataViatico['id_empleado']);
$nombreEmpleado = trim(($dataEmpleado['nombre1'] ?? '') . ' ' . ($dataEmpleado['nombre2'] ?? '') . ' ' . ($dataEmpleado['apellido1'] ?? '') . ' ' . ($dataEmpleado['apellido2'] ?? ''));
$cedulaEmpleado = $dataEmpleado['no_documento'] ?? '';

// 3. Obtener Cargo
$Cargos = new Cargos();
$nombreCargo = $Cargos->getCargoEmpleado($dataViatico['id_empleado']);
if (empty($nombreCargo)) {
    $nombreCargo = 'NO REGISTRADO';
}

// 4. Obtener Dependencia (Centro de Costo)
$Ccostos = new Ccostos();
$listaCC = $Ccostos->getRegistrosDT(0, 1, ['id' => $dataViatico['id_empleado']], 1, 'ASC');
$nombreDependencia = !empty($listaCC) ? $listaCC[0]['nombre'] : 'SIN DEFINIR';

// 5. Obtener Historial de Novedades
$ViaticoNovedades = new ViaticoNovedades();
// Ordenar por ID ascendente para mostrar cronología. Columna 1 es id_novedad.
$listaNovedades = $ViaticoNovedades->getRegistrosDT(0, -1, ['id_viatico' => $id_viatico], 1, 'ASC');

// Mapeo de tipos de registro a nombres
$tiposRegistro = [
    1 => 'ANTICIPO',
    2 => 'APROBADO',
    3 => 'LEGALIZADO',
    4 => 'RECHAZADO',
    5 => 'CADUCADO'
];

// Construcción del cuerpo de novedades
$bodyNovedades = '';
foreach ($listaNovedades as $nov) {
    $tipoTexto = $tiposRegistro[$nov['tipo_registro']] ?? 'OTRO';
    $observacion = $nov['observacion'];
    $fechaNov = $nov['fecha']; // Ya viene formateada Y-m-d desde la clase, o eso esperamos. La clase ViaticoNovedades hace DATE_FORMAT(..., '%Y-%m-%d')
    // Re-formatear fecha a d/m/Y
    $dateObj = date_create($fechaNov);
    $fechaFormat = $dateObj ? date_format($dateObj, 'd/m/Y') : $fechaNov;

    $estiloBadge = "border: 1px solid #333; padding: 2px 5px; font-weight: bold; font-size: 10px; display: inline-block;";

    $bodyNovedades .= "
        <tr>
            <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$fechaFormat}</td>
            <td style='padding: 10px; border-bottom: 1px solid #ddd;'>
                <span style='{$estiloBadge}'>{$tipoTexto}</span>
            </td>
            <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: justify;'>{$observacion}</td>
        </tr>
    ";
}

if (empty($bodyNovedades)) {
    $bodyNovedades = "<tr><td colspan='3' style='text-align:center; padding: 10px;'>No hay novedades registradas.</td></tr>";
}

// Formatear fechas del encabezado
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');
$fechaSolicitud = strftime('%d de %B de %Y', strtotime($dataViatico['fec_inicia']));
// Fix para servidores windows o si strftime falla
if (!$fechaSolicitud || strpos($fechaSolicitud, '%') !== false) {
    $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $f = strtotime($dataViatico['fec_inicia']);
    $fechaSolicitud = date('d', $f) . ' de ' . $meses[(int)date('m', $f)] . ' de ' . date('Y', $f);
}

// Estilos
$styleSection = "background-color: #f5f5f5; border-left: 4px solid #333; padding: 5px 10px; font-weight: bold; margin-top: 15px; margin-bottom: 10px; font-size: 11px; text-transform: uppercase;";
$styleLabel = "font-weight: bold; font-size: 9px; color: #666; text-transform: uppercase; margin-bottom: 2px;";
$styleValue = "font-size: 11px; font-weight: bold; margin-bottom: 10px; text-transform: uppercase;";

$html = <<<HTML
    <style>
        .section-title { {$styleSection} }
        .label { {$styleLabel} }
        .value { {$styleValue} }
        .table-novedades { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10px; }
        .table-novedades th { text-align: left; padding: 8px; border-bottom: 2px solid #333; font-weight: bold; text-transform: uppercase; color: #444; }
    </style>

    <!-- 1. DATOS DEL FUNCIONARIO -->
    <div class="section-title">1. DATOS DEL FUNCIONARIO</div>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="label">NOMBRE COMPLETO:</div>
                <div class="value">{$nombreEmpleado}</div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="label">IDENTIFICACIÓN:</div>
                <div class="value">CC. {$cedulaEmpleado}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="label">CARGO:</div>
                <div class="value">{$nombreCargo}</div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="label">DEPENDENCIA:</div>
                <div class="value">{$nombreDependencia}</div>
            </td>
        </tr>
    </table>

    <!-- 2. INFORMACIÓN DE LA COMISIÓN -->
    <div class="section-title">2. INFORMACIÓN DE LA COMISIÓN</div>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="label">FECHA DE SOLICITUD:</div>
                <div class="value">{$fechaSolicitud}</div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="label">Nº RESOLUCIÓN:</div>
                <div class="value">{$dataViatico['no_resolucion']}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="label">DESTINO:</div>
                <div class="value">{$dataViatico['destino']}</div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="label">MOTIVO:</div>
                <div class="value" style="text-transform: none;">{$dataViatico['objetivo']}</div>
            </td>
        </tr>
    </table>

    <!-- 3. HISTORIAL DE NOVEDADES Y ESTADOS -->
    <div class="section-title">3. HISTORIAL DE NOVEDADES Y ESTADOS</div>
    <table class="table-novedades">
        <thead>
            <tr>
                <th style="width: 20%;">FECHA</th>
                <th style="width: 25%;">TIPO DE REGISTRO</th>
                <th style="width: 55%;">OBSERVACIÓN</th>
            </tr>
        </thead>
        <tbody>
            {$bodyNovedades}
        </tbody>
    </table>
HTML;

// Preparar firma del funcionario
$firmas = (new CReportes())->getFormFirmas([
    'nom_tercero' => $nombreEmpleado,
    'cargo' => 'Funcionario Comisionado'
], 51, $dataViatico['fec_inicia'], '');

// Generar PDF
$Imprimir = new Imprimir($documento, "letter");
$Imprimir->addEncabezado($documento);
$Imprimir->addContenido($html);
$Imprimir->addFirmas($firmas); // Agregar firmas al final

$pdf = isset($_POST['pdf']) ? filter_var($_POST['pdf'], FILTER_VALIDATE_BOOLEAN) : false;
$resul = $Imprimir->render($pdf);

if ($pdf) {
    $Imprimir->getPDF($resul);
    exit();
}
