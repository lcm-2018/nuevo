<?php

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}

include_once '../../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;
use Config\Clases\Conexion;

$cmd = Conexion::getConexion();

try {
    $sql = "SELECT
                `tb_tipo_bien_servicio`.`id_tipo_b_s`
                , `tb_tipo_compra`.`tipo_compra`
                , `tb_tipo_bien_servicio`.`tipo_bn_sv`
                , `pto_cargue`.`cod_pptal`
                , `ctt_escala_honorarios`.`val_honorarios`
                , `ctt_escala_honorarios`.`val_hora`
                , `ctt_escala_honorarios`.`vigencia`
            FROM
                `tb_tipo_bien_servicio`
            INNER JOIN `tb_tipo_compra` 
                ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_compra`.`id_tipo`)
            LEFT JOIN `ctt_escala_honorarios`
                ON(`tb_tipo_bien_servicio`.`id_tipo_b_s` = `ctt_escala_honorarios`.`id_tipo_b_s`)
            LEFT JOIN `pto_cargue`
                ON(`ctt_escala_honorarios`.`cod_pptal` = `pto_cargue`.`id_cargue`)
            ORDER BY `tb_tipo_compra`.`tipo_compra`, `tb_tipo_bien_servicio`.`tipo_bn_sv` ASC";
    $rs = $cmd->query($sql);
    $tipo = $rs->fetchAll(PDO::FETCH_ASSOC);
$rs->closeCursor();
unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
// Define the output file name and headers for CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=homologacion_escala_honorarios.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Output column headers
fputcsv($output, ['id_tipo_servicio', 'tipo_compra', 'tipo_servicio', 'codigo_presupuestal', 'Vigencia'], ';');

foreach ($tipo as $fila) {
    fputcsv($output, [
        $fila['id_tipo_b_s'],
        mb_convert_encoding($fila['tipo_compra'], 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding($fila['tipo_bn_sv'], 'ISO-8859-1', 'UTF-8'),
        $fila['cod_pptal'],
        $fila['vigencia']
    ], ';');
}
fclose($output);
exit();
