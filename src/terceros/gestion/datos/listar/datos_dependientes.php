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

$id_t = isset($_POST['id_t']) ? $_POST['id_t'] : exit('Acción no permitida');

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `td`.`id_dependiente`,
                `t_doc`.`descripcion` as `tipo_doc`,
                `td`.`no_documento`,
                `td`.`nombre_completo`,
                `t_dep`.`descripcion` as `parentesco`,
                `td`.`estado`
            FROM `tb_terceros_dependientes` `td`
            INNER JOIN `tb_tipos_documento` `t_doc` ON `td`.`id_tipo_doc` = `t_doc`.`id_tipodoc`
            INNER JOIN `tb_tipo_dependientes` `t_dep` ON `td`.`id_tipo_dependiente` = `t_dep`.`id_tipo`
            WHERE `td`.`id_tercero_api` = $id_t";
    $rs = $cmd->query($sql);
    $dependientes = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$data = [];
if (!empty($dependientes)) {
    foreach ($dependientes as $d) {
        $id_dep = $d['id_dependiente'];
        $borrar  = '';
        $editar = '';

        if ($permisos->PermisosUsuario($opciones, 5201, 3) || $id_rol == 1) {
            $editar = '<a onclick="EditarDependiente(' . $id_dep . ')" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1" title="Editar"><span class="fas fa-pencil-alt"></span></a>';
        }

        if ($permisos->PermisosUsuario($opciones, 5201, 4) || $id_rol == 1) {
            $borrar = '<a onclick="BorrarDependiente(' . $id_dep . ')" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
        }

        if ($d['estado'] == 1) {
            $estado = '<a href="#" value="' . $id_dep . '" class="estadodepend" title="Activo - Click para desactivar"><span class="fas fa-toggle-on fa-lg activo text-success"></span></a>';
        } else {
            $estado = '<a href="#" value="' . $id_dep . '" class="estadodepend" title="Inactivo - Click para activar"><span class="fas fa-toggle-off fa-lg inactivo text-secondary"></span></a>';
        }

        $data[] = [
            'tipo_doc'   => mb_strtoupper($d['tipo_doc']),
            'nombre'     => mb_strtoupper($d['nombre_completo']),
            'documento'  => $d['no_documento'],
            'parentesco' => mb_strtoupper($d['parentesco']),
            'estado'     => '<div class="text-center">' . $estado . '</div>',
            'acciones'   => '<div class="text-center">' . $editar . $borrar . '</div>',
        ];
    }
}

$datos = ['data' => $data];
echo json_encode($datos);
