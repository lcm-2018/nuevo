<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include '../../../../config/autoloader.php';
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_tercero_api`, `nit_tercero`, `nom_tercero`
            FROM
                `tb_terceros`";
    $rs = $cmd->query($sql);
    $terEmpr = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $sql = "SELECT 
                `id_tercero_api`, 
                GROUP_CONCAT(`id_responsabilidad` ORDER BY `id_responsabilidad` SEPARATOR ', ') AS `responsabilidades`
            FROM `ctt_resposabilidad_terceros`
            GROUP BY `id_tercero_api`";
    $rs = $cmd->query($sql);
    $responsabilidades = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$id_t = [];
foreach ($terEmpr as $l) {
    if ($l['id_tercero_api'] > 0) {
        $id_t[] = $l['id_tercero_api'];
    }
}
$payload = json_encode($id_t);
//API URL
$api = \Config\Clases\Conexion::Api();
$url = $api . 'terceros/datos/res/lista/reportes';
$ch = curl_init($url);
//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$result = curl_exec($ch);
curl_close($ch);
$datos = json_decode($result, true);
$head = '';
if (!empty($datos)) {
    foreach ($datos[0] as $key => $value) {
        if ($key == 'resposabilidades') continue;
        $head .= '<th>' . mb_convert_encoding($key, 'UTF-8', 'ISO-8859-1') . '</th>';
    }
    $head .= '<th>Responsabilidades</th>';
} else {
    echo 'No hay datos para mostrar';
    exit();
}
$tbody = '';
foreach ($datos as $d) {
    $id_ter = $d['id_tercero'];
    $key = array_search($id_ter, array_column($responsabilidades, 'id_tercero_api'));
    $resp = $key !== false ? $responsabilidades[$key]['responsabilidades'] : '';
    $tbody .= '<tr>';
    foreach ($d as $ds => $value) {
        if ($ds == 'resposabilidades') continue;
        $tbody .= '<td>' . mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') . '</td>';
    }
    $tbody .= '<td>' . $resp . '</td>';
    $tbody .= '</tr>';
}
$tabla = <<<EOT
<table class="table-striped table-bordered table-sm nowrap" style="width:100%">
        <thead>
            <tr>$head</tr>
        </thead>
        <tbody>$tbody</tbody>
    </table>
EOT;
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
header('Content-type:application/xls');
header('Content-Disposition: attachment; filename=reporte' . $date->format('mdHms') . '.xls');
echo "\xEF\xBB\xBF";
echo $tabla;
