<?php

include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
if (isset($_POST['search'])) {
    $search = '%' . $_POST['search'] . '%';
    $sql = "SELECT `nit_tercero`, `id_tercero_api` 
            FROM `tb_terceros` 
            WHERE `nit_tercero` LIKE :search1 OR id_tercero_api LIKE :search2";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([':search1' => $search, ':search2' => $search]);
    $rows = $stmt->fetchAll();
    $response = [];
    if (!empty($rows)) {
        foreach ($rows as $row) {
            $response[] = array("value" => $row['nit_tercero'], "label" => $row['id_tercero_api']);
        }
    } else {
        $response[] = array("value" => "0", "label" => "No se encontraron resultados...");
    }
    echo json_encode($response);
}
exit;
