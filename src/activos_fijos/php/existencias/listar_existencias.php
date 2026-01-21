<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';


use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
include '../common/funciones_generales.php';

$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$limit = "";
if ($length != -1) {
    $limit = "LIMIT $start, $length";
}
$col = $_POST['order'][0]['column'] + 1;
$dir = $_POST['order'][0]['dir'];
$idusr = $_SESSION['id_user'];
$idrol = $_SESSION['rol'];

$where_usr = " WHERE HV.estado IN (1,2,3,4)";
if (isset($_POST['id_sede']) && $_POST['id_sede']) {
    $where_usr .= " AND HV.id_sede=" . $_POST['id_sede'];
}
if (isset($_POST['id_area']) && $_POST['id_area']) {
    $where_usr .= " AND HV.id_area=" . $_POST['id_area'];
}

$where = " WHERE (FG.id_grupo IN (3,4,5) OR FG.af_menor_cuantia=1)";
if (isset($_POST['codigo']) && $_POST['codigo']) {
    $where .= " AND ME.cod_medicamento LIKE '" . $_POST['codigo'] . "%'";
}
if (isset($_POST['nombre']) && $_POST['nombre']) {
    $where .= " AND ME.nom_medicamento LIKE '" . $_POST['nombre'] . "%'";
}
if (isset($_POST['id_subgrupo']) && $_POST['id_subgrupo']) {
    $where .= " AND ME.id_subgrupo=" . $_POST['id_subgrupo'];
}
if (isset($_POST['art_activo']) && $_POST['art_activo']) {
    $where .= " AND ME.estado=1";
}

if (isset($_POST['con_existencia']) && $_POST['con_existencia']) {
    if ($_POST['con_existencia'] == 1) {
        $where .= " AND IF(EX.existencia IS NULL,0,EX.existencia)>=1";
    } else {
        $where .= " AND IF(EX.existencia IS NULL,0,EX.existencia)=0";
    }
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    //Consulta el total de registros de la tabla
    $sql = "SELECT COUNT(*) AS total FROM far_medicamentos AS ME
            INNER JOIN far_subgrupos AS FG ON (FG.id_subgrupo=ME.id_subgrupo)
            WHERE FG.id_grupo IN (3,4,5) OR FG.af_menor_cuantia=1";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecords = $total['total'];

    //Consulta el total de registros aplicando el filtro
    $sql = "SELECT COUNT(*) AS total FROM far_medicamentos AS ME
            INNER JOIN far_subgrupos AS FG ON (FG.id_subgrupo=ME.id_subgrupo)
            LEFT JOIN (SELECT HV.id_articulo, COUNT(*) AS existencia,SUM(HV.valor) AS val_total
                FROM acf_hojavida AS HV
                $where_usr
                GROUP BY HV.id_articulo) AS EX ON (EX.id_articulo=ME.id_med)
            $where";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecordsFilter = $total['total'];

    //Consulta los datos para listarlos en la tabla
    $sql = "SELECT ME.id_med,ME.cod_medicamento,ME.nom_medicamento,
                FG.nom_subgrupo,
                IF(EX.existencia IS NULL,0,EX.existencia) AS existencia,
                IF(EX.existencia IS NULL,0,EX.val_total/EX.existencia) AS val_promedio,
                IF(EX.val_total IS NULL,0,EX.val_total) AS val_total,
                IF(DE.valor IS NULL,0,DE.valor) AS val_ult_compra,
                IF(ME.estado=1,'ACTIVO','INACTIVO') AS estado
            FROM far_medicamentos AS ME
            INNER JOIN far_subgrupos AS FG ON (FG.id_subgrupo=ME.id_subgrupo)
            LEFT JOIN (SELECT HV.id_articulo, COUNT(*) AS existencia,SUM(HV.valor) AS val_total
                FROM acf_hojavida AS HV
                $where_usr
                GROUP BY HV.id_articulo) AS EX ON (EX.id_articulo=ME.id_med)
            LEFT JOIN (SELECT OED.id_articulo,MAX(OED.id_ing_detalle) AS id 
                FROM acf_orden_ingreso_detalle AS OED
                INNER JOIN acf_orden_ingreso as OE ON (OE.id_ingreso=OED.id_ingreso)
                WHERE OE.estado=2
                GROUP BY OED.id_articulo) AS VU ON (VU.id_articulo=ME.id_med)	   
            LEFT JOIN acf_orden_ingreso_detalle AS DE ON (DE.id_ing_detalle=VU.id)
            $where ORDER BY $col $dir $limit";
    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$examinar = NULL;
$data = [];
if (!empty($objs)) {
    foreach ($objs as $obj) {
        $id = $obj['id_med'];
        //1-Consultar,2-Crear,3-Editar,4-Eliminar,5-Anular,6-Imprimir
        if ($permisos->PermisosUsuario($opciones, 5710, 1) || $id_rol == 1) {
            $examinar = '<a value="' . $id . '" class="btn btn-outline-primary btn-sm btn-circle btn_examinar" title="Activos Fijos"><span class="fa fa-wpforms "></span></a>';
        }
        $data[] = [
            "id_med" => $id,
            "cod_medicamento" => $obj['cod_medicamento'],
            "nom_medicamento" => mb_strtoupper($obj['nom_medicamento']),
            "nom_subgrupo" => mb_strtoupper($obj['nom_subgrupo']),
            "existencia" => $obj['existencia'],
            "val_promedio" => formato_valor($obj['val_promedio']),
            "val_total" => formato_valor($obj['val_total']),
            "val_ult_compra" => formato_valor($obj['val_ult_compra']),
            "estado" => $obj['estado'],
            "botones" => '<div class="text-center">' . $examinar . '</div>',
        ];
    }
}
$datos = [
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordsFilter
];

echo json_encode($datos);
