<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../config/autoloader.php';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                id_cargue
                , cod_pptal
                , nom_rubro
            FROM
                pto_cargue
            WHERE id_pto=2";
    $rs = $cmd->query($sql);
    $obj_rubros = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$data = [];
$buscar = mb_strtoupper($_POST['term']);
if ($buscar == '%%') {
    foreach ($obj_rubros as $obj) {
        $rubro = mb_strtoupper($obj['cod_pptal']) . ' -> ' . $obj['nom_rubro'];
        $data[] = [
            'id' => $obj['id_cargue'],
            'label' => $rubro,
        ];
    }
} else {
    foreach ($obj_rubros as $obj) {
        $rubro = mb_strtoupper($obj['cod_pptal']) . ' -> ' . $obj['nom_rubro'];
        $pos = strpos($rubro, $buscar);
        if ($pos !== false) {
            $data[] = [
                'id' => $obj['id_cargue'],
                'label' => $rubro,
            ];
        }
    }
}

if (empty($data)) {
    $data[] = [
        'id' => '0',
        'label' => 'No hay coincidencias...',
    ];
}
echo json_encode($data);
