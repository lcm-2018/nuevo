<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';

$cmd = \Config\Clases\Conexion::getConexion();

$term = isset($_POST['term']) ? $_POST['term'] : '';

try {
    $sql = "SELECT 
                `id_codificacion`,
                CONCAT(`codigo`, ' - ', `descripcion`) AS `label`,
                `codigo`,
                `descripcion`
            FROM 
                `tb_codificacion_unspsc`
            WHERE 
                CONCAT(`codigo`, ' ', `descripcion`) LIKE ?
            ORDER BY `codigo` ASC
            LIMIT 20";

    $stmt = $cmd->prepare($sql);
    $searchTerm = '%' . $term . '%';
    $stmt->bindParam(1, $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    $cmd = null;

    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
