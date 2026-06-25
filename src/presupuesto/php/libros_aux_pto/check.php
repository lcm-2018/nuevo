<?php
include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();

echo "Buscando '484' en cualquier columna de pto_cdp_detalle:\n";
$sql = "SELECT * FROM pto_cdp_detalle WHERE id_pto_cdp = 484 OR id_rubro = 484 OR valor = 484 OR valor_liberado = 484";
$rs = $cmd->query($sql);
print_r($rs->fetchAll(PDO::FETCH_ASSOC));

echo "\nRegistros en pto_cargue con id_cargue = 484:\n";
$sql = "SELECT * FROM pto_cargue WHERE id_cargue = 484";
$rs = $cmd->query($sql);
print_r($rs->fetchAll(PDO::FETCH_ASSOC));

echo "\nBuscando si hay algun CDP detalle con ese rubro sin importar el estado:\n";
$sql = "SELECT pto_cdp_detalle.*, pto_cdp.estado, pto_cdp.fecha 
        FROM pto_cdp_detalle 
        LEFT JOIN pto_cdp ON pto_cdp.id_pto_cdp = pto_cdp_detalle.id_pto_cdp 
        WHERE pto_cdp_detalle.id_rubro = 484";
$rs = $cmd->query($sql);
print_r($rs->fetchAll(PDO::FETCH_ASSOC));

?>
