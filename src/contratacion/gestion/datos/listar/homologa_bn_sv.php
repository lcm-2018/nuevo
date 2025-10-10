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
                `cbs`.`id_b_s`, `tc`.`tipo_compra`, `tb`.`tipo_bn_sv`, `cbs`.`bien_servicio`,
                `ccbs`.`cod_unspsc`, `ccbs`.`cod_cuipo`, `ccbs`.`cod_siho`,
                `ccbs`.`honorarios`, `ccbs`.`horas`, `ccbs`.`vigencia`
            FROM
                `ctt_bien_servicio` `cbs`
                INNER JOIN `tb_tipo_bien_servicio` `tb`
                    ON (`cbs`.`id_tipo_bn_sv` = `tb`.`id_tipo_b_s`)
                INNER JOIN `tb_tipo_compra` `tc`
                    ON (`tb`.`id_tipo` = `tc`.`id_tipo`)
                LEFT JOIN `ctt_clasificacion_bn_sv` `ccbs`
                    ON (`ccbs`.`id_b_s` = `cbs`.`id_b_s`)
            ORDER BY `tb`.`tipo_bn_sv`, `cbs`.`bien_servicio` ASC";
    $rs = $cmd->query($sql);
    $bien_servicio = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

// Define the output file name and headers for CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=homologacion.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Output column headers
fputcsv($output, ['id_b_s', 'tipo_compra', 'tipo_bn_sv', 'bien_servicio', 'Honorarios', 'horas', 'cod_unspsc', 'cod_cuipo', 'cod_siho', 'vigencia'], ';');

// Output rows
foreach ($bien_servicio as $fila) {
    fputcsv($output, [
        $fila['id_b_s'],
        mb_convert_encoding($fila['tipo_compra'], 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding($fila['tipo_bn_sv'], 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding($fila['bien_servicio'], 'ISO-8859-1', 'UTF-8'),
        $fila['honorarios'],
        $fila['horas'],
        $fila['cod_unspsc'],
        $fila['cod_cuipo'],
        $fila['cod_siho'],
        $fila['vigencia']
    ], ';');
}

// Close the output stream
fclose($output);
exit();
