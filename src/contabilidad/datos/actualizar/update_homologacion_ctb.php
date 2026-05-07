<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "Sesión expirada. Por favor, vuelva a iniciar sesión.";
    exit();
}
include '../../../../config/autoloader.php';

$id_user = $_SESSION['id_user'];
$vigencia = $_SESSION['id_vigencia'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha = $date->format('Y-m-d H:i:s');

$formulario = isset($_POST['formulario']) ? $_POST['formulario'] : [];
$id_cuenta_otros = isset($_POST['id_cuenta_otros']) ? $_POST['id_cuenta_otros'] : [];
$id_cuenta_1009 = isset($_POST['id_cuenta_1009']) ? $_POST['id_cuenta_1009'] : [];

if (empty($formulario)) {
    echo "No se recibieron datos para guardar.";
    exit();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $cmd->beginTransaction();

    // Validar y recorrer lo recibido
    foreach ($formulario as $id_pgcp => $id_form) {
        $val_otros = isset($id_cuenta_otros[$id_pgcp]) && $id_cuenta_otros[$id_pgcp] > 0 ? $id_cuenta_otros[$id_pgcp] : null;
        $val_1009 = isset($id_cuenta_1009[$id_pgcp]) && $id_cuenta_1009[$id_pgcp] > 0 ? $id_cuenta_1009[$id_pgcp] : null;

        // Si no hay ninguna configuración para este pgcp, saltar.
        if (empty($id_form) && empty($val_otros) && empty($val_1009)) {
            continue;
        }

        // Consultar si ya existe
        $sql = "SELECT `id_hom` FROM `ctb_homologacion` WHERE `id_cuenta` = ? AND `id_vigencia` = ?";
        $rs = $cmd->prepare($sql);
        $rs->bindParam(1, $id_pgcp, PDO::PARAM_INT);
        $rs->bindParam(2, $vigencia, PDO::PARAM_INT);
        $rs->execute();
        $existe = $rs->fetch(PDO::FETCH_ASSOC);

        if ($existe) {
            // Update
            $sqlUp = "UPDATE `ctb_homologacion` 
                      SET `id_cuenta_otros` = ?, `id_cuenta_1009` = ?, `id_user_act` = ?, `fec_act` = ? 
                      WHERE `id_hom` = ?";
            $rsUp = $cmd->prepare($sqlUp);
            $rsUp->bindValue(1, $val_otros, $val_otros === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $rsUp->bindValue(2, $val_1009, $val_1009 === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $rsUp->bindValue(3, $id_user, PDO::PARAM_INT);
            $rsUp->bindValue(4, $fecha, PDO::PARAM_STR);
            $rsUp->bindValue(5, $existe['id_hom'], PDO::PARAM_INT);
            $rsUp->execute();
        } else {
            // Insert
            $sqlIn = "INSERT INTO `ctb_homologacion` (`id_vigencia`, `id_cuenta`, `id_cuenta_otros`, `id_cuenta_1009`, `id_user_reg`, `fec_reg`) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $rsIn = $cmd->prepare($sqlIn);
            $rsIn->bindValue(1, $vigencia, PDO::PARAM_INT);
            $rsIn->bindValue(2, $id_pgcp, PDO::PARAM_INT);
            $rsIn->bindValue(3, $val_otros, $val_otros === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $rsIn->bindValue(4, $val_1009, $val_1009 === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $rsIn->bindValue(5, $id_user, PDO::PARAM_INT);
            $rsIn->bindValue(6, $fecha, PDO::PARAM_STR);
            $rsIn->execute();
        }
    }

    $cmd->commit();
    echo 'ok';
} catch (PDOException $e) {
    if (isset($cmd) && $cmd->inTransaction()) {
        $cmd->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
