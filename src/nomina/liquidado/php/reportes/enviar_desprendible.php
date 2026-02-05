<?php

/**
 * Endpoint para enviar desprendibles de nómina por correo electrónico
 * 
 * @param POST id - ID del empleado|ID de la nómina codificado en base64
 */

use Src\Common\Php\Clases\Correo;
use Src\Common\Php\Clases\GeneradorPDF;
use Src\Common\Php\Clases\Valores;
use Src\Nomina\Empleados\Php\Clases\Empleados;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Liquidado\Php\Clases\Detalles;
use Src\Usuarios\Login\Php\Clases\Usuario;

session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'msg' => 'No autorizado']);
    exit();
}

include_once '../../../../../config/autoloader.php';

header('Content-Type: application/json');

// Validar datos de entrada
$datos = isset($_POST['id']) ? explode('|', base64_decode($_POST['id'])) : null;
if (!$datos || count($datos) < 2) {
    echo json_encode(['status' => 'error', 'msg' => 'Datos inválidos']);
    exit();
}

$id_empleado = intval($datos[0]);
$id_nomina = intval($datos[1]);

try {
    $detalles = new Detalles();
    $usuario = new Usuario();
    $empresa = $usuario->getEmpresa();
    $nomina = Nomina::getRegistro($id_nomina);
    $mes = mb_strtoupper(Valores::NombreMes($nomina['mes']));

    // Obtener datos del empleado para el correo
    $empleados = new Empleados();
    $empleadoData = $empleados->getEmpleados($id_empleado);

    if (empty($empleadoData['correo'])) {
        echo json_encode([
            'status' => 'error',
            'msg' => 'El empleado no tiene correo electrónico registrado'
        ]);
        exit();
    }

    //$empleadoData['correo'] = "eachitanc@gmail.com";
    // Obtener datos del desprendible
    $datosEmpleado = $detalles->getRegistrosDT(1, -1, ['id_empleado' => $id_empleado, 'id_nomina' => $id_nomina], 1, 'ASC');

    if (empty($datosEmpleado)) {
        echo json_encode(['status' => 'error', 'msg' => 'No se encontraron datos de liquidación']);
        exit();
    }

    // Generar el PDF usando GeneradorPDF con detalles discriminados
    $documento = "Desprendible de Nómina";
    $subtitulo = "NÓMINA No. {$id_nomina} - MES: {$mes} - VIGENCIA: {$nomina['vigencia']}";

    // Obtener firmas
    $firmas = (new \Src\Common\Php\Clases\Reportes())->getFormFirmas(
        ['nom_tercero' => $nomina['elabora'], 'cargo' => $nomina['cargo']],
        51,
        $nomina['vigencia'] . '-' . $nomina['mes'] . '-01',
        ''
    );

    $generadorPDF = new GeneradorPDF('letter', 'portrait');
    $pdfContent = $generadorPDF->generarDesprendiblePDF($datosEmpleado, $documento, $subtitulo, $firmas, $detalles, $id_nomina);

    // Enviar correo con el desprendible adjunto
    $correo = new Correo();

    $nombreEmpleado = $datosEmpleado['nombre'];
    $asunto = "Desprendible de Nómina - {$mes} {$nomina['vigencia']}";

    $contenido = <<<HTML
        <p>Estimado(a) <strong>{$nombreEmpleado}</strong>,</p>
        <p>Adjunto encontrará su desprendible de nómina correspondiente al mes de <strong>{$mes}</strong> del año <strong>{$nomina['vigencia']}</strong>.</p>
        <p>Este documento contiene el detalle de sus devengados y deducciones del período.</p>
        <p>Si tiene alguna consulta sobre su desprendible, por favor comuníquese con el área de Recursos Humanos.</p>
        <br>
        <p>Cordialmente,<br><strong>{$empresa['nombre']}</strong></p>
HTML;

    $nombreArchivo = "Desprendible_{$mes}_{$nomina['vigencia']}_{$datosEmpleado['no_documento']}.pdf";

    $resultado = $correo
        ->addDestinatario($empleadoData['correo'], $nombreEmpleado)
        ->setAsunto($asunto)
        ->setCuerpoHTML($correo->generarPlantillaHTML($asunto, $contenido))
        ->addAdjuntoDesdeString($pdfContent, $nombreArchivo)
        ->enviar();

    if ($resultado['success']) {
        echo json_encode([
            'status' => 'ok',
            'msg' => "Desprendible enviado correctamente a {$empleadoData['correo']}"
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'msg' => $resultado['message'],
            'errors' => $resultado['errors']
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Error: ' . $e->getMessage()
    ]);
}
