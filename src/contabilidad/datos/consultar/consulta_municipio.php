<?php

include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
if (isset($_POST['search'])) {
    $search = $_POST['search'];
    // Consulta del muncipio asociado a la empresa
    $sql = "SELECT
                `tb_sedes`.`nom_sede`
                , `tb_municipios`.`nom_municipio`
                , `tb_sedes`.`id_municipio`
                , `tb_sedes`.`id_sede`
            FROM
                `tb_sedes`
                INNER JOIN `tb_municipios` 
                    ON (`tb_sedes`.`id_municipio` = `tb_municipios`.`id_municipio`)
            WHERE (`tb_municipios`.`nom_municipio` LIKE '%$search%')
            GROUP BY `tb_sedes`.`id_municipio`";
    $sql = $cmd->prepare($sql);
    $sql->execute();
    $obj = $sql->fetchAll();
    $response = [];
    if (!empty($obj)) {
        foreach ($obj as $row) {
            $response[] = array("value" => $row['id_municipio'], "label" => $row['nom_municipio']);
        }
    } else {
        $response[] = array("value" => "0", "label" => "No se encontraron resultados...");
    }
    echo json_encode($response);
}
exit;
