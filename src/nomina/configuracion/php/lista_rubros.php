<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$vigencia =     $_SESSION['vigencia'];
$start =        isset($_POST['start']) ? intval($_POST['start']) : 0;
$length =       isset($_POST['length']) ? intval($_POST['length']) : 10;
$val_busca =    $_POST['search']['value'] ?? '';
$col =          $_POST['order'][0]['column'] + 1;
$dir =          $_POST['order'][0]['dir'];

$id_rol =         $_SESSION['rol'];
$id_user =        $_SESSION['id_user'];
$esPtoCaracter =  (($_SESSION['caracter'] ?? 0) == 1 && ($_SESSION['pto'] ?? 0) == 1);


use Src\Nomina\Configuracion\Php\Clases\Rubros;
use Src\Common\Php\Clases\Permisos;
use Src\Common\Php\Clases\Valores;


$sql =      new Rubros();
$permisos = new Permisos();
$pesos =    new Valores();

$opciones =             $permisos->PermisoOpciones($id_user);
$obj =                  $sql->getRubros($start, $length, $val_busca, $col, $dir);
$totalRecordsFilter =   $sql->getRegistrosFilter($val_busca);
$totalRecords =         $sql->getRegistrosTotal();

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_relacion'];
        $actualizar = $eliminar = '';
        if ($permisos->PermisosUsuario($opciones, 5114, 3) || $id_rol == 1) {
            $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Actualizar valor concepto"><span class="fas fa-pencil-alt"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5114, 4) || $id_rol == 1) {
            $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar concepto"><span class="fas fa-trash-alt"></span></button>';
        }
        $fila = [
            'id'       => $id,
            'tipo'     => $o['nombre'],
            'cod_ra'   => '<div class="text-start">' . $o['cod_admin'] . '</div>',
            'nom_ra'   => $o['nom_admin'],
            'cod_ro'   => '<div class="text-start">' . $o['cod_opera'] . '</div>',
            'nom_ro'   => $o['nom_opera'],
            'acciones' => '<div class="text-center">' . $actualizar . $eliminar . '</div>',
        ];
        if ($esPtoCaracter) {
            $fila['ccosto'] = $o['nom_ccosto'] ?? '';
        }
        $datos[] = $fila;
    }
}
$data = [
    'data' =>               $datos,
    'recordsFiltered' =>    $totalRecordsFilter,
    'recordsTotal' =>       $totalRecords,
];
echo json_encode($data);
