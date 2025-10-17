<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$novedad = isset($_POST['slcTipoNovedad']) ? $_POST['slcTipoNovedad'] : exit('Accion no permitida');
$id_novedad = $_POST['id_novendad'];
$observacion = $_POST['txtAObservaNov'];
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));

$cmd = \Config\Clases\Conexion::getConexion();
switch ($novedad) {
    case 1:
        $val_adicion = $_POST['numValAdicion'];
        $fec_adicion = $_POST['datFecAdicion'];
        $fini_pro = NULL;
        $ffin_pro = NULL;
        try {
            $sql = "UPDATE `ctt_novedad_adicion_prorroga` SET
                        `id_tip_nov` = ?, `val_adicion` = ?, `fec_adcion` = ?, `fec_ini_prorroga` = ?, `fec_fin_prorroga` = ?, `observacion` = ?
                    WHERE `id_nov_con` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $novedad, PDO::PARAM_INT);
            $sql->bindParam(2, $val_adicion, PDO::PARAM_STR);
            $sql->bindParam(3, $fec_adicion);
            $sql->bindParam(4, $fini_pro);
            $sql->bindParam(5, $ffin_pro);
            $sql->bindParam(6, $observacion, PDO::PARAM_STR);
            $sql->bindParam(7, $id_novedad, PDO::PARAM_INT);
            if (!($sql->execute())) {
                echo $sql->errorInfo()[2];
                exit();
            } else {
                if ($sql->rowCount() > 0) {
                    $cmd = \Config\Clases\Conexion::getConexion();

                    $sql = "UPDATE `ctt_novedad_adicion_prorroga` SET  `id_user_act` = ?, `fec_act` = ? WHERE `id_nov_con` = ?";
                    $sql = $cmd->prepare($sql);
                    $sql->bindParam(1, $iduser, PDO::PARAM_INT);
                    $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
                    $sql->bindParam(3, $id_novedad, PDO::PARAM_INT);
                    $sql->execute();
                    if ($sql->rowCount() > 0) {
                        echo '1';
                    } else {
                        echo $sql->errorInfo()[2];
                    }
                } else {
                    echo 'No se registró ningún nuevo dato';
                }
            }
            $cmd = NULL;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
        break;
    case 2:
        $val_adicion = NULL;
        $fec_adicion = NULL;
        $fini_pro = isset($_POST['datFecIniProrroga']) ? $_POST['datFecIniProrroga'] : NULL;
        $ffin_pro = isset($_POST['datFecFinProrroga']) ? $_POST['datFecFinProrroga'] : NULL;
        try {
            $sql = "UPDATE `ctt_novedad_adicion_prorroga` SET
                        `id_tip_nov` = ?, `val_adicion` = ?, `fec_adcion` = ?, `fec_ini_prorroga` = ?, `fec_fin_prorroga` = ?, `observacion` = ?
                    WHERE `id_nov_con` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $novedad, PDO::PARAM_INT);
            $sql->bindParam(2, $val_adicion, PDO::PARAM_STR);
            $sql->bindParam(3, $fec_adicion, PDO::PARAM_STR);
            $sql->bindParam(4, $fini_pro, PDO::PARAM_STR);
            $sql->bindParam(5, $ffin_pro, PDO::PARAM_STR);
            $sql->bindParam(6, $observacion, PDO::PARAM_STR);
            $sql->bindParam(7, $id_novedad, PDO::PARAM_INT);
            if (!($sql->execute())) {
                echo $sql->errorInfo()[2];
                exit();
            } else {
                if ($sql->rowCount() > 0) {
                    $cmd = \Config\Clases\Conexion::getConexion();

                    $sql = "UPDATE `ctt_novedad_adicion_prorroga` SET  `id_user_act` = ?, `fec_act` = ? WHERE `id_nov_con` = ?";
                    $sql = $cmd->prepare($sql);
                    $sql->bindParam(1, $iduser, PDO::PARAM_INT);
                    $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
                    $sql->bindParam(3, $id_novedad, PDO::PARAM_INT);
                    $sql->execute();
                    if ($sql->rowCount() > 0) {
                        echo '1';
                    } else {
                        echo $sql->errorInfo()[2];
                    }
                } else {
                    echo 'No se registró ningún nuevo dato';
                }
            }
            $cmd = NULL;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
        break;
    case 3:
        $val_adicion = $_POST['numValAdicion'];
        $fec_adicion = $_POST['datFecAdicion'];
        $fini_pro = $_POST['datFecIniProrroga'];
        $ffin_pro = $_POST['datFecFinProrroga'];
        try {
            $sql = "UPDATE `ctt_novedad_adicion_prorroga` SET
                        `id_tip_nov` = ?, `val_adicion` = ?, `fec_adcion` = ?, `fec_ini_prorroga` = ?, `fec_fin_prorroga` = ?, `observacion` = ?
                    WHERE `id_nov_con` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $novedad, PDO::PARAM_INT);
            $sql->bindParam(2, $val_adicion, PDO::PARAM_STR);
            $sql->bindParam(3, $fec_adicion, PDO::PARAM_STR);
            $sql->bindParam(4, $fini_pro, PDO::PARAM_STR);
            $sql->bindParam(5, $ffin_pro, PDO::PARAM_STR);
            $sql->bindParam(6, $observacion, PDO::PARAM_STR);
            $sql->bindParam(7, $id_novedad, PDO::PARAM_INT);
            if (!($sql->execute())) {
                echo $sql->errorInfo()[2];
                exit();
            } else {
                if ($sql->rowCount() > 0) {
                    $cmd = \Config\Clases\Conexion::getConexion();

                    $sql = "UPDATE `ctt_novedad_adicion_prorroga` SET  `id_user_act` = ?, `fec_act` = ? WHERE `id_nov_con` = ?";
                    $sql = $cmd->prepare($sql);
                    $sql->bindParam(1, $iduser, PDO::PARAM_INT);
                    $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
                    $sql->bindParam(3, $id_novedad, PDO::PARAM_INT);
                    $sql->execute();
                    if ($sql->rowCount() > 0) {
                        echo '1';
                    } else {
                        echo $sql->errorInfo()[2];
                    }
                } else {
                    echo 'No se registró ningún nuevo dato';
                }
            }
            $cmd = NULL;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
        break;
    case 4:
        $fec_cesion = $_POST['datFecCesion'];
        $id_tercero = $_POST['id_tercero'];
        try {
            $sql = "UPDATE `ctt_novedad_cesion` SET
                        `id_tercero` = ?, `fec_cesion` = ?, `observacion` = ?
                    WHERE `id_cesion` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $id_tercero, PDO::PARAM_INT);
            $sql->bindParam(2, $fec_cesion, PDO::PARAM_STR);
            $sql->bindParam(3, $observacion, PDO::PARAM_STR);
            $sql->bindParam(4, $id_novedad, PDO::PARAM_INT);
            if (!($sql->execute())) {
                echo $sql->errorInfo()[2];
                exit();
            } else {
                if ($sql->rowCount() > 0) {
                    $cmd = \Config\Clases\Conexion::getConexion();

                    $sql = "UPDATE `ctt_novedad_cesion` SET  `id_user_act` = ?, `fec_act` = ? WHERE `id_cesion` = ?";
                    $sql = $cmd->prepare($sql);
                    $sql->bindParam(1, $iduser, PDO::PARAM_INT);
                    $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
                    $sql->bindParam(3, $id_novedad, PDO::PARAM_INT);
                    $sql->execute();
                    if ($sql->rowCount() > 0) {
                        echo '1';
                    } else {
                        echo $sql->errorInfo()[2];
                    }
                } else {
                    echo 'No se registró ningún nuevo dato';
                }
            }
            $cmd = NULL;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
        break;
    case 5:
        $fini_susp = $_POST['datFecIniSuspencion'];
        $ffin_susp = $_POST['datFecFinSuspencion'];
        try {
            $sql = "UPDATE `ctt_novedad_suspension` SET
                        `fec_inicia` = ?, `fec_fin` = ?, `observacion` = ?
                    WHERE `id_suspension` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $fini_susp, PDO::PARAM_STR);
            $sql->bindParam(2, $ffin_susp, PDO::PARAM_STR);
            $sql->bindParam(3, $observacion, PDO::PARAM_STR);
            $sql->bindParam(4, $id_novedad, PDO::PARAM_INT);
            if (!($sql->execute())) {
                echo $sql->errorInfo()[2];
                exit();
            } else {
                if ($sql->rowCount() > 0) {
                    $cmd = \Config\Clases\Conexion::getConexion();

                    $sql = "UPDATE `ctt_novedad_suspension` SET  `id_user_act` = ?, `fec_act` = ? WHERE `id_suspension` = ?";
                    $sql = $cmd->prepare($sql);
                    $sql->bindParam(1, $iduser, PDO::PARAM_INT);
                    $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
                    $sql->bindParam(3, $id_novedad, PDO::PARAM_INT);
                    $sql->execute();
                    if ($sql->rowCount() > 0) {
                        echo '1';
                    } else {
                        echo $sql->errorInfo()[2];
                    }
                } else {
                    echo 'No se registró ningún nuevo dato';
                }
            }
            $cmd = NULL;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
        break;
    case 6:
        $frein = $_POST['datFecReinicio'];
        try {
            $sql = "UPDATE `ctt_novedad_reinicio` SET
                        `fec_reinicia` = ?, `observacion` = ?
                    WHERE `id_reinicio` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $frein, PDO::PARAM_STR);
            $sql->bindParam(2, $observacion, PDO::PARAM_STR);
            $sql->bindParam(3, $id_novedad, PDO::PARAM_INT);
            if (!($sql->execute())) {
                echo $sql->errorInfo()[2];
                exit();
            } else {
                if ($sql->rowCount() > 0) {
                    $cmd = \Config\Clases\Conexion::getConexion();

                    $sql = "UPDATE `ctt_novedad_reinicio` SET  `id_user_act` = ?, `fec_act` = ? WHERE `id_reinicio` = ?";
                    $sql = $cmd->prepare($sql);
                    $sql->bindParam(1, $iduser, PDO::PARAM_INT);
                    $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
                    $sql->bindParam(3, $id_novedad, PDO::PARAM_INT);
                    $sql->execute();
                    if ($sql->rowCount() > 0) {
                        echo '1';
                    } else {
                        echo $sql->errorInfo()[2];
                    }
                } else {
                    echo 'No se registró ningún nuevo dato';
                }
            }
            $cmd = NULL;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
        break;
    case 7:
        $id_tt = $_POST['slcTipTerminacion'];
        try {
            $sql = "UPDATE `ctt_novedad_terminacion` SET
                        `id_t_terminacion` = ?, `observacion` = ?
                    WHERE `id_terminacion` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $id_tt, PDO::PARAM_STR);
            $sql->bindParam(2, $observacion, PDO::PARAM_STR);
            $sql->bindParam(3, $id_novedad, PDO::PARAM_INT);
            if (!($sql->execute())) {
                echo $sql->errorInfo()[2];
                exit();
            } else {
                if ($sql->rowCount() > 0) {
                    $cmd = \Config\Clases\Conexion::getConexion();

                    $sql = "UPDATE `ctt_novedad_terminacion` SET  `id_user_act` = ?, `fec_act` = ? WHERE `id_terminacion` = ?";
                    $sql = $cmd->prepare($sql);
                    $sql->bindParam(1, $iduser, PDO::PARAM_INT);
                    $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
                    $sql->bindParam(3, $id_novedad, PDO::PARAM_INT);
                    $sql->execute();
                    if ($sql->rowCount() > 0) {
                        echo '1';
                    } else {
                        echo $sql->errorInfo()[2];
                    }
                } else {
                    echo 'No se registró ningún nuevo dato';
                }
            }
            $cmd = NULL;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
        break;
    case 8:
        $fec_liq = $_POST['datFecLiq'];
        $tip_liq = $_POST['slcTipLiquidacion'];
        $val_ctte = $_POST['numValFavorCtrate'];
        $val_ctta = $_POST['numValFavorCtrista'];
        try {
            $sql = "UPDATE `ctt_novedad_liquidacion` SET
                        `id_t_liq` = ?, `fec_liq` = ?, `val_cte` = ?, `val_cta` = ?, `observacion` = ?
                    WHERE `id_liquidacion` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $tip_liq, PDO::PARAM_INT);
            $sql->bindParam(2, $fec_liq, PDO::PARAM_STR);
            $sql->bindParam(3, $val_ctte, PDO::PARAM_STR);
            $sql->bindParam(4, $val_ctta, PDO::PARAM_STR);
            $sql->bindParam(5, $observacion, PDO::PARAM_STR);
            $sql->bindParam(6, $id_novedad, PDO::PARAM_INT);
            if (!($sql->execute())) {
                echo $sql->errorInfo()[2];
                exit();
            } else {
                if ($sql->rowCount() > 0) {
                    $cmd = \Config\Clases\Conexion::getConexion();

                    $sql = "UPDATE `ctt_novedad_liquidacion` SET  `id_user_act` = ?, `fec_act` = ? WHERE `id_liquidacion` = ?";
                    $sql = $cmd->prepare($sql);
                    $sql->bindParam(1, $iduser, PDO::PARAM_INT);
                    $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
                    $sql->bindParam(3, $id_novedad, PDO::PARAM_INT);
                    $sql->execute();
                    if ($sql->rowCount() > 0) {
                        echo '1';
                    } else {
                        echo $sql->errorInfo()[2];
                    }
                } else {
                    echo 'No se registró ningún nuevo dato';
                }
            }
            $cmd = NULL;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
        break;
    default:
        exit('Acción no permitida');
        break;
}
