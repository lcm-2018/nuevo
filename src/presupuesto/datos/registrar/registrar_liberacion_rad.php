<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    echo '<script>window.location.replace("../../../index.php");</script>';
    exit();
}

include '../../../../config/autoloader.php';

$oper = isset($_POST['oper']) ? $_POST['oper'] : exit('Acción no permitida');

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    if ($oper == "add") {
        $id_rad = $_POST['id_rad'];
        $fec_lib = $_POST['txt_fec_lib'];
        $concepto_lib = "Liberación: " . $_POST['txt_concepto_lib'];
        $array_rubros = $_POST['txt_id_rubro'];
        $array_valores_liberacion = $_POST['txt_valor_liberar'];
        $iduser = $_SESSION['id_user'];
        $date = new DateTime('now', new DateTimeZone('America/Bogota'));
        $fecha_reg = $date->format('Y-m-d H:i:s');
        $estado = 2;
        $valor_cero = 0;

        $cmd->beginTransaction();

        // Obtener datos del RAD original
        $sql = "SELECT id_pto, id_manu, id_tercero_api, num_factura, tipo_movimiento FROM pto_rad WHERE id_pto_rad = :id_rad LIMIT 1";
        $stmt = $cmd->prepare($sql);
        $stmt->bindParam(':id_rad', $id_rad, PDO::PARAM_INT);
        $stmt->execute();
        $rad_original = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rad_original) {
            throw new Exception("El registro RAD original no existe.");
        }

        // Insertar nuevo pto_rad (Liberación)
        $sql_insert_rad = "INSERT INTO pto_rad (id_pto, fecha, id_manu, id_tercero_api, objeto, num_factura, estado, tipo_movimiento, id_user_reg, fecha_reg) 
                           VALUES (:id_pto, :fecha, :id_manu, :id_tercero_api, :objeto, :num_factura, :estado, :tipo_movimiento, :id_user_reg, :fecha_reg)";
        $stmt_insert_rad = $cmd->prepare($sql_insert_rad);
        $stmt_insert_rad->bindParam(':id_pto', $rad_original['id_pto'], PDO::PARAM_INT);
        $stmt_insert_rad->bindParam(':fecha', $fec_lib, PDO::PARAM_STR);
        $stmt_insert_rad->bindParam(':id_manu', $rad_original['id_manu'], PDO::PARAM_INT);
        $stmt_insert_rad->bindParam(':id_tercero_api', $rad_original['id_tercero_api'], PDO::PARAM_INT);
        $stmt_insert_rad->bindParam(':objeto', $concepto_lib, PDO::PARAM_STR);
        $stmt_insert_rad->bindParam(':num_factura', $rad_original['num_factura'], PDO::PARAM_STR);
        $stmt_insert_rad->bindParam(':estado', $estado, PDO::PARAM_INT);
        $stmt_insert_rad->bindParam(':tipo_movimiento', $rad_original['tipo_movimiento'], PDO::PARAM_INT);
        $stmt_insert_rad->bindParam(':id_user_reg', $iduser, PDO::PARAM_INT);
        $stmt_insert_rad->bindParam(':fecha_reg', $fecha_reg, PDO::PARAM_STR);
        
        $stmt_insert_rad->execute();
        $id_rad_nuevo = $cmd->lastInsertId();

        if ($id_rad_nuevo == 0) {
            throw new Exception("Error al insertar el nuevo registro RAD.");
        }

        // Insertar detalles (pto_rad_detalle)
        $sql_insert_det = "INSERT INTO pto_rad_detalle (id_pto_rad, id_tercero_api, id_rubro, valor, valor_liberado, id_user_reg, fecha_reg) 
                           VALUES (:id_pto_rad, :id_tercero_api, :id_rubro, :valor, :valor_liberado, :id_user_reg, :fecha_reg)";
        $stmt_insert_det = $cmd->prepare($sql_insert_det);

        $stmt_insert_det->bindParam(':id_pto_rad', $id_rad_nuevo, PDO::PARAM_INT);
        $stmt_insert_det->bindParam(':id_tercero_api', $rad_original['id_tercero_api'], PDO::PARAM_INT);
        $stmt_insert_det->bindParam(':id_rubro', $id_rubro, PDO::PARAM_INT);
        $stmt_insert_det->bindParam(':valor', $valor_cero, PDO::PARAM_STR);
        $stmt_insert_det->bindParam(':valor_liberado', $valor_liberado, PDO::PARAM_STR);
        $stmt_insert_det->bindParam(':id_user_reg', $iduser, PDO::PARAM_INT);
        $stmt_insert_det->bindParam(':fecha_reg', $fecha_reg, PDO::PARAM_STR);

        foreach ($array_rubros as $key => $value) {
            $id_rubro = $array_rubros[$key];
            $valor_liberado = $array_valores_liberacion[$key];
            
            if ($valor_liberado > 0) {
                $stmt_insert_det->execute();
            }
        }

        // Insertar relación en pto_rad_liberacion
        $sql_insert_rel = "INSERT INTO pto_rad_liberacion (id_rad_main, id_rad_rel) VALUES (:id_rad_main, :id_rad_rel)";
        $stmt_insert_rel = $cmd->prepare($sql_insert_rel);
        $stmt_insert_rel->bindParam(':id_rad_main', $id_rad, PDO::PARAM_INT);
        $stmt_insert_rel->bindParam(':id_rad_rel', $id_rad_nuevo, PDO::PARAM_INT);
        $stmt_insert_rel->execute();

        $cmd->commit();
        echo 1;
    }
} catch (Exception $e) {
    if (isset($cmd) && $cmd->inTransaction()) {
        $cmd->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
