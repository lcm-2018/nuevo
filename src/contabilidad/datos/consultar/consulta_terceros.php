<?php

include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
if (isset($_POST['search'])) {
    $search = mysqli_real_escape_string($conexion, $_POST['search']);
    $sql = "SELECT 
                `nit_tercero`, `id_tercero_api` 
            FROM `tb_terceros` 
            WHERE `nit_tercero` LIKE '$search%' OR `id_tercero_api` LIKE '$search%'";
    $res = $cmd->query($sql);
    if ($res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $response[] = array("value" => $row['nit_tercero'], "label" => $row['id_tercero_api']);
        }
    } else {
        $response[] = array("value" => "0", "label" => "No se encontraron resultados...");
    }
    echo json_encode($response);
}
exit;
