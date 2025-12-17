<?php
$data = file_get_contents("php://input");
include '../../../../config/autoloader.php';
try {
    $pdo = \Config\Clases\Conexion::getConexion();
    $query = $pdo->prepare("SELECT
                `pto_documento_detalles`.`id_detalle`
                , `pto_documento_detalles`.`id_documento`
                , `pto_documento_detalles`.`tipo_mov`
                , `pto_documento_detalles`.`rubro`
                , `pto_documento_detalles`.`valor`
                , `pto_cargue`.`nom_rubro`
                , `pto_cargue`.`tipo_dato`
            FROM
                `pto_documento_detalles`
                INNER JOIN `pto_cargue` 
                    ON (`pto_cargue`.`cod_pptal` = `pto_documento_detalles`.`rubro`)
            WHERE (`pto_documento_detalles`.`id_detalle` =:id);");
    $query->bindParam(":id", $data);
    $query->execute();
    $resultado = $query->fetch(PDO::FETCH_ASSOC);
    echo json_encode($resultado);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
