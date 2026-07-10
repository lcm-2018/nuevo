<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include '../../common/php/funciones_generales.php';

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $cmd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

    $id = $_POST['id'];
    $parametros = json_decode($_POST['parametros']);
    $ids_bd = json_decode($_POST['ids_bd']);
    $detalles = $_POST['detalles'];

    $limite = ' LIMIT 1000';
    
    $sql = 'SELECT consulta_sql,consulta_sql_group,tipo_bdatos,tipo_informe,tipo_consulta 
            FROM dash_consultas 
            WHERE id_consulta=' . $id . ' LIMIT 1';
    $rs = $cmd->query($sql);
    $obj = $rs->fetch();
    $cnsql = trim($obj['consulta_sql']);
    $cnsql_group = trim($obj['consulta_sql_group']);

    // Replaza los parametros
    foreach ($parametros as $pr) {
        $cnsql = str_replace('[@' . $pr->parametro . ']', $pr->valor, $cnsql);
    }

    // Coloca el prefijo de base de datos [@bd].
    $cnsql = convertir_sql($cnsql);

    $sql = 'SELECT nombre_bd FROM dash_bdatos WHERE id_bdatos IN (' . implode(',', $ids_bd) . ')';
    $rs = $cmd->query($sql);
    $obj_bd = $rs->fetchAll();

    $cnsql_union = '';
    foreach ($obj_bd as $bd) {
        $cnsql_bd = str_replace('[@bd]', $bd['nombre_bd'], $cnsql);
        if ($cnsql_union != '') {
            $cnsql_union .= ' UNION ALL ';
        }
        $cnsql_union .= $cnsql_bd;
    }

    $cnsql_final = $cnsql_union;
    if(trim($cnsql_group) != '' && $detalles == 0) {
        $cnsql_final = str_replace('[@sql]', $cnsql_union, $cnsql_group);
    }

    $sqlcount = "SELECT COUNT(*) AS count FROM ($cnsql_final) AS c2";
    $rs = $cmd->query($sqlcount);
    $obj = $rs->fetch();
    $total = $obj['count']; // Total count of records

    $rs = $cmd->query($cnsql_final . $limite, PDO::FETCH_BOTH);
    $objs = $rs->fetchAll(PDO::FETCH_ASSOC);
    $n = $rs->columnCount();
    
    // Construir listado de columnas
    $columns = [];
    for ($i = 0; $i < $n; $i++) {
        $col = $rs->getColumnMeta($i);
        $columns[] = $col['name'];
    }

    // Contador de filas retornadas
    $j = count($objs);

    // Preparar respuesta JSON
    $response = [
        'columns' => $columns,
        'rows' => $objs,
        'count' => $j,
        'total' => $total
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    $cmd = null;

} catch (PDOException $e) {
    echo $e->getMessage();
}
