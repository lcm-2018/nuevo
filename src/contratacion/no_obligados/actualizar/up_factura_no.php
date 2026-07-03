<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';
use Config\Clases\Logs;

$id_facno = isset($_POST['id_factura']) ? $_POST['id_factura'] : exit('Acción no permitida');
$valfac = isset($_POST['valfac']) ? $_POST['valfac'] : exit('Acción no permitida');
$fec_compra = $_POST['fecCompraNO'];
$fec_vence = $_POST['fecVenceNO'];
$met_pago = $_POST['slcMetPago'];
$forma_pago = $_POST['slcFormaPago'];
$procede = $_POST['slcProcedencia'];
$tipo_org = $_POST['slcTipoOrg'];
$reg_fiscal = $_POST['slcRegFiscal'];
$resp_fiscal = $_POST['slcRespFiscal'];
$tipo_doc = $_POST['slcTipoDoc'];
$no_doc = $_POST['numNoDoc'];
$nombre = $_POST['txtNombreRazonSocial'];
$correo = $_POST['txtCorreoOrg'];
$telefono = $_POST['txtTelefonoOrg'];
$pais = $_POST['slcPaisEmp'];
$dpto = $_POST['slcDptoEmp'];
$ciudad = $_POST['slcMunicipioEmp'];
$direccion = $_POST['txtDireccion'];
$array_codigos = $_POST['txtCod'];
$array_descripcion = $_POST['txtDescripcion'];
$array_valu = $_POST['numValorUnitario'];
$array_cant = $_POST['numCantidad'];
$array_piva = $_POST['numPIVA'];
$array_viva = $_POST['valIva'];
$array_pdcto = $_POST['numPDcto'];
$array_vpdcto = $_POST['numValDcto'];
$array_vtotal = $_POST['numValorTotal'];
$base_imp = $_POST['valSubTotal'];
$valivag = $_POST['ifIVA'] > 0 ? $_POST['valIVAfno'] : 0;
$pivag = $_POST['ifIVA'] > 0 ? $_POST['ifIVA'] : 0;
$valdctog = isset($_POST['dctoCondicionado']) ? $_POST['valDctofno'] : 0;
$pdctog = isset($_POST['dctoCondicionado']) ? $_POST['ifDcto'] : 0;
$prteftel = $_POST['prtefte'] == '' ? 0 : $_POST['prtefte'];
$valprtefte = $_POST['valprtefte'];
$pretiva = $_POST['pretiva'] == '' ? 0 : $_POST['pretiva'];
$valpretiva = $_POST['valpretiva'];
$observacion = $_POST['observaNO'];
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$vigencia = $_SESSION['vigencia'];
$inserta = 0;
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `id_tercero`FROM `seg_terceros_noblig` WHERE `no_doc` = '$no_doc'";
    $rs = $cmd->query($sql);
    $tercero = $rs->fetch();
    if ($tercero['id_tercero'] == '') {
        $sql = "INSERT INTO `seg_terceros_noblig` (`id_tdoc`, `no_doc`, `nombre`, `procedencia`, `tipo_org`, `reg_fiscal`, `resp_fiscal`, `correo`, `telefono`, `id_pais`, `id_dpto`, `id_municipio`, `direccion`, `id_user_reg`, `fec_reg`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $tipo_doc, PDO::PARAM_INT);
        $sql->bindParam(2, $no_doc, PDO::PARAM_STR);
        $sql->bindParam(3, $nombre, PDO::PARAM_STR);
        $sql->bindParam(4, $procede, PDO::PARAM_STR);
        $sql->bindParam(5, $tipo_org, PDO::PARAM_STR);
        $sql->bindParam(6, $reg_fiscal, PDO::PARAM_STR);
        $sql->bindParam(7, $resp_fiscal, PDO::PARAM_STR);
        $sql->bindParam(8, $correo, PDO::PARAM_STR);
        $sql->bindParam(9, $telefono, PDO::PARAM_STR);
        $sql->bindParam(10, $pais, PDO::PARAM_INT);
        $sql->bindParam(11, $dpto, PDO::PARAM_INT);
        $sql->bindParam(12, $ciudad, PDO::PARAM_INT);
        $sql->bindParam(13, $direccion, PDO::PARAM_STR);
        $sql->bindParam(14, $iduser, PDO::PARAM_INT);
        $sql->bindValue(15, $date->format('Y-m-d H:i:s'));
        $sql->execute();
        $id_tercero = $cmd->lastInsertId();
        if ($id_tercero > 0) {
            Logs::guardaLog("INSERT INTO `seg_terceros_noblig` (`id_tdoc`, `no_doc`, `nombre`, `procedencia`, `tipo_org`, `reg_fiscal`, `resp_fiscal`, `correo`, `telefono`, `id_pais`, `id_dpto`, `id_municipio`, `direccion`, `id_user_reg`, `fec_reg`) VALUES ($tipo_doc, '$no_doc', '$nombre', '$procede', '$tipo_org', '$reg_fiscal', '$resp_fiscal', '$correo', '$telefono', $pais, $dpto, $ciudad, '$direccion', $iduser, '{$date->format('Y-m-d H:i:s')}')");
        }
    } else {
        $id_tercero = $tercero['id_tercero'];
        $sql = "UPDATE `seg_terceros_noblig` SET `id_tdoc` = ?, `no_doc` = ?, `nombre` = ?, `procedencia` = ?, `tipo_org` = ?, `reg_fiscal` = ?, `resp_fiscal` = ?, `correo` = ?, `telefono` = ?, `id_pais` = ?, `id_dpto` = ?, `id_municipio` = ?, `direccion` = ? WHERE `id_tercero` = ?";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $tipo_doc, PDO::PARAM_INT);
        $sql->bindParam(2, $no_doc, PDO::PARAM_STR);
        $sql->bindParam(3, $nombre, PDO::PARAM_STR);
        $sql->bindParam(4, $procede, PDO::PARAM_STR);
        $sql->bindParam(5, $tipo_org, PDO::PARAM_STR);
        $sql->bindParam(6, $reg_fiscal, PDO::PARAM_STR);
        $sql->bindParam(7, $resp_fiscal, PDO::PARAM_STR);
        $sql->bindParam(8, $correo, PDO::PARAM_STR);
        $sql->bindParam(9, $telefono, PDO::PARAM_STR);
        $sql->bindParam(10, $pais, PDO::PARAM_INT);
        $sql->bindParam(11, $dpto, PDO::PARAM_INT);
        $sql->bindParam(12, $ciudad, PDO::PARAM_INT);
        $sql->bindParam(13, $direccion, PDO::PARAM_STR);
        $sql->bindParam(14, $id_tercero, PDO::PARAM_INT);
        if (!($sql->execute())) {
            echo $sql->errorInfo()[2];
            exit();
        } else {
            if ($sql->rowCount() > 0) {
                Logs::guardaLog("UPDATE `seg_terceros_noblig` SET `id_tdoc` = $tipo_doc, `no_doc` = '$no_doc', `nombre` = '$nombre', `procedencia` = '$procede', `tipo_org` = '$tipo_org', `reg_fiscal` = '$reg_fiscal', `resp_fiscal` = '$resp_fiscal', `correo` = '$correo', `telefono` = '$telefono', `id_pais` = $pais, `id_dpto` = $dpto, `id_municipio` = $ciudad, `direccion` = '$direccion' WHERE `id_tercero` = $id_tercero");
                $inserta++;
                $sql = null;
                $cmd = \Config\Clases\Conexion::getConexion();

                $sql = "UPDATE `seg_terceros_noblig` SET  `id_user_act` = ?, `fec_act` = ?  WHERE `id_tercero` = ?";
                $sql = $cmd->prepare($sql);
                $sql->bindParam(1, $iduser, PDO::PARAM_INT);
                $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
                $sql->bindParam(3, $id_tercero, PDO::PARAM_STR);
                $sql->execute();
                if ($sql->rowCount() > 0) {
                    Logs::guardaLog("UPDATE `seg_terceros_noblig` SET  `id_user_act` = $iduser, `fec_act` = '{$date->format('Y-m-d H:i:s')}'  WHERE `id_tercero` = $id_tercero");
                }
            }
        }
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $sql = null;
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "UPDATE `ctt_fact_noobligado`
                SET `id_tercero_no` = ?, `fec_compra`= ?, `fec_vence`= ?, `met_pago`= ?, `forma_pago`= ?, `val_retefuente`= ?, `porc_retefuente`= ?, `val_reteiva`= ?, `porc_reteiva`= ?, `val_iva`= ?, `porc_iva`= ?, `val_dcto`= ?, `porc_dcto`= ?, `observaciones` = ?
            WHERE `id_facturano` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id_tercero, PDO::PARAM_INT);
    $sql->bindParam(2, $fec_compra, PDO::PARAM_STR);
    $sql->bindParam(3, $fec_vence, PDO::PARAM_STR);
    $sql->bindParam(4, $met_pago, PDO::PARAM_STR);
    $sql->bindParam(5, $forma_pago, PDO::PARAM_STR);
    $sql->bindParam(6, $valprtefte, PDO::PARAM_STR);
    $sql->bindParam(7, $prteftel, PDO::PARAM_STR);
    $sql->bindParam(8, $valpretiva, PDO::PARAM_STR);
    $sql->bindParam(9, $pretiva, PDO::PARAM_STR);
    $sql->bindParam(10, $valivag, PDO::PARAM_STR);
    $sql->bindParam(11, $pivag, PDO::PARAM_STR);
    $sql->bindParam(12, $valdctog, PDO::PARAM_STR);
    $sql->bindParam(13, $pdctog, PDO::PARAM_STR);
    $sql->bindParam(14, $observacion, PDO::PARAM_STR);
    $sql->bindParam(15, $id_facno, PDO::PARAM_INT);
    if (!($sql->execute())) {
        echo $sql->errorInfo()[2];
        exit();
    } else {
        if ($sql->rowCount() > 0) {
            Logs::guardaLog("UPDATE `ctt_fact_noobligado` SET `id_tercero_no` = $id_tercero, `fec_compra`= '$fec_compra', `fec_vence`= '$fec_vence', `met_pago`= '$met_pago', `forma_pago`= '$forma_pago', `val_retefuente`= '$valprtefte', `porc_retefuente`= '$prteftel', `val_reteiva`= '$valpretiva', `porc_reteiva`= '$pretiva', `val_iva`= '$valivag', `porc_iva`= '$pivag', `val_dcto`= '$valdctog', `porc_dcto`= '$pdctog', `observaciones` = '$observacion' WHERE `id_facturano` = $id_facno");
            $inserta++;
            $sql = null;
            $cmd = \Config\Clases\Conexion::getConexion();

            $sql = "UPDATE `ctt_fact_noobligado` SET  `id_user_act` = ?, `fec_act` = ?  WHERE `id_facturano` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $iduser, PDO::PARAM_INT);
            $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
            $sql->bindParam(3, $id_facno, PDO::PARAM_STR);
            $sql->execute();
            if ($sql->rowCount() > 0) {
                Logs::guardaLog("UPDATE `ctt_fact_noobligado` SET  `id_user_act` = $iduser, `fec_act` = '{$date->format('Y-m-d H:i:s')}'  WHERE `id_facturano` = $id_facno");
            }
        }
    }
    $cmd = \Config\Clases\Conexion::getConexion();

    $query = "DELETE FROM `ctt_fact_noobligado_det` WHERE `id_fno` = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_facno, PDO::PARAM_INT);
    $query->execute();
    $query = "INSERT INTO `ctt_fact_noobligado_det`
                    (`id_fno`, `codigo`, `detalle`, `val_unitario`, `cantidad`, `p_iva`, `val_iva`, `p_dcto`, `val_dcto`, `id_user_reg`, `fec_reg`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_facno, PDO::PARAM_INT);
    $query->bindParam(2, $codigo, PDO::PARAM_STR);
    $query->bindParam(3, $detalle, PDO::PARAM_STR);
    $query->bindParam(4, $val_unitario, PDO::PARAM_STR);
    $query->bindParam(5, $cantidad, PDO::PARAM_STR);
    $query->bindParam(6, $p_iva, PDO::PARAM_STR);
    $query->bindParam(7, $val_iva, PDO::PARAM_STR);
    $query->bindParam(8, $p_dcto, PDO::PARAM_STR);
    $query->bindParam(9, $val_dcto, PDO::PARAM_STR);
    $query->bindParam(10, $iduser, PDO::PARAM_INT);
    $query->bindValue(11, $date->format('Y-m-d H:i:s'));
    foreach ($array_descripcion as $key => $value) {
        $codigo = $array_codigos[$key];
        $detalle = $value;
        $val_unitario = $array_valu[$key];
        $cantidad = $array_cant[$key];
        $p_iva = $array_piva[$key] != '' ? $array_piva[$key] : 0;
        $val_iva = $array_viva[$key] != '' ? $array_viva[$key] : 0;
        if ($p_iva == 0) {
            $val_iva = 0;
        }
        $p_dcto = $array_pdcto[$key] != '' ? $array_pdcto[$key] : 0;
        $val_dcto = $array_vpdcto[$key] != '' ? $array_vpdcto[$key] : 0;
        if ($p_dcto == 0) {
            $val_dcto = 0;
        }
        $query->execute();
        if ($cmd->lastInsertId() > 0) {
            Logs::guardaLog("INSERT INTO `ctt_fact_noobligado_det` (`id_fno`, `codigo`, `detalle`, `val_unitario`, `cantidad`, `p_iva`, `val_iva`, `p_dcto`, `val_dcto`, `id_user_reg`, `fec_reg`) VALUES ($id_facno, '$codigo', '$detalle', '$val_unitario', '$cantidad', '$p_iva', '$val_iva', '$p_dcto', '$val_dcto', $iduser, '{$date->format('Y-m-d H:i:s')}')");
            $inserta++;
        } else {
            echo $query->errorInfo()[2];
        }
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
if ($inserta > 0) {
    echo '1';
}
