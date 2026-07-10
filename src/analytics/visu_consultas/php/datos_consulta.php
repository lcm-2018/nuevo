<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$usuario = $_SESSION['id_user'];
$id = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida');
$data = [];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $sql = "SELECT id_consulta,titulo_consulta,detalle_consulta
                ,tipo_bdatos,tipo_informe,tipo_consulta,tipo_acceso
                ,CASE tipo_bdatos WHEN  1 THEN 'Base de Datos Local' WHEN 2 THEN 'Múltiples Bases de Datos' ELSE '' END nom_tipo_bdatos
                ,CASE tipo_informe WHEN  1 THEN 'Un Informe' WHEN 2 THEN 'Múltiples Informes' ELSE '' END nom_tipo_informe
                ,CASE tipo_consulta WHEN  1 THEN 'Bases de Datos Locales' WHEN 2 THEN 'Bases de Datos Remotas' ELSE '' END nom_tipo_consulta
                ,CASE tipo_acceso WHEN  1 THEN 'Público' WHEN 2 THEN 'Usuarios Autorizados' ELSE '' END nom_tipo_acceso
            FROM dash_consultas WHERE id_consulta=$id";
    $rs = $cmd->query($sql);
    $obj_consulta = $rs->fetch();

    $sql = "SELECT id_parametro,parametro,etiqueta,descripcion,tipo
            FROM dash_consulta_param
            WHERE id_consulta=$id";
    $rs = $cmd->query($sql);
    $obj_parametros = $rs->fetchAll();

    $sql = "SELECT dash_consulta_bd.id_bdatos,dash_bdatos.nombre_entidad
    FROM dash_consulta_bd
    INNER JOIN dash_bdatos ON (dash_bdatos.id_bdatos = dash_consulta_bd.id_bdatos)
    WHERE dash_consulta_bd.id_consulta=$id";
    $rs = $cmd->query($sql);
    $obj_bdatos = $rs->fetchAll();

    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

if ($obj_consulta){
    $data['id_consulta'] = $obj_consulta['id_consulta'];
    $data['nom_consulta'] = $obj_consulta['titulo_consulta'];
    $data['des_consulta'] = $obj_consulta['detalle_consulta'];
    $data['tipo_bdatos'] = $obj_consulta['tipo_bdatos'];
    $data['tipo_informe'] = $obj_consulta['tipo_informe'];
    $data['tipo_consulta'] = $obj_consulta['tipo_consulta'];
    $data['tipo_acceso'] = $obj_consulta['tipo_acceso'];
    $data['nom_tipo_bdatos'] = $obj_consulta['nom_tipo_bdatos'];
    $data['nom_tipo_informe'] = $obj_consulta['nom_tipo_informe'];
    $data['nom_tipo_consulta'] = $obj_consulta['nom_tipo_consulta'];
    $data['nom_tipo_acceso'] = $obj_consulta['nom_tipo_acceso'];
    $data['parametros'] = $obj_parametros;
    $data['bdatos'] = $obj_bdatos;
}

echo json_encode($data);
