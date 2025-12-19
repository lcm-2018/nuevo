<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include_once '../../../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
include_once '../../../financiero/consultas.php';

// Div de acciones de la lista
$vigencia = $_SESSION['vigencia'];
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$where = $_POST['search']['value'] != '' ? "WHERE (`cuenta` LIKE '%{$_POST['search']['value']}%' OR `nombre` LIKE '%{$_POST['search']['value']}%')" : '';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `id_pgcp`, `fecha`, `cuenta`, `nombre`, `tipo_dato`, `estado`, `desagrega` 
            FROM `ctb_pgcp`
            $where
            ORDER BY `cuenta` ASC
            LIMIT $start, $length";
    $rs = $cmd->query($sql);
    $lista = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $sql = "SELECT COUNT(*) AS `total` FROM `ctb_pgcp` $where";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecords = $total['total'];
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
if (!empty($lista)) {
    foreach ($lista as $lp) {
        $cerrar = $editar = $detalles = $borrar = $desagrega = $costos = null;
        $id_ctb = $lp['id_pgcp'];
        if (($permisos->PermisosUsuario($opciones, 5504, 3) || $id_rol == 1) && ($lp['estado'] == 1)) {
            $editar = '<a id ="editar_' . $id_ctb . '" value="' . $id_ctb . '" onclick="editarDatosPlanCuenta(' . $id_ctb . ')" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow"  title="Editar_' . $id_ctb . '"><span class="fas fa-pencil-alt "></span></a>';
        }
        $desagrega = '<button value="' . $id_ctb . '" onclick="formDesagregacion(' . $id_ctb . ')" class="btn btn-outline-info btn-xs rounded-circle me-1 shadow"  title="Formulario de desagregación de terceros"><span class="fas fa-clipboard-list "></span></button>';

        if (strlen($lp['cuenta']) == 4 && substr($lp['cuenta'], 0, 1) === "7") {
            $costos = '<button value="' . $id_ctb . '" onclick="formCostos(' . $id_ctb . ')" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow"  title="Formulario traslado de costos"><span class="fas fa-exchange-alt "></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5504, 4) || $id_rol == 1) {
            if ($lp['estado'] == 1) {
                $cerrar = '<a value="' . $id_ctb . '" class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow" onclick="cerrarCuentaPlan(' . $id_ctb . ')" title="Desactivar cuenta"><span class="fas fa-unlock "></span></a>';
                $borrar = '<a value="' . $id_ctb . '" onclick="eliminarCuentaContable(' . $id_ctb . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow "  title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
            } else {
                $cerrar = '<a value="' . $id_ctb . '" class="btn btn-outline-secondary btn-xs rounded-circle me-1 shadow" onclick="abrirCuentaPlan(' . $id_ctb . ')" title="Activar cuenta"><span class="fas fa-lock "></span></a>';
            }
        }
        if ($lp['estado'] == 1) {
            $estado = '<span class="badge rounded-pill text-bg-success">Activa</span>';
        } else {
            $estado = '<span class="badge rounded-pill text-bg-danger">Inactiva</span>';
        }
        $des  = $lp['desagrega'] == 1 ? '<span class="badge rounded-pill text-bg-info">Si</span>' : '<span class="badge rounded-pill text-bg-secondary">No</span>';

        $fecha = date("d-m-Y", strtotime($lp['fecha']));
        $data[] = [

            'fecha' => $fecha,
            'cuenta' => $lp['cuenta'],
            'nombre' => $lp['nombre'],
            'tipo' => '<div class="text-center">' . $lp['tipo_dato'] . '</div>',
            'nivel' => '<div class="text-center">' . Nivel($lp['cuenta']) . '</div>',
            'desagrega' => '<div class="text-center">' . $des . '</div>',
            'estado' => '<div class="text-center">' . $estado . '</div>',
            'botones' => '<div class="text-center" style="position:relative">' . $editar . $desagrega . $costos . $borrar . $cerrar . '</div>',
        ];
    }
} else {
    $data = [];
}
$cmd = null;
$datos = [
    'data' => $data,
    'recordsFiltered' => $totalRecords,
    'recordsTotal' => $totalRecords,
];


echo json_encode($datos);
