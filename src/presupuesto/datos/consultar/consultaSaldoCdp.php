<?php
session_start();
// Realiza la suma del valor total asignado a un CDP
include '../../../../config/autoloader.php';
include '../../../financiero/consultas.php';
$cmd = \Config\Clases\Conexion::getConexion();
$_post = json_decode(file_get_contents('php://input'), true);

$vigencia = $_SESSION['vigencia'] ?? date('Y');
$rubro = $_post['rubro'] ?? '';

// consultar id_cargue y valor_aprobado en pto_cargue
$sql = "SELECT id_cargue, valor_aprobado FROM pto_cargue WHERE cod_pptal = :rubro AND vigencia = :vigencia";
$stmt = $cmd->prepare($sql);
$stmt->execute([':rubro' => $rubro, ':vigencia' => $vigencia]);
$valor_aprobado = $stmt->fetch();

$id_cargue = $valor_aprobado['id_cargue'] ?? 0;
$total = (float)($valor_aprobado['valor_aprobado'] ?? 0);
$comprometido = 0;

if ($id_cargue > 0) {
    $saldos = saldoRubroGastos($vigencia, $id_cargue, $cmd);
    if (!empty($saldos)) {
        foreach ($saldos as $s) {
            $valor = (float)($s['debito'] - $s['credito']);
            $total -= $valor;
            if ($s['id_tipo_mod'] == '0') {
                $comprometido += (float)$s['debito'];
            }
        }
    }
}

$response[] = array("total" => $total, "liberado" => $comprometido);
echo json_encode($response);
exit;
