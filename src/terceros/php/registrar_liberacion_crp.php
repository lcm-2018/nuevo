<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../config/autoloader.php';

$oper = isset($_POST['oper']) ? $_POST['oper'] : exit('Acción no permitida');
$res = array();

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    

    if ($oper == "add") {
        $id_crp = $_POST['id_crp'];
        $fec_lib = $_POST['txt_fec_lib_crp'];
        $concepto_lib = $_POST['txt_concepto_lib_crp'];
        $valor = 0;
        $array_rubros = $_POST['txt_id_rubro_crp'];
        $array_valores_liberacion = $_POST['txt_valor_liberar_crp'];
        $iduser = $_SESSION['id_user'];
        $date = new DateTime('now', new DateTimeZone('America/Bogota'));
        $inserta = 0;

        $query = "INSERT INTO pto_crp_detalle (id_pto_crp, id_pto_cdp_det, valor, valor_liberado, fecha_libera, concepto_libera, id_user_reg, fecha_reg, id_user_act, fecha_act) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $query = $cmd->prepare($query);
        $query->bindParam(1, $id_crp, PDO::PARAM_INT);
        $query->bindParam(2, $id_rubro, PDO::PARAM_INT);
        $query->bindParam(3, $valor, PDO::PARAM_STR);
        $query->bindParam(4, $valor_liberado, PDO::PARAM_STR);
        $query->bindParam(5, $fec_lib, PDO::PARAM_STR);
        $query->bindParam(6, $concepto_lib, PDO::PARAM_STR);
        $query->bindParam(7, $iduser, PDO::PARAM_INT);
        $query->bindValue(8, $date->format('Y-m-d H:i:s'));
        $query->bindParam(9, $iduser, PDO::PARAM_INT);
        $query->bindValue(10, $date->format('Y-m-d H:i:s'));
        foreach ($array_rubros as $key => $value) {
            $id_rubro = $array_rubros[$key];
            $valor_liberado = $array_valores_liberacion[$key];
            $query->execute();
            if ($cmd->lastInsertId() > 0) {
                $inserta++;
            } else {
                echo $query->errorInfo()[2];
            }
        }
        if ($inserta > 0) {
            echo '1';
        }
    }

    if ($oper == "del") {
        $id = $_POST['id'];
        $sql = "DELETE FROM pto_crp_detalle WHERE id_pto_crp_det=" . $id;
        $rs = $cmd->query($sql);
        if ($rs) {
            $res['mensaje'] = 'ok';
        } else {
            $res['mensaje'] = $cmd->errorInfo()[2];
        }
        echo json_encode($res);
    }

    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
