<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';

$id_deduccion = isset($_POST['idDeduccion']) ? $_POST['idDeduccion'] : 0;
$id_tercero = isset($_POST['idTercero']) ? $_POST['idTercero'] : 0;

$intereses = isset($_POST['txtIntereses']) ? $_POST['txtIntereses'] : 0;
$medicina = isset($_POST['txtMedicina']) ? $_POST['txtMedicina'] : 0;
$polizas = isset($_POST['txtPolizas']) ? $_POST['txtPolizas'] : 0;
$afc = isset($_POST['txtAfc']) ? $_POST['txtAfc'] : 0;
$pension = isset($_POST['txtPension']) ? $_POST['txtPension'] : 0;

$id_user = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha = $date->format('Y-m-d H:i:s');

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    if ($id_deduccion > 0) {
        // Update
        $sql = "UPDATE tb_terceros_deducciones SET 
                    intereses_vivienda = ?, 
                    medicina_prepagada = ?, 
                    polizas_salud = ?, 
                    ahorros_afc = ?, 
                    aportes_pension = ?, 
                    id_user_act = ?, 
                    fec_act = ? 
                WHERE id_deduccion = ?";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([
            $intereses,
            $medicina,
            $polizas,
            $afc,
            $pension,
            $id_user,
            $fecha,
            $id_deduccion
        ]);

        if ($stmt->rowCount() > 0 || $stmt->errorCode() == '00000') {
            echo 'ok';
        } else {
            echo 'No se actualizó ningún registro.';
        }
    } else {
        // Insert
        // Verificar si ya existe este tercero
        $sql = "SELECT id_deduccion FROM tb_terceros_deducciones WHERE id_tercero_api = ?";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([$id_tercero]);
        if ($stmt->rowCount() > 0) {
            echo 'El tercero ya cuenta con deducciones registradas. Debe actualizar el existente.';
            exit();
        }

        $sql = "INSERT INTO tb_terceros_deducciones (id_tercero_api, intereses_vivienda, medicina_prepagada, polizas_salud, ahorros_afc, aportes_pension, estado, id_user_reg, fec_reg) 
                VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([
            $id_tercero,
            $intereses,
            $medicina,
            $polizas,
            $afc,
            $pension,
            $id_user,
            $fecha
        ]);

        if ($stmt->rowCount() > 0) {
            echo 'ok';
        } else {
            echo 'No se guardó el registro.';
        }
    }
    
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
