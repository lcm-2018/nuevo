<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$start  =                  isset($_POST['start'])  ? intval($_POST['start'])  : 0;
$length =                  isset($_POST['length']) ? intval($_POST['length']) : 10;
$col    =                  $_POST['order'][0]['column'] + 1;
$dir    =                  $_POST['order'][0]['dir'];
$_POST['search']['id'] =   $_POST['id_empleado'];
$busca  =                  $_POST['search'];

$id_rol  = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Src\Nomina\Empleados\Php\Clases\Otros_Devengados;
use Src\Common\Php\Clases\Permisos;
use Src\Common\Php\Clases\Valores;

$sql     = new Otros_Devengados();
$permisos = new Permisos();
$Valores = new Valores();

$opciones            = $permisos->PermisoOpciones($id_user);
$obj                 = $sql->getRegistrosDT($start, $length, $busca, $col, $dir);
$totalRecordsFilter  = $sql->getRegistrosFilter($busca);
$totalRecords        = $sql->getRegistrosTotal($busca);

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_devengado'];
        $actualizar = $eliminar = '';
        if ($permisos->PermisosUsuario($opciones, 5101, 3) || $id_rol == 1) {
            $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Actualizar"><span class="fas fa-pencil-alt fa-sm"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5101, 4) || $id_rol == 1) {
            $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar"><span class="fas fa-trash-alt fa-sm"></span></button>';
        }
        if ($o['estado'] == '0') {
            $actualizar = $eliminar = '';
        }
        $estado = $o['estado'] == 1
            ? '<a href="javascript:CambiaEstadoDevengado(' . $id . ',0,\'otros_devengados\');" title="Inactivar"><i class="fas fa-toggle-on fa-lg text-success"></i></a>'
            : '<a href="javascript:CambiaEstadoDevengado(' . $id . ',1,\'otros_devengados\');" title="Activar"><i class="fas fa-toggle-off fa-lg text-secondary"></i></a>';

        $datos[] = [
            'id'       => $id,
            'concepto' => $o['concepto'],
            'tipo'     => $o['descripcion'],
            'valor'    => $Valores->Pesos($o['valor']),
            'inicia'   => $o['fec_inicia'],
            'fin'      => $o['fec_fin'],
            'aportado' => $Valores->Pesos($o['aportado']),
            'estado'   => '<div class="text-center">' . $estado . '</div>',
            'acciones' => '<div class="text-center">' . $actualizar . $eliminar . '</div>',
        ];
    }
}
$data = [
    'data'            => $datos,
    'recordsFiltered' => $totalRecordsFilter,
    'recordsTotal'    => $totalRecords,
];
echo json_encode($data);
