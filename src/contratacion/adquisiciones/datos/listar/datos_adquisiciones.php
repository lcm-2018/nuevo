<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
function pesos($valor)
{
    return '$' . number_format($valor, 2, ",", ".");
}
include_once '../../../../../config/autoloader.php';

$iduser = $_SESSION['id_user'];
$id_rol = $_SESSION['rol'];

$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$col = $_POST['col'] ?? 0;
$dir = $_POST['dir'] ?? 'asc';
$length = $_POST['length'] ?? 10;

$limit = "";
if ($length != -1) {
    $limit = "LIMIT $start, $length";
}

$where = '';
if (isset($_POST['filter_Status']) && $_POST['filter_Status'] != 0) {
    $where .= " AND `ctt_adquisiciones`.`estado` = {$_POST['filter_Status']}";
}

if (isset($_POST['filter_Modalidad']) && $_POST['filter_Modalidad'] != '0') {
    $where .= " AND `ctt_adquisiciones`.`id_modalidad` = {$_POST['filter_Modalidad']}";
}

if (isset($_POST['filter_Valor']) && $_POST['filter_Valor'] != '') {
    $where .= " AND `ctt_adquisiciones`.`valor` LIKE '%{$_POST['filter_Valor']}%'";
}
if (isset($_POST['filter_Fecha']) && $_POST['filter_Fecha'] != '') {
    $where .= " AND `ctt_adquisiciones`.`fecha_adquisicion` = '{$_POST['filter_Fecha']}'";
}
if (isset($_POST['filter_Objeto']) && $_POST['filter_Objeto'] != '') {
    $where .= " AND `ctt_adquisiciones`.`objeto` LIKE '%{$_POST['filter_Objeto']}%'";
}

if (isset($_POST['filter_Tercero']) && $_POST['filter_Tercero'] != '') {
    $where .= " AND `tb_terceros`.`nom_tercero` LIKE '%{$_POST['filter_Tercero']}%'";
}



$permisos = new \Src\Common\Php\Clases\Permisos();
$opciones = $permisos->PermisoOpciones($iduser);

$vigencia = $_SESSION['vigencia'];
$anulados = $_POST['anulados'] ?? 0;

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `id`, `descripcion` FROM `ctt_estado_adq`";
    $rs = $cmd->query($sql);
    $estado_adq = $rs->fetchAll();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `id_area` FROM `tb_area_responsable` WHERE `id_user` = $iduser GROUP BY `id_area`";
    $rs = $cmd->query($sql);
    $areas = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

if ($id_rol == '1') {
    $usuario = '';
} else {
    if (!empty($areas)) {
        $areas = array_column($areas, 'id_area');
        $areas = implode(',', $areas);
        $usuario = " AND `ctt_adquisiciones`.`id_area` IN ($areas)";
    } else {
        $usuario = " AND `ctt_adquisiciones`.`id_user_reg` =" . $iduser;
    }
}


