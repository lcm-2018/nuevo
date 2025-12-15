<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
// Div de acciones de la lista
$vigencia =  $_SESSION['vigencia'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `pto_presupuestos`.`id_pto` AS `id_pto_presupuestos`
                , `pto_presupuestos`.`id_tipo`
                , `pto_tipo`.`nombre` AS `tipo`
                , `pto_presupuestos`.`nombre`
                , `pto_presupuestos`.`descripcion`
                , `tb_vigencias`.`anio` AS `vigencia`
            FROM
                `pto_presupuestos`
                INNER JOIN `pto_tipo` 
                    ON (`pto_presupuestos`.`id_tipo` = `pto_tipo`.`id_tipo`)
                INNER JOIN `tb_vigencias` 
                    ON (`pto_presupuestos`.`id_vigencia` = `tb_vigencias`.`id_vigencia`)
            WHERE (`tb_vigencias`.`anio` = '$vigencia')";
    $rs = $cmd->query($sql);
    $listappto = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
if (!empty($listappto)) {

    foreach ($listappto as $lp) {
        $id_pto = $lp['id_pto_presupuestos'];
        $detalles = null;
        if ($permisos->PermisosUsuario($opciones, 5401, 3) || $id_rol == 1) {
            $editar = '<a value="' . $id_pto . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow editar" title="Editar"><span class="fas fa-pencil-alt "></span></a>';
            $ejecucion = '<a value="' . $id_pto . '" tipo-id="' . $lp['id_tipo'] . '" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow ejecucion" title="Ejecucion"><span class="fas fa-tasks "></span></a>';
            //$detalles = '<a value="' . $id_pto . '" class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow detalles" title="Detalles"><span class="fas fa-eye "></span></a>';
            $acciones =
                <<<HTML
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-outline-secondary btn-xs rounded-circle me-1 shadow" type="button" data-bs-toggle="dropdown" data-bs-boundary="window" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-lg"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a value="$id_pto" class="dropdown-item sombra carga" href="javascript:void(0);">Cargar presupuesto</a></li>
                            <li><a value="$id_pto" class="dropdown-item sombra modifica" href="javascript:void(0);">Modificaciones</a></li>
                            <!--<a value="$id_pto" class="dropdown-item sombra ejecuta" href="javascript:void(0);">Ejecución</a>-->
                            <li><a value="$id_pto" class="dropdown-item sombra homologa" href="javascript:void(0);">Homologación</a></li>
                        </ul>
                    </div>
                HTML;
        } else {
            $editar = null;
            $acciones = null;
        }
        if ($permisos->PermisosUsuario($opciones, 5401, 4) || $id_rol == 1) {
            $borrar = '<a value="' . $id_pto . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow borrar" title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
        } else {
            $borrar = null;
        }

        $data[] = [
            'id_pto' => $lp['id_pto_presupuestos'],
            'nombre' => $lp['nombre'],
            'tipo' => mb_strtoupper($lp['tipo']),
            'vigencia' => $lp['vigencia'],
            'botones' => '<div class="text-center">' . $editar . $borrar . $ejecucion . $detalles . $acciones . '</div>',

        ];
    }
} else {
    $data = [];
}

$datos = ['data' => $data];


echo json_encode($datos);
