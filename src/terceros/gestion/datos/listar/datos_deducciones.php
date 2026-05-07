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
    $sql = "SELECT d.id_deduccion, v.anio, d.intereses_vivienda, d.medicina_prepagada, d.polizas_salud, d.ahorros_afc, d.aportes_pension, d.estado 
            FROM tb_terceros_deducciones d
            INNER JOIN tb_vigencias v ON d.id_vigencia = v.id_vigencia
            WHERE d.id_tercero_api = $id_t";
    $rs = $cmd->query($sql);
    $deducciones = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$data = [];
if (!empty($deducciones)) {
    foreach ($deducciones as $d) {
        $id_deduc = $d['id_deduccion'];
        $borrar  = '';
        $editar = '';

        if ($permisos->PermisosUsuario($opciones, 5201, 3) || $id_rol == 1) {
            $editar = '<a onclick="EditarDeduccion(' . $id_deduc . ')" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1" title="Editar"><span class="fas fa-pencil-alt"></span></a>';
        }

        if ($permisos->PermisosUsuario($opciones, 5201, 4) || $id_rol == 1) {
            $borrar = '<a onclick="BorrarDeduccion(' . $id_deduc . ')" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
        }

        if ($d['estado'] == 1) {
            $estado = '<a href="#" value="' . $id_deduc . '" class="estadodeduc" title="Activo - Click para desactivar"><span class="fas fa-toggle-on fa-lg activo text-success"></span></a>';
        } else {
            $estado = '<a href="#" value="' . $id_deduc . '" class="estadodeduc" title="Inactivo - Click para activar"><span class="fas fa-toggle-off fa-lg inactivo text-secondary"></span></a>';
        }

        $data[] = [
            'vigencia' => $d['anio'],
            'intereses' => '$ ' . number_format($d['intereses_vivienda'], 2, ',', '.'),
            'medicina' => '$ ' . number_format($d['medicina_prepagada'], 2, ',', '.'),
            'polizas' => '$ ' . number_format($d['polizas_salud'], 2, ',', '.'),
            'afc' => '$ ' . number_format($d['ahorros_afc'], 2, ',', '.'),
            'pension' => '$ ' . number_format($d['aportes_pension'], 2, ',', '.'),
            'estado' => '<div class="text-center">' . $estado . '</div>',
            'acciones' => '<div class="text-center">' . $editar . $borrar . '</div>',
        ];
    }
}

$datos = ['data' => $data];
echo json_encode($datos);
