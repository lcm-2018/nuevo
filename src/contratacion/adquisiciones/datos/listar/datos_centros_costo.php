<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
$id_sede = isset($_POST['id_sede']) ? $_POST['id_sede'] : exit('Acción no permitida');
include_once '../../../../../config/autoloader.php';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $sql = "SELECT
                `far_centrocosto_area`.`id_area`, `tb_centrocostos`.`nom_centro`
            FROM
                `far_centrocosto_area`
                INNER JOIN `tb_centrocostos` 
                    ON (`far_centrocosto_area`.`id_centrocosto` = `tb_centrocostos`.`id_centro`)
            WHERE `far_centrocosto_area`.`id_sede` = '$id_sede' 
            ORDER BY `tb_centrocostos`.`nom_centro` ASC";
    $rs = $cmd->query($sql);
    $centros_costo = $rs->fetchAll();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$res = '';
if (!empty($centros_costo)) {
    $res .=  '<option value="0">--Seleccione--</option>';
    foreach ($centros_costo as $centro_costo) {
        $res .= '<option value="' . $centro_costo['id_area'] . '">' . $centro_costo['nom_centro'] . '</option>';
    }
} else {
    $res .=  '<option value="0">--Sede sin centros de costo--</option>';
}

echo $res;
