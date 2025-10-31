<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$anulados = isset($_POST['anulados']) ? $_POST['anulados'] : 0;
$limit = "";
if ($length != -1) {
    $limit = "LIMIT $start, $length";
}
$col = $_POST['order'][0]['column'] + 1;
$dir = $_POST['order'][0]['dir'];
$dato = null;
if ($anulados == 1 || $_POST['search']['value'] != '') {
    $where = '>= 0 ';
} else {
    $where = '> 0 ';
}
$where .= $_POST['search']['value'] != '' ? "AND (`tb_terceros`.`nit_tercero` LIKE '%{$_POST['search']['value']}%' OR `tb_terceros`.`nom_tercero` LIKE '%{$_POST['search']['value']}%')" : '';

//----------- filtros--------------------------

$andwhere = " ";

if (isset($_POST['ccnit']) && $_POST['ccnit']) {
    $andwhere .= " AND tb_terceros.nit_tercero LIKE '%" . $_POST['ccnit'] . "%'";
}
if (isset($_POST['tercero']) && $_POST['tercero']) {
    $andwhere .= " AND tb_terceros.nom_tercero LIKE '%" . $_POST['tercero'] . "%'";
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `tb_terceros`.`id_tercero`
                , `tb_terceros`.`id_tercero_api`
                , `tb_terceros`.`nit_tercero`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`estado`
                , GROUP_CONCAT(`tb_tipo_tercero`.`descripcion` ORDER BY `tb_tipo_tercero`.`descripcion` SEPARATOR ', ') AS `descripcion`
            FROM
                `tb_terceros`
                LEFT JOIN `tb_rel_tercero` 
                    ON (`tb_rel_tercero`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                LEFT JOIN `tb_tipo_tercero` 
                    ON (`tb_rel_tercero`.`id_tipo_tercero` = `tb_tipo_tercero`.`id_tipo`)
            WHERE `tb_terceros`.`estado` $where $andwhere
            GROUP BY
                `tb_terceros`.`id_tercero_api`
            ORDER BY $col $dir $limit";
    $rs = $cmd->query($sql);
    $terEmpr = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                COUNT(*) AS `total`
            FROM
                `tb_terceros`";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecords = $total['total'];
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                COUNT(*) AS `total`
            FROM
                `tb_terceros`
            WHERE `tb_terceros`.`estado` $where $andwhere";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecordsFilter = $total['total'];
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$id_t = [];
foreach ($terEmpr as $l) {
    if ($l['id_tercero_api'] != '') {
        $id_t[] = $l['id_tercero_api'];
    }
}
$data = [];
if (!empty($id_t)) {
    $payload = json_encode($id_t);
    $api = \Config\Clases\Conexion::Api();
    $url = $api . 'terceros/datos/res/lista/terceros';
    $ch = curl_init($url);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch);
    curl_close($ch);
    $terceros = json_decode($result, true);
    foreach ($terEmpr as $t) {
        $idter = $t['nit_tercero'];
        $key = array_search($idter, array_column($terceros, 'cc_nit'));
        if ($key !== false) {
            $idT = $terceros[$key]['id_tercero'];
            if ($permisos->PermisosUsuario($opciones, 5201, 2) || $id_rol == 1) {
                $addresponsabilidad = '<button value="' . $idT . '" class="btn btn-outline-info btn-xs rounded-circle shadow me-1 responsabilidad" title="+ Responsabilidad Económica"><span class="fas fa-hand-holding-usd"></span></button>';
                $addactividad = '<button value="' . $idT . '" class="btn btn-outline-success btn-xs rounded-circle shadow me-1 actividad" title="+ Actividad Económica"><span class="fas fa-donate"></span></button>';
                $histtecero = '<button value="' . $idT . '" class="btn btn-outline-success btn-xs rounded-circle shadow me-1 historial" title="+ historial tercero"><span class="fas fa-history"></span></button>';
            } else {
                $addresponsabilidad = null;
                $addactividad = null;
                $histtecero = null;
            }
            $editar = null;

            if ($permisos->PermisosUsuario($opciones, 5201, 3) || $id_rol == 1) {
                if ($t['estado'] == '1') {
                    $editar = '<button value="' . $idter . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 editar" title="Editar"><span class="fas fa-pencil-alt"></span></button>';
                    $estado = '<a href="javascript:void(0);" data-id="' . $idT . '" title="Activo"><span class="fas fa-toggle-on fa-lg estado activo text-success" value="' . $idT . '"></span></a>';
                } else {
                    $estado = '<a href="javascript:void(0);" data-id="' . $idT . '" title="Inactivo"><span class="fas fa-toggle-off fa-lg estado inactivo text-secondary" value="' . $idT . '"></span></a>';
                }
            } else {
                $estado = $terceros[$key]['estado'];
            }
            if ($permisos->PermisosUsuario($opciones, 5201, 4) || $id_rol == 1) {
                $borrar = '<button value="' . $idT . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 borrar" title="Eliminar"><span class="fas fa-trash-alt"></span></button>';
            } else {
                $borrar = null;
            }
            if ($t['estado'] == '0') {
                $editar = $addresponsabilidad = $addactividad = $borrar = null;
            }
            $detalles = '<button class="btn btn-outline-warning btn-xs rounded-circle shadow me-1 detalles" value="' . $idT . '" title="Detalles"><span class="far fa-eye"></span></button>';
            $data[] = [
                'cc_nit' => $terceros[$key]['cc_nit'],
                'nombre_tercero' => mb_strtoupper(trim($terceros[$key]['nombre1'] . ' ' . $terceros[$key]['nombre2'] . ' ' . $terceros[$key]['apellido1'] . ' ' . $terceros[$key]['apellido2'] . ' ' . $terceros[$key]['razon_social'])),
                //'razon_social' => $terceros[$key]['razon_social'],
                'tipo' => $t['descripcion'],
                'municipio' => $terceros[$key]['nom_municipio'],
                'direccion' => $terceros[$key]['direccion'],
                'telefono' => $terceros[$key]['telefono'],
                'correo' => $terceros[$key]['correo'],
                'estado' => '<div class="text-center">' . $estado . '</div>',
                'botones' => '<div class="text-center">' . $editar . $addresponsabilidad . $addactividad . $detalles . $histtecero . $borrar . '</div>',
            ];
        }
    }
}

$datos = [
    'data' => $data,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $totalRecordsFilter,
];

echo json_encode($datos);
