<?php
include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
$_post = json_decode(file_get_contents('php://input'), true);
$id_retencion = $_post['id'] ?? 0;
$valor_base = $_post['base'];
$valor_iva = $_post['iva'];
$descuento = 0;
// Consultar tipo de retencion de tabla ctb_retenciones
$sql = "SELECT id_retencion_tipo FROM ctb_retenciones WHERE id_retencion={$id_retencion}";
$rs = $cmd->query($sql);
$retencion = $rs->fetch();
$id_retencion_tipo = $retencion['id_retencion_tipo'] ?? 0;
// Para retención en la fuente
// Consultar los rangos para aplicar la tarifa que corresponda a la base
$sql = "SELECT 
            `valor_base`, `valor_tope`, `tarifa`, `id_rango`
        FROM 
            `ctb_retencion_rango` 
        WHERE `id_retencion` = $id_retencion AND `valor_base` <= $valor_base AND (`valor_tope` = 0 OR `valor_tope` >= $valor_base)";
$res = $cmd->query($sql);
$rango = $res->fetch();
// Consulto el tercero de acuerdo al tipo de retencion
$sql = "SELECT `id_tercero` FROM `ctb_retencion_tipo` WHERE `id_retencion_tipo` = $id_retencion_tipo";
$res = $cmd->query($sql);
$tercero = $res->fetch();
$terceroapi = !empty($tercero) ? $tercero['id_tercero'] : 0;
if ($id_retencion_tipo  == 1) {
    $descuento = $valor_base * $rango['tarifa'];
}
// Para retención en el IVA
if ($id_retencion_tipo  == 2) {
    $descuento = $valor_iva * $rango['tarifa'];
}
// Para retención en el ICA
if ($id_retencion_tipo  == 3) {
    $descuento = $valor_base * $rango['tarifa'];
}
if ($id_retencion_tipo == 6) {
    $descuento = $rango['valor_base'];
}
if ($id_retencion_tipo == 5) {
    $descuento =  $valor_base * $rango['tarifa'];
}

if ($id_retencion_tipo == 8) {
    $descuento =  $valor_base * $rango['tarifa'];
}
$response[] = array("value" => "ok", "desc" => $descuento, "tarifa" => $rango['tarifa'] ?? 0, "terceroapi" => $terceroapi, "id_rango" => $rango['id_rango'] ?? 0);
echo json_encode($response);
exit;
