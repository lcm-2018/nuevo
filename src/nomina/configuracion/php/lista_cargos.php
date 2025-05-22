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


use Src\Nomina\Configuracion\Php\Clases\Cargos;
use Src\Common\Php\Clases\Permisos;
use Src\Common\Php\Clases\Valores;


$sql = new Cargos();
$permisos = new Permisos();
$pesos = new Valores();

$opciones = $permisos->PermisoOpciones($id_user);
$obj = $sql->getCargos($start, $length, $val_busca, $col, $dir);
$totalRecordsFilter = $sql->getRegistrosFilter($val_busca);
$totalRecords = $sql->getRegistrosTotal();

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $actualizar = $eliminar = '';
        $id = $o['id_cargo'];
        if ($permisos->PermisosUsuario($opciones, 5114, 3) || $id_rol == 1) {
            $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Actualizar valor concepto"><span class="fas fa-pencil-alt"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5114, 4) || $id_rol == 1) {
            $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar concepto"><span class="fas fa-trash-alt"></span></button>';
        }
        $datos[] = array(
            'id' => $id,
            'codigo' => $o['id_codigo'],
            'cargo' => $o['descripcion_carg'],
            'grado' => $o['grado'],
            'perfil' => $o['perfil_siho'],
            'nombramiento' => $o['tipo'],
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
