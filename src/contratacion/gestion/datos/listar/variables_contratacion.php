<?php

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}

include_once '../../../../../config/autoloader.php';

use Config\Clases\Conexion;

$cmd = Conexion::getConexion();

try {
    $sql = "SELECT
                `variable`, `tipo`, `contexto`, `ejemplo`
            FROM `ctt_variables_forms`";
    $rs = $cmd->query($sql);
    $variables = $rs->fetchAll(PDO::FETCH_ASSOC);
$rs->closeCursor();
unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
// Define the output file name and headers for CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=variables_contratacion.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Output column headers
fputcsv($output, ['Variable', 'Tipo', 'Descripcion', 'ejemplo'], ';');

// Output rows
foreach ($variables as $fila) {
    fputcsv($output, [
        $fila['variable'],
        $fila['tipo'] == '1' ? 'Texto' : 'Fila',
        mb_convert_encoding($fila['contexto'], 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding($fila['ejemplo'], 'ISO-8859-1', 'UTF-8'),
    ], ';');
}

// Close the output stream
fclose($output);
exit();
