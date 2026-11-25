<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$id_rec = isset($_POST['id_rec']) ? intval($_POST['id_rec']) : 0;
$listappto = [];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `pto_rec_detalle`.`id_pto_rec_det` AS `id_detalle`
                , `pto_rec_detalle`.`id_pto_rac` AS `id_pto_doc`
                , IFNULL(`pto_rec_detalle`.`valor`, 0) AS `valor_deb`
                , IFNULL(`pto_rec_detalle`.`valor_liberado`, 0) AS `valor_cred`
                , `pto_cargue`.`id_cargue` AS `id_pto`
                , `pto_cargue`.`cod_pptal` AS `rubro`
                , `pto_cargue`.`nom_rubro` AS `nom_rubro`
            FROM
                `pto_rec_detalle`
                LEFT JOIN `pto_rad_detalle`
                    ON (`pto_rec_detalle`.`id_pto_rad_detalle` = `pto_rad_detalle`.`id_pto_rad_det`)
                INNER JOIN `pto_cargue`
                    ON (`pto_cargue`.`id_cargue` = COALESCE(`pto_rec_detalle`.`id_rubro`, `pto_rad_detalle`.`id_rubro`))
            WHERE (`pto_rec_detalle`.`id_pto_rac` = :id_rec)";
    $stmt = $cmd->prepare($sql);
    $stmt->bindValue(':id_rec', $id_rec, PDO::PARAM_INT);
    $stmt->execute();
    $listappto = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    unset($stmt);
    $cmd = null;
} catch (PDOException $e) {
    echo json_encode([
        'data' => [],
        'error' => $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode(),
    ]);
    exit();
}

$data = [];
$suma = 0.0;

if (!empty($listappto)) {
    foreach ($listappto as $lp) {
        $id_detalle = intval($lp['id_detalle']);
        $neto = (float)$lp['valor_deb'] - (float)$lp['valor_cred'];
        $debito = number_format($neto, 2, ',', '.');
        $suma += $neto;
        $data[] = [
            'id' => $id_detalle,
            'rubro' => $lp['rubro'] . ' - ' . $lp['nom_rubro'],
            'valor' => '<div class="text-end">' . $debito . '</div>',
        ];
    }
}

$sumaFormateada = number_format($suma, 2, ',', '.');
$data[] = [
    'id' => '0',
    'rubro' => '<div class="text-center"><b>TOTAL</b></div>',
    'valor' => '<div class="text-end"><b>' . $sumaFormateada . '</b></div>',
];

echo json_encode(['data' => $data]);
