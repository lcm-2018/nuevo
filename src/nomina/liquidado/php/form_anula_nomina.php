<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include_once '../../../../config/autoloader.php';

use Src\Nomina\Liquidacion\Php\Clases\Nomina;

$id_nomina = isset($_POST['id']) ? intval($_POST['id']) : 0;

if (!$id_nomina) {
    echo json_encode(['status' => 'error', 'msg' => 'Parámetros inválidos']);
    exit();
}

try {
    $nomina = (new Nomina())->getRegistro($id_nomina);
    $numero = $nomina['id_nomina'] ?? $id_nomina;
} catch (Exception $e) {
    $numero = $id_nomina;
}

$fecha_hoy = date('Y-m-d');

$html  = '<div class="px-0">';
$html .= '<form id="formAnulaDoc">';
$html .= '<input type="hidden" id="id_nomina_anul" name="id_nomina" value="' . $id_nomina . '">';
$html .= '<div class="shadow mb-3">';
$html .= '<div class="card-header py-2 text-center" style="background-color: #16a085 !important;">';
$html .= '<h5 class="mb-0" style="color: white;">';
$html .= '<i class="fas fa-lock fa-lg" style="color: #FCF3CF"></i>&nbsp;ANULACI&Oacute;N DE N&Oacute;MINA';
$html .= '</h5>';
$html .= '</div>';
$html .= '<div class="pt-3 px-3">';
$html .= '<div class="row">';
$html .= '<div class="form-group col-md-6">';
$html .= '<label for="numDocAnul" class="small">N&Oacute;MINA No.</label>';
$html .= '<input type="text" id="numDocAnul" class="form-control form-control-sm" value="' . htmlspecialchars($numero) . '" readonly>';
$html .= '</div>';
$html .= '<div class="form-group col-md-6">';
$html .= '<label for="fec_anull" class="small">FECHA</label>';
$html .= '<input type="date" name="fec_anull" id="fec_anull" class="form-control form-control-sm bg-input" value="' . $fecha_hoy . '">';
$html .= '</div>';
$html .= '</div>';
$html .= '<div class="row py-3">';
$html .= '<div class="form-group col-md-12">';
$html .= '<label for="concepto_anul" class="small">CONCEPTO</label>';
$html .= '<textarea id="concepto_anul" name="concepto_anul" class="form-control form-control-sm py-0 bg-input" rows="3" required></textarea>';
$html .= '</div>';
$html .= '</div>';
$html .= '</div>';
$html .= '</div>';
$html .= '<div class="text-end">';
$html .= '<button type="button" class="btn btn-primary btn-sm" id="btnConfirmarAnulNomina">Anular</button>';
$html .= ' <a class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</a>';
$html .= '</div>';
$html .= '</form>';
$html .= '</div>';

echo json_encode(['status' => 'ok', 'msg' => $html]);
