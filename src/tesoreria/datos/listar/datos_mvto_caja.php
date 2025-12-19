<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include_once '../../../../config/autoloader.php';


use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
include_once '../../../financiero/consultas.php';
// Div de acciones de la lista
$id_ctb_doc = $_POST['id_doc'];
$anulados = $_POST['anulados'];
$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$limit = "";
if ($length != -1) {
    $limit = "LIMIT $start, $length";
}
$col = $_POST['order'][0]['column'] + 1;
$dir = $_POST['order'][0]['dir'];
$where = $_POST['search']['value'] != '' ? "AND (`tes_caja_const`.`nombre_caja` LIKE '%{$_POST['search']['value']}%' OR `tes_caja_const`.`fecha_ini` LIKE '%{$_POST['search']['value']}%' OR  `pto_actos_admin`.`nombre`)" : '';
if ($anulados == 1 || $_POST['search']['value'] != '') {
    $where .= " AND `tes_caja_const`.`estado` >= 0";
} else {
    $where .= " AND `tes_caja_const`.`estado` > 0";
}
$dato = null;
function pesos($valor)
{
    return '$ ' . number_format($valor, 2, ',', '.');
}
$cmd = \Config\Clases\Conexion::getConexion();
$fecha_cierre = fechaCierre($vigencia, 56, $cmd);
try {
    $sql = "SELECT
                `tes_caja_const`.`id_caja_const`
                , `pto_actos_admin`.`nombre` AS `acto`
                , `tes_caja_const`.`num_acto`
                , `tes_caja_const`.`nombre_caja`
                , `tes_caja_const`.`fecha_ini`
                , `tes_caja_const`.`fecha_acto`
                , `tes_caja_const`.`valor_total`
                , `tes_caja_const`.`valor_minimo`
                , `tes_caja_const`.`num_poliza`
                , `tes_caja_const`.`porcentaje`
                , `tes_caja_const`.`estado`
            FROM
                `tes_caja_const`
                INNER JOIN `pto_actos_admin` 
                    ON (`tes_caja_const`.`id_tipo_acto` = `pto_actos_admin`.`id_acto`)
            WHERE (`tes_caja_const`.`fecha_ini` BETWEEN '$vigencia-01-01' AND '$vigencia-12-31' $where)
            ORDER BY $col $dir $limit";
    $rs = $cmd->query($sql);
    $listado = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                COUNT(*) AS `total`
            FROM
                `tes_caja_const`
                INNER JOIN `pto_actos_admin` 
                    ON (`tes_caja_const`.`id_tipo_acto` = `pto_actos_admin`.`id_acto`)
            WHERE (`tes_caja_const`.`fecha_ini` BETWEEN '$vigencia-01-01' AND '$vigencia-12-31')";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecords = $total['total'];
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                COUNT(*) AS `total`
            FROM
                `tes_caja_const`
                INNER JOIN `pto_actos_admin` 
                    ON (`tes_caja_const`.`id_tipo_acto` = `pto_actos_admin`.`id_acto`)
            WHERE (`tes_caja_const`.`fecha_ini` BETWEEN '$vigencia-01-01' AND '$vigencia-12-31' $where)";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecordsFilter = $total['total'];
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
// consultar la fecha de cierre del periodo del módulo de presupuesto 
if (!empty($listado)) {
    foreach ($listado as $lp) {
        $id_ctb = $lp['id_caja_const'];
        $estado = $lp['estado'];
        $fecha = date('Y-m-d', strtotime($lp['fecha_ini']));
        $editar = $detalles = $acciones = $borrar = $responsable = $rubros = null;
        if ($fecha <= $fecha_cierre) {
            $anular = null;
            $cerrar = null;
        } else {
            $anular = '<a value="' . $id_ctb . '" class="dropdown-item sombra " href="#" onclick="anularDocumentoTes(' . $id_ctb . ');">Anulación</a>';
        }
        if (($permisos->PermisosUsuario($opciones, 5604, 2) || $id_rol == 1) && $estado == '1') {
            $responsable = '<a value="' . $id_ctb . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow" href="#" onclick="cargarResponsableCaja(' . $id_ctb . ',0);" title="Gestionar Responsables"><span class="fas fa-user-tie"></span></a>';
            $rubro = '<a value="' . $id_ctb . '" class="btn btn-outline-secondary btn-xs rounded-circle me-1 shadow" href="#" onclick="cargarRubrosCaja(' . $id_ctb . ',0);" title="Gestionar Rubros"><span class="far fa-list-alt"></span></a>';
        }
        if (($permisos->PermisosUsuario($opciones, 5604, 3) || $id_rol == 1)) {
            $editar = '<a id ="editar_' . $id_ctb . '" value="' . $id_ctb . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow editarCaja"  text="' . $id_ctb . '"><span class="fas fa-pencil-alt"></span></a>';
            $detalles = '<a value="' . $id_ctb . '" class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow" title="Detalles" onclick="cargarListaDetalleCajaEdit(' . $id_ctb . ')"><span class="fas fa-eye"></span></a>';
            $imprimir = '<a value="' . $id_ctb . '" onclick="imprimirFormatoCaja()" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow " title="Detalles"><span class="fas fa-print"></span></a>';
            // Acciones teniendo en cuenta el tipo de rol
            //si es lider de proceso puede abrir o cerrar documentos
        }
        if (($permisos->PermisosUsuario($opciones, 5604, 4) || $id_rol == 1)) {
            $borrar = '<a value="' . $id_ctb . '" onclick="eliminarRegistroCaja(' . $id_ctb . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow "  title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
            if ($estado == '1') {
                $cerrar = '<a value="' . $id_ctb . '" class="dropdown-item sombra carga" onclick="cerrarDocumentoCtbTes(' . $id_ctb . ')" href="#">Cerrar documento</a>';
            } else {
                $cerrar = '<a value="' . $id_ctb . '" class="dropdown-item sombra carga" onclick="abrirDocumentoTes(' . $id_ctb . ')" href="#">Abrir documento</a>';
            }
        }
        if ($estado == '1') {
            $estado = '<span class="badge bg-success">Abierto</span>';
        }
        if ($estado == '2') {
            $editar = null;
            $borrar = null;
            $estado = '<span class="badge bg-secondary">Cerrado</span>';
        }
        if ($estado == '0') {
            $editar = null;
            $borrar = null;
            $imprimir = null;
            $estado = '<span class="badge bg-danger">Anulado</span>';
        }
        $data[] = [
            'acto' => $lp['acto'],
            'num_acto' => $lp['num_acto'],
            'nombre_caja' => $lp['nombre_caja'],
            'fecha_ini' => $lp['fecha_ini'],
            'fecha_acto' => $lp['fecha_acto'],
            'valor_total' => '<div class="text-end">' . pesos($lp['valor_total']) . '</div>',
            'valor_minimo' => '<div class="text-end">' . pesos($lp['valor_minimo']) . '</div>',
            'num_poliza' => $lp['num_poliza'],
            'porcentaje' => $lp['porcentaje'],
            'estado' => '<div class="text-center">' . $estado . '</div>',
            'botones' => '<div class="text-center" style="position:relative">' . $editar . $detalles . $borrar . $imprimir  . $responsable . $rubro . '</div>',
        ];
    }
} else {
    $data = [];
}
$cmd = null;
$datos = [
    'data' => $data,
    'recordsFiltered' => $totalRecordsFilter,
    'recordsTotal' => $totalRecords,
];


echo json_encode($datos);
