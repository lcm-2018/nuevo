<?php
$data = file_get_contents("php://input");
include '../../../conexion.php';
try {
    $pdo = \Config\Clases\Conexion::getConexion();

    $query = $pdo->prepare("SELECT
        `pto_documento`.`id_doc`
        , `pto_documento`.`id_tercero`
        , `pto_documento`.`fecha`
        , `pto_documento`.`objeto`
        , `z_terceros`.`nombre`
    FROM
        `pto_documento`
        INNER JOIN `z_terceros` 
            ON (`pto_documento`.`id_tercero` = `z_terceros`.`num_id`)
    WHERE (`pto_documento`.`id_doc` =:id);");
    $query->bindParam(":id", $data);
    $query->execute();
    $resultado = $query->fetch(PDO::FETCH_ASSOC);
    echo json_encode($resultado);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
