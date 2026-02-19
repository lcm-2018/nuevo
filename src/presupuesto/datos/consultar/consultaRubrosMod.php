<?php
session_start();
include '../../../../config/autoloader.php';

$search = isset($_POST['search']) ? $_POST['search'] : exit('Acceso denegado');
$id_pto = isset($_POST['id_pto']) ? $_POST['id_pto'] : 0;
$id_vigencia = $_SESSION['id_vigencia'];
$where = $id_pto > 0 ? " AND `pc`.`id_pto` = $id_pto" : "";
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `pc`.`id_cargue`, `pc`.`cod_pptal`, `pc`.`nom_rubro`, `pc`.`tipo_dato`
            FROM
                `pto_cargue` AS `pc`
                INNER JOIN `pto_presupuestos` AS `pp`
                    ON (`pc`.`id_pto` = `pp`.`id_pto`)
            WHERE `pp`.`id_vigencia` = $id_vigencia AND (`pc`.`cod_pptal` LIKE '$search%' OR `pc`.`nom_rubro` LIKE '$search%') $where";
    $rs = $cmd->query($sql);
    $datos = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$response = [];
if (!empty($datos)) {
    foreach ($datos as $row) {
        $response[] = array("value" => $row['id_cargue'], "label" => $row['cod_pptal'] . " - " . $row['nom_rubro'], "tipo" => $row['tipo_dato']);
    }
} else {
    $response[] = array("value" => "0", "label" => "No encontrado...", "tipo" => "3");
}
echo json_encode($response);
