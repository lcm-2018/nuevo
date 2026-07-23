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
                `id_respxtercero`,`codigo`,`descripcion`,`estado`
            FROM `ctt_resposabilidad_terceros`
                INNER JOIN `tb_responsabilidades_tributarias`
                    ON(`ctt_resposabilidad_terceros`.`id_responsabilidad` = `tb_responsabilidades_tributarias`.`id_responsabilidad`)
            WHERE `id_tercero_api` = $id_t";
    $sql = $cmd->prepare($sql);
    $sql->execute();
    $responsabilidades = $sql->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
if (!empty($responsabilidades)) {
    foreach ($responsabilidades as $r) {
        $icono = $r['estado'] == '1' ? '<span class="fas fa-toggle-on fa-lg activo text-success"></span>' : '<span class="fas fa-toggle-off fa-lg inactivo text-secondary"></span>';
        $estado = '<div class="text-center"><a href="#" class="estadoresp" value="' . $r['id_respxtercero'] . '" title="' . ($r['estado'] == '1' ? 'Activo - Click para desactivar' : 'Inactivo - Click para activar') . '">' . $icono . '</a></div>';
        $data[] = [
            'id' => '<div class="text-center">' . $r['id_respxtercero'] . '</div>',
            'codigo' => '<div class="text-center">' . $r['codigo'] . '</div>',
            'descripcion' => mb_strtoupper($r['descripcion']),
            'estado' => $estado
        ];
    }
} else {
    $data = [];
}

$datos = ['data' => $data];

echo json_encode($datos);
