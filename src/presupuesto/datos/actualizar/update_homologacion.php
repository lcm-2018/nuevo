<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
use Config\Clases\Logs;
$idsHomolgacion = $_POST['idHomol'];
$codCgrs = $_POST['codCgr'];
$codCpc = $_POST['cpc'];
$codFuente = $_POST['fuente'];
$codTercero = $_POST['tercero'];
$codPolitica = $_POST['polPub'];
$codSiho = $_POST['siho'];
$codSia = $_POST['sia'];
$codSituacion = $_POST['situacion'];
$codVigencia = $_POST['vigencia'];
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$suma = 0;
$presupuesto = $_POST['id_pto_tipo'];
$error = '';
if ($presupuesto == 1) {
    $ingreso = $_POST['ingreso'];
    try {
        $cmd = \Config\Clases\Conexion::getConexion();


        $sqlI = "INSERT INTO `pto_homologa_ingresos`
                    (`id_cargue`, `id_cgr`, `id_cpc`, `id_fuente`, `id_tercero`, `id_politica`, `id_siho`, `id_sia`, `id_situacion`, `id_vigencia`, `id_user_reg`, `fec_reg`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $sqlU = "UPDATE `pto_homologa_ingresos`
                    SET `id_cargue` = ?, `id_cgr` = ?, `id_cpc` = ?, `id_fuente` = ?, `id_tercero` = ?, `id_politica` = ?, `id_siho` = ?, `id_sia` = ?, `id_situacion` = ?, `id_vigencia` = ?
                WHERE `id_homologacion` = ?";

        $insert = $cmd->prepare($sqlI);
        $update = $cmd->prepare($sqlU);
        foreach ($codCgrs as $key => $value) {
            if ($codCpc[$key] > 0) {
                $params = [
                    (int) $key,
                    $value,
                    $codCpc[$key],
                    $codFuente[$key],
                    $codTercero[$key],
                    $codPolitica[$key],
                    $codSiho[$key],
                    $codSia[$key],
                    $codSituacion[$key],
                    $codVigencia[$key],
                    (int) $iduser,
                    $date->format('Y-m-d H:i:s')
                ];
                $idHom = $idsHomolgacion[$key];

                if ($idHom == 0) {
                    $insert->execute($params);
                    if ($insert->rowCount() > 0) {
                        Logs::guardaLog("INSERT INTO `pto_homologa_ingresos` (`id_cargue`, `id_cgr`, `id_cpc`, `id_fuente`, `id_tercero`, `id_politica`, `id_siho`, `id_sia`, `id_situacion`, `id_vigencia`, `id_user_reg`, `fec_reg`) VALUES (" . $params[0] . ", '" . $params[1] . "', '" . $params[2] . "', '" . $params[3] . "', '" . $params[4] . "', '" . $params[5] . "', '" . $params[6] . "', '" . $params[7] . "', '" . $params[8] . "', " . $params[9] . ", " . $params[10] . ", '" . $params[11] . "')");
                        $suma++;
                    } else {
                        $error .= $insert->errorInfo()[2];
                    }
                } else {
                    $paramsUpdate = array_slice($params, 0, 10); // Solo los 10 primeros
                    $paramsUpdate[] = (int) $idHom;
                    $update->execute($paramsUpdate);
                    if ($update->rowCount() > 0) {
                        Logs::guardaLog("UPDATE `pto_homologa_ingresos` SET `id_cargue` = {$paramsUpdate[0]}, `id_cgr` = '{$paramsUpdate[1]}', `id_cpc` = '{$paramsUpdate[2]}', `id_fuente` = '{$paramsUpdate[3]}', `id_tercero` = '{$paramsUpdate[4]}', `id_politica` = '{$paramsUpdate[5]}', `id_siho` = '{$paramsUpdate[6]}', `id_sia` = '{$paramsUpdate[7]}', `id_situacion` = '{$paramsUpdate[8]}', `id_vigencia` = {$paramsUpdate[9]} WHERE `id_homologacion` = {$paramsUpdate[10]}");
                        $suma++;
                        $con = \Config\Clases\Conexion::getConexion();
                        $query = "UPDATE `pto_homologa_ingresos` SET `id_user_act` = ?, `fec_act` = ? WHERE `id_homologacion` = ?";
                        $query = $con->prepare($query);
                        $query->bindParam(1, $iduser, PDO::PARAM_INT);
                        $query->bindValue(2, $date->format('Y-m-d H:i:s'));
                        $query->bindParam(3, $idHom, PDO::PARAM_INT);
                        $query->execute();
                        if ($query->rowCount() > 0) {
                            Logs::guardaLog("UPDATE `pto_homologa_ingresos` SET `id_user_act` = $iduser, `fec_act` = '" . $date->format('Y-m-d H:i:s') . "' WHERE `id_homologacion` = $idHom");
                        }
                        $con = null;
                    } else {
                        $error .= $insert->errorInfo()[2];
                    }
                }
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
} else if ($presupuesto == 2) {
    $gasto = $_POST['gasto'];
    $codSeccion = $_POST['seccion'];
    $codSector = $_POST['sector'];
    $codClaseSia = $_POST['csia'];
    $mhs = $_POST['mmto_h'];
    $pss = isset($_POST['prest_s']) ? $_POST['prest_s'] : [];
    try {
        $cmd = \Config\Clases\Conexion::getConexion();

        $sqlI = "INSERT INTO `pto_homologa_gastos`
                    (`id_cargue`, `id_cgr`, `id_cpc`, `id_fuente`, `id_tercero`, `id_politica`, `id_siho`, `id_sia`, `id_situacion`, `id_vigencia`, `id_seccion`, `id_sector`, `id_csia`, `id_user_reg`, `fec_reg`,`id_mh`,`id_ps`)
                VALUES (?, ?, ?, ? , ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $sqlU = "UPDATE `pto_homologa_gastos` 
                    SET `id_cargue` = ?, `id_cgr` = ?, `id_cpc` = ?, `id_fuente` = ?, `id_tercero` = ?, `id_politica` = ?, `id_siho` = ?, `id_sia` = ?, `id_situacion` = ?, `id_vigencia` = ?, `id_seccion` = ?, `id_sector` = ?, `id_csia` = ?, `id_mh` = ?, `id_ps` = ?
                WHERE `id_homologacion` = ?";
        $insert = $cmd->prepare($sqlI);
        $update = $cmd->prepare($sqlU);
        foreach ($codCgrs as $key => $value) {
            if ($codCpc[$key] > 0) {
                $params = [
                    (int) $key,
                    $value,
                    $codCpc[$key],
                    $codFuente[$key],
                    $codTercero[$key],
                    $codPolitica[$key],
                    $codSiho[$key],
                    $codSia[$key],
                    $codSituacion[$key],
                    $codVigencia[$key],
                    $codSeccion[$key],
                    $codSector[$key],
                    $codClaseSia[$key],
                    (int) $iduser,
                    $date->format('Y-m-d H:i:s'),
                    (int) $mhs[$key],
                    (int) ($pss[$key] ?? 0)
                ];
                $idHom = $idsHomolgacion[$key];

                if ($idHom == 0) {
                    $insert->execute($params);
                    if ($insert->rowCount() > 0) {
                        Logs::guardaLog("INSERT INTO `pto_homologa_gastos` (`id_cargue`, `id_cgr`, `id_cpc`, `id_fuente`, `id_tercero`, `id_politica`, `id_siho`, `id_sia`, `id_situacion`, `id_vigencia`, `id_seccion`, `id_sector`, `id_csia`, `id_user_reg`, `fec_reg`,`id_mh`,`id_ps`) VALUES ({$params[0]}, '{$params[1]}', '{$params[2]}', '{$params[3]}', '{$params[4]}', '{$params[5]}', '{$params[6]}', '{$params[7]}', '{$params[8]}', {$params[9]}, '{$params[10]}', '{$params[11]}', '{$params[12]}', {$params[13]}, '{$params[14]}', {$params[15]}, {$params[16]})");
                        $suma++;
                    } else {
                        $error .= $insert->errorInfo()[2];
                    }
                } else {
                    $paramsUpdate = array_slice($params, 0, 13);
                    $paramsUpdate[] = (int) $mhs[$key];
                    $paramsUpdate[] = (int) ($pss[$key] ?? 0);
                    $paramsUpdate[] = (int) $idHom;
                    $update->execute($paramsUpdate);
                    if ($update->rowCount() > 0) {
                        Logs::guardaLog("UPDATE `pto_homologa_gastos` SET `id_cargue` = {$paramsUpdate[0]}, `id_cgr` = '{$paramsUpdate[1]}', `id_cpc` = '{$paramsUpdate[2]}', `id_fuente` = '{$paramsUpdate[3]}', `id_tercero` = '{$paramsUpdate[4]}', `id_politica` = '{$paramsUpdate[5]}', `id_siho` = '{$paramsUpdate[6]}', `id_sia` = '{$paramsUpdate[7]}', `id_situacion` = '{$paramsUpdate[8]}', `id_vigencia` = {$paramsUpdate[9]}, `id_seccion` = '{$paramsUpdate[10]}', `id_sector` = '{$paramsUpdate[11]}', `id_csia` = '{$paramsUpdate[12]}', `id_mh` = {$paramsUpdate[13]}, `id_ps` = {$paramsUpdate[14]} WHERE `id_homologacion` = {$paramsUpdate[15]}");
                        $suma++;
                        $con = \Config\Clases\Conexion::getConexion();
                        $query = "UPDATE `pto_homologa_gastos` SET `id_user_act` = ?, `fec_act` = ? WHERE `id_homologacion` = ?";
                        $query = $con->prepare($query);
                        $query->bindParam(1, $iduser, PDO::PARAM_INT);
                        $query->bindValue(2, $date->format('Y-m-d H:i:s'));
                        $query->bindParam(3, $idHom, PDO::PARAM_INT);
                        $query->execute();
                        if ($query->rowCount() > 0) {
                            Logs::guardaLog("UPDATE `pto_homologa_gastos` SET `id_user_act` = $iduser, `fec_act` = '" . $date->format('Y-m-d H:i:s') . "' WHERE `id_homologacion` = $idHom");
                        }
                        $con = null;
                    } else {
                        $error .= $insert->errorInfo()[2];
                    }
                }
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
}
if ($suma > 0) {
    echo 'ok';
} else {
    echo 'No se realizó ninguna modificación' . $error;
}
