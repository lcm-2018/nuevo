<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

// Columnas del formato
$columnas = [
    'DOCUMENTO',
    'PENSION_EMPLEADO',
    'PENSION_PATRON',
    'PENSION_SOLID',
    'SALUD_EMPLEADO',
    'SALUD_PATRON',
    'CAJA',
    'RIESGOS',
    'SENA',
    'ICBF',
];

if (ob_get_length()) ob_end_clean();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="formato_planilla.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

$out = fopen('php://output', 'w');

// BOM para que Excel abra el CSV con tildes/ñ correctamente
fputs($out, "\xEF\xBB\xBF");

// Encabezados
fputcsv($out, $columnas, ';');

// Una fila vacía de ejemplo
fputcsv($out, array_fill(0, count($columnas), ''), ';');

fclose($out);
exit;
