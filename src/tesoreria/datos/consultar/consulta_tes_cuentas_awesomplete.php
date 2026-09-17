<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}
include '../../../../config/autoloader.php';
$search = $_POST['search'] ?? '';

$response = [];
if (strlen($search) >= 2) {
    try {
        $cmd = \Config\Clases\Conexion::getConexion();
        $sql = "SELECT
                    `tes_cuentas`.`id_tes_cuenta` AS `id`
                    , CONCAT(`tb_bancos`.`nom_banco`, ' - ', `tes_cuentas`.`nombre`) AS `label`
                FROM
                    `tes_cuentas`
                    INNER JOIN `tb_bancos` 
                        ON (`tes_cuentas`.`id_banco` = `tb_bancos`.`id_banco`)
                WHERE `tes_cuentas`.`estado` = 1 
                  AND (`tes_cuentas`.`nombre` LIKE :search OR `tb_bancos`.`nom_banco` LIKE :search)
                ORDER BY `tb_bancos`.`nom_banco` ASC, `tes_cuentas`.`nombre` ASC
                LIMIT 50";
        $stmt = $cmd->prepare($sql);
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->execute();
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        $cmd = null;
    } catch (PDOException $e) {
        $response = ['error' => $e->getMessage()];
    }
}

echo json_encode($response);
