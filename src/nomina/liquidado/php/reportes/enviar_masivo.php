<?php

/**
 * Endpoint para enviar desprendibles de nómina masivamente por correo electrónico
 * 
 * @param POST id_nomina - ID de la nómina
 */

use Src\Common\Php\Clases\Correo;
use Src\Common\Php\Clases\GeneradorPDF;
use Src\Common\Php\Clases\Valores;
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

// Aumentar límites para proceso masivo
set_time_limit(0);
ini_set('memory_limit', '512M');

// Validar datos de entrada
$id_nomina = isset($_POST['id_nomina']) ? intval($_POST['id_nomina']) : 0;
if ($id_nomina <= 0) {
    echo json_encode(['status' => 'error', 'msg' => 'ID de nómina inválido']);
    exit();
}

try {
    $detalles = new Detalles();
    $usuario = new Usuario();
    $empresa = $usuario->getEmpresa();
    $nomina = Nomina::getRegistro($id_nomina);
    $mes = mb_strtoupper(Valores::NombreMes($nomina['mes']));

    // Obtener todos los empleados de esta nómina
    $empleados = $detalles->getRegistrosDT(1, -1, ['id_nomina' => $id_nomina], -1, 'ASC');

    if (empty($empleados)) {
        echo json_encode(['status' => 'error', 'msg' => 'No se encontraron empleados en esta nómina']);
        exit();
    }

    // Estadísticas del proceso
    $stats = [
        'total' => count($empleados),
        'enviados' => 0,
        'sin_correo' => 0,
        'fallidos' => 0,
        'fallidos_docs' => [],
        'errores' => []
    ];

    // Preparar datos comunes
    $documento = "Desprendible de Nómina";
    $subtitulo = "NÓMINA No. {$id_nomina} - MES: {$mes} - VIGENCIA: {$nomina['vigencia']}";

    // Obtener firmas (se usan para todos los empleados)
    $firmas = (new \Src\Common\Php\Clases\Reportes())->getFormFirmas(
        ['nom_tercero' => $nomina['elabora'], 'cargo' => $nomina['cargo']],
        51,
        $nomina['vigencia'] . '-' . $nomina['mes'] . '-01',
        ''
    );

    // =====================================================
    // MODO PRUEBA: Limitar a 2 empleados
    // DESCOMENTAR SOLO PARA PRUEBAS
    // =====================================================
    // $empleados = array_slice($empleados, 0, 2);
    // $stats['total'] = count($empleados);
    // =====================================================

    foreach ($empleados as $datosEmpleado) {
        try {
            // Verificar si el empleado tiene correo
            $correoEmpleado = $datosEmpleado['correo'] ?? '';

            if (empty($correoEmpleado)) {
                $stats['sin_correo']++;
                $stats['fallidos_docs'][] = $datosEmpleado['no_documento'];
                $stats['errores'][] = [
                    'empleado' => $datosEmpleado['nombre'],
                    'error' => 'Sin correo registrado'
                ];
                continue;
            }

            // Generar PDF usando GeneradorPDF con detalles discriminados
            // Crear nueva instancia para cada empleado (evita acumulación de memoria)
            $generadorPDF = new GeneradorPDF('letter', 'portrait');
            $pdfContent = $generadorPDF->generarDesprendiblePDF($datosEmpleado, $documento, $subtitulo, $firmas, $detalles, $id_nomina);

            // Preparar y enviar correo
            $correo = new Correo();
            $nombreEmpleado = $datosEmpleado['nombre'];
            $asunto = "Desprendible de Nómina - {$mes} {$nomina['vigencia']}";

            $contenido = <<<HTML
                <p>Estimado(a) <strong>{$nombreEmpleado}</strong>,</p>
                <p>Adjunto encontrará su desprendible de nómina correspondiente a: <strong>{$nomina['descripcion']}</strong>.</p>
                <p>Este documento contiene el detalle de sus devengados y deducciones del período.</p>
                <p>Si tiene alguna consulta sobre su desprendible, por favor comuníquese con el área de Recursos Humanos.</p>
                <br>
                <p>Cordialmente,<br><strong>{$empresa['nombre']}</strong></p>
HTML;

            $nombreArchivo = "Desprendible_{$mes}_{$nomina['vigencia']}_{$datosEmpleado['no_documento']}.pdf";

            $resultado = $correo
                ->addDestinatario($correoEmpleado, $nombreEmpleado)
                ->setAsunto($asunto)
                ->setCuerpoHTML($correo->generarPlantillaHTML($asunto, $contenido))
                ->addAdjuntoDesdeString($pdfContent, $nombreArchivo)
                ->enviar();

            if ($resultado['success']) {
                $stats['enviados']++;
            } else {
                $stats['fallidos']++;
                $stats['fallidos_docs'][] = $datosEmpleado['no_documento'];
                $stats['errores'][] = [
                    'empleado' => $nombreEmpleado,
                    'correo' => $correoEmpleado,
                    'error' => $resultado['message']
                ];
            }

            // Limpiar memoria para el siguiente correo
            unset($pdfContent, $correo, $generadorPDF);

            // Opcional: Pequeña pausa para evitar sobrecarga del servidor SMTP
            usleep(100000); // 0.1 segundos

        } catch (Exception $e) {
            $stats['fallidos']++;
            $stats['fallidos_docs'][] = $datosEmpleado['no_documento'] ?? 'N/A';
            $stats['errores'][] = [
                'empleado' => $datosEmpleado['nombre'] ?? 'Desconocido',
                'error' => $e->getMessage()
            ];
        }
    }

    // Convertir array de documentos fallidos a cadena separada por comas
    $stats['fallidos_docs'] = implode(', ', $stats['fallidos_docs']);

    // Preparar respuesta
    if ($stats['enviados'] > 0) {
        $mensaje = "Se enviaron {$stats['enviados']} de {$stats['total']} desprendibles correctamente.";
        if ($stats['sin_correo'] > 0) {
            $mensaje .= " ({$stats['sin_correo']} empleados sin correo registrado)";
        }
        if ($stats['fallidos'] > 0) {
            $mensaje .= " ({$stats['fallidos']} envíos fallidos)";
        }

        echo json_encode([
            'status' => 'ok',
            'msg' => $mensaje,
            'stats' => $stats
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'msg' => 'No se pudo enviar ningún desprendible',
            'stats' => $stats
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Error: ' . $e->getMessage()
    ]);
}
