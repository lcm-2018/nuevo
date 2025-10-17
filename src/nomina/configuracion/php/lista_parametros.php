<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$vigencia = $_SESSION['vigencia'];
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$val_busca = $_POST['search']['value'] ?? '';
$col = $_POST['order'][0]['column'] + 1;
$dir = $_POST['order'][0]['dir'];

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];


use Src\Nomina\Configuracion\Php\Clases\Parametros;
use Src\Common\Php\Clases\Permisos;
use Src\Common\Php\Clases\Valores;


$sql = new Parametros();
$permisos = new Permisos();
$pesos = new Valores();

$opciones = $permisos->PermisoOpciones($id_user);
$obj = $sql->getParametros($vigencia, $start, $length, $val_busca, $col, $dir);
$totalRecordsFilter = $sql->getRegistrosFilter($vigencia, $val_busca);
$totalRecords = $sql->getRegistrosTotal();

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $cp) {
        $actualizar = $eliminar = '';
        $id = $cp['id_valxvig'];
        if ($permisos->PermisosUsuario($opciones, 5114, 3) || $id_rol == 1) {
            $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Actualizar valor concepto"><span class="fas fa-pencil-alt"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5114, 4) || $id_rol == 1) {
            $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar concepto"><span class="fas fa-trash-alt"></span></button>';
        }
        $datos[] = array(
            'id' => $cp['id_concepto'],
            'concepto' => mb_strtoupper($cp['concepto']),
            'valor' => '<div class="text-end">' . $pesos->Pesos($cp['valor']) . '</div>',
            'botones' => '<div class="text-center">' . $actualizar . $eliminar . '</div>'
        );
    }
}
$data = [
    'data' => $datos,
    'recordsFiltered' => $totalRecordsFilter,
    'recordsTotal' => $totalRecords,
];
echo json_encode($data);
