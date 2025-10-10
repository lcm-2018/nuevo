<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;
use Config\Clases\Conexion;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($_SESSION['id_user']);
$cmd = Conexion::getConexion();
$id_compra = isset($_POST['id_adq']) ? $_POST['id_adq'] : exit('Acción no permitida');
$centros = $_POST['slcCentroCosto'];
$cantidades = $_POST['numHorasMes'];
$id_user = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$added = 0;
$accion = $_POST['accion'];
try {
    // The previous line creating a new PDO instance is removed
    // $cmd = \Config\Clases\Conexion::getConexion();

    if ($accion == '0') {
        $sql = "INSERT INTO `ctt_destino_contrato`
                (`id_adquisicion`, `id_area_cc`, `horas_mes`, `id_user_reg`, `fec_reg`)
            VALUES (?, ?, ?, ?, ?)";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $id_compra, PDO::PARAM_INT);
        $sql->bindParam(2, $id_cc, PDO::PARAM_INT);
        $sql->bindParam(3, $numhoras, PDO::PARAM_INT);
        $sql->bindParam(4, $id_user, PDO::PARAM_INT);
        $sql->bindValue(5, $date->format('Y-m-d H:i:s'));
        foreach ($centros as $key => $value) {
            $id_cc = $value;
            $numhoras = $cantidades[$key];
            $sql->execute();
            $id = $cmd->lastInsertId();
            if ($id > 0) {
                $added++;
            } else {
                echo $cmd->errorInfo()[2];
                exit();
            }
        }
        if ($added > 0) {
            echo 1;
        } else {
            echo 'No se registraron destinos';
        }
    } else {
        $sql = "DELETE FROM `ctt_destino_contrato` WHERE `id_adquisicion` = ?";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $id_compra, PDO::PARAM_INT);
        $sql->execute();
        $sql = "INSERT INTO `ctt_destino_contrato`
                (`id_adquisicion`, `id_area_cc`, `horas_mes`, `id_user_reg`, `fec_reg`)
            VALUES (?, ?, ?, ?, ?)";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $id_compra, PDO::PARAM_INT);
        $sql->bindParam(2, $id_cc, PDO::PARAM_INT);
        $sql->bindParam(3, $numhoras, PDO::PARAM_INT);
        $sql->bindParam(4, $id_user, PDO::PARAM_INT);
        $sql->bindValue(5, $date->format('Y-m-d H:i:s'));
        foreach ($centros as $key => $value) {
            $id_cc = $value;
            $numhoras = $cantidades[$key];
            $sql->execute();
            $id = $cmd->lastInsertId();
            if ($id > 0) {
                $added++;
            } else {
                echo $cmd->errorInfo()[2];
                exit();
            }
        }
        if ($added > 0) {
            echo 1;
        } else {
            echo 'No se registraron destinos';
        }
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
