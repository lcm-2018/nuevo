<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

include_once '../../../config/autoloader.php';

use Config\Clases\Conexion;
use Src\Common\Php\Clases\GeneradorPDF;
use PDO;

$id_contrato = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_contrato <= 0) {
    exit('ID de contrato inválido.');
}

$conexion = Conexion::getConexion();

// Traer info del contrato y minuta
$sql = "SELECT c.codigo_contrato, c.objeto_contrato, c.fec_inicio, c.fec_fin, c.valor_total,
               t.nom_tercero as contratista, t.nit_tercero,
               p.codigo_proceso,
               m.contenido_html, m.version
        FROM ctt_contratos_new c
        LEFT JOIN tb_terceros t ON c.id_tercero = t.id_tercero
        LEFT JOIN ctt_procesos_new p ON c.id_proceso = p.id_proceso
        LEFT JOIN ctt_minutas m ON c.id_contrato = m.id_contrato
        WHERE c.id_contrato = ?
        ORDER BY m.version DESC LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->execute([$id_contrato]);
$contrato = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contrato) {
    exit('Contrato no encontrado.');
}

$htmlMinuta = $contrato['contenido_html'] ?: '<p>El contrato aún no tiene una minuta generada.</p>';

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato {$contrato['codigo_contrato']}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #333; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; padding: 0; }
        .metadata { margin-bottom: 20px; }
        .metadata table { width: 100%; border-collapse: collapse; }
        .metadata th, .metadata td { padding: 5px; text-align: left; border-bottom: 1px solid #ccc; }
        .metadata th { background-color: #f5f5f5; width: 30%; }
        .minuta-content { margin-top: 30px; text-align: justify; }
        .footer { position: fixed; bottom: -30px; left: 0px; right: 0px; height: 30px; text-align: center; font-size: 9pt; color: #666; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h2>CONTRATO DE PRESTACIÓN DE SERVICIOS / ADQUISICIÓN</h2>
        <p><strong>CÓDIGO:</strong> {$contrato['codigo_contrato']}</p>
    </div>

    <div class="metadata">
        <table>
            <tr><th>Contratista</th><td>{$contrato['contratista']} (NIT: {$contrato['nit_tercero']})</td></tr>
            <tr><th>Proceso Relacionado</th><td>{$contrato['codigo_proceso']}</td></tr>
            <tr><th>Fechas</th><td>Desde {$contrato['fec_inicio']} hasta {$contrato['fec_fin']}</td></tr>
            <tr><th>Valor Total</th><td>$ {$contrato['valor_total']}</td></tr>
            <tr><th>Objeto</th><td>{$contrato['objeto_contrato']}</td></tr>
            <tr><th>Versión de Minuta</th><td>v{$contrato['version']}</td></tr>
        </table>
    </div>

    <div class="minuta-content">
        <h3>MINUTA DEL CONTRATO</h3>
        <hr>
        {$htmlMinuta}
    </div>

    <!-- Si quisieran mostrar firmas, se pondrían aquí abajo -->
</body>
</html>
HTML;

$pdf = new GeneradorPDF('letter', 'portrait');
$pdfContent = $pdf->generarDesdeHTML($html);
$pdf->mostrarEnNavegador($pdfContent, "Contrato_{$contrato['codigo_contrato']}.pdf");
