<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$vigencia =                 $_SESSION['vigencia'];
$start =                    isset($_POST['start']) ? intval($_POST['start']) : 0;
$length =                   isset($_POST['length']) ? intval($_POST['length']) : 10;
$col =                      $_POST['order'][0]['column'] + 1;
$dir =                      $_POST['order'][0]['dir'];
$_POST['search']['id'] =    $_POST['id_empleado'];
$busca =                    $_POST['search'];

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];


use Src\Nomina\Empleados\Php\Clases\Vacaciones;
use Src\Common\Php\Clases\Permisos;


$sql        = new Vacaciones();
$permisos   = new Permisos();

$opciones =             $permisos->PermisoOpciones($id_user);
$obj =                  $sql->getRegistrosDT($start, $length, $busca, $col, $dir);
$totalRecordsFilter =   $sql->getRegistrosFilter($busca);
$totalRecords =         $sql->getRegistrosTotal($busca);

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_vac'];
        $actualizar = $eliminar =  $imprimir = '';
        if ($permisos->PermisosUsuario($opciones, 5101, 3) || $id_rol == 1) {
            $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Actualizar"><span class="fas fa-pencil-alt fa-sm"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5101, 4) || $id_rol == 1) {
            $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar"><span class="fas fa-trash-alt fa-sm"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5101, 6) || $id_rol == 1) {
            $imprimir = '<button data-id="' . $id . '" class="btn btn-outline-success btn-xs rounded-circle shadow me-1 imprimir" title="Imprimir"><span class="fas fa-print fa-sm"></span></button>';
        }
        if ($o['estado'] == '2') {
            $actualizar = $eliminar = '';
        }

        $datos[] = [
            'id'            => $id,
            'anticipo'      => $o['anticipo'] == '1' ? 'SI' : 'NO',
            'inicia'        => $o['fec_inicial'],
            'termina'       => $o['fec_fin'],
            'inactivo'      => $o['dias_inactivo'],
            'habiles'       => $o['dias_habiles'],
            'corte'         => $o['corte'],
            'liquidar'      => $o['dias_liquidar'],
            'acciones'      => '<div class="text-center">' . $actualizar . $eliminar . $imprimir . '</div>',
        ];
    }
}
$data = [
    'data'              => $datos,
    'recordsFiltered'   => $totalRecordsFilter,
    'recordsTotal'      => $totalRecords,
];
echo json_encode($data);