try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT 
                `modalidad`
                , `ctt_adquisiciones`.`id_adquisicion`
                , `ctt_adquisiciones`.`val_contrato`
                , `ctt_adquisiciones`.`estado`
                , `ctt_adquisiciones`.`fecha_adquisicion`
                , `ctt_adquisiciones`.`objeto`
                , `tb_terceros`.`id_tercero_api`
                , `tb_terceros`.`nom_tercero`
                , `pto_cdp`.`id_pto_cdp`
                , `pto_cdp`.`estado` AS `status`
            FROM
                `ctt_adquisiciones`
            INNER JOIN `ctt_modalidad` 
                ON (`ctt_adquisiciones`.`id_modalidad` = `ctt_modalidad`.`id_modalidad`)
            LEFT JOIN `tb_terceros`
                ON (`ctt_adquisiciones`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            LEFT JOIN `pto_cdp`
                ON (`pto_cdp`.`id_pto_cdp` = `ctt_adquisiciones`.`id_cdp`)
            WHERE `vigencia` = '$vigencia' $usuario $where";
    $rs = $cmd->query($sql);
    $ladquis = $rs->fetchAll(PDO::FETCH_ASSOC);
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
                `ctt_adquisiciones`
            INNER JOIN `ctt_modalidad` 
                ON (`ctt_adquisiciones`.`id_modalidad` = `ctt_modalidad`.`id_modalidad`)
            LEFT JOIN `tb_terceros`
                ON (`ctt_adquisiciones`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            LEFT JOIN `pto_cdp`
                ON (`pto_cdp`.`id_pto_cdp` = `ctt_adquisiciones`.`id_cdp`)
            WHERE `vigencia` = '$vigencia' $usuario $where";
    $rs = $cmd->query($sql);
    $totalRecordsFilter = $rs->fetch()['total'];
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
                `ctt_adquisiciones`
            INNER JOIN `ctt_modalidad` 
                ON (`ctt_adquisiciones`.`id_modalidad` = `ctt_modalidad`.`id_modalidad`)
            LEFT JOIN `tb_terceros`
                ON (`ctt_adquisiciones`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            LEFT JOIN `pto_cdp`
                ON (`pto_cdp`.`id_pto_cdp` = `ctt_adquisiciones`.`id_cdp`)
            WHERE `vigencia` = '$vigencia' $usuario";
    $rs = $cmd->query($sql);
    $totalRecords = $rs->fetch()['total'];
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

if (!empty($ladquis)) {
    foreach ($ladquis as $la) {
        $id_adq = $la['id_adquisicion'];
        $editar = null;
        $detalles = null;
        $anular = null;
        $duplicar = null;
        if ($la['estado'] <= '6' && ($permisos->PermisosUsuario($opciones, 5302, 3) || $id_rol == 1) && ($la['status'] == '0' || $la['id_pto_cdp'] == '')) {
            $anular = '<a value="' . $id_adq . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 anular" title="Anular"><span class="fas fa-ban"></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5302, 3) || $id_rol == 1) {
            $detalles = '<a value="' . $id_adq . '" class="btn btn-outline-warning btn-xs rounded-circle shadow me-1 detalles" title="Detalles"><span class="fas fa-eye"></span></a>';
            if ($la['estado'] <= 2) {
                $editar = '<a value="' . $id_adq . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 editar" title="Editar"><span class="fas fa-pencil-alt"></span></a>';
            }
        }
        if ($la['estado'] >= '6') {
            $duplicar = '<a value="' . $id_adq . '" class="btn btn-outline-info btn-xs rounded-circle shadow me-1 duplicar" title="Duplicar"><span class="fas fa-clone"></span></a>';
        }
        $accion = null;
        switch ($la['estado']) {
            case 0:
                $accion = '<a class="btn btn-outline-secondary btn-xs rounded-circle shadow me-1 disabled" title="Orden sin productos"><span class="fas fa-sign-out-alt"></span></a>';
                break;
                /*
        case 1:
            $accion = '<a class="btn btn-outline-secondary btn-xs rounded-circle shadow me-1 disabled" title="Orden sin productos"><span class="fas fa-sign-out-alt"></span></a>';
            break;
        case 2:
            $accion = '<a value="' . $id_adq . '" class="btn btn-outline-success btn-xs rounded-circle shadow me-1 enviar" title="Enviar cotización"><span class="fas fa-sign-out-alt"></span></a>';
            break;
        case 3:
            $accion = '<a value="' . $id_adq . '" class="btn btn-outline-info btn-xs rounded-circle shadow me-1 bajar" title="Bajar cotización"><span class="fas fa-chevron-circle-down"></span></a>';
            break;
        case 4:
            $accion = '<a value="' . $id_adq . '" class="btn btn-outline-warning btn-xs rounded-circle shadow me-1 comprobar" title="Ver cotización de terceros"><span class="fas fa-clipboard-check"></span></a>';
            break;
        case 7:
            $accion = '<a value="' . $id_adq . '" class="btn btn-outline-success btn-xs rounded-circle shadow me-1 envContrato" title="Enviar Contrato"><span class="fas fa-file-upload"></span></a>';
            break;
        */
        }
        if (($permisos->PermisosUsuario($opciones, 5302, 4) || $id_rol == 1) && $la['estado'] <= 2) {
            $borrar = '<a value="' . $id_adq . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 borrar" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
        } else {
            $borrar = null;
        }
        if ($la['estado'] == '99') {
            $borrar = null;
            $editar = null;
            $detalles = '<span class="badge badge-secondary">ANULADO</span>';
            $accion = null;
            $anular = null;
        }
        $est = $la['estado'];
        $tercer = $la['nom_tercero'] ? $la['nom_tercero'] : '---';
        $key = array_search($est, array_column($estado_adq, 'id'));
        $estd = $estado_adq[$key]['descripcion'];
        $data[] = [
            'id' => $id_adq,
            'modalidad' => $la['modalidad'],
            'adquisicion' => 'ADQ-' . $id_adq,
            'valor' => '<div class="text-right">' . pesos($la['val_contrato']) . '</div>',
            'fecha' => $la['fecha_adquisicion'],
            'objeto' => $la['objeto'],
            'tercero' => $tercer,
            'estado' => $estd,
            'botones' => '<div class="text-center">' . $editar . $borrar . $detalles . $accion . $anular . $duplicar . '</div>',
        ];
    }
} else {
    $data = [];
}

$datos = [
    'data' => $data,
    'recordsFiltered'   => $totalRecordsFilter,
    'recordsTotal'      => $totalRecords,
];

echo json_encode($datos);
